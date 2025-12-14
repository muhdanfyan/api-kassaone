<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole Middleware
 * Validates that the authenticated user has one of the allowed roles
 * 
 * Usage in routes:
 * Route::middleware(['auth:sanctum', 'role:Admin,Bendahara'])->group(function () {
 *     // Protected routes
 * });
 */
class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Comma-separated list of allowed roles (e.g., "Admin,Bendahara")
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        // Get the user's role
        $user = $request->user();
        $userRole = $user->role->name ?? null;

        // Parse allowed roles
        $allowedRoles = array_map('trim', explode(',', $roles));

        // Check if user's role is in the allowed roles
        if (!in_array($userRole, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to access this resource.',
                'required_roles' => $allowedRoles,
                'your_role' => $userRole
            ], 403);
        }

        return $next($request);
    }
}
