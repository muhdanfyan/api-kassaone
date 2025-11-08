<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;

class MeetingController extends Controller
{
    public function index()
    {
        $meetings = Meeting::orderBy('meeting_date', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $meetings,
        ]);
    }

    public function show(Meeting $meeting)
    {
        return response()->json([
            'success' => true,
            'data' => $meeting,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'location' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'agenda' => 'nullable|string',
            'status' => 'nullable|in:upcoming,completed,cancelled',
        ]);

        $meeting = Meeting::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Meeting created successfully',
            'data' => $meeting,
        ], 201);
    }

    public function update(Request $request, Meeting $meeting)
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'sometimes|required|date',
            'location' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:50',
            'agenda' => 'nullable|string',
            'summary' => 'nullable|string',
            'status' => 'nullable|in:upcoming,completed,cancelled',
        ]);

        $meeting->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Meeting updated successfully',
            'data' => $meeting,
        ]);
    }

    public function destroy(Meeting $meeting)
    {
        $meeting->delete();
        return response()->json([
            'success' => true,
            'message' => 'Meeting deleted successfully',
        ]);
    }
}
