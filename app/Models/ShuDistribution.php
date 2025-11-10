<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class ShuDistribution extends Model
{
    use HasCuid;

    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'fiscal_year',
        'setting_id',
        'total_shu_amount',
        'cadangan_amount',
        'jasa_modal_amount',
        'jasa_usaha_amount',
        'distribution_date',
        'status',
        'approved_at',
        'approved_by',
        'notes',
        'reserve_percentage',
        'member_service_percentage',
        'management_service_percentage',
        'education_percentage',
        'social_percentage',
        'zakat_percentage',
    ];

    protected $casts = [
        'total_shu_amount' => 'decimal:2',
        'cadangan_amount' => 'decimal:2',
        'jasa_modal_amount' => 'decimal:2',
        'jasa_usaha_amount' => 'decimal:2',
        'distribution_date' => 'date',
        'approved_at' => 'datetime',
        'reserve_percentage' => 'decimal:2',
        'member_service_percentage' => 'decimal:2',
        'management_service_percentage' => 'decimal:2',
        'education_percentage' => 'decimal:2',
        'social_percentage' => 'decimal:2',
        'zakat_percentage' => 'decimal:2',
    ];

    // Relasi ke allocations
    public function allocations()
    {
        return $this->hasMany(ShuMemberAllocation::class);
    }

    // Relasi ke percentage setting
    public function setting()
    {
        return $this->belongsTo(ShuPercentageSetting::class, 'setting_id');
    }

    // Relasi ke member yang approve
    public function approver()
    {
        return $this->belongsTo(Member::class, 'approved_by');
    }

    // Helper: total yang sudah dibayarkan
    public function getTotalPaidOutAttribute()
    {
        return $this->allocations()->where('is_paid_out', true)->sum('amount_allocated');
    }

    // Helper: total yang belum dibayar
    public function getTotalUnpaidAttribute()
    {
        return $this->allocations()->where('is_paid_out', false)->sum('amount_allocated');
    }

    // Helper: jumlah member yang dapat SHU
    public function getTotalMembersAttribute()
    {
        return $this->allocations()->count();
    }

    // Helper: jumlah member yang sudah dibayar
    public function getPaidMembersCountAttribute()
    {
        return $this->allocations()->where('is_paid_out', true)->count();
    }

    // Helper: persentase pembayaran
    public function getPaymentProgressAttribute()
    {
        $total = $this->allocations->sum('amount_allocated');
        $paid = $this->total_paid_out;
        
        return $total > 0 ? round(($paid / $total) * 100, 2) : 0;
    }
}
