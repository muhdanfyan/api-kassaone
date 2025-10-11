<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShuMemberAllocation extends Model
{
    public $timestamps = false; // No timestamps in schema

    protected $fillable = [
        'shu_distribution_id',
        'member_id',
        'amount_allocated',
        'is_paid_out',
        'payout_transaction_id',
    ];

    protected $casts = [
        'is_paid_out' => 'boolean',
    ];

    public function shuDistribution()
    {
        return $this->belongsTo(ShuDistribution::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function payoutTransaction()
    {
        return $this->belongsTo(Transaction::class, 'payout_transaction_id');
    }
}
