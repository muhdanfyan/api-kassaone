<?php

namespace App\Services;

use App\Helpers\SettingHelper;
use Carbon\Carbon;

class FeeCalculationService
{
    /**
     * Calculate penalty based on settings
     * 
     * @param string|Carbon $dueDate
     * @param string|Carbon|null $paymentDate
     * @return array
     */
    public function calculatePenalty($dueDate, $paymentDate = null): array
    {
        // Check if penalty is enabled
        $penaltyEnabled = SettingHelper::get('penalty_enabled', true);
        
        if (!$penaltyEnabled) {
            return [
                'penalty_amount' => 0,
                'late_days' => 0,
                'penalty_per_day' => 0,
            ];
        }
        
        // Get settings
        $gracePeriodDays = (int) SettingHelper::get('grace_period_days', 3);
        $penaltyPerDay = (float) SettingHelper::get('penalty_per_day', 5000);
        $penaltyMaxDays = (int) SettingHelper::get('penalty_max_days', 30);
        
        // Parse dates
        $dueDate = Carbon::parse($dueDate);
        $paymentDate = $paymentDate ? Carbon::parse($paymentDate) : Carbon::now();
        
        // Calculate days late
        $totalDaysLate = $paymentDate->diffInDays($dueDate, false); // false = can be negative
        
        if ($totalDaysLate <= 0) {
            // Paid on time or early
            return [
                'penalty_amount' => 0,
                'late_days' => 0,
                'penalty_per_day' => $penaltyPerDay,
                'grace_period_days' => $gracePeriodDays,
                'max_penalty_days' => $penaltyMaxDays,
            ];
        }
        
        // Apply grace period
        $effectiveLateDays = max(0, $totalDaysLate - $gracePeriodDays);
        
        // Cap at max days
        $effectiveLateDays = min($effectiveLateDays, $penaltyMaxDays);
        
        // Calculate penalty
        $penaltyAmount = $effectiveLateDays * $penaltyPerDay;
        
        return [
            'penalty_amount' => $penaltyAmount,
            'late_days' => $effectiveLateDays,
            'penalty_per_day' => $penaltyPerDay,
            'grace_period_days' => $gracePeriodDays,
            'max_penalty_days' => $penaltyMaxDays,
            'total_days_late' => $totalDaysLate,
        ];
    }
    
    /**
     * Get due date for a given period
     * 
     * @param int $periodMonth
     * @param int $periodYear
     * @return Carbon
     */
    public function getDueDate($periodMonth, $periodYear): Carbon
    {
        $dueDay = (int) SettingHelper::get('due_date', 5);
        
        return Carbon::create($periodYear, $periodMonth, $dueDay, 0, 0, 0);
    }
    
    /**
     * Update payment with penalty calculation
     * 
     * @param \App\Modules\Perumahan\Models\Payment $payment
     * @return \App\Modules\Perumahan\Models\Payment
     */
    public function updatePaymentPenalty($payment)
    {
        $dueDate = $this->getDueDate($payment->period_month, $payment->period_year);
        $paymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : Carbon::now();
        
        $penaltyData = $this->calculatePenalty($dueDate, $paymentDate);
        
        $payment->penalty_amount = $penaltyData['penalty_amount'];
        $payment->total_amount = $payment->amount + $penaltyData['penalty_amount'];
        $payment->save();
        
        return $payment;
    }
    
    /**
     * Get monthly fee amount from settings
     * 
     * @return float
     */
    public function getMonthlyFeeAmount(): float
    {
        return (float) SettingHelper::get('monthly_fee', 150000);
    }
}
