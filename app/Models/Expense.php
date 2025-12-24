<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasCuid;

    protected $fillable = [
        'account_id',
        'description',
        'unit_price',
        'unit',
        'quantity',
        'amount',
        'expense_date',
        'receipt_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'quantity' => 'integer',
        'expense_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            // Auto-calculate amount
            $model->amount = $model->unit_price * $model->quantity;
        });

        static::updating(function ($model) {
            // Auto-calculate amount on update
            $model->amount = $model->unit_price * $model->quantity;
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }
}
