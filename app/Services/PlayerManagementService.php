<?php

namespace App\Services;

use App\Enums\TournamentPlayerStatus;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentPlayer;
use Illuminate\Support\Facades\DB;

final class PlayerManagementService
{
    public function add(Tournament $tournament, string $name): TournamentPlayer
    {
        return DB::transaction(function () use ($tournament, $name): TournamentPlayer {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            if (in_array($lockedTournament->status, [TournamentStatus::Completed, TournamentStatus::Cancelled], true)) {
                throw new \InvalidArgumentException('Players cannot be changed after a Game is complete.');
            }

            $normalizedName = mb_strtolower(trim($name));
            $alreadyExists = $lockedTournament->tournamentPlayers()
                ->with('player')
                ->get()
                ->contains(fn (TournamentPlayer $membership): bool => mb_strtolower($membership->player->name) === $normalizedName);

            if ($alreadyExists) {
                throw new \InvalidArgumentException('Player names must be unique within this Game.');
            }

            $player = Player::create(['name' => trim($name)]);
            $joinedAtRound = $this->effectiveNextRound($lockedTournament);

            return $lockedTournament->tournamentPlayers()->create([
                'player_id' => $player->id,
                'status' => TournamentPlayerStatus::Active,
                'joined_at_round' => $joinedAtRound,
            ]);
        });
    }

    public function withdraw(Tournament $tournament, TournamentPlayer $membership): TournamentPlayer
    {
        return DB::transaction(function () use ($tournament, $membership): TournamentPlayer {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            if (in_array($lockedTournament->status, [TournamentStatus::Completed, TournamentStatus::Cancelled], true)) {
                throw new \InvalidArgumentException('Players cannot be changed after a Game is complete.');
            }

            $lockedMembership = $lockedTournament->tournamentPlayers()->with('player')->findOrFail($membership->id);

            if ($lockedMembership->status !== TournamentPlayerStatus::Active) {
                throw new \InvalidArgumentException('This player is already not playing.');
            }

            $leftAtRound = $this->effectiveNextRound($lockedTournament);
            $lockedMembership->update([
                'status' => TournamentPlayerStatus::Withdrawn,
                'left_at_round' => $leftAtRound,
            ]);

            return $lockedMembership->fresh(['player']);
        });
    }

    public function effectiveNextRound(Tournament $tournament): int
    {
        $ongoingRound = $tournament->rounds()->where('status', 'ongoing')->orderByDesc('round_number')->first();

        if ($ongoingRound !== null) {
            return $ongoingRound->round_number + 1;
        }

        $scheduledRound = $tournament->rounds()->where('status', 'scheduled')->orderBy('round_number')->first();

        return $scheduledRound?->round_number ?? (($tournament->rounds()->max('round_number') ?? 0) + 1);
    }
}
