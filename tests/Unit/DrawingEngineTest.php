<?php

namespace Tests\Unit;

use App\Services\Drawing\CandidateGenerator;
use App\Services\Drawing\DrawingCandidate;
use App\Services\Drawing\DrawingException;
use App\Services\Drawing\DrawingMatch;
use App\Services\Drawing\DrawingRequest;
use App\Services\Drawing\DrawingScorer;
use App\Services\Drawing\DrawingService;
use App\Services\Drawing\DrawingValidator;
use App\Services\Drawing\DrawingWeights;
use App\Services\Drawing\FairnessHistory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DrawingEngineTest extends TestCase
{
    #[DataProvider('requiredCombinations')]
    public function test_required_roster_and_court_combinations_are_valid(int $playerCount, int $courts, int $expectedMatches): void
    {
        $playerIds = range(1, $playerCount);
        $result = $this->service()->generate($this->request($playerIds, $courts));

        self::assertCount($expectedMatches, $result->matches);
        self::assertCount($playerCount - ($expectedMatches * 4), $result->restingPlayerIds);
        self::assertSame([], (new DrawingValidator)->validate(
            new DrawingCandidate($result->matches, $result->restingPlayerIds),
            $playerIds,
            $courts,
        ));
    }

    public static function requiredCombinations(): iterable
    {
        yield '4 players / 1 court' => [4, 1, 1];
        yield '5 players / 1 court' => [5, 1, 1];
        yield '6 players / 1 court' => [6, 1, 1];
        yield '8 players / 1 court' => [8, 1, 1];
        yield '8 players / 2 courts' => [8, 2, 2];
        yield '10 players / 2 courts' => [10, 2, 2];
        yield '12 players / 2 courts' => [12, 2, 2];
        yield '12 players / 3 courts' => [12, 3, 3];
    }

    public function test_same_seed_and_history_are_reproducible(): void
    {
        $request = $this->request(range(1, 12), 3, seed: 77);
        $first = $this->service()->generate($request);
        $second = $this->service()->generate($request);

        self::assertSame($this->drawingKey($first), $this->drawingKey($second));
        self::assertSame($first->metrics(), $second->metrics());
    }

    public function test_players_who_rest_are_prioritized_to_play_next_round(): void
    {
        $playerIds = range(1, 6);
        $first = $this->service()->generate($this->request($playerIds, 1, seed: 3));
        $history = FairnessHistory::empty($playerIds)->after(new DrawingCandidate($first->matches, $first->restingPlayerIds));
        $second = $this->service()->generate($this->request($playerIds, 1, $history, seed: 3));

        self::assertNotSame($first->restingPlayerIds, $second->restingPlayerIds);
        self::assertLessThanOrEqual(1, $second->metrics()['consecutive_rests']);
        self::assertLessThanOrEqual(1, $second->metrics()['match_count_spread']);
    }

    public function test_history_tracks_partners_and_opponents_across_rounds(): void
    {
        $playerIds = range(1, 8);
        $first = $this->service()->generate($this->request($playerIds, 2, seed: 5));
        $history = FairnessHistory::empty($playerIds)->after(new DrawingCandidate($first->matches, $first->restingPlayerIds));
        $second = $this->service()->generate($this->request($playerIds, 2, $history, seed: 5));

        self::assertGreaterThanOrEqual(1, $first->metrics()['players'][1]['unique_partners']);
        self::assertGreaterThanOrEqual(1, $second->metrics()['players'][1]['unique_partners']);
        self::assertGreaterThanOrEqual(2, $second->metrics()['players'][1]['unique_opponents']);
        self::assertGreaterThanOrEqual(0, $second->metrics()['repeated_partnerships']);
        self::assertGreaterThanOrEqual(0, $second->metrics()['repeated_opponents']);
    }

    public function test_less_than_four_active_players_fails_with_actionable_exception(): void
    {
        $this->expectException(DrawingException::class);
        $this->expectExceptionMessage('At least four active players');

        $this->service()->generate($this->request([1, 2, 3], 1));
    }

    public function test_validator_rejects_player_collision(): void
    {
        $candidate = new DrawingCandidate([
            new DrawingMatch(1, [1, 2], [3, 4]),
            new DrawingMatch(2, [1, 5], [6, 7]),
        ], [8]);

        $errors = (new DrawingValidator)->validate($candidate, range(1, 8), 2);

        self::assertNotEmpty($errors);
        self::assertStringContainsString('appears in more than one match', implode(' ', $errors));
    }

    public function test_weight_configuration_changes_scoring_without_breaking_validity(): void
    {
        $playerIds = range(1, 12);
        $history = FairnessHistory::empty($playerIds);
        $default = $this->service()->generate($this->request($playerIds, 3, $history, seed: 10));
        $restFirst = $this->service()->generate($this->request(
            $playerIds,
            3,
            $history,
            seed: 10,
            weights: new DrawingWeights(matchCountImbalance: 0, restImbalance: 0, repeatedPartner: 0, repeatedOpponent: 0, consecutiveRest: 100),
        ));

        self::assertNotEmpty($default->metrics());
        self::assertNotEmpty($restFirst->metrics());
        self::assertSame([], (new DrawingValidator)->validate(
            new DrawingCandidate($restFirst->matches, $restFirst->restingPlayerIds),
            $playerIds,
            3,
        ));
    }

    private function service(): DrawingService
    {
        return new DrawingService(
            new CandidateGenerator,
            new DrawingValidator,
            new DrawingScorer,
        );
    }

    private function request(array $playerIds, int $courts, ?FairnessHistory $history = null, int $seed = 1, ?DrawingWeights $weights = null): DrawingRequest
    {
        return new DrawingRequest(
            activePlayerIds: $playerIds,
            numberOfCourts: $courts,
            history: $history ?? FairnessHistory::empty($playerIds),
            seed: $seed,
            weights: $weights ?? new DrawingWeights,
            candidateLimit: 250,
        );
    }

    private function drawingKey($drawing): string
    {
        return json_encode([
            array_map(static fn ($match): array => [$match->courtNumber, $match->teamA, $match->teamB], $drawing->matches),
            $drawing->restingPlayerIds,
        ], JSON_THROW_ON_ERROR);
    }
}
