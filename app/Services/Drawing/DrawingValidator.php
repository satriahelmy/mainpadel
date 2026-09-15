<?php

namespace App\Services\Drawing;

final class DrawingValidator
{
    public function validate(DrawingCandidate $candidate, array $activePlayerIds, int $numberOfCourts): array
    {
        $activePlayerIds = array_values(array_unique(array_map('intval', $activePlayerIds)));
        $active = array_fill_keys($activePlayerIds, true);
        $expectedMatches = self::expectedMatchCount(count($activePlayerIds), $numberOfCourts);
        $errors = [];
        $seenPlayers = [];
        $seenCourts = [];

        if ($numberOfCourts < 1) {
            $errors[] = 'number_of_courts must be at least 1.';
        }

        if (count($candidate->matches) !== $expectedMatches) {
            $errors[] = "Expected {$expectedMatches} matches, received ".count($candidate->matches).'.';
        }

        foreach ($candidate->matches as $matchIndex => $match) {
            if (! $match instanceof DrawingMatch) {
                $errors[] = "Match {$matchIndex} is invalid.";

                continue;
            }

            if ($match->courtNumber < 1 || $match->courtNumber > $numberOfCourts || isset($seenCourts[$match->courtNumber])) {
                $errors[] = "Court {$match->courtNumber} is duplicated or outside the requested court range.";
            }

            $seenCourts[$match->courtNumber] = true;

            if (count($match->teamA) !== 2 || count($match->teamB) !== 2) {
                $errors[] = "Court {$match->courtNumber} must have exactly two players per team.";
            }

            $matchPlayers = $match->playerIds();
            if (count($matchPlayers) !== 4 || count(array_unique($matchPlayers)) !== 4) {
                $errors[] = "Court {$match->courtNumber} must have exactly four unique players.";
            }

            foreach ($matchPlayers as $playerId) {
                if (! isset($active[$playerId])) {
                    $errors[] = "Player {$playerId} is not active in this drawing.";
                }

                if (isset($seenPlayers[$playerId])) {
                    $errors[] = "Player {$playerId} appears in more than one match.";
                }

                $seenPlayers[$playerId] = true;
            }
        }

        if (count($candidate->restingPlayerIds) !== count($activePlayerIds) - ($expectedMatches * 4)) {
            $errors[] = 'The resting-player count does not match court capacity.';
        }

        if (count(array_unique($candidate->restingPlayerIds)) !== count($candidate->restingPlayerIds)) {
            $errors[] = 'A resting player is listed more than once.';
        }

        foreach ($candidate->restingPlayerIds as $playerId) {
            if (! isset($active[$playerId])) {
                $errors[] = "Resting player {$playerId} is not active in this drawing.";
            }

            if (isset($seenPlayers[$playerId])) {
                $errors[] = "Player {$playerId} cannot both play and rest.";
            }

            $seenPlayers[$playerId] = true;
        }

        if (count($seenPlayers) !== count($activePlayerIds)) {
            $errors[] = 'Every active player must be either playing or resting.';
        }

        return array_values(array_unique($errors));
    }

    public function assertValid(DrawingCandidate $candidate, array $activePlayerIds, int $numberOfCourts): void
    {
        $errors = $this->validate($candidate, $activePlayerIds, $numberOfCourts);

        if ($errors !== []) {
            throw new DrawingException(implode(' ', $errors));
        }
    }

    public static function expectedMatchCount(int $activePlayerCount, int $numberOfCourts): int
    {
        if ($activePlayerCount < 4 || $numberOfCourts < 1) {
            return 0;
        }

        return min($numberOfCourts, intdiv($activePlayerCount, 4));
    }
}
