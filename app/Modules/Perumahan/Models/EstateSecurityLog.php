<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateSecurityLog extends Model
{
    use HasCuid;

    protected $table = 'estate_security_logs';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'log_type',
        'resident_id',
        'house_number',
        'visitor_name',
        'visitor_phone',
        'visitor_purpose',
        'vehicle_plate',
        'incident_type',
        'incident_description',
        'incident_severity',
        'incident_status',
        'patrol_area',
        'patrol_notes',
        'log_datetime',
        'guard_name',
        'guard_shift',
        'notes',
        'photo_url',
        'created_by',
    ];

    protected $casts = [
        'log_datetime' => 'datetime',
    ];

    // Relationships
    public function resident(): BelongsTo
    {
        return $this->belongsTo(EstateResident::class, 'resident_id');
    }

    // Scopes
    public function scopeByLogType($query, $type)
    {
        return $query->where('log_type', $type);
    }

    public function scopeIncidents($query)
    {
        return $query->where('log_type', 'incident');
    }

    public function scopeActiveIncidents($query)
    {
        return $query->where('log_type', 'incident')
                    ->whereIn('incident_status', ['open', 'investigating']);
    }
}
