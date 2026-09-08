<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAndHealthTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('correct-password'),
        ], $attributes));
    }

    public function test_health_endpoint_has_a_stable_json_contract(): void
    {
        $this->getJson('/api/health')->assertOk()->assertExactJson(['success' => true, 'data' => ['status' => 'ok']]);
    }

    public function test_active_administrator_can_login_and_load_current_identity(): void
    {
        $user = $this->administrator(['email' => 'admin@example.test']);
        $login = $this->postJson('/api/v1/admin/auth/login', ['email' => $user->email, 'password' => 'correct-password']);
        $login->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->withToken($login->json('data.token'))->getJson('/api/v1/admin/auth/me')->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_invalid_or_inactive_administrator_cannot_login(): void
    {
        $user = $this->administrator(['email' => 'inactive@example.test', 'is_active' => false]);
        $this->postJson('/api/v1/admin/auth/login', ['email' => $user->email, 'password' => 'correct-password'])->assertUnprocessable()->assertJsonPath('success', false);
        $this->postJson('/api/v1/admin/auth/login', ['email' => 'missing@example.test', 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_non_admin_has_no_dashboard_permission(): void
    {
        $user = $this->administrator(['role' => 'enquiry_manager']);
        Sanctum::actingAs($user);
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden()->assertJsonPath('success', false);
        $this->getJson('/api/v1/admin/programs')->assertForbidden()->assertJsonPath('success', false);
    }

    public function test_anonymous_admin_request_is_json_unauthorized(): void
    {
        $this->getJson('/api/v1/admin/auth/me')->assertUnauthorized()->assertJsonPath('success', false);
    }
}
