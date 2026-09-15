<?php

namespace App\Services\Drawing;

final class DrawingScorer
{
    public function score(
        DrawingCandidate $candidate,
        array $activePlayerIds,
        FairnessHistory $history,
        DrawingWeights $weights,
    ): FairnessScore {
        $activePlayerIds = array_values(array_unique(array_map('intval', $activePlayerIds)));
        $playing = [];

        foreach ($candidate->matches as $match) {
            foreach ($match->playerIds() as $playerId) {
                $playing[(int) $playerId] = true;
            }
        }

        $matchCounts = [];
        $restCounts = [];

        foreach ($activePlayerIds as $playerId) {
            $matchCounts[$playerId] = $history->matchCount($playerId) + (isset($playing[$playerId]) ? 1 : 0);
            $restCounts[$playerId] = $history->restCount($playerId) + (in_array($playerId, $candidate->restingPlayerIds, true) ? 1 : 0);
        }

        $matchImbalance = $this->spread($matchCounts);
        $restImbalance = $this->spread($restCounts);
        $repeatedPartner = 0;
        $repeatedOpponent = 0;

        foreach ($candidate->matches as $match) {
            foreach ([$match->teamA, $match->teamB] as $team) {
                $repeatedPartner += $this->pairPenalty($team, $history, true);
            }

            foreach ($match->teamA as $firstPlayerId) {
                foreach ($match->teamB as $secondPlayerId) {
                    $repeatedOpponent += $history->opponentCount((int) $firstPlayerId, (int) $secondPlayerId);
                }
            }
        }

        $consecutiveRest = 0;
        foreach ($candidate->restingPlayerIds as $playerId) {
            if ($history->restedLastRound((int) $playerId)) {
                $consecutiveRest += 1 + $history->restStreak((int) $playerId);
            }
        }

        $components = [
            'match_count_imbalance' => $matchImbalance,
            'rest_imbalance' => $restImbalance,
            'repeated_partner' => $repeatedPartner,
            'repeated_opponent' => $repeatedOpponent,
            'consecutive_rest' => $consecutiveRest,
        ];

        $penalty = ($matchImbalance * $weights->matchCountImbalance)
            + ($restImbalance * $weights->restImbalance)
            + ($repeatedPartner * $weights->repeatedPartner)
            + ($repeatedOpponent * $weights->repeatedOpponent)
            + ($consecutiveRest * $weights->consecutiveRest);

        $metrics = [
            'penalty' => $penalty,
            'components' => $components,
            'match_count_min' => min($matchCounts),
            'match_count_max' => max($matchCounts),
            'match_count_spread' => $matchImbalance,
            'rest_min' => min($restCounts),
            'rest_max' => max($restCounts),
            'rest_spread' => $restImbalance,
            'repeated_partnerships' => $repeatedPartner,
            'repeated_opponents' => $repeatedOpponent,
            'consecutive_rests' => $consecutiveRest,
            'players' => $this->playerMetrics($activePlayerIds, $matchCounts, $restCounts, $candidate, $history),
        ];

        return new FairnessScore($penalty, $components, $metrics);
    }

    private function pairPenalty(array $team, FairnessHistory $history, bool $partner): int
    {
        if (count($team) !== 2) {
            return 0;
        }

        return $partner
            ? $history->partnerCount((int) $team[0], (int) $team[1])
            : $history->opponentCount((int) $team[0], (int) $team[1]);
    }

    private function playerMetrics(array $activePlayerIds, array $matchCounts, array $restCounts, DrawingCandidate $candidate, FairnessHistory $history): array
    {
        $resting = array_fill_keys($candidate->restingPlayerIds, true);
        $metrics = [];

        foreach ($activePlayerIds as $playerId) {
            $partners = array_unique(array_merge(array_keys($history->partnerCountsFor($playerId)), $this->currentPartners($playerId, $candidate)));
            $opponents = array_unique(array_merge(array_keys($history->opponentCountsFor($playerId)), $this->currentOpponents($playerId, $candidate)));

            $metrics[$playerId] = [
                'matches' => $matchCounts[$playerId],
                'rests' => $restCounts[$playerId],
                'resting_now' => isset($resting[$playerId]),
                'unique_partners' => count($partners),
                'unique_opponents' => count($opponents),
            ];
        }

        return $metrics;
    }

    private function currentPartners(int $playerId, DrawingCandidate $candidate): array
    {
        foreach ($candidate->matches as $match) {
            foreach ([$match->teamA, $match->teamB] as $team) {
                if (in_array($playerId, $team, true)) {
                    return array_values(array_diff(array_map('intval', $team), [$playerId]));
                }
            }
        }

        return [];
    }

    private function currentOpponents(int $playerId, DrawingCandidate $candidate): array
    {
        foreach ($candidate->matches as $match) {
            if (in_array($playerId, $match->teamA, true)) {
                return array_map('intval', $match->teamB);
            }

            if (in_array($playerId, $match->teamB, true)) {
                return array_map('intval', $match->teamA);
            }
        }

        return [];
    }

    private function spread(array $values): int
    {
        return max($values) - min($values);
    }
}
