<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\RoundStatus;
use App\Enums\TournamentStatus;
use App\Models\MatchModel;
use Illuminate\Support\Facades\DB;

final class ScoreService
{
    public function save(MatchModel $match, int $scoreA, int $scoreB): MatchModel
    {
        return DB::transaction(function () use ($match, $scoreA, $scoreB): MatchModel {
            $lockedMatch = MatchModel::query()->lockForUpdate()->with('round.tournament')->findOrFail($match->id);
            $tournament = $lockedMatch->round->tournament;

            if ($scoreA < 0 || $scoreB < 0) {
                throw new \InvalidArgumentException('Scores cannot be negative.');
            }

            if (($scoreA + $scoreB) !== $tournament->target_points) {
                throw new \InvalidArgumentException("Scores must total {$tournament->target_points} points.");
            }

            $lockedMatch->update([
                'team_a_score' => $scoreA,
                'team_b_score' => $scoreB,
                'status' => MatchStatus::Completed,
            ]);

            $round = $lockedMatch->round;
            $unfinishedMatches = $round->matches()->where('status', '!=', MatchStatus::Completed)->exists();

            if (! $unfinishedMatches) {
                $round->update([
                    'status' => RoundStatus::Completed,
                    'locked_at' => $round->locked_at ?? now(),
                ]);

                if ($round->round_number >= $tournament->number_of_rounds) {
                    $tournament->update(['status' => TournamentStatus::Completed]);
                } else {
                    $tournament->update(['status' => TournamentStatus::Ongoing]);
                }
            }

            return $lockedMatch->fresh(['round.tournament', 'matchPlayers.player']);
        });
    }
}
