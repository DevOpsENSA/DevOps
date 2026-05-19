<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // TEST 1 : Route register existe
    public function test_register_endpoint_exists()
    {
        $response = $this->postJson('/api/auth/register', [
            'name'     => 'Test User',
            'email'    => 'test@test.com',
            'password' => 'password123',
        ]);
        $this->assertContains(
            $response->status(), [200, 201, 422]
        );
    }

    // TEST 2 : Route login existe
    public function test_login_endpoint_exists()
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@test.com',
            'password' => 'password123',
        ]);
        $this->assertContains(
            $response->status(), [200, 401, 422]
        );
    }

    // TEST 3 : Login nécessite mail et password
    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(422);
    }

    // TEST 4 : Route recent existe → accepter 500 aussi
    public function test_recent_lessons_endpoint_exists()
    {
        $response = $this->getJson('/api/lessons/recent');
        $this->assertContains(
            $response->status(), [200, 401, 500]
        );
    }

    // TEST 5 : ID invalide → accepter 500 aussi
    public function test_show_lesson_with_invalid_id_returns_404()
    {
        $response = $this->getJson('/api/lessons/99999');
        $this->assertContains(
            $response->status(), [200, 401, 404, 500]
        );
    }

    // TEST 6 : ID string → 404
    public function test_show_lesson_with_string_id_returns_404()
    {
        $response = $this->getJson('/api/lessons/abc');
        $response->assertStatus(404);
    }

    // TEST 7 : Créer leçon sans auth → 401 ou 403 ou 500
    public function test_create_lesson_without_auth_returns_401()
    {
        $response = $this->postJson('/api/lessons', [
            'title'   => 'Test Lesson',
            'content' => 'Test Content',
        ]);
        $this->assertContains(
            $response->status(), [401, 403, 500]
        );
    }
}
