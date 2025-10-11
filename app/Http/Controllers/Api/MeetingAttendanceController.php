<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingAttendance;
use App\Models\Meeting;
use Illuminate\Validation\ValidationException;

class MeetingAttendanceController extends Controller
{
    public function index(Meeting $meeting)
    {
        return response()->json($meeting->attendance()->with('member')->get());
    }

    public function store(Request $request, Meeting $meeting)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'is_present' => 'required|boolean',
        ]);

        // Check for unique attendance per meeting and member
        if (MeetingAttendance::where('meeting_id', $meeting->id)->where('member_id', $request->member_id)->exists()) {
            throw ValidationException::withMessages([
                'member_id' => ['Member attendance already recorded for this meeting.'],
            ]);
        }

        $attendance = $meeting->attendance()->create($request->all());
        $attendance->load('member');

        return response()->json($attendance, 201);
    }

    public function update(Request $request, MeetingAttendance $meetingAttendance)
    {
        $request->validate([
            'is_present' => 'sometimes|required|boolean',
        ]);

        $meetingAttendance->update($request->all());
        $meetingAttendance->load('member');

        return response()->json($meetingAttendance);
    }
}
