<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_an_admin_can_list_and_filter_students(): void
    {
        User::factory()->create(['role' => 'student', 'is_active' => true, 'name' => 'Active Student']);
        User::factory()->create(['role' => 'student', 'is_active' => false, 'name' => 'Inactive Student']);
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/v1/admin/students')->assertOk()->assertJsonCount(2, 'data.data');
        $this->getJson('/api/v1/admin/students?status=active')->assertOk()->assertJsonCount(1, 'data.data');
    }

    public function test_an_admin_can_activate_and_deactivate_a_student(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/v1/admin/students/{$student->id}/deactivate")->assertOk()->assertJsonPath('data.is_active', false);
        $this->assertFalse($student->fresh()->is_active);

        $this->patchJson("/api/v1/admin/students/{$student->id}/activate")->assertOk()->assertJsonPath('data.is_active', true);
        $this->assertTrue($student->fresh()->is_active);
    }

    public function test_the_student_management_endpoints_cannot_be_used_against_an_administrator_account(): void
    {
        $otherAdmin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        Sanctum::actingAs($this->admin());

        $this->getJson("/api/v1/admin/students/{$otherAdmin->id}")->assertNotFound();
        $this->patchJson("/api/v1/admin/students/{$otherAdmin->id}/deactivate")->assertNotFound();
        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_a_non_admin_role_without_the_students_permission_is_forbidden(): void
    {
        $manager = User::factory()->create(['role' => 'enquiry_manager', 'is_active' => true]);
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/admin/students')->assertForbidden();
    }
}
