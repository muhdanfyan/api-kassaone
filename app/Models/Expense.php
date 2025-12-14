<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasCuid;

    /**
     * The primary key type
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'account_id',
        'description',
        'unit_price',
        'unit',
        'quantity',
        'expense_date',
        'receipt_number',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the account for this expense
     */
    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the member who created this expense
     */
    public function creator()
    {
        return $this->belongsTo(Member::class, 'created_by');
    }
}
