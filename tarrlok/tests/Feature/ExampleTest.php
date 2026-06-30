<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_login_page_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_guests_are_redirected_from_home_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_public_track_page_is_available(): void
    {
        $response = $this->get('/track');

        $response->assertStatus(200);
    }
}
