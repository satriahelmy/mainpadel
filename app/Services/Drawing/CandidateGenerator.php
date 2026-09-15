<?php

namespace App\Services\Drawing;

use Generator;

final class CandidateGenerator
{
    public function generate(DrawingRequest $request): Generator
    {
        $activePlayerIds = $this->normalizedPlayerIds($request->activePlayerIds);
        $matchCount = DrawingValidator::expectedMatchCount(count($activePlayerIds), $request->numberOfCourts);
        $playingCount = $matchCount * 4;

        if ($playingCount === 0) {
            return;
        }

        $priorityOrder = $this->priorityOrder($activePlayerIds, $request->history);
        $seen = [];
        $generated = 0;
        $candidateLimit = $request->resolvedCandidateLimit();
        $variantLimit = max(12, min(48, intdiv(max(1, $candidateLimit), max(1, count($activePlayerIds)))));

        foreach ($this->combinations($priorityOrder, $playingCount) as $selected) {
            foreach ($this->partitionVariants($selected, $request->seed, $variantLimit) as $matches) {
                $resting = array_values(array_diff($activePlayerIds, $selected));
                sort($resting);
                $candidate = new DrawingCandidate($matches, $resting);
                $key = $candidate->canonicalKey();

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                yield $candidate;
                $generated++;

                if ($generated >= $candidateLimit) {
                    return;
                }
            }
        }
    }

    private function priorityOrder(array $playerIds, FairnessHistory $history): array
    {
        usort($playerIds, static function (int $first, int $second) use ($history): int {
            $firstPriority = [
                $history->matchCount($first),
                -$history->restCount($first),
                $history->restedLastRound($first) ? 0 : 1,
                -$history->restStreak($first),
                $first,
            ];
            $secondPriority = [
                $history->matchCount($second),
                -$history->restCount($second),
                $history->restedLastRound($second) ? 0 : 1,
                -$history->restStreak($second),
                $second,
            ];

            return $firstPriority <=> $secondPriority;
        });

        return $playerIds;
    }

    private function partitionVariants(array $selected, int $seed, int $variantLimit): Generator
    {
        $selected = array_values($selected);
        $count = count($selected);
        $steps = $this->coprimeSteps($count);
        $patterns = [
            [0, 1, 2, 3],
            [0, 2, 1, 3],
            [0, 3, 1, 2],
        ];
        $seedOffset = abs($seed) % max(1, $count);

        for ($variant = 0; $variant < $variantLimit; $variant++) {
            $step = $steps[$variant % count($steps)];
            $rotation = ($seedOffset + $variant) % $count;
            $ordered = [];

            for ($position = 0; $position < $count; $position++) {
                $ordered[] = $selected[($rotation + ($position * $step)) % $count];
            }

            $pattern = $patterns[$variant % count($patterns)];
            $matches = [];

            for ($offset = 0; $offset < $count; $offset += 4) {
                $chunk = array_slice($ordered, $offset, 4);
                $matches[] = new DrawingMatch(
                    courtNumber: intdiv($offset, 4) + 1,
                    teamA: [$chunk[$pattern[0]], $chunk[$pattern[1]]],
                    teamB: [$chunk[$pattern[2]], $chunk[$pattern[3]]],
                );
            }

            yield $matches;
        }
    }

    private function combinations(array $items, int $choose, int $start = 0, array $current = []): Generator
    {
        if (count($current) === $choose) {
            yield $current;

            return;
        }

        $remaining = $choose - count($current);
        $lastStart = count($items) - $remaining;

        for ($index = $start; $index <= $lastStart; $index++) {
            $next = $current;
            $next[] = $items[$index];

            yield from $this->combinations($items, $choose, $index + 1, $next);
        }
    }

    private function coprimeSteps(int $count): array
    {
        $steps = [];

        for ($step = 1; $step < $count; $step++) {
            if ($this->greatestCommonDivisor($step, $count) === 1) {
                $steps[] = $step;
            }
        }

        return $steps === [] ? [1] : $steps;
    }

    private function greatestCommonDivisor(int $first, int $second): int
    {
        while ($second !== 0) {
            [$first, $second] = [$second, $first % $second];
        }

        return abs($first);
    }

    private function normalizedPlayerIds(array $playerIds): array
    {
        $playerIds = array_values(array_unique(array_map('intval', $playerIds)));
        sort($playerIds);

        return $playerIds;
    }
}
