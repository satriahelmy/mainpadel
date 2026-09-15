<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guests_can_see_the_public_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('A fair draw for every game.')->assertSee('Create free account');
    }
}
