<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Check if user has a role
        if (!$user->role) {
            return response()->json([
                'success' => false,
                'message' => 'User role not found'
            ], 403);
        }

        // Check if user's role is in the allowed roles
        if (!in_array($user->role->name, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. This feature is only accessible by ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
