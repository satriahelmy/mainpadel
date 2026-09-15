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
        $prior = $this->service()->generate($this->request($playerIds, 3, $history, seed: 70));
        $history = $history->after(new DrawingCandidate($prior->matches, $prior->restingPlayerIds));
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
        self::assertNotSame($this->drawingKey($default), $this->drawingKey($restFirst));
        self::assertSame([], (new DrawingValidator)->validate(
            new DrawingCandidate($restFirst->matches, $restFirst->restingPlayerIds),
            $playerIds,
            3,
        ));
    }

    public function test_score_components_have_expected_penalty_ordering(): void
    {
        $playerIds = range(1, 5);
        $previousRound = new DrawingCandidate([
            new DrawingMatch(1, [1, 2], [3, 4]),
        ], [5]);
        $history = FairnessHistory::empty($playerIds)->after($previousRound);
        $scorer = new DrawingScorer;
        $weights = new DrawingWeights(matchCountImbalance: 1, restImbalance: 1, repeatedPartner: 1, repeatedOpponent: 1, consecutiveRest: 1);

        $score = $scorer->score($previousRound, $playerIds, $history, $weights);

        self::assertSame(2, $score->components['match_count_imbalance']);
        self::assertSame(2, $score->components['rest_imbalance']);
        self::assertSame(2, $score->components['repeated_partner']);
        self::assertSame(4, $score->components['repeated_opponent']);
        self::assertSame(2, $score->components['consecutive_rest']);
        self::assertSame(12, $score->penalty);
    }

    public function test_hand_built_history_penalizes_repeated_partners(): void
    {
        $playerIds = range(1, 8);
        $history = FairnessHistory::empty($playerIds)->after(new DrawingCandidate([
            new DrawingMatch(1, [1, 2], [3, 4]),
            new DrawingMatch(2, [5, 6], [7, 8]),
        ], []));
        $weights = new DrawingWeights(matchCountImbalance: 0, restImbalance: 0, repeatedPartner: 1, repeatedOpponent: 0, consecutiveRest: 0);
        $scorer = new DrawingScorer;

        $repeated = $scorer->score(new DrawingCandidate([
            new DrawingMatch(1, [1, 2], [3, 4]),
            new DrawingMatch(2, [5, 6], [7, 8]),
        ], []), $playerIds, $history, $weights);
        $varied = $scorer->score(new DrawingCandidate([
            new DrawingMatch(1, [1, 3], [2, 4]),
            new DrawingMatch(2, [5, 7], [6, 8]),
        ], []), $playerIds, $history, $weights);

        self::assertGreaterThan($varied->components['repeated_partner'], $repeated->components['repeated_partner']);
        self::assertGreaterThan($varied->penalty, $repeated->penalty);
    }

    public function test_drawing_diagnostics_are_opt_in_and_report_candidate_comparisons(): void
    {
        $request = new DrawingRequest(
            activePlayerIds: range(1, 8),
            numberOfCourts: 2,
            history: FairnessHistory::empty(range(1, 8)),
            seed: 12,
            weights: new DrawingWeights,
            candidateLimit: 20,
            includeDiagnostics: true,
        );

        $result = $this->service()->generate($request);

        self::assertGreaterThan(0, $result->diagnostics['candidate_count']);
        self::assertGreaterThan(0, $result->diagnostics['valid_candidate_count']);
        self::assertNotEmpty($result->diagnostics['candidate_penalties']);
        self::assertArrayHasKey('components', $result->diagnostics['candidate_penalties'][0]);
    }

    #[DataProvider('fairnessSimulationCombinations')]
    public function test_multi_round_simulations_keep_fairness_spreads_bounded(int $playerCount, int $courts): void
    {
        $playerIds = range(1, $playerCount);
        $history = FairnessHistory::empty($playerIds);

        for ($round = 1; $round <= 6; $round++) {
            $result = $this->service()->generate($this->request($playerIds, $courts, $history, seed: 100 + $round));

            self::assertSame([], (new DrawingValidator)->validate(
                new DrawingCandidate($result->matches, $result->restingPlayerIds),
                $playerIds,
                $courts,
            ));

            $history = $history->after(new DrawingCandidate($result->matches, $result->restingPlayerIds));
        }

        $last = $this->service()->generate($this->request($playerIds, $courts, $history, seed: 107));

        self::assertLessThanOrEqual(1, $last->metrics()['match_count_spread']);
        self::assertLessThanOrEqual(1, $last->metrics()['rest_spread']);
        self::assertArrayHasKey('match_count_variance', $last->metrics());
        self::assertArrayHasKey('rest_variance', $last->metrics());
    }

    public static function fairnessSimulationCombinations(): iterable
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
