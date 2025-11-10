<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class ShuMemberAllocation extends Model
{
    use HasCuid;

    public $timestamps = false; // No timestamps in schema

    protected $fillable = [
        'shu_distribution_id',
        'member_id',
        'jasa_modal_amount',
        'jasa_usaha_amount',
        'amount_allocated',
        'is_paid_out',
        'payout_transaction_id',
        'paid_out_at',
    ];

    protected $casts = [
        'jasa_modal_amount' => 'decimal:2',
        'jasa_usaha_amount' => 'decimal:2',
        'amount_allocated' => 'decimal:2',
        'is_paid_out' => 'boolean',
        'paid_out_at' => 'datetime',
    ];

    // Relasi ke SHU Distribution
    public function shuDistribution()
    {
        return $this->belongsTo(ShuDistribution::class);
    }

    // Relasi ke Member
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    // Relasi ke Transaction (pembayaran)
    public function payoutTransaction()
    {
        return $this->belongsTo(Transaction::class, 'payout_transaction_id');
    }

    // Helper: status pembayaran string
    public function getPaymentStatusAttribute()
    {
        return $this->is_paid_out ? 'Paid' : 'Unpaid';
    }

    // Helper: persentase jasa modal dari total
    public function getJasaModalPercentageAttribute()
    {
        return $this->amount_allocated > 0 
            ? round(($this->jasa_modal_amount / $this->amount_allocated) * 100, 2) 
            : 0;
    }

    // Helper: persentase jasa usaha dari total
    public function getJasaUsahaPercentageAttribute()
    {
        return $this->amount_allocated > 0 
            ? round(($this->jasa_usaha_amount / $this->amount_allocated) * 100, 2) 
            : 0;
    }
}
