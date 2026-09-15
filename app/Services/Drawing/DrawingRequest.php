<?php

namespace App\Services\Drawing;

final readonly class DrawingRequest
{
    public array $activePlayerIds;

    public function __construct(
        array $activePlayerIds,
        public int $numberOfCourts,
        public FairnessHistory $history,
        public int $seed = 1,
        public ?DrawingWeights $weights = null,
        public int $candidateLimit = 0,
    ) {
        $this->activePlayerIds = array_values(array_unique(array_map('intval', $activePlayerIds)));
    }

    public function resolvedWeights(): DrawingWeights
    {
        return $this->weights ?? DrawingWeights::fromConfig();
    }

    public function resolvedCandidateLimit(): int
    {
        return $this->candidateLimit > 0
            ? $this->candidateLimit
            : (int) config('mainpadel.drawing.candidate_limit', 1500);
    }
}
