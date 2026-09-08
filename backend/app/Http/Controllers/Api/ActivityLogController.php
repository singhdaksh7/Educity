<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    private const SORTABLE = ['created_at', 'action', 'resource_type'];

    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,name,email');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        if ($request->filled('resource_type')) {
            $query->where('resource_type', $request->get('resource_type'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        $sort = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        return response()->json([
            'success' => true,
            'message' => 'Activity logs retrieved.',
            'data' => $query->orderBy($sort, $direction)->paginate(min((int) $request->get('per_page', 20), 100)),
        ]);
    }
}
