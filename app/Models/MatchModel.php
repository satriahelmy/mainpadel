<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Match is named MatchModel to avoid colliding with PHP's built-in match keyword.
 */
class MatchModel extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'round_id',
        'court_number',
        'team_a_score',
        'team_b_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => MatchStatus::class,
            'court_number' => 'integer',
            'team_a_score' => 'integer',
            'team_b_score' => 'integer',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function matchPlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function players()
    {
        return $this->hasManyThrough(Player::class, MatchPlayer::class, 'match_id', 'id', 'id', 'player_id');
    }
}
