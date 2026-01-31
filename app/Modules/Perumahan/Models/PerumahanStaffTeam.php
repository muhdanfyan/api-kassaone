<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerumahanStaffTeam extends Model
{
    use HasCuid;

    protected $table = 'perumahan_staff_teams';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'team_name',
        'team_code',
        'description',
        'member_count',
        'is_active',
    ];

    protected $casts = [
        'member_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Team has many waste schedules
     */
    public function wasteSchedules(): HasMany
    {
        return $this->hasMany(EstateWasteSchedule::class, 'assigned_team_id');
    }

    /**
     * Scope: Filter active teams
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if team is assigned to any schedule
     */
    public function isAssignedToSchedules(): bool
    {
        return $this->wasteSchedules()->exists();
    }
}
