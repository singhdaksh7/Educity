<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\Enquiry;
use App\Models\Gallery;
use App\Models\Program;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();
        $rangeStart = $now->copy()->subMonths(11)->startOfMonth();

        $enquiriesByStatus = Enquiry::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status');

        $applicationsByStatus = AdmissionApplication::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')->pluck('count', 'status');

        $monthExpression = $this->monthGroupExpression();

        $monthlyEnquiries = Enquiry::selectRaw("{$monthExpression} as month, count(*) as count")
            ->where('created_at', '>=', $rangeStart)
            ->groupBy('month')->orderBy('month')->get();

        $monthlyApplications = AdmissionApplication::selectRaw("{$monthExpression} as month, count(*) as count")
            ->where('created_at', '>=', $rangeStart)
            ->groupBy('month')->orderBy('month')->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard metrics retrieved.',
            'data' => [
                'total_enquiries' => Enquiry::count(),
                'new_enquiries' => Enquiry::where('status', 'new')->count(),
                'enquiries_this_month' => Enquiry::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                'enquiries_by_status' => (object) $enquiriesByStatus->toArray(),
                'total_applications' => AdmissionApplication::count(),
                'applications_this_month' => AdmissionApplication::whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->count(),
                'applications_by_status' => (object) $applicationsByStatus->toArray(),
                'active_programs' => Program::where('is_active', true)->count(),
                'active_testimonials' => Testimonial::where('is_active', true)->count(),
                'active_gallery_items' => Gallery::where('is_active', true)->count(),
                'recent_enquiries' => Enquiry::latest()->take(5)->get(['id', 'name', 'email', 'phone', 'status', 'created_at']),
                'recent_applications' => AdmissionApplication::with('program:id,title')->latest()->take(5)->get(['id', 'application_number', 'full_name', 'program_id', 'status', 'created_at']),
                'monthly_enquiry_counts' => $monthlyEnquiries,
                'monthly_application_counts' => $monthlyApplications,
            ],
        ]);
    }

    /**
     * MySQL/MariaDB (production) and SQLite (local dev) group month strings
     * differently; pick the right expression for the active connection.
     */
    private function monthGroupExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at)"
            : "DATE_FORMAT(created_at, '%Y-%m')";
    }
}
