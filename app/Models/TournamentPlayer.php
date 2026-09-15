<?php

namespace App\Models;

use App\Enums\TournamentPlayerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPlayer extends Model
{
    protected $fillable = [
        'tournament_id',
        'player_id',
        'status',
        'joined_at_round',
        'left_at_round',
    ];

    protected function casts(): array
    {
        return [
            'status' => TournamentPlayerStatus::class,
            'joined_at_round' => 'integer',
            'left_at_round' => 'integer',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
