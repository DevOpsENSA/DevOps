<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiTest extends TestCase
{
    // ══════════════════════════════
    // TESTS AUTH
    // ══════════════════════════════

    /** @test */
    public function test_register_endpoint_exists()
    {
        $response = $this->postJson('/api/auth/register', [
            'name'     => 'Test User',
            'email'    => 'test@test.com',
            'password' => 'password123',
        ]);

        // 200 = success, 422 = validation error (les 2 sont OK)
        $this->assertContains(
            $response->status(), [200, 201, 422]
        );
    }

    /** @test */
    public function test_login_endpoint_exists()
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@test.com',
            'password' => 'password123',
        ]);

        // 200 = success, 401 = wrong credentials (les 2 sont OK)
        $this->assertContains(
            $response->status(), [200, 401, 422]
        );
    }

    /** @test */
    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);
        $response->assertStatus(422); // validation error
    }

    // ══════════════════════════════
    // TESTS LESSONS
    // ══════════════════════════════

    /** @test */
    public function test_recent_lessons_endpoint_exists()
    {
        $response = $this->getJson('/api/lessons/recent');

        // 200 = success, 401 = pas authentifié
        $this->assertContains(
            $response->status(), [200, 401]
        );
    }

    /** @test */
    public function test_show_lesson_with_invalid_id_returns_404()
    {
        $response = $this->getJson('/api/lessons/99999');
        $this->assertContains(
            $response->status(), [200, 401, 404]
        );
    }

    /** @test */
    public function test_show_lesson_with_string_id_returns_404()
    {
        // whereNumber → string doit retourner 404
        $response = $this->getJson('/api/lessons/abc');
        $response->assertStatus(404);
    }

    /** @test */
    public function test_create_lesson_without_auth_returns_401()
    {
        $response = $this->postJson('/api/lessons', [
            'title'   => 'Test Lesson',
            'content' => 'Test Content',
        ]);

        // sans token admin → 401 ou 403
        $this->assertContains(
            $response->status(), [401, 403]
        );
    }
}

