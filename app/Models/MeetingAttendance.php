<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingAttendance extends Model
{
    public $timestamps = false; // This is a pivot table, no timestamps

    protected $table = 'meeting_attendance'; // Explicitly set table name

    protected $fillable = [
        'meeting_id',
        'member_id',
        'is_present',
    ];

    protected $casts = [
        'is_present' => 'boolean',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
