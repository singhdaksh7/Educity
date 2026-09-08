<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return app(DashboardController::class)->index();
    }

    public function logs(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Activity logs retrieved.',
            'data' => ActivityLog::with('user:id,name,email')->latest()
                ->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }
}
