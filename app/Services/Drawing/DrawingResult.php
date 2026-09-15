<?php

namespace App\Services\Drawing;

final readonly class DrawingResult
{
    public array $restingPlayerIds;

    public function __construct(
        public array $matches,
        array $restingPlayerIds,
        public FairnessScore $score,
        public int $seed,
        public array $diagnostics = [],
    ) {
        $this->restingPlayerIds = array_values(array_map('intval', $restingPlayerIds));
    }

    public function metrics(): array
    {
        return $this->score->metrics;
    }
}
