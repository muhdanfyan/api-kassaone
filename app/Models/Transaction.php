<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $timestamps = false; // Only has created_at

    protected $fillable = [
        'savings_account_id',
        'member_id',
        'transaction_type',
        'amount',
        'description',
        'transaction_date',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];

    public function savingsAccount()
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
