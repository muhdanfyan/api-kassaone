<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'title',
        'description',
        'meeting_date',
        'location',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }
}
