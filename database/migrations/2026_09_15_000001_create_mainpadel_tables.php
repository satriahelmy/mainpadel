<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->date('played_at');
            $table->unsignedTinyInteger('number_of_courts');
            $table->unsignedSmallInteger('target_points')->default(21);
            $table->string('round_mode', 16)->default('auto');
            $table->unsignedSmallInteger('number_of_rounds')->default(3);
            $table->string('status', 16)->default('draft')->index();
            $table->unsignedBigInteger('draw_seed')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('tournament_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('active')->index();
            $table->unsignedSmallInteger('joined_at_round')->nullable();
            $table->unsignedSmallInteger('left_at_round')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'player_id']);
        });

        Schema::create('rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round_number');
            $table->string('status', 16)->default('scheduled')->index();
            $table->unsignedBigInteger('draw_seed')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['tournament_id', 'round_number']);
        });

        Schema::create('matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('court_number');
            $table->unsignedSmallInteger('team_a_score')->nullable();
            $table->unsignedSmallInteger('team_b_score')->nullable();
            $table->string('status', 16)->default('scheduled')->index();
            $table->timestamps();
            $table->unique(['round_id', 'court_number']);
        });

        Schema::create('match_players', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('team', 1);
            $table->timestamps();
            $table->unique(['match_id', 'player_id']);
            $table->index(['player_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_players');
        Schema::dropIfExists('matches');
        Schema::dropIfExists('rounds');
        Schema::dropIfExists('tournament_players');
        Schema::dropIfExists('players');
        Schema::dropIfExists('tournaments');
    }
};
