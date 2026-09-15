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

    public function test_authentication_screens_render_for_guests(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create account');
        $this->get(route('login'))->assertOk()->assertSee('Sign in');
    }

    public function test_guests_are_redirected_from_game_creation(): void
    {
        $this->get(route('games.create'))
            ->assertRedirect(route('login'));
    }

    public function test_mutating_forms_include_csrf_tokens(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('name="_token"', false);
        $this->get(route('register'))->assertOk()->assertSee('name="_token"', false);

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('games.create'))->assertOk()->assertSee('name="_token"', false);
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

    public function test_recent_games_are_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($owner)->post(route('games.store'), [
            'name' => 'Owner Recent Game',
            'played_at' => '2026-09-15',
            'players' => ['A', 'B', 'C', 'D'],
            'number_of_courts' => 1,
            'target_points' => 21,
            'round_mode' => 'custom',
            'number_of_rounds' => 1,
        ]);

        $this->actingAs($owner)->get(route('home'))->assertOk()->assertSee('Owner Recent Game');
        $this->actingAs($otherUser)->get(route('home'))->assertOk()->assertDontSee('Owner Recent Game');
    }
}
