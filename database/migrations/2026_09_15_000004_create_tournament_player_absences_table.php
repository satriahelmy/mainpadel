<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_player_absences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_player_id')->constrained('tournament_players')->cascadeOnDelete();
            $table->unsignedSmallInteger('unavailable_from_round');
            $table->unsignedSmallInteger('available_again_round')->nullable();
            $table->timestamps();
            $table->index(['tournament_player_id', 'unavailable_from_round'], 'tpa_player_round_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_player_absences');
    }
};
