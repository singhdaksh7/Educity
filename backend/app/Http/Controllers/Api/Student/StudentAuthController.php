<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentForgotPasswordRequest;
use App\Http\Requests\StudentLoginRequest;
use App\Http\Requests\StudentRegisterRequest;
use App\Http\Requests\StudentResetPasswordRequest;
use App\Http\Requests\UpdateStudentPasswordRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\Student;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class StudentAuthController extends Controller
{
    public function register(StudentRegisterRequest $request): JsonResponse
    {
        // Honeypot field: real visitors never fill this hidden input.
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'message' => 'Invalid submission.', 'errors' => (object) []], 422);
        }

        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            // Role is never accepted from the request — every public registration is a student.
            'role' => 'student',
            'is_active' => true,
        ]);

        $token = $user->createToken('student-api')->plainTextToken;

        ActivityLogger::log($request, 'register', 'user', $user->id, 'Student registered');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'token' => $token,
                'user' => $user->only('id', 'name', 'email', 'phone', 'role', 'is_active'),
            ],
        ], 201);
    }

    public function login(StudentLoginRequest $request): JsonResponse
    {
        $key = 'student-login:'.$request->ip().'|'.strtolower($request->string('email'));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'errors' => (object) [],
            ], 429);
        }

        $data = $request->validated();
        $user = User::where('email', $data['email'])->where('role', 'student')->first();

        if (! $user || ! $user->is_active || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
                'errors' => (object) [],
            ], 422);
        }

        RateLimiter::clear($key);

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('student-api')->plainTextToken;

        ActivityLogger::log($request, 'login', 'user', $user->id, 'Student logged in');

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data' => [
                'token' => $token,
                'user' => $user->only('id', 'name', 'email', 'phone', 'role', 'is_active'),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Current student retrieved.',
            'data' => $request->user()->only('id', 'name', 'email', 'phone', 'role', 'is_active'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLogger::log($request, 'logout', 'user', $request->user()->id, 'Student logged out');

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
            'data' => (object) [],
        ]);
    }

    public function updateProfile(UpdateStudentProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        ActivityLogger::log($request, 'profile_updated', 'user', $user->id, 'Student updated profile');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated.',
            'data' => $user->fresh()->only('id', 'name', 'email', 'phone', 'role', 'is_active'),
        ]);
    }

    public function updatePassword(UpdateStudentPasswordRequest $request): JsonResponse
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

        ActivityLogger::log($request, 'password_changed', 'user', $user->id, 'Student changed password');

        return response()->json([
            'success' => true,
            'message' => 'Password changed. Please log in again.',
            'data' => (object) [],
        ]);
    }

    public function forgotPassword(StudentForgotPasswordRequest $request): JsonResponse
    {
        $key = 'forgot-password:'.$request->ip().'|'.strtolower($request->string('email'));

        if (! RateLimiter::tooManyAttempts($key, 3)) {
            RateLimiter::hit($key, 60);

            try {
                Password::broker('students')->sendResetLink($request->only('email'));
            } catch (\Throwable $e) {
                // Never let an unavailable mail transport turn into a 500 or leak account existence.
                Log::warning('Student password reset email failed', ['error' => $e->getMessage()]);
            }
        }

        // Always return the same generic response so account existence can't be enumerated.
        return response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
            'data' => (object) [],
        ]);
    }

    public function resetPassword(StudentResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('students')->reset(
            $request->validated(),
            function (Student $user, string $password) {
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

        $user = User::where('email', $request->input('email'))->where('role', 'student')->first();
        if ($user) {
            ActivityLogger::log($request, 'password_reset', 'user', $user->id, 'Student reset password via email link');
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please log in with your new password.',
            'data' => (object) [],
        ]);
    }
}
