<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstateFee extends Model
{
    use HasCuid;

    protected $table = 'estate_fees';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fee_name',
        'fee_type',
        'amount',
        'applies_to',
        'specific_houses',
        'description',
        'is_mandatory',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'specific_houses' => 'array',
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    // Relationships
    public function payments(): HasMany
    {
        return $this->hasMany(EstateFeePayment::class, 'fee_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('fee_type', 'monthly');
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }
}
