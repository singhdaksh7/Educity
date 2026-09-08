<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAuthTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'student',
            'is_active' => true,
            'password' => Hash::make('Correct-Pass1'),
        ], $attributes));
    }

    private function validRegistrationPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Jane Student',
            'email' => 'jane@example.test',
            'phone' => '555-0100',
            'password' => 'Correct-Pass1',
            'password_confirmation' => 'Correct-Pass1',
            'website' => '',
        ], $overrides);
    }

    public function test_a_visitor_can_register_and_is_always_given_the_student_role(): void
    {
        $response = $this->postJson('/api/v1/student/auth/register', $this->validRegistrationPayload(['role' => 'super_admin']));

        $response->assertCreated()->assertJsonPath('data.user.role', 'student');
        $this->assertDatabaseHas('users', ['email' => 'jane@example.test', 'role' => 'student', 'is_active' => true]);
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        $this->student(['email' => 'dup@example.test']);

        $this->postJson('/api/v1/student/auth/register', $this->validRegistrationPayload(['email' => 'dup@example.test']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_honeypot_silently_rejects_bots(): void
    {
        $this->postJson('/api/v1/student/auth/register', $this->validRegistrationPayload(['website' => 'http://spam.example']))
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.test']);
    }

    public function test_active_student_can_login_and_load_current_identity(): void
    {
        $user = $this->student(['email' => 'jane@example.test']);

        $login = $this->postJson('/api/v1/student/auth/login', ['email' => $user->email, 'password' => 'Correct-Pass1']);
        $login->assertOk()->assertJsonPath('data.user.id', $user->id)->assertJsonPath('data.user.role', 'student');

        $this->withToken($login->json('data.token'))
            ->getJson('/api/v1/student/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = $this->student(['email' => 'jane@example.test']);

        $this->postJson('/api/v1/student/auth/login', ['email' => $user->email, 'password' => 'Wrong-Pass1'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_inactive_student_cannot_login(): void
    {
        $user = $this->student(['email' => 'jane@example.test', 'is_active' => false]);

        $this->postJson('/api/v1/student/auth/login', ['email' => $user->email, 'password' => 'Correct-Pass1'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = $this->student(['email' => 'jane@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/student/auth/login', ['email' => $user->email, 'password' => 'Wrong-Pass1']);
        }

        $this->postJson('/api/v1/student/auth/login', ['email' => $user->email, 'password' => 'Wrong-Pass1'])
            ->assertStatus(429);
    }

    public function test_student_can_logout_and_the_token_is_revoked(): void
    {
        $user = $this->student();
        $newToken = $user->createToken('student-api');

        $this->withToken($newToken->plainTextToken)->postJson('/api/v1/student/auth/logout')->assertOk();

        // Assert directly against the database rather than a second live
        // request: Laravel's RequestGuard caches the resolved user for the
        // lifetime of the guard instance, and that instance is reused across
        // calls within a single test, so a second request here would read
        // the cached pre-logout user instead of re-authenticating the token.
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $newToken->accessToken->id]);
    }

    public function test_student_can_update_profile(): void
    {
        $user = $this->student();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/student/auth/profile', ['name' => 'New Name', 'phone' => '999-0000'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name', 'phone' => '999-0000']);
    }

    public function test_student_can_update_password_with_correct_current_password(): void
    {
        $user = $this->student();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/student/auth/password', [
            'current_password' => 'Correct-Pass1',
            'password' => 'Another-Pass2',
            'password_confirmation' => 'Another-Pass2',
        ])->assertOk();

        $this->assertTrue(Hash::check('Another-Pass2', $user->fresh()->password));
    }

    public function test_student_password_update_rejects_wrong_current_password(): void
    {
        $user = $this->student();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/student/auth/password', [
            'current_password' => 'Wrong-Pass1',
            'password' => 'Another-Pass2',
            'password_confirmation' => 'Another-Pass2',
        ])->assertUnprocessable()->assertJsonPath('success', false);
    }

    public function test_forgot_password_returns_a_generic_response_whether_or_not_the_account_exists(): void
    {
        Notification::fake();
        $user = $this->student(['email' => 'jane@example.test']);

        $known = $this->postJson('/api/v1/student/auth/forgot-password', ['email' => 'jane@example.test']);
        $unknown = $this->postJson('/api/v1/student/auth/forgot-password', ['email' => 'nobody@example.test']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
        // The broker notifies via App\Models\Student (the scoped provider), so
        // assert against that class rather than the App\Models\User the test created it as.
        Notification::assertSentTo(Student::find($user->id), ResetPassword::class);
    }

    public function test_forgot_password_never_crashes_even_if_notification_dispatch_fails(): void
    {
        $this->student(['email' => 'jane@example.test']);

        // No Notification::fake(): the log mailer is configured for tests, so this
        // exercises the real send path; the endpoint must still respond 200 either way.
        $this->postJson('/api/v1/student/auth/forgot-password', ['email' => 'jane@example.test'])->assertOk();
    }

    public function test_a_student_forgot_password_request_cannot_target_an_administrator_account(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true, 'email' => 'admin@example.test']);

        $this->postJson('/api/v1/student/auth/forgot-password', ['email' => 'admin@example.test'])->assertOk();

        // The students provider's global scope means no matching Student is ever found for
        // an admin's email, so nothing should be dispatched at all — not even under Student::class.
        Notification::assertNothingSent();
        $this->assertNotNull($admin);
    }
}
