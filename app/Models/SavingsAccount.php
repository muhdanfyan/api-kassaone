<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class SavingsAccount extends Model
{
    use HasCuid;

    protected $fillable = [
        'member_id',
        'account_type',
        'balance',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
