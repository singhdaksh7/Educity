<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentEnquiryRequest;
use App\Models\Enquiry;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Enquiry::where('user_id', $request->user()->id)->latest();

        return response()->json([
            'success' => true,
            'message' => 'Enquiries retrieved.',
            'data' => $query->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }

    public function show(Request $request, int $enquiry): JsonResponse
    {
        // Scoped lookup so a student can never read another student's enquiry (IDOR).
        $record = Enquiry::where('user_id', $request->user()->id)->findOrFail($enquiry);

        return response()->json(['success' => true, 'message' => 'Enquiry retrieved.', 'data' => $record]);
    }

    public function store(StoreStudentEnquiryRequest $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['success' => false, 'message' => 'Invalid submission.', 'errors' => (object) []], 422);
        }

        $enquiry = Enquiry::create([
            ...$request->safe()->except('website'),
            'user_id' => $request->user()->id,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip()) : null,
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);

        ActivityLogger::log($request, 'enquiry_submitted', 'enquiry', $enquiry->id, 'Student submitted an enquiry');

        return response()->json([
            'success' => true,
            'message' => 'Your enquiry has been received. We will get back to you shortly.',
            'data' => ['id' => $enquiry->id],
        ], 201);
    }
}
