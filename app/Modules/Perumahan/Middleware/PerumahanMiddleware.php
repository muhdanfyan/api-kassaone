<?php

namespace App\Modules\Perumahan\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PerumahanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated via admin guard
        if (!auth()->guard('admin')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth()->guard('admin')->user();

        // Check if user role is Perumahan
        if ($user->role->name !== 'Perumahan') {
            Log::warning('Unauthorized access attempt to Perumahan module', [
                'user_id' => $user->id,
                'username' => $user->username,
                'role' => $user->role->name,
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only Perumahan role can access this resource.',
            ], 403);
        }

        // Log action for audit trail
        Log::info('Perumahan module access', [
            'user_id' => $user->id,
            'username' => $user->username,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
