<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Member;
use App\Models\Role; // Import Role model
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'member_id_number' => 'required|string|max:100|unique:members',
            'username' => 'required|string|max:100|unique:members',
            'email' => 'nullable|string|email|max:255|unique:members',
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'required|date',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'sometimes|required|exists:roles,id', // Make role_id sometimes required
        ]);

        // Find 'Anggota' role ID, or use provided role_id
        $roleId = $request->role_id;
        if (!$roleId) {
            $anggotaRole = Role::where('name', 'Anggota')->first();
            if (!$anggotaRole) {
                return response()->json(['message' => 'Default role "Anggota" not found.'], 500);
            }
            $roleId = $anggotaRole->id;
        }


        $member = Member::create([
            'full_name' => $request->full_name,
            'member_id_number' => $request->member_id_number,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'join_date' => $request->join_date,
            'password' => Hash::make($request->password),
            'status' => 'active', // Default status to active
            'role_id' => $roleId,
        ]);

        $token = $member->createToken('auth_token')->plainTextToken;

        // Eager load role for the response
        $member->load('role');

        return response()->json([
            'message' => 'Registration successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $member, // Changed 'member' to 'user' for consistency with project.md
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $member = Member::where('username', $request->username)->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            throw ValidationException::withMessages([
                'username' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $member->createToken('auth_token')->plainTextToken;

        // Eager load role for the response
        $member->load('role');

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $member, // Changed 'member' to 'user' for consistency with project.md
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        // Eager load role for the response
        $user = $request->user()->load('role');
        return response()->json($user);
    }
}
