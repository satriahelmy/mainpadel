<?php

namespace App\Models;

use App\Enums\RoundMode;
use App\Enums\TournamentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'played_at',
        'number_of_courts',
        'target_points',
        'round_mode',
        'number_of_rounds',
        'status',
        'draw_seed',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'date',
            'round_mode' => RoundMode::class,
            'status' => TournamentStatus::class,
            'number_of_courts' => 'integer',
            'target_points' => 'integer',
            'number_of_rounds' => 'integer',
            'draw_seed' => 'integer',
        ];
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_players')
            ->withPivot(['status', 'joined_at_round', 'left_at_round'])
            ->withTimestamps();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tournamentPlayers(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class)->orderBy('round_number');
    }

    public function activePlayers(): BelongsToMany
    {
        return $this->players()->wherePivot('status', 'active');
    }

    public function isOngoing(): bool
    {
        return $this->status === TournamentStatus::Ongoing;
    }
}
