<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShuDistribution extends Model
{
    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'fiscal_year',
        'total_shu_amount',
        'distribution_date',
        'notes',
    ];

    protected $casts = [
        'distribution_date' => 'date',
    ];

    public function allocations()
    {
        return $this->hasMany(ShuMemberAllocation::class);
    }
}
