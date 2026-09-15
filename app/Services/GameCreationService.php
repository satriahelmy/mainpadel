<?php

namespace App\Services;

use App\Enums\RoundMode;
use App\Enums\TournamentPlayerStatus;
use App\Enums\TournamentStatus;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

final class GameCreationService
{
    public function __construct(private readonly AutoRoundPlanner $autoRoundPlanner) {}

    public function create(array $data): Tournament
    {
        $names = array_values(array_filter(array_map(static fn ($name): string => trim((string) $name), $data['players'] ?? [])));

        if (count($names) < 4) {
            throw new \InvalidArgumentException('At least four players are required to start a Game.');
        }

        $roundMode = RoundMode::from($data['round_mode'] ?? config('mainpadel.defaults.round_mode', 'auto'));
        $numberOfRounds = $roundMode === RoundMode::Custom
            ? (int) ($data['number_of_rounds'] ?? 0)
            : $this->autoRoundPlanner->forPlayerCount(count($names));

        return DB::transaction(function () use ($data, $names, $roundMode, $numberOfRounds): Tournament {
            $tournament = Tournament::create([
                'name' => trim((string) $data['name']),
                'played_at' => $data['played_at'],
                'number_of_courts' => (int) $data['number_of_courts'],
                'target_points' => (int) $data['target_points'],
                'round_mode' => $roundMode,
                'number_of_rounds' => $numberOfRounds,
                'status' => TournamentStatus::Draft,
                'draw_seed' => random_int(1, 2_000_000_000),
            ]);

            foreach ($names as $name) {
                $player = Player::create(['name' => $name]);

                $tournament->tournamentPlayers()->create([
                    'player_id' => $player->id,
                    'status' => TournamentPlayerStatus::Active,
                    'joined_at_round' => 1,
                ]);
            }

            return $tournament;
        });
    }
}
