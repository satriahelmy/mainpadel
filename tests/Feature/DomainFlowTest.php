<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use App\Services\AutoRoundPlanner;
use App\Services\GameCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class DomainFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_multi_record_game_creation_rolls_back_the_transaction(): void
    {
        $user = User::factory()->create();

        Event::listen('eloquent.creating: '.Player::class, static function (): void {
            throw new RuntimeException('Simulated player write failure.');
        });

        $this->expectException(RuntimeException::class);

        try {
            (new GameCreationService(new AutoRoundPlanner))->create($user, [
                'name' => 'Rollback Game',
                'played_at' => '2026-09-15',
                'players' => ['A', 'B', 'C', 'D'],
                'number_of_courts' => 1,
                'target_points' => 21,
                'round_mode' => 'custom',
                'number_of_rounds' => 1,
            ]);
        } finally {
            self::assertSame(0, Tournament::query()->count());
            self::assertSame(0, Player::query()->count());
        }
    }
}
