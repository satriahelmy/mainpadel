<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\RoundStatus;
use App\Enums\TournamentPlayerStatus;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GameFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_game_creation_persists_roster_and_full_auto_schedule(): void
    {
        $response = $this->post(route('games.store'), $this->gameData());

        $tournament = Tournament::query()->firstOrFail();

        $response->assertRedirect(route('games.draw', $tournament));
        self::assertSame('auto', $tournament->round_mode->value);
        self::assertSame(5, $tournament->number_of_rounds);
        self::assertCount(5, $tournament->rounds);
        self::assertCount(6, $tournament->tournamentPlayers);
        self::assertTrue($tournament->rounds->every(fn ($round): bool => $round->status === RoundStatus::Scheduled));
        self::assertNotNull($tournament->rounds->first()->drawing_metrics);
        self::assertArrayHasKey('match_count_spread', $tournament->rounds->first()->drawing_metrics);
    }

    #[DataProvider('drawingCombinations')]
    public function test_required_drawing_combinations_persist_fairness_metrics(int $playerCount, int $courts): void
    {
        $this->post(route('games.store'), $this->gameData([
            'name' => "Metrics {$playerCount}-{$courts}",
            'players' => array_map(static fn (int $player): string => "Player {$player}", range(1, $playerCount)),
            'number_of_courts' => $courts,
            'round_mode' => 'custom',
            'number_of_rounds' => 1,
        ]))->assertRedirect();

        $round = Tournament::query()->latest('id')->firstOrFail()->rounds()->firstOrFail();

        self::assertNotNull($round->drawing_metrics);
        self::assertArrayHasKey('components', $round->drawing_metrics);
        self::assertArrayHasKey('players', $round->drawing_metrics);
        self::assertCount($playerCount, $round->drawing_metrics['players']);
    }

    public static function drawingCombinations(): iterable
    {
        yield '4/1' => [4, 1];
        yield '5/1' => [5, 1];
        yield '6/1' => [6, 1];
        yield '8/1' => [8, 1];
        yield '8/2' => [8, 2];
        yield '10/2' => [10, 2];
        yield '12/2' => [12, 2];
        yield '12/3' => [12, 3];
    }

    public function test_game_can_start_score_progress_and_update_live_standings(): void
    {
        $this->post(route('games.store'), $this->gameData(['round_mode' => 'custom', 'number_of_rounds' => 2]));
        $tournament = Tournament::query()->firstOrFail();

        $this->post(route('games.start', $tournament))->assertRedirect(route('games.show', $tournament));
        $tournament->refresh();
        $firstRound = $tournament->rounds()->where('round_number', 1)->with('matches')->firstOrFail();
        self::assertSame(TournamentStatus::Ongoing, $tournament->status);
        self::assertSame(RoundStatus::Ongoing, $firstRound->status);

        foreach ($firstRound->matches as $match) {
            $this->put(route('games.score.update', [$tournament, $match]), [
                'team_a_score' => 12,
                'team_b_score' => 9,
            ])->assertRedirect(route('games.show', $tournament));
        }

        $firstRound->refresh();
        self::assertSame(RoundStatus::Completed, $firstRound->status);
        self::assertSame(MatchStatus::Completed, $firstRound->matches()->firstOrFail()->status);

        $standingsResponse = $this->get(route('games.standings', $tournament));
        $standingsResponse->assertOk()->assertSee('12')->assertSee('9');

        $this->post(route('games.next', $tournament))->assertRedirect(route('games.show', $tournament));
        self::assertSame(RoundStatus::Ongoing, $tournament->rounds()->where('round_number', 2)->firstOrFail()->status);
    }

    public function test_final_score_completes_game_and_keeps_individual_stats(): void
    {
        $this->post(route('games.store'), $this->gameData(['round_mode' => 'custom', 'number_of_rounds' => 1]));
        $tournament = Tournament::query()->firstOrFail();
        $this->post(route('games.start', $tournament));

        $match = $tournament->rounds()->with('matches')->firstOrFail()->matches->firstOrFail();
        $this->put(route('games.score.update', [$tournament, $match]), ['team_a_score' => 11, 'team_b_score' => 10]);

        $tournament->refresh();
        self::assertSame(TournamentStatus::Completed, $tournament->status);
        $standings = app(StandingsService::class)->forTournament($tournament);
        self::assertCount(6, $standings);
        self::assertSame(11, $standings->firstWhere('name', 'Helmy')['points_for']);
    }

    public function test_completed_game_roster_is_read_only(): void
    {
        $this->post(route('games.store'), $this->gameData(['round_mode' => 'custom', 'number_of_rounds' => 1]));
        $tournament = Tournament::query()->firstOrFail();
        $this->post(route('games.start', $tournament));
        $match = $tournament->rounds()->with('matches')->firstOrFail()->matches->firstOrFail();
        $this->put(route('games.score.update', [$tournament, $match]), ['team_a_score' => 11, 'team_b_score' => 10]);
        $membership = $tournament->tournamentPlayers()->firstOrFail();

        $this->from(route('games.players', $tournament))
            ->post(route('games.players.store', $tournament), ['name' => 'Late Player'])
            ->assertRedirect(route('games.players', $tournament))
            ->assertSessionHasErrors('name');
        $this->from(route('games.players', $tournament))
            ->post(route('games.players.withdraw', [$tournament, $membership]))
            ->assertRedirect(route('games.players', $tournament))
            ->assertSessionHasErrors('player');

        self::assertSame(6, $tournament->tournamentPlayers()->count());
    }

    public function test_roster_changes_apply_from_next_round_and_leave_completed_history_untouched(): void
    {
        $this->post(route('games.store'), $this->gameData(['round_mode' => 'custom', 'number_of_rounds' => 3]));
        $tournament = Tournament::query()->firstOrFail();
        $this->post(route('games.start', $tournament));

        $firstRound = $tournament->rounds()->with('matches.matchPlayers')->where('round_number', 1)->firstOrFail();
        $firstRoundPlayerSnapshot = $firstRound->matches->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->sort()->values()->all();
        $firstRoundMetricsSnapshot = $firstRound->drawing_metrics;
        $match = $firstRound->matches->first();
        $this->put(route('games.score.update', [$tournament, $match]), ['team_a_score' => 12, 'team_b_score' => 9]);
        // One court is used for six players, so round one is now completed.
        $firstRound->refresh();

        $this->post(route('games.players.store', $tournament), ['name' => 'Galih'])->assertRedirect(route('games.players', $tournament));
        $newMembership = $tournament->tournamentPlayers()->with('player')->whereHas('player', fn ($query) => $query->where('name', 'Galih'))->firstOrFail();
        self::assertSame(TournamentPlayerStatus::Active, $newMembership->status);
        self::assertSame(2, $newMembership->joined_at_round);

        $this->post(route('games.redraw', $tournament), ['confirmed' => 1])->assertRedirect(route('games.show', $tournament));
        $tournament->refresh();
        $firstRound->refresh();
        $afterRedrawSnapshot = $firstRound->matches()->with('matchPlayers')->get()->flatMap(fn ($match) => $match->matchPlayers->pluck('player_id'))->sort()->values()->all();
        self::assertSame($firstRoundPlayerSnapshot, $afterRedrawSnapshot);
        self::assertSame($firstRoundMetricsSnapshot, $firstRound->drawing_metrics);

        $this->post(route('games.players.withdraw', [$tournament, $newMembership]))->assertRedirect(route('games.players', $tournament));
        $newMembership->refresh();
        self::assertSame(TournamentPlayerStatus::Withdrawn, $newMembership->status);
    }

    public function test_duplicate_names_are_rejected_within_a_game(): void
    {
        $response = $this->from(route('games.create'))->post(route('games.store'), $this->gameData([
            'players' => ['Helmy', 'helmy', 'Andi', 'Budi'],
        ]));

        $response->assertRedirect(route('games.create'))->assertSessionHasErrors('players');
        self::assertSame(0, Tournament::query()->count());
    }

    public function test_invalid_game_creation_leaves_no_partial_records(): void
    {
        $response = $this->from(route('games.create'))->post(route('games.store'), $this->gameData([
            'players' => ['A', 'B', 'C'],
        ]));

        $response->assertRedirect(route('games.create'))->assertSessionHasErrors('players');
        $this->assertDatabaseCount('tournaments', 0);
        $this->assertDatabaseCount('players', 0);
        $this->assertDatabaseCount('tournament_players', 0);
    }

    public function test_create_screen_explains_capacity_when_courts_exceed_roster_capacity(): void
    {
        $this->get(route('games.create'))
            ->assertOk()
            ->assertSee('players can fill')
            ->assertSee('per round.');
    }

    public function test_game_views_render_for_the_live_session(): void
    {
        $this->post(route('games.store'), $this->gameData(['round_mode' => 'custom', 'number_of_rounds' => 1, 'players' => ['A', 'B', 'C', 'D']]));
        $tournament = Tournament::query()->firstOrFail();
        $this->post(route('games.start', $tournament));
        $match = $tournament->rounds()->with('matches')->firstOrFail()->matches->firstOrFail();

        $this->get(route('games.show', $tournament))->assertOk()->assertSee('Enter Result');
        $this->get(route('games.rounds', $tournament))->assertOk()->assertSee('Round 1');
        $this->get(route('games.standings', $tournament))->assertOk()->assertSee('Live standings');
        $this->get(route('games.players', $tournament))->assertOk()->assertSee('Players');
        $this->get(route('games.score.edit', [$tournament, $match]))->assertOk()->assertSee('Save Result');
    }

    private function gameData(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Friday Padel',
            'played_at' => '2026-09-15',
            'players' => ['Helmy', 'Andi', 'Budi', 'Rizky', 'Fajar', 'Dimas'],
            'number_of_courts' => 1,
            'target_points' => 21,
            'round_mode' => 'auto',
            'number_of_rounds' => null,
        ], $overrides);
    }
}
