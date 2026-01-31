<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstateResident extends Model
{
    use HasCuid;

    protected $table = 'estate_residents';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'house_number',
        'owner_name',
        'owner_phone',
        'owner_email',
        'nik',
        'current_occupant_name',
        'current_occupant_phone',
        'current_occupant_relationship',
        'house_type',
        'house_status',
        'total_occupants',
        'has_vehicle',
        'vehicle_count',
        'status',
        'joined_date',
        'notes',
    ];

    protected $casts = [
        'has_vehicle' => 'boolean',
        'vehicle_count' => 'integer',
        'total_occupants' => 'integer',
        'joined_date' => 'date',
    ];

    protected $attributes = [
        'status' => 'active',
        'house_status' => 'owner_occupied',
        'house_type' => '45',
        'total_occupants' => 1,
        'has_vehicle' => false,
        'vehicle_count' => 0,
    ];

    protected $appends = ['occupant_count'];

    /**
     * Accessor for occupant_count (frontend compatibility)
     */
    public function getOccupantCountAttribute()
    {
        return (int) ($this->total_occupants ?? 1);
    }

    // Relationships
    public function securityLogs(): HasMany
    {
        return $this->hasMany(EstateSecurityLog::class, 'resident_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(EstateService::class, 'resident_id');
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(EstateFeePayment::class, 'resident_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByHouseNumber($query, $houseNumber)
    {
        return $query->where('house_number', $houseNumber);
    }
}
