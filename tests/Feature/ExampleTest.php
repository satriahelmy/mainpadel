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

        $response->assertOk()
            ->assertSee('Fair padel game draws, without the spreadsheet.')
            ->assertSee('Create free account')
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('<link rel="icon"', false)
            ->assertSee('application/ld+json', false);
    }
}
