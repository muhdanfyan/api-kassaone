<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateFeePayment extends Model
{
    use HasCuid;

    protected $table = 'estate_fee_payments';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payment_number',
        'resident_id',
        'house_number',
        'fee_id',
        'period_month',
        'period_year',
        'amount',
        'payment_date',
        'payment_method',
        'status',
        'due_date',
        'late_days',
        'penalty_amount',
        'receipt_number',
        'notes',
        'proof_url',
        'recorded_by',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'payment_date' => 'date',
        'due_date' => 'date',
        'verified_at' => 'datetime',
        'period_month' => 'integer',
        'period_year' => 'integer',
        'late_days' => 'integer',
    ];

    // Relationships
    public function resident(): BelongsTo
    {
        return $this->belongsTo(EstateResident::class, 'resident_id');
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(EstateFee::class, 'fee_id');
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeByPeriod($query, $month, $year)
    {
        return $query->where('period_month', $month)
                    ->where('period_year', $year);
    }

    // Methods
    public static function generatePaymentNumber()
    {
        $year = date('Y');
        $lastPayment = self::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = (int) substr($lastPayment->payment_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "PAY-{$year}-{$newNumber}";
    }
}
