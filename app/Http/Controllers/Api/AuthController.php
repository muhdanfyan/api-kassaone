<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Member;
use App\Models\Role; // Import Role model
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'member_id_number' => 'nullable|string|max:100|unique:members,member_id_number', // Make it optional but still unique if provided
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'sometimes|required|exists:roles,id',
            'join_date' => 'nullable|date',
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

        $user = User::create([
            'name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $roleId,
        ]);

        // Generate member_id_number automatically if not provided
        $member_id_number = $request->member_id_number;
        if (!$member_id_number) {
            // Find the last member to determine the next ID
            $lastMember = Member::orderBy('id', 'desc')->first();
            if ($lastMember && preg_match('/M-(\d+)/', $lastMember->member_id_number, $matches)) {
                $nextId = (int)$matches[1] + 1;
            } else {
                $nextId = 1; // Start from 1 if no members exist or format doesn't match
            }
            $member_id_number = 'M-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
        }

        $member = $user->member()->create([
            'full_name' => $request->full_name,
            'member_id_number' => $member_id_number,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'join_date' => $request->join_date ?? now(),
            'status' => 'Aktif',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load('role', 'member');

        return response()->json([
            'message' => 'Registration successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $user->load('role', 'member');

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
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
