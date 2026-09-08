<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAdminSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_token_cannot_access_admin_routes(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/admin/auth/me')->assertForbidden()->assertJsonPath('success', false);
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_an_admin_token_cannot_access_student_routes(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/student/auth/me')->assertForbidden()->assertJsonPath('success', false);
        $this->getJson('/api/v1/student/dashboard')->assertForbidden();
    }

    public function test_unauthenticated_requests_are_rejected_on_both_areas(): void
    {
        $this->getJson('/api/v1/student/auth/me')->assertUnauthorized()->assertJsonPath('success', false);
        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_an_inactive_student_is_rejected_by_student_active_middleware(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => false]);
        Sanctum::actingAs($student);

        $this->getJson('/api/v1/student/dashboard')->assertForbidden()->assertJsonPath('success', false);
    }
}
