<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_the_landing_page_is_available_to_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Every donor is a', false);
        $response->assertSee(route('track.index', absolute: false));
    }

    public function test_public_track_page_is_available(): void
    {
        $response = $this->get('/track');

        $response->assertStatus(200);
    }
}
