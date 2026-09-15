<?php

namespace App\Services\Drawing;

final readonly class DrawingCandidate
{
    public array $restingPlayerIds;

    public function __construct(
        public array $matches,
        array $restingPlayerIds,
    ) {
        foreach ($matches as $match) {
            if (! $match instanceof DrawingMatch) {
                throw new DrawingException('A drawing candidate contains an invalid match.');
            }
        }

        $this->restingPlayerIds = array_values(array_map('intval', $restingPlayerIds));
    }

    public function canonicalKey(): string
    {
        return json_encode([
            'matches' => array_map(static fn (DrawingMatch $match): array => [
                $match->courtNumber,
                $match->teamA,
                $match->teamB,
            ], $this->matches),
            'resting' => $this->restingPlayerIds,
        ], JSON_THROW_ON_ERROR);
    }
}
