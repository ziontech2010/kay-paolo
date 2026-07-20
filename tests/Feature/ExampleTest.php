<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_public_kay_paolo_pages_render(): void
    {
        foreach (['/login', '/quote', '/tracking', '/about', '/services', '/contact'] as $path) {
            $this->get($path)->assertStatus(200);
        }
    }

    public function test_dashboard_redirects_when_zion_session_is_missing(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
