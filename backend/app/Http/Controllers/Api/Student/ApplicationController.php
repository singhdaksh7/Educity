<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\AdmissionApplication;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    /** Statuses a student may still withdraw from. */
    private const WITHDRAWABLE = ['submitted', 'under_review'];

    public function index(Request $request): JsonResponse
    {
        $query = AdmissionApplication::where('user_id', $request->user()->id)
            ->with('program:id,title')->latest();

        return response()->json([
            'success' => true,
            'message' => 'Applications retrieved.',
            'data' => $query->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(Request $request, int $application): JsonResponse
    {
        // Scoped lookup (never a plain findOrFail on a global query) so a
        // student can never read another student's application by id (IDOR).
        $record = AdmissionApplication::where('user_id', $request->user()->id)
            ->with('program:id,title')->findOrFail($application);

        return response()->json(['success' => true, 'message' => 'Application retrieved.', 'data' => $record]);
    }

    public function store(StoreApplicationRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'message' => 'Invalid submission.', 'errors' => (object) []], 422);
        }

        $user = $request->user();
        $data = $request->safe()->except('website');

        $application = AdmissionApplication::create([
            ...$data,
            // Never trust a client-supplied user_id/email/full_name for the owning identity.
            'user_id' => $user->id,
            'email' => $user->email,
            'full_name' => $data['full_name'] ?? $user->name,
            'application_number' => 'APP-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
        ]);

        ActivityLogger::log($request, 'application_submitted', 'admission_application', $application->id, 'Student submitted an admission application');

        return response()->json([
            'success' => true,
            'message' => 'Your application has been submitted.',
            'data' => ['id' => $application->id, 'application_number' => $application->application_number],
        ], 201);
    }

    public function withdraw(Request $request, int $application): JsonResponse
    {
        $record = AdmissionApplication::where('user_id', $request->user()->id)->findOrFail($application);

        if (! in_array($record->status, self::WITHDRAWABLE, true)) {
            return response()->json([
                'success' => false,
                'message' => 'This application can no longer be withdrawn.',
                'errors' => (object) [],
            ], 422);
        }

        $record->update(['status' => 'withdrawn']);

        ActivityLogger::log($request, 'application_withdrawn', 'admission_application', $record->id, 'Student withdrew an admission application');

        return response()->json(['success' => true, 'message' => 'Application withdrawn.', 'data' => $record->fresh()]);
    }
}
