<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentPlayerAbsence extends Model
{
    protected $fillable = [
        'tournament_player_id',
        'unavailable_from_round',
        'available_again_round',
    ];

    protected function casts(): array
    {
        return [
            'unavailable_from_round' => 'integer',
            'available_again_round' => 'integer',
        ];
    }

    public function tournamentPlayer(): BelongsTo
    {
        return $this->belongsTo(TournamentPlayer::class);
    }

    public function coversRound(int $roundNumber): bool
    {
        return $this->unavailable_from_round <= $roundNumber
            && ($this->available_again_round === null || $this->available_again_round > $roundNumber);
    }
}
