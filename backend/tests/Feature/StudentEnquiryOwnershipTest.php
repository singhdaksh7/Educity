<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentEnquiryOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_submit_an_enquiry_and_it_is_owned_by_them(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/v1/student/enquiries', [
            'name' => $student->name,
            'phone' => '555-0100',
            'message' => 'I would like more information.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('enquiries', ['id' => $response->json('data.id'), 'user_id' => $student->id]);
    }

    public function test_a_student_cannot_view_another_students_enquiry(): void
    {
        $owner = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $intruder = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $enquiry = Enquiry::create([
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '555-0100',
            'message' => 'Private enquiry.',
            'status' => 'new',
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/v1/student/enquiries/{$enquiry->id}")->assertNotFound();
    }

    public function test_a_student_can_view_their_own_enquiry_list_and_detail(): void
    {
        $owner = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $enquiry = Enquiry::create([
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'phone' => '555-0100',
            'message' => 'My own enquiry.',
            'status' => 'new',
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/student/enquiries')->assertOk()->assertJsonPath('data.data.0.id', $enquiry->id);
        $this->getJson("/api/v1/student/enquiries/{$enquiry->id}")->assertOk()->assertJsonPath('data.id', $enquiry->id);
    }

    public function test_a_guest_can_still_submit_an_enquiry_without_authentication(): void
    {
        $this->postJson('/api/v1/enquiries', [
            'name' => 'Guest',
            'phone' => '555-0199',
            'message' => 'General question.',
        ])->assertCreated();

        $this->assertDatabaseHas('enquiries', ['name' => 'Guest', 'user_id' => null]);
    }
}
