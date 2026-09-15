<?php

namespace App\Services\Drawing;

final class DrawingService
{
    public function __construct(
        private readonly CandidateGenerator $candidateGenerator,
        private readonly DrawingValidator $validator,
        private readonly DrawingScorer $scorer,
    ) {}

    public function generate(DrawingRequest $request): DrawingResult
    {
        $activePlayerIds = array_values(array_unique(array_map('intval', $request->activePlayerIds)));

        if (count($activePlayerIds) < 4) {
            throw new DrawingException('At least four active players are required to generate a draw.');
        }

        if ($request->numberOfCourts < 1) {
            throw new DrawingException('At least one court is required to generate a draw.');
        }

        $bestCandidate = null;
        $bestScore = null;
        $candidateCount = 0;
        $validCandidateCount = 0;
        $candidatePenalties = [];

        foreach ($this->candidateGenerator->generate($request) as $candidate) {
            $candidateCount++;
            $errors = $this->validator->validate($candidate, $activePlayerIds, $request->numberOfCourts);

            if ($errors !== []) {
                continue;
            }

            $validCandidateCount++;
            $score = $this->scorer->score($candidate, $activePlayerIds, $request->history, $request->resolvedWeights());

            if ($request->includeDiagnostics) {
                $candidatePenalties[] = [
                    'canonical_key' => $candidate->canonicalKey(),
                    'penalty' => $score->penalty,
                    'components' => $score->components,
                ];
            }

            if ($bestScore === null || $score->penalty < $bestScore->penalty || ($score->penalty === $bestScore->penalty && $candidate->canonicalKey() < $bestCandidate->canonicalKey())) {
                $bestCandidate = $candidate;
                $bestScore = $score;
            }
        }

        if ($bestCandidate === null || $bestScore === null) {
            throw new DrawingException('No valid drawing could be generated within the configured search budget.');
        }

        return new DrawingResult(
            matches: $bestCandidate->matches,
            restingPlayerIds: $bestCandidate->restingPlayerIds,
            score: $bestScore,
            seed: $request->seed,
            diagnostics: $request->includeDiagnostics ? [
                'candidate_count' => $candidateCount,
                'valid_candidate_count' => $validCandidateCount,
                'candidate_penalties' => $candidatePenalties,
            ] : [],
        );
    }
}
