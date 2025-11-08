<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CsrfToken;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidateCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate CSRF for state-changing methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Get CSRF token from header
        $csrfToken = $request->header('X-CSRF-Token');

        if (!$csrfToken) {
            return response()->json([
                'message' => 'CSRF token is required for this request',
                'error' => 'csrf_token_missing'
            ], 403);
        }

        // Validate CSRF token
        $token = CsrfToken::find($csrfToken);

        if (!$token) {
            return response()->json([
                'message' => 'Invalid CSRF token',
                'error' => 'csrf_token_invalid'
            ], 403);
        }

        if ($token->isExpired()) {
            $token->delete();
            return response()->json([
                'message' => 'CSRF token has expired',
                'error' => 'csrf_token_expired'
            ], 403);
        }

        // Verify token belongs to authenticated user
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if ($token->member_id !== $user->id) {
                return response()->json([
                    'message' => 'CSRF token does not belong to authenticated user',
                    'error' => 'csrf_token_mismatch'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'error' => 'authentication_failed'
            ], 401);
        }

        return $next($request);
    }
}
