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

    public function test_dashboard_renders_without_a_server_side_zion_session(): void
    {
        $this->get('/dashboard')->assertStatus(200);
    }

    public function test_login_form_posts_to_kay_paolo_api_route(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('action="http://localhost/api/kay-paolo/login"', false)
            ->assertSee('data-api-login', false);

        $this->post('/login')->assertStatus(405);
    }
}
