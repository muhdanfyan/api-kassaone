<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasCuid;

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'title',
        'description',
        'meeting_date',
        'location',
        'type',
        'agenda',
        'summary',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
    ];

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }
}
