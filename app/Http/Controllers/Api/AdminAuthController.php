<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    /**
     * Login untuk Admin/Staff (Table: users)
     */
    public function login(Request $request)
    {
        // Log incoming request
        Log::info('Admin Login Attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find user by username
        $user = User::with('role')
            ->where('username', $request->username)
            ->first();

        if (!$user) {
            Log::warning('User not found', ['username' => $request->username]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check password
        $passwordCheck = Hash::check($request->password, $user->password);
        Log::info('Password Check', [
            'username' => $request->username,
            'password_match' => $passwordCheck,
            'user_status' => $user->status,
        ]);

        if (!$passwordCheck) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Check if user is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Account is not active',
            ], 403);
        }

        // Generate JWT token
        $token = auth()->guard('admin')->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Get TTL from config
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'description' => $user->role->description,
                ],
            ],
        ]);
    }

    /**
     * Get authenticated admin/staff user
     */
    public function me()
    {
        $user = auth()->guard('admin')->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ],
            ],
        ]);
    }

    /**
     * Logout admin/staff
     */
    public function logout()
    {
        auth()->guard('admin')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }
}
