<?php

namespace App\Services\Drawing;

final readonly class DrawingWeights
{
    public function __construct(
        public int $matchCountImbalance = 1000,
        public int $restImbalance = 500,
        public int $repeatedPartner = 50,
        public int $repeatedOpponent = 25,
        public int $consecutiveRest = 10,
    ) {}

    public static function fromConfig(?array $weights = null): self
    {
        $weights ??= config('mainpadel.drawing.weights', []);

        return new self(
            matchCountImbalance: (int) ($weights['match_count_imbalance'] ?? 1000),
            restImbalance: (int) ($weights['rest_imbalance'] ?? 500),
            repeatedPartner: (int) ($weights['repeated_partner'] ?? 50),
            repeatedOpponent: (int) ($weights['repeated_opponent'] ?? 25),
            consecutiveRest: (int) ($weights['consecutive_rest'] ?? 10),
        );
    }
}
