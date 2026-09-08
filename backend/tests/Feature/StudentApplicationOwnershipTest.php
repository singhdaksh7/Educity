<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentApplicationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function program(): Program
    {
        return Program::create([
            'title' => 'Graduation Degree',
            'slug' => 'graduation-degree',
            'short_description' => 'Foundation for future leaders.',
            'degree_type' => 'Undergraduate',
            'is_active' => true,
        ]);
    }

    public function test_a_student_can_submit_an_application_and_it_is_owned_by_them(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $program = $this->program();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/applications', [
            'full_name' => $student->name,
            'email' => 'someone-else@example.test',
            'phone' => '555-0100',
            'program_id' => $program->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('admission_applications', [
            'id' => $response->json('data.id'),
            'user_id' => $student->id,
            // The authenticated user's own email is used, ignoring any spoofed value.
            'email' => $student->email,
        ]);
    }

    public function test_a_student_cannot_spoof_another_users_id_via_the_payload(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $otherStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $program = $this->program();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/applications', [
            'full_name' => $student->name,
            'email' => $student->email,
            'phone' => '555-0100',
            'program_id' => $program->id,
            'user_id' => $otherStudent->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('admission_applications', ['id' => $response->json('data.id'), 'user_id' => $student->id]);
        $this->assertDatabaseMissing('admission_applications', ['id' => $response->json('data.id'), 'user_id' => $otherStudent->id]);
    }

    public function test_a_student_cannot_view_another_students_application(): void
    {
        $owner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $intruder = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $program = $this->program();

        $application = AdmissionApplication::create([
            'user_id' => $owner->id,
            'application_number' => 'APP-TEST-000001',
            'full_name' => $owner->name,
            'email' => $owner->email,
            'phone' => '555-0100',
            'program_id' => $program->id,
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/v1/student/applications/{$application->id}")->assertNotFound();
    }

    public function test_a_student_can_withdraw_their_own_eligible_application(): void
    {
        $owner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $program = $this->program();

        $application = AdmissionApplication::create([
            'user_id' => $owner->id,
            'application_number' => 'APP-TEST-000002',
            'full_name' => $owner->name,
            'email' => $owner->email,
            'phone' => '555-0100',
            'program_id' => $program->id,
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/student/applications/{$application->id}/withdraw")
            ->assertOk()
            ->assertJsonPath('data.status', 'withdrawn');
    }

    public function test_a_student_cannot_withdraw_another_students_application(): void
    {
        $owner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $intruder = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $program = $this->program();

        $application = AdmissionApplication::create([
            'user_id' => $owner->id,
            'application_number' => 'APP-TEST-000003',
            'full_name' => $owner->name,
            'email' => $owner->email,
            'phone' => '555-0100',
            'program_id' => $program->id,
            'status' => 'submitted',
        ]);

        Sanctum::actingAs($intruder);

        $this->patchJson("/api/v1/student/applications/{$application->id}/withdraw")->assertNotFound();
        $this->assertSame('submitted', $application->fresh()->status);
    }

    public function test_a_student_cannot_withdraw_an_application_that_is_no_longer_eligible(): void
    {
        $owner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $program = $this->program();

        $application = AdmissionApplication::create([
            'user_id' => $owner->id,
            'application_number' => 'APP-TEST-000004',
            'full_name' => $owner->name,
            'email' => $owner->email,
            'phone' => '555-0100',
            'program_id' => $program->id,
            'status' => 'accepted',
        ]);

        Sanctum::actingAs($owner);

        $this->patchJson("/api/v1/student/applications/{$application->id}/withdraw")
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->assertSame('accepted', $application->fresh()->status);
    }

    public function test_a_guest_can_still_submit_an_application_without_authentication(): void
    {
        $program = $this->program();

        $this->postJson('/api/v1/admission-applications', [
            'full_name' => 'Guest Applicant',
            'email' => 'guest@example.test',
            'phone' => '555-0199',
            'program_id' => $program->id,
        ])->assertCreated();

        $this->assertDatabaseHas('admission_applications', ['email' => 'guest@example.test', 'user_id' => null]);
    }
}
