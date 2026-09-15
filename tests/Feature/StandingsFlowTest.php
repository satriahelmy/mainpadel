<?php

namespace Tests\Feature;

use App\Models\MatchModel;
use App\Models\Tournament;
use App\Models\User;
use App\Services\StandingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandingsFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_standings_derive_individual_stats_from_multiple_completed_matches(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'],
            'number_of_courts' => 2,
            'round_mode' => 'custom',
            'number_of_rounds' => 1,
        ]);
        $this->post(route('games.start', $tournament));
        $matches = $tournament->rounds()->with('matches')->firstOrFail()->matches;

        $this->saveScore($tournament, $matches[0], 12, 9);
        $this->saveScore($tournament, $matches[1], 11, 10);

        $standings = app(StandingsService::class)->forTournament($tournament);

        self::assertCount(8, $standings);
        self::assertSame(8, $standings->sum('played'));
        self::assertSame(84, $standings->sum('points_for'));
        self::assertSame(84, $standings->sum('points_against'));
        self::assertSame(4, $standings->where('wins', 1)->count());
        self::assertSame(4, $standings->where('losses', 1)->count());
        self::assertTrue($standings->every(fn (array $standing): bool => array_key_exists('point_difference', $standing)));
    }

    public function test_withdrawn_and_zero_result_players_remain_in_standings(): void
    {
        $tournament = $this->createGame([
            'players' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'round_mode' => 'custom',
            'number_of_rounds' => 2,
        ]);
        $this->post(route('games.start', $tournament));
        $match = $this->firstMatch($tournament);
        $playedIds = $match->matchPlayers()->pluck('player_id')->all();
        $restingMembership = $tournament->tournamentPlayers()->whereNotIn('player_id', $playedIds)->firstOrFail();
        $playedMembership = $tournament->tournamentPlayers()->whereIn('player_id', $playedIds)->firstOrFail();

        $this->saveScore($tournament, $match, 12, 9);
        $this->post(route('games.players.withdraw', [$tournament, $playedMembership]))->assertRedirect();

        $standings = app(StandingsService::class)->forTournament($tournament);
        $withdrawn = $standings->firstWhere('player_id', $playedMembership->player_id);
        $zeroResult = $standings->firstWhere('player_id', $restingMembership->player_id);

        self::assertNotNull($withdrawn);
        self::assertSame('withdrawn', $withdrawn['status']);
        self::assertSame(1, $withdrawn['played']);
        self::assertNotNull($zeroResult);
        self::assertSame(0, $zeroResult['played']);
        self::assertCount(6, $standings);
    }

    public function test_tied_standings_use_a_deterministic_name_tie_breaker(): void
    {
        $tournament = $this->createGame([
            'players' => ['Zed', 'Amy', 'Mia', 'Ben'],
            'target_points' => 20,
            'round_mode' => 'custom',
            'number_of_rounds' => 1,
        ]);
        $this->post(route('games.start', $tournament));
        $this->saveScore($tournament, $this->firstMatch($tournament), 10, 10);

        $standings = app(StandingsService::class)->forTournament($tournament);

        self::assertSame(['Amy', 'Ben', 'Mia', 'Zed'], $standings->pluck('name')->all());
        self::assertTrue($standings->every(fn (array $standing): bool => $standing['wins'] === 0 && $standing['losses'] === 0));
    }

    private function createGame(array $overrides = []): Tournament
    {
        $data = array_replace([
            'name' => 'Standings Test',
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

    private function saveScore(Tournament $tournament, MatchModel $match, int $scoreA, int $scoreB): void
    {
        $this->put(route('games.score.update', [$tournament, $match]), [
            'team_a_score' => $scoreA,
            'team_b_score' => $scoreB,
        ])->assertRedirect();
    }
}
