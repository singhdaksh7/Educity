<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveStudent
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Authentication required.', 'errors' => (object) []], 401);
        }

        if ($user->role !== 'student') {
            return response()->json(['success' => false, 'message' => 'This action is only available to students.', 'errors' => (object) []], 403);
        }

        if (! $user->is_active) {
            return response()->json(['success' => false, 'message' => 'This account has been deactivated.', 'errors' => (object) []], 403);
        }

        return $next($request);
    }
}
