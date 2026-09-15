<?php

namespace App\Services\Drawing;

final readonly class DrawingMatch
{
    public array $teamA;

    public array $teamB;

    public function __construct(
        public int $courtNumber,
        array $teamA,
        array $teamB,
    ) {
        $this->teamA = array_values(array_map('intval', $teamA));
        $this->teamB = array_values(array_map('intval', $teamB));
    }

    public function playerIds(): array
    {
        return array_merge($this->teamA, $this->teamB);
    }
}
