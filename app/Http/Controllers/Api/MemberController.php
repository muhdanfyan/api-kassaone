<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function index()
    {
        $members = Member::with('role')->get();
        return response()->json($members);
    }

    public function show(Member $member)
    {
        $member->load('role');
        return response()->json($member);
    }

    public function store(Request $request)
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
            'status' => 'required|in:active,inactive,suspended',
            'role_id' => 'required|exists:roles,id',
        ]);

        $member = Member::create([
            'full_name' => $request->full_name,
            'member_id_number' => $request->member_id_number,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'join_date' => $request->join_date,
            'password' => Hash::make($request->password),
            'status' => $request->status,
            'role_id' => $request->role_id,
        ]);

        $member->load('role');

        return response()->json($member, 201);
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'member_id_number' => 'sometimes|required|string|max:100|unique:members,member_id_number,' . $member->id,
            'username' => 'sometimes|required|string|max:100|unique:members,username,' . $member->id,
            'email' => 'nullable|string|email|max:255|unique:members,email,' . $member->id,
            'phone_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'join_date' => 'sometimes|required|date',
            'password' => 'sometimes|required|string|min:8|confirmed',
            'status' => 'sometimes|required|in:active,inactive,suspended',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        $member->fill($request->except('password'));

        if ($request->has('password')) {
            $member->password = Hash::make($request->password);
        }

        $member->save();
        $member->load('role');

        return response()->json($member);
    }

    public function destroy(Member $member)
    {
        $member->delete();
        return response()->json(['message' => 'Member deleted successfully']);
    }
}
