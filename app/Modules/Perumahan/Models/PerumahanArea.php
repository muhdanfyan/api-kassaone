<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class PerumahanArea extends Model
{
    use HasCuid;

    protected $table = 'perumahan_areas';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'area_code',
        'area_name',
        'description',
        'house_count',
        'is_active',
    ];

    protected $casts = [
        'house_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Filter active areas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
