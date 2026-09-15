<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_register_and_is_signed_in(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Helmy',
            'email' => 'helmy@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Helmy',
            'email' => 'helmy@example.com',
        ]);
    }

    public function test_a_registered_user_can_sign_in_and_sign_out(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_games_are_visible_only_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)->post(route('games.store'), [
            'name' => 'Private Game',
            'played_at' => '2026-09-15',
            'players' => ['A', 'B', 'C', 'D'],
            'number_of_courts' => 1,
            'target_points' => 21,
            'round_mode' => 'custom',
            'number_of_rounds' => 1,
        ]);

        $tournament = Tournament::query()->firstOrFail();

        $this->actingAs($otherUser)
            ->get(route('games.show', $tournament))
            ->assertNotFound();

        $this->actingAs($owner)
            ->get(route('games.show', $tournament))
            ->assertOk();
    }
}
