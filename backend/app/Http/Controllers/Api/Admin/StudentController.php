<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'student');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        return response()->json([
            'success' => true,
            'message' => 'Students retrieved.',
            'data' => $query->latest()->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(User $student): JsonResponse
    {
        $this->ensureIsStudent($student);

        return response()->json([
            'success' => true,
            'message' => 'Student retrieved.',
            'data' => $student->loadCount(['applications', 'enquiries']),
        ]);
    }

    public function update(Request $request, User $student): JsonResponse
    {
        $this->ensureIsStudent($student);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => 'sometimes|nullable|string|max:30',
        ]);

        $student->update($data);

        ActivityLogger::log($request, 'student_updated', 'user', $student->id, 'Administrator updated a student profile', $data);

        return response()->json(['success' => true, 'message' => 'Student updated.', 'data' => $student->fresh()]);
    }

    public function activate(Request $request, User $student): JsonResponse
    {
        $this->ensureIsStudent($student);

        $student->update(['is_active' => true]);

        ActivityLogger::log($request, 'student_activated', 'user', $student->id, 'Administrator activated a student account');

        return response()->json(['success' => true, 'message' => 'Student activated.', 'data' => $student->fresh()]);
    }

    public function deactivate(Request $request, User $student): JsonResponse
    {
        $this->ensureIsStudent($student);

        $student->update(['is_active' => false]);

        ActivityLogger::log($request, 'student_deactivated', 'user', $student->id, 'Administrator deactivated a student account');

        return response()->json(['success' => true, 'message' => 'Student deactivated.', 'data' => $student->fresh()]);
    }

    /**
     * Every route in this controller is bound to a numeric {student} id, but
     * nothing stops that id from belonging to an admin/super_admin account.
     * Refuse those so this controller can never be used to view or modify
     * an administrator, and so it can never touch the last super_admin.
     */
    private function ensureIsStudent(User $student): void
    {
        abort_unless($student->role === 'student', 404, 'Student not found.');
    }
}
