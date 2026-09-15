<?php

namespace App\Services\Drawing;

final class FairnessHistory
{
    public function __construct(
        private array $matchCounts = [],
        private array $restCounts = [],
        private array $restedLastRound = [],
        private array $restStreaks = [],
        private array $partnerCounts = [],
        private array $opponentCounts = [],
    ) {
        $this->matchCounts = $this->normalizePlayerCounts($this->matchCounts);
        $this->restCounts = $this->normalizePlayerCounts($this->restCounts);
        $this->restedLastRound = array_map('boolval', $this->restedLastRound);
        $this->restStreaks = $this->normalizePlayerCounts($this->restStreaks);
    }

    public static function empty(array $playerIds): self
    {
        $counts = [];
        $rested = [];

        foreach (array_unique(array_map('intval', $playerIds)) as $playerId) {
            $counts[$playerId] = 0;
            $rested[$playerId] = false;
        }

        return new self($counts, $counts, $rested, $counts);
    }

    public static function fromCandidates(array $playerIds, array $candidates): self
    {
        $history = self::empty($playerIds);

        foreach ($candidates as $candidate) {
            if (! $candidate instanceof DrawingCandidate) {
                throw new DrawingException('Fairness history can only consume drawing candidates.');
            }

            $history = $history->after($candidate);
        }

        return $history;
    }

    public function matchCount(int $playerId): int
    {
        return (int) ($this->matchCounts[$playerId] ?? 0);
    }

    public function restCount(int $playerId): int
    {
        return (int) ($this->restCounts[$playerId] ?? 0);
    }

    public function restedLastRound(int $playerId): bool
    {
        return (bool) ($this->restedLastRound[$playerId] ?? false);
    }

    public function restStreak(int $playerId): int
    {
        return (int) ($this->restStreaks[$playerId] ?? 0);
    }

    public function partnerCount(int $firstPlayerId, int $secondPlayerId): int
    {
        return (int) ($this->partnerCounts[$this->pairKey($firstPlayerId, $secondPlayerId)] ?? 0);
    }

    public function opponentCount(int $firstPlayerId, int $secondPlayerId): int
    {
        return (int) ($this->opponentCounts[$this->pairKey($firstPlayerId, $secondPlayerId)] ?? 0);
    }

    public function partnerCountsFor(int $playerId): array
    {
        return $this->encounterCountsFor($playerId, $this->partnerCounts);
    }

    public function opponentCountsFor(int $playerId): array
    {
        return $this->encounterCountsFor($playerId, $this->opponentCounts);
    }

    public function after(DrawingCandidate $candidate): self
    {
        $nextMatches = $this->matchCounts;
        $nextRests = $this->restCounts;
        $nextRested = $this->restedLastRound;
        $nextStreaks = $this->restStreaks;
        $nextPartners = $this->partnerCounts;
        $nextOpponents = $this->opponentCounts;
        $playing = [];

        foreach ($candidate->matches as $match) {
            foreach (array_merge($match->teamA, $match->teamB) as $playerId) {
                $playerId = (int) $playerId;
                $playing[$playerId] = true;
                $nextMatches[$playerId] = ($nextMatches[$playerId] ?? 0) + 1;
                $nextRested[$playerId] = false;
                $nextStreaks[$playerId] = 0;
            }

            foreach ([$match->teamA, $match->teamB] as $team) {
                $this->incrementPairs($nextPartners, $team);
            }

            foreach ($match->teamA as $firstPlayerId) {
                foreach ($match->teamB as $secondPlayerId) {
                    $key = $this->pairKey((int) $firstPlayerId, (int) $secondPlayerId);
                    $nextOpponents[$key] = ($nextOpponents[$key] ?? 0) + 1;
                }
            }
        }

        foreach ($candidate->restingPlayerIds as $playerId) {
            $playerId = (int) $playerId;
            if (isset($playing[$playerId])) {
                throw new DrawingException('A player cannot both play and rest in the same drawing.');
            }

            $nextRests[$playerId] = ($nextRests[$playerId] ?? 0) + 1;
            $nextRested[$playerId] = true;
            $nextStreaks[$playerId] = ($nextStreaks[$playerId] ?? 0) + 1;
        }

        foreach (array_keys($nextMatches + $nextRests) as $playerId) {
            if (! isset($playing[(int) $playerId]) && ! in_array((int) $playerId, $candidate->restingPlayerIds, true)) {
                $nextRested[(int) $playerId] = false;
                $nextStreaks[(int) $playerId] = 0;
            }
        }

        return new self($nextMatches, $nextRests, $nextRested, $nextStreaks, $nextPartners, $nextOpponents);
    }

    private function incrementPairs(array &$counts, array $players): void
    {
        $players = array_values(array_map('intval', $players));

        for ($first = 0; $first < count($players); $first++) {
            for ($second = $first + 1; $second < count($players); $second++) {
                $key = $this->pairKey($players[$first], $players[$second]);
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }
    }

    private function pairKey(int $firstPlayerId, int $secondPlayerId): string
    {
        return min($firstPlayerId, $secondPlayerId).':'.max($firstPlayerId, $secondPlayerId);
    }

    private function encounterCountsFor(int $playerId, array $counts): array
    {
        $encounters = [];
        $prefix = $playerId.':';

        foreach ($counts as $key => $count) {
            [$first, $second] = array_map('intval', explode(':', (string) $key));

            if ($first === $playerId) {
                $encounters[$second] = (int) $count;
            } elseif ($second === $playerId) {
                $encounters[$first] = (int) $count;
            }
        }

        return $encounters;
    }

    private function normalizePlayerCounts(array $counts): array
    {
        $normalized = [];

        foreach ($counts as $playerId => $count) {
            $normalized[(int) $playerId] = (int) $count;
        }

        return $normalized;
    }
}
