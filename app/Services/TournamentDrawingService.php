<?php

namespace App\Services;

use App\Enums\MatchStatus;
use App\Enums\MatchTeam;
use App\Enums\RoundStatus;
use App\Enums\TournamentPlayerStatus;
use App\Enums\TournamentStatus;
use App\Models\Round;
use App\Models\Tournament;
use App\Services\Drawing\DrawingCandidate;
use App\Services\Drawing\DrawingException;
use App\Services\Drawing\DrawingMatch;
use App\Services\Drawing\DrawingRequest;
use App\Services\Drawing\DrawingService;
use App\Services\Drawing\FairnessHistory;
use Illuminate\Support\Facades\DB;

final class TournamentDrawingService
{
    public function __construct(private readonly DrawingService $drawingService) {}

    public function generateInitial(Tournament $tournament): Round
    {
        return DB::transaction(function () use ($tournament): Round {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);

            if ($lockedTournament->rounds()->exists()) {
                return $lockedTournament->rounds()->orderBy('round_number')->firstOrFail();
            }

            $history = FairnessHistory::empty($this->activePlayerIds($lockedTournament, 1));
            $firstRound = null;

            for ($roundNumber = 1; $roundNumber <= $lockedTournament->number_of_rounds; $roundNumber++) {
                [$round, $candidate] = $this->generateRound($lockedTournament, $roundNumber, $history);
                $history = $history->after($candidate);
                $firstRound ??= $round;
            }

            return $firstRound;
        });
    }

    public function generateNext(Tournament $tournament): ?Round
    {
        return DB::transaction(function () use ($tournament): ?Round {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);
            $scheduledRound = $lockedTournament->rounds()
                ->where('status', RoundStatus::Scheduled)
                ->orderBy('round_number')
                ->first();

            if ($scheduledRound !== null) {
                return $scheduledRound;
            }

            $lastRound = $lockedTournament->rounds()->orderByDesc('round_number')->first();

            if ($lastRound === null) {
                [$round] = $this->generateRound($lockedTournament, 1, FairnessHistory::empty($this->activePlayerIds($lockedTournament, 1)));

                return $round;
            }

            if ($lastRound->status !== RoundStatus::Completed) {
                throw new DrawingException('Complete the current round before generating the next round.');
            }

            $nextRoundNumber = $lastRound->round_number + 1;
            if ($nextRoundNumber > $lockedTournament->number_of_rounds) {
                $lockedTournament->update(['status' => TournamentStatus::Completed]);

                return null;
            }

            $history = $this->historyBefore($lockedTournament, $nextRoundNumber, $this->activePlayerIds($lockedTournament, $nextRoundNumber));
            [$round] = $this->generateRound($lockedTournament, $nextRoundNumber, $history);

            return $round;
        });
    }

    public function advance(Tournament $tournament): ?Round
    {
        return DB::transaction(function () use ($tournament): ?Round {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);
            $nextRound = $lockedTournament->rounds()
                ->where('status', RoundStatus::Scheduled)
                ->orderBy('round_number')
                ->first();

            if ($nextRound === null) {
                $lockedTournament->update(['status' => TournamentStatus::Completed]);

                return null;
            }

            $nextRound->update([
                'status' => RoundStatus::Ongoing,
                'locked_at' => $nextRound->locked_at ?? now(),
            ]);
            $lockedTournament->update(['status' => TournamentStatus::Ongoing]);

            return $nextRound->fresh(['matches.matchPlayers.player']);
        });
    }

    public function redrawFuture(Tournament $tournament): ?Round
    {
        return DB::transaction(function () use ($tournament): ?Round {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);
            $rounds = $lockedTournament->rounds()->orderBy('round_number')->get();
            $firstScheduled = $rounds->firstWhere('status', RoundStatus::Scheduled);
            $startRound = $firstScheduled?->round_number ?? (($rounds->max('round_number') ?? 0) + 1);

            if ($startRound > $lockedTournament->number_of_rounds) {
                return null;
            }

            $rounds->where('status', RoundStatus::Scheduled)
                ->filter(fn (Round $round): bool => $round->round_number >= $startRound)
                ->each(fn (Round $round): bool => (bool) $round->delete());

            $history = $this->historyBefore($lockedTournament, $startRound, $this->activePlayerIds($lockedTournament, $startRound));
            $firstRound = null;

            for ($roundNumber = $startRound; $roundNumber <= $lockedTournament->number_of_rounds; $roundNumber++) {
                [$round, $candidate] = $this->generateRound($lockedTournament, $roundNumber, $history);
                $history = $history->after($candidate);
                $firstRound ??= $round;
            }

            return $firstRound?->load(['matches.matchPlayers.player']);
        });
    }

    public function redrawSummary(Tournament $tournament): array
    {
        $rounds = $tournament->rounds()->orderBy('round_number')->get();
        $firstScheduled = $rounds->firstWhere('status', RoundStatus::Scheduled);
        $from = $firstScheduled?->round_number ?? (($rounds->max('round_number') ?? 0) + 1);
        $last = $rounds->max('round_number') ?? $tournament->number_of_rounds;

        return [
            'from' => $from,
            'to' => min($tournament->number_of_rounds, max($from, $last)),
            'has_future' => $from <= $tournament->number_of_rounds,
        ];
    }

    public function start(Tournament $tournament): Tournament
    {
        return DB::transaction(function () use ($tournament): Tournament {
            $lockedTournament = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);
            $round = $lockedTournament->rounds()
                ->where('status', RoundStatus::Scheduled)
                ->orderBy('round_number')
                ->first();

            if ($round === null) {
                throw new DrawingException('Generate a draw before starting the Game.');
            }

            if ($round->status === RoundStatus::Completed) {
                throw new DrawingException('This round is already completed.');
            }

            $round->update([
                'status' => RoundStatus::Ongoing,
                'locked_at' => $round->locked_at ?? now(),
            ]);
            $lockedTournament->update(['status' => TournamentStatus::Ongoing]);

            return $lockedTournament->fresh(['rounds']);
        });
    }

    public function activePlayerIds(Tournament $tournament, int $roundNumber): array
    {
        return $tournament->tournamentPlayers()
            ->with('player')
            ->get()
            ->filter(function ($membership) use ($roundNumber): bool {
                if ($membership->status === TournamentPlayerStatus::Withdrawn && $membership->left_at_round === null) {
                    return false;
                }

                if ($membership->joined_at_round !== null && $membership->joined_at_round > $roundNumber) {
                    return false;
                }

                return $membership->left_at_round === null || $membership->left_at_round > $roundNumber;
            })
            ->pluck('player_id')
            ->map(static fn ($playerId): int => (int) $playerId)
            ->values()
            ->all();
    }

    private function generateRound(Tournament $tournament, int $roundNumber, FairnessHistory $history): array
    {
        $activePlayerIds = $this->activePlayerIds($tournament, $roundNumber);

        if (count($activePlayerIds) < 4) {
            throw new DrawingException('At least four active players are required to generate the next round.');
        }

        $seed = (int) ($tournament->draw_seed ?? 1) + $roundNumber;
        $result = $this->drawingService->generate(new DrawingRequest(
            activePlayerIds: $activePlayerIds,
            numberOfCourts: $tournament->number_of_courts,
            history: $history,
            seed: $seed,
        ));

        $round = $tournament->rounds()->create([
            'round_number' => $roundNumber,
            'status' => RoundStatus::Scheduled,
            'draw_seed' => $result->seed,
            'generated_at' => now(),
        ]);

        foreach ($result->matches as $drawingMatch) {
            $match = $round->matches()->create([
                'court_number' => $drawingMatch->courtNumber,
                'status' => MatchStatus::Scheduled,
            ]);

            foreach ($drawingMatch->teamA as $playerId) {
                $match->matchPlayers()->create([
                    'player_id' => $playerId,
                    'team' => MatchTeam::A,
                ]);
            }

            foreach ($drawingMatch->teamB as $playerId) {
                $match->matchPlayers()->create([
                    'player_id' => $playerId,
                    'team' => MatchTeam::B,
                ]);
            }
        }

        return [
            $round->load(['matches.matchPlayers.player']),
            new DrawingCandidate($result->matches, $result->restingPlayerIds),
        ];
    }

    private function historyBefore(Tournament $tournament, int $roundNumber, array $activePlayerIds): FairnessHistory
    {
        $history = FairnessHistory::empty($activePlayerIds);
        $rounds = $tournament->rounds()
            ->with(['matches.matchPlayers'])
            ->where('round_number', '<', $roundNumber)
            ->where('status', '!=', RoundStatus::Scheduled)
            ->orderBy('round_number')
            ->get();

        foreach ($rounds as $round) {
            $matches = [];

            foreach ($round->matches as $match) {
                if ($match->status !== MatchStatus::Completed && $round->status === RoundStatus::Completed) {
                    continue;
                }

                $teamA = $match->matchPlayers->where('team', MatchTeam::A)->pluck('player_id')->map(fn ($id): int => (int) $id)->values()->all();
                $teamB = $match->matchPlayers->where('team', MatchTeam::B)->pluck('player_id')->map(fn ($id): int => (int) $id)->values()->all();
                $matches[] = new DrawingMatch($match->court_number, $teamA, $teamB);
            }

            if ($matches === []) {
                continue;
            }

            $playing = collect($matches)->flatMap(fn (DrawingMatch $match): array => $match->playerIds())->all();
            $roundActivePlayerIds = $this->activePlayerIds($tournament, $round->round_number);
            $resting = array_values(array_diff($roundActivePlayerIds, $playing));
            $history = $history->after(new DrawingCandidate($matches, $resting));
        }

        return $history;
    }
}
