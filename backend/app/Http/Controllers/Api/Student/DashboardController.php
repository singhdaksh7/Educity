<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Enquiry;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $applicationsByStatus = AdmissionApplication::where('user_id', $user->id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'message' => 'Student dashboard retrieved.',
            'data' => [
                'profile' => $user->only('id', 'name', 'email', 'phone', 'is_active'),
                'total_applications' => AdmissionApplication::where('user_id', $user->id)->count(),
                'applications_by_status' => (object) $applicationsByStatus->toArray(),
                'recent_applications' => AdmissionApplication::where('user_id', $user->id)
                    ->with('program:id,title')->latest()->take(5)
                    ->get(['id', 'application_number', 'full_name', 'program_id', 'status', 'created_at']),
                'total_enquiries' => Enquiry::where('user_id', $user->id)->count(),
                'recent_enquiries' => Enquiry::where('user_id', $user->id)->latest()->take(5)
                    ->get(['id', 'subject', 'status', 'created_at']),
                'featured_programmes' => Program::where('is_active', true)->where('is_featured', true)
                    ->orderBy('display_order')->get(['id', 'title', 'slug', 'short_description', 'degree_type']),
            ],
        ]);
    }
}
