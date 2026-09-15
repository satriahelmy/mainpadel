<?php

namespace App\Services;

use App\Enums\MatchTeam;
use App\Models\Tournament;
use Illuminate\Support\Collection;

final class StandingsService
{
    public function forTournament(Tournament $tournament): Collection
    {
        $tournament->loadMissing(['tournamentPlayers.player', 'rounds.matches.matchPlayers']);
        $stats = [];

        foreach ($tournament->tournamentPlayers as $membership) {
            $stats[$membership->player_id] = [
                'player_id' => (int) $membership->player_id,
                'player' => $membership->player,
                'name' => $membership->player->name,
                'played' => 0,
                'wins' => 0,
                'losses' => 0,
                'points_for' => 0,
                'points_against' => 0,
                'point_difference' => 0,
                'status' => $membership->status->value,
            ];
        }

        foreach ($tournament->rounds as $round) {
            foreach ($round->matches as $match) {
                if ($match->status->value !== 'completed') {
                    continue;
                }

                $teamA = $match->matchPlayers->where('team', MatchTeam::A);
                $teamB = $match->matchPlayers->where('team', MatchTeam::B);
                $scoreA = (int) $match->team_a_score;
                $scoreB = (int) $match->team_b_score;

                foreach ($teamA as $assignment) {
                    $this->applyResult($stats[$assignment->player_id], $scoreA, $scoreB, $scoreA <=> $scoreB);
                }

                foreach ($teamB as $assignment) {
                    $this->applyResult($stats[$assignment->player_id], $scoreB, $scoreA, $scoreB <=> $scoreA);
                }
            }
        }

        $rows = collect(array_values($stats));
        $rankingKeys = config('mainpadel.ranking.keys', ['points_for', 'point_difference', 'wins']);

        $rows = $rows->sort(function (array $first, array $second) use ($rankingKeys): int {
            foreach ($rankingKeys as $key) {
                $comparison = ($second[$key] ?? 0) <=> ($first[$key] ?? 0);
                if ($comparison !== 0) {
                    return $comparison;
                }
            }

            return [mb_strtolower($first['name']), $first['player_id']] <=> [mb_strtolower($second['name']), $second['player_id']];
        })->values();

        return $rows->map(function (array $row, int $index): array {
            $row['rank'] = $index + 1;
            $row['points'] = $row['points_for'];

            return $row;
        });
    }

    private function applyResult(array &$stat, int $pointsFor, int $pointsAgainst, int $outcome): void
    {
        $stat['played']++;
        $stat['points_for'] += $pointsFor;
        $stat['points_against'] += $pointsAgainst;
        $stat['point_difference'] = $stat['points_for'] - $stat['points_against'];

        if ($outcome > 0) {
            $stat['wins']++;
        } elseif ($outcome < 0) {
            $stat['losses']++;
        }
    }
}
