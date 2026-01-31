<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateWasteSchedule extends Model
{
    use HasCuid;

    protected $table = 'estate_waste_schedules';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'schedule_name',
        'day_of_week',
        'time',
        'waste_type',
        'coverage_area',
        'assigned_team_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'coverage_area' => 'array',
        'is_active' => 'boolean',
        'time' => 'datetime:H:i',
    ];

    // Relationships
    public function collections(): HasMany
    {
        return $this->hasMany(EstateWasteCollection::class, 'schedule_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(PerumahanStaffTeam::class, 'assigned_team_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDayOfWeek($query, $day)
    {
        return $query->where('day_of_week', $day);
    }
}
