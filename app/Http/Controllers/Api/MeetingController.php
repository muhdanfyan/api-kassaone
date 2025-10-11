<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;

class MeetingController extends Controller
{
    public function index()
    {
        return response()->json(Meeting::all());
    }

    public function show(Meeting $meeting)
    {
        return response()->json($meeting);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'location' => 'nullable|string|max:255',
        ]);

        $meeting = Meeting::create($request->all());

        return response()->json($meeting, 201);
    }

    public function update(Request $request, Meeting $meeting)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
        ]);

        $meeting->update($request->all());

        return response()->json($meeting);
    }

    public function destroy(Meeting $meeting)
    {
        $meeting->delete();
        return response()->json(['message' => 'Meeting deleted successfully']);
    }
}
