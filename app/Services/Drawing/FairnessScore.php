<?php

namespace App\Services\Drawing;

final readonly class FairnessScore
{
    public function __construct(
        public int $penalty,
        public array $components,
        public array $metrics,
    ) {}
}
