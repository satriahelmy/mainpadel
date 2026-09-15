<?php

namespace Tests\Feature;

use App\Enums\RoundStatus;
use App\Enums\TournamentPlayerStatus;
use App\Models\MatchModel;
use App\Models\Tournament;
use App\Models\TournamentPlayerAbsence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RosterEdgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_join_before_first_round_is_included_after_redraw(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->addPlayer($tournament, 'E');
        $newMembership = $tournament->tournamentPlayers()->with('player')->latest('id')->firstOrFail();

        $this->redraw($tournament);

        $round = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        self::assertSame(1, $newMembership->joined_at_round);
        self::assertArrayHasKey((string) $newMembership->player_id, $round->drawing_metrics['players']);
        self::assertSame(5, count($tournament->tournamentPlayers));
    }

    public function test_join_after_a_completed_round_is_excluded_from_history_and_added_to_future_rounds(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D'],
            'round_mode' => 'custom',
            'number_of_rounds' => 3,
        ]);
        $this->post(route('games.start', $tournament));
        $this->scoreFirstMatch($tournament, 12, 9);
        $this->addPlayer($tournament, 'E');
        $newMembership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();

        $this->redraw($tournament);
        $roundOne = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $roundTwo = $tournament->rounds()->where('round_number', 2)->firstOrFail();

        self::assertSame(2, $newMembership->joined_at_round);
        self::assertArrayNotHasKey((string) $newMembership->player_id, $roundOne->drawing_metrics['players']);
        self::assertArrayHasKey((string) $newMembership->player_id, $roundTwo->drawing_metrics['players']);
    }

    public function test_withdrawal_before_first_round_removes_player_from_redrawn_future_round(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();

        $this->post(route('games.players.withdraw', [$tournament, $membership]))->assertRedirect();
        $this->redraw($tournament);

        $round = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $assignedIds = $round->matches()->with('matchPlayers')->get()->flatMap(fn (MatchModel $match) => $match->matchPlayers->pluck('player_id'))->all();

        self::assertSame(TournamentPlayerStatus::Withdrawn, $membership->fresh()->status);
        self::assertNotContains($membership->player_id, $assignedIds);
    }

    public function test_withdrawal_during_a_partial_round_preserves_that_round_and_changes_future_rounds(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'number_of_courts' => 2,
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $firstRound = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $firstMatch = $firstRound->matches()->with('matchPlayers')->firstOrFail();
        $membership = $tournament->tournamentPlayers()->where('player_id', $firstMatch->matchPlayers->first()->player_id)->firstOrFail();

        $this->put(route('games.score.update', [$tournament, $firstMatch]), ['team_a_score' => 12, 'team_b_score' => 9]);
        $firstRound->refresh();
        $snapshot = $this->matchSnapshot($firstRound);
        $this->post(route('games.players.withdraw', [$tournament, $membership]))->assertRedirect();
        $this->redraw($tournament);

        $firstRound->refresh();
        $futureRound = $tournament->rounds()->where('round_number', 2)->firstOrFail();
        $futureIds = $futureRound->matches()->with('matchPlayers')->get()->flatMap(fn (MatchModel $match) => $match->matchPlayers->pluck('player_id'))->all();

        self::assertSame($snapshot, $this->matchSnapshot($firstRound));
        self::assertNotContains($membership->player_id, $futureIds);
        self::assertSame(2, $membership->fresh()->left_at_round);
    }

    public function test_temporary_unavailability_excludes_a_player_until_they_resume(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();
        $playerId = $membership->player_id;

        $this->post(route('games.players.unavailable', [$tournament, $membership]))
            ->assertRedirect(route('games.players', $tournament));
        $membership->refresh();

        self::assertSame(TournamentPlayerStatus::Active, $membership->status);
        self::assertTrue($membership->fresh('absences')->hasOpenAbsence());
        self::assertSame(1, $membership->absences()->firstOrFail()->unavailable_from_round);

        $this->redraw($tournament);
        $futureIds = $tournament->rounds()
            ->where('status', RoundStatus::Scheduled)
            ->with('matches.matchPlayers')
            ->get()
            ->flatMap(fn ($round) => $round->matches->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id')))
            ->all();
        self::assertNotContains($playerId, $futureIds);

        $this->post(route('games.players.available', [$tournament, $membership]))
            ->assertRedirect(route('games.players', $tournament));
        $membership->refresh();
        self::assertFalse($membership->fresh('absences')->hasOpenAbsence());

        $this->redraw($tournament);
        $round = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        self::assertArrayHasKey((string) $playerId, $round->drawing_metrics['players']);
    }

    public function test_temporary_unavailability_during_a_partial_round_preserves_the_locked_round(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'number_of_courts' => 2,
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $firstRound = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $firstMatch = $firstRound->matches()->with('matchPlayers')->firstOrFail();
        $membership = $tournament->tournamentPlayers()->where('player_id', $firstMatch->matchPlayers->first()->player_id)->firstOrFail();

        $this->put(route('games.score.update', [$tournament, $firstMatch]), ['team_a_score' => 12, 'team_b_score' => 9]);
        $firstRound->refresh();
        $snapshot = $this->matchSnapshot($firstRound);
        $this->post(route('games.players.unavailable', [$tournament, $membership]))->assertRedirect();
        $this->redraw($tournament);

        $firstRound->refresh();
        $futureRound = $tournament->rounds()->where('round_number', 2)->firstOrFail();
        $futureIds = $futureRound->matches()->with('matchPlayers')->get()->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();

        self::assertSame($snapshot, $this->matchSnapshot($firstRound));
        self::assertNotContains($membership->player_id, $futureIds);
        self::assertSame(2, $membership->fresh()->absences()->firstOrFail()->unavailable_from_round);

        $this->post(route('games.players.available', [$tournament, $membership]))->assertRedirect();
        $this->redraw($tournament);
        self::assertArrayHasKey((string) $membership->player_id, $tournament->rounds()->where('round_number', 2)->firstOrFail()->drawing_metrics['players']);
    }

    public function test_temporary_unavailability_can_redraw_an_unplayed_current_round(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'number_of_courts' => 2,
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();

        $this->post(route('games.players.unavailable', [$tournament, $membership]))->assertRedirect();
        self::assertSame(1, $membership->fresh()->absences()->firstOrFail()->unavailable_from_round);

        $this->redraw($tournament);
        $round = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $assignedIds = $round->matches()->with('matchPlayers')->get()->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();

        self::assertSame(RoundStatus::Scheduled, $round->status);
        self::assertNotContains($membership->player_id, $assignedIds);
        $this->get(route('games.show', $tournament))->assertOk()->assertSee('Start round 1');
    }

    public function test_a_future_pause_can_be_applied_to_the_unplayed_current_round(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'number_of_courts' => 2,
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();
        TournamentPlayerAbsence::create([
            'tournament_player_id' => $membership->id,
            'unavailable_from_round' => 2,
        ]);

        $this->post(route('games.players.unavailable.current', [$tournament, $membership]))
            ->assertRedirect(route('games.players', $tournament));
        self::assertSame(1, $membership->fresh()->absences()->firstOrFail()->unavailable_from_round);

        $this->redraw($tournament);
        $round = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $assignedIds = $round->matches()->with('matchPlayers')->get()->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->all();

        self::assertNotContains($membership->player_id, $assignedIds);
    }

    public function test_a_late_joiner_can_be_included_in_the_unplayed_current_round(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();
        $membership->update(['joined_at_round' => 2]);

        $this->post(route('games.players.include.current', [$tournament, $membership]))
            ->assertRedirect(route('games.players', $tournament));
        self::assertSame(1, $membership->fresh()->joined_at_round);

        $this->redraw($tournament);
        $round = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        self::assertArrayHasKey((string) $membership->player_id, $round->drawing_metrics['players']);
    }

    public function test_a_player_can_pause_and_resume_more_than_once(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D'],
            'round_mode' => 'custom',
            'number_of_rounds' => 4,
        ]);
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();

        $this->post(route('games.start', $tournament));
        $this->scoreFirstMatch($tournament, 12, 9);
        $this->post(route('games.players.unavailable', [$tournament, $membership]));
        $this->redraw($tournament);
        $this->post(route('games.start', $tournament));
        $this->scoreRound($tournament, 2, 11, 10);
        $this->post(route('games.players.available', [$tournament, $membership]));
        $this->redraw($tournament);
        $this->post(route('games.start', $tournament));
        $this->scoreRound($tournament, 3, 12, 9);
        $this->post(route('games.players.unavailable', [$tournament, $membership]));

        $this->redraw($tournament);
        $absences = $membership->fresh('absences')->absences->sortBy('unavailable_from_round')->values();

        self::assertCount(2, $absences);
        self::assertSame(2, $absences[0]->unavailable_from_round);
        self::assertSame(3, $absences[0]->available_again_round);
        self::assertSame(4, $absences[1]->unavailable_from_round);
        self::assertNull($absences[1]->available_again_round);
    }

    public function test_four_to_five_players_is_supported_by_a_future_redraw(): void
    {
        $tournament = $this->createGame(['players' => ['A', 'B', 'C', 'D'], 'round_mode' => 'custom', 'number_of_rounds' => 1]);
        $this->addPlayer($tournament, 'E');
        $this->redraw($tournament);

        $round = $tournament->rounds()->with('matches.matchPlayers')->firstOrFail();
        self::assertCount(1, $round->matches);
        self::assertCount(4, $round->matches->first()->matchPlayers);
        self::assertSame(5, count($tournament->tournamentPlayers));
    }

    public function test_six_to_five_players_is_supported_and_below_four_is_actionable(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $membership = $tournament->tournamentPlayers()->latest('id')->firstOrFail();
        $this->post(route('games.players.withdraw', [$tournament, $membership]));
        $this->redraw($tournament);
        self::assertCount(1, $tournament->rounds()->firstOrFail()->matches);

        $fourPlayerGame = $this->createGame([
            'name' => 'Below Four Test',
            'players' => ['W', 'X', 'Y', 'Z'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $fourPlayerMembership = $fourPlayerGame->tournamentPlayers()->latest('id')->firstOrFail();
        $this->post(route('games.players.withdraw', [$fourPlayerGame, $fourPlayerMembership]));
        $this->from(route('games.players', $fourPlayerGame))
            ->post(route('games.redraw', $fourPlayerGame), ['confirmed' => 1])
            ->assertRedirect(route('games.players', $fourPlayerGame))
            ->assertSessionHasErrors('redraw');
        self::assertSame(2, $fourPlayerGame->rounds()->count());
    }

    public function test_redraw_requires_confirmation_and_repeated_confirmation_is_deterministic(): void
    {
        $tournament = $this->createGame(['round_mode' => 'custom', 'number_of_rounds' => 2]);
        $this->addPlayer($tournament, 'E');
        $before = $this->futureSnapshot($tournament);

        $this->from(route('games.players', $tournament))
            ->post(route('games.redraw', $tournament))
            ->assertRedirect(route('games.players', $tournament))
            ->assertSessionHasErrors('confirmed');
        self::assertSame($before, $this->futureSnapshot($tournament));

        $this->redraw($tournament);
        $afterFirst = $this->futureSnapshot($tournament);
        $this->redraw($tournament);
        self::assertSame($afterFirst, $this->futureSnapshot($tournament));
    }

    private function createGame(array $overrides = []): Tournament
    {
        $data = array_replace([
            'name' => 'Roster Test',
            'played_at' => '2026-09-15',
            'players' => ['A', 'B', 'C', 'D'],
            'number_of_courts' => 1,
            'target_points' => 21,
            'round_mode' => 'auto',
            'number_of_rounds' => null,
        ], $overrides);

        $this->post(route('games.store'), $data)->assertRedirect();

        return Tournament::query()->latest('id')->firstOrFail();
    }

    private function addPlayer(Tournament $tournament, string $name): void
    {
        $this->post(route('games.players.store', $tournament), ['name' => $name])->assertRedirect();
        $tournament->refresh();
    }

    private function redraw(Tournament $tournament): void
    {
        $this->post(route('games.redraw', $tournament), ['confirmed' => 1])->assertRedirect();
        $tournament->refresh();
    }

    private function scoreFirstMatch(Tournament $tournament, int $scoreA, int $scoreB): void
    {
        $this->scoreRound($tournament, 1, $scoreA, $scoreB);
    }

    private function scoreRound(Tournament $tournament, int $roundNumber, int $scoreA, int $scoreB): void
    {
        $match = $tournament->rounds()->where('round_number', $roundNumber)->with('matches')->firstOrFail()->matches->firstOrFail();
        $this->put(route('games.score.update', [$tournament, $match]), [
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
        ])->assertRedirect();
    }

    private function matchSnapshot($round): array
    {
        return $round->matches()->with('matchPlayers')->get()->map(fn (MatchModel $match): array => [
            'court' => $match->court_number,
            'status' => $match->status->value,
            'score_a' => $match->team_a_score,
            'score_b' => $match->team_b_score,
            'players' => $match->matchPlayers->sortBy('player_id')->map(fn ($assignment): array => [
                'player_id' => $assignment->player_id,
                'team' => $assignment->team->value,
            ])->values()->all(),
        ])->values()->all();
    }

    private function futureSnapshot(Tournament $tournament): array
    {
        return $tournament->rounds()->where('status', RoundStatus::Scheduled)->orderBy('round_number')->get()->map(fn ($round): array => [
            'round' => $round->round_number,
            'matches' => $this->matchSnapshot($round),
            'metrics' => $round->drawing_metrics,
        ])->all();
    }
}
