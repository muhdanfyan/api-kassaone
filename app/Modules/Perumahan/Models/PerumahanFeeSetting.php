<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PerumahanFeeSetting extends Model
{
    use HasCuid;

    protected $table = 'perumahan_fee_settings';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fee_code',
        'fee_name',
        'amount',
        'is_active',
        'description',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('fee_name');
    }

    // Accessors & Mutators
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    // Business Methods
    public function isInUse(): bool
    {
        // Check if this fee setting is used in any payments
        // This would need to be connected with the actual payment table
        return false; // Default implementation
    }

    public function canBeDeleted(): bool
    {
        return !$this->isInUse();
    }
}
