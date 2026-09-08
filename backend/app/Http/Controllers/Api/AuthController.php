<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $key = 'admin-login:'.$request->ip().'|'.strtolower($request->string('email'));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'errors' => (object) [],
            ], 429);
        }

        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
                'errors' => (object) [],
            ], 422);
        }

        RateLimiter::clear($key);

        $token = $user->createToken('admin-api')->plainTextToken;

        ActivityLogger::log($request, 'login', 'user', $user->id, 'Administrator logged in');

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data' => [
                'token' => $token,
                'user' => $user->only('id', 'name', 'email', 'role', 'is_active'),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Current administrator retrieved.',
            'data' => $request->user()->only('id', 'name', 'email', 'role', 'is_active'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLogger::log($request, 'logout', 'user', $request->user()->id, 'Administrator logged out');

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
            'data' => (object) [],
        ]);
    }

    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $user->update(['password' => $data['password']]);

        // Revoke every other active session/token so a compromised token can't survive a password change.
        $user->tokens()->delete();

        ActivityLogger::log($request, 'password_changed', 'user', $user->id, 'Administrator changed password');

        return response()->json([
            'success' => true,
            'message' => 'Password changed. Please log in again.',
            'data' => (object) [],
        ]);
    }

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $key = 'forgot-password:'.$request->ip().'|'.strtolower($request->string('email'));

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 60);
            Password::sendResetLink($request->only('email'));
        }

        // Always return the same generic response so account existence can't be enumerated.
        return response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
            'data' => (object) [],
        ]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                // Revoke all tokens issued before the reset.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to reset password. The link may be invalid or expired.',
                'errors' => (object) [],
            ], 422);
        }

        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            ActivityLogger::log($request, 'password_reset', 'user', $user->id, 'Administrator reset password via email link');
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please log in with your new password.',
            'data' => (object) [],
        ]);
    }
}
