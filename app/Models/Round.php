<?php

namespace App\Models;

use App\Enums\RoundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'round_number',
        'status',
        'draw_seed',
        'generated_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RoundStatus::class,
            'round_number' => 'integer',
            'draw_seed' => 'integer',
            'generated_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(MatchModel::class)->orderBy('court_number');
    }

    public function hasCompletedMatch(): bool
    {
        return $this->matches()->where('status', 'completed')->exists();
    }
}
