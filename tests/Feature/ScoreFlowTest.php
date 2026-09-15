<?php

namespace Tests\Feature;

use App\Enums\MatchStatus;
use App\Enums\RoundStatus;
use App\Models\MatchModel;
use App\Models\Tournament;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScoreFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    #[DataProvider('validScores')]
    public function test_valid_scores_are_saved_for_default_and_custom_targets(int $scoreA, int $scoreB, int $target): void
    {
        $tournament = $this->createGame([
            'target_points' => $target,
            'round_mode' => 'custom',
            'number_of_rounds' => 1,
        ]);
        $this->post(route('games.start', $tournament));
        $match = $this->firstMatch($tournament);

        $this->put(route('games.score.update', [$tournament, $match]), [
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
        ])->assertRedirect(route('games.show', $tournament));

        $match->refresh();
        self::assertSame(MatchStatus::Completed, $match->status);
        self::assertSame($scoreA, $match->team_a_score);
        self::assertSame($scoreB, $match->team_b_score);
    }

    public static function validScores(): iterable
    {
        yield '11-10' => [11, 10, 21];
        yield '12-9' => [12, 9, 21];
        yield 'zero edge' => [0, 21, 21];
        yield 'custom target' => [15, 15, 30];
    }

    #[DataProvider('invalidScores')]
    public function test_invalid_scores_are_rejected_and_not_persisted(mixed $scoreA, mixed $scoreB): void
    {
        $tournament = $this->createGame(['round_mode' => 'custom', 'number_of_rounds' => 1]);
        $this->post(route('games.start', $tournament));
        $match = $this->firstMatch($tournament);

        $this->from(route('games.score.edit', [$tournament, $match]))
            ->put(route('games.score.update', [$tournament, $match]), [
                'team_a_score' => $scoreA,
                'team_b_score' => $scoreB,
            ])
            ->assertRedirect(route('games.score.edit', [$tournament, $match]))
            ->assertSessionHasErrors();

        $match->refresh();
        self::assertSame(MatchStatus::Scheduled, $match->status);
        self::assertNull($match->team_a_score);
        self::assertNull($match->team_b_score);
    }

    public static function invalidScores(): iterable
    {
        yield 'incorrect total' => [10, 10];
        yield 'negative score' => [-1, 22];
        yield 'non integer score' => ['not-a-score', 21];
    }

    public function test_correction_and_duplicate_submission_keep_assignments_unchanged(): void
    {
        $tournament = $this->createGame(['round_mode' => 'custom', 'number_of_rounds' => 1]);
        $this->post(route('games.start', $tournament));
        $match = $this->firstMatch($tournament);
        $assignmentSnapshot = $match->matchPlayers()->orderBy('player_id')->get(['player_id', 'team'])->toArray();
        $teamAPlayerId = (int) $match->matchPlayers()->where('team', 'A')->firstOrFail()->player_id;

        $payload = ['team_a_score' => 12, 'team_b_score' => 9];
        $this->put(route('games.score.update', [$tournament, $match]), $payload);
        self::assertSame(12, app(StandingsService::class)->forTournament($tournament)->firstWhere('player_id', $teamAPlayerId)['points_for']);
        $this->put(route('games.score.update', [$tournament, $match]), ['team_a_score' => 11, 'team_b_score' => 10]);

        $match->refresh();
        $tournament->refresh();
        self::assertSame(MatchStatus::Completed, $match->status);
        self::assertSame(11, $match->team_a_score);
        self::assertSame(10, $match->team_b_score);
        self::assertSame(11, app(StandingsService::class)->forTournament($tournament)->firstWhere('player_id', $teamAPlayerId)['points_for']);
        self::assertSame($assignmentSnapshot, $match->matchPlayers()->orderBy('player_id')->get(['player_id', 'team'])->toArray());
        self::assertSame(1, MatchModel::query()->where('round_id', $match->round_id)->count());
    }

    public function test_continue_is_blocked_until_every_match_in_a_multi_court_round_is_complete(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'number_of_courts' => 2,
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $firstRound = $tournament->rounds()->where('round_number', 1)->firstOrFail();
        $match = $firstRound->matches()->firstOrFail();

        $this->put(route('games.score.update', [$tournament, $match]), ['team_a_score' => 12, 'team_b_score' => 9]);

        $this->from(route('games.show', $tournament))
            ->post(route('games.next', $tournament))
            ->assertRedirect(route('games.show', $tournament))
            ->assertSessionHasErrors('round');

        self::assertSame(RoundStatus::Ongoing, $firstRound->fresh()->status);
        self::assertSame(RoundStatus::Scheduled, $tournament->rounds()->where('round_number', 2)->firstOrFail()->status);
    }

    public function test_repeated_continue_does_not_advance_an_unfinished_next_round(): void
    {
        $tournament = $this->createGame(['round_mode' => 'custom', 'number_of_rounds' => 3]);
        $this->post(route('games.start', $tournament));
        $this->saveFirstMatch($tournament, 12, 9);

        $this->post(route('games.next', $tournament))->assertRedirect();
        $this->from(route('games.show', $tournament))
            ->post(route('games.next', $tournament))
            ->assertRedirect(route('games.show', $tournament))
            ->assertSessionHasErrors('round');

        self::assertSame(RoundStatus::Ongoing, $tournament->rounds()->where('round_number', 2)->firstOrFail()->status);
        self::assertSame(RoundStatus::Scheduled, $tournament->rounds()->where('round_number', 3)->firstOrFail()->status);
    }

    private function createGame(array $overrides = []): Tournament
    {
        $data = array_replace([
            'name' => 'Score Test',
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

    private function firstMatch(Tournament $tournament): MatchModel
    {
        return $tournament->rounds()->with('matches')->firstOrFail()->matches->firstOrFail();
    }

    private function saveFirstMatch(Tournament $tournament, int $scoreA, int $scoreB): void
    {
        $match = $this->firstMatch($tournament);
        $this->put(route('games.score.update', [$tournament, $match]), [
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
        ])->assertRedirect();
    }
}
