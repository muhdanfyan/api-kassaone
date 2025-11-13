<?php

namespace App\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetRequest extends Model
{
    use HasFactory, HasCuid;

    protected $fillable = [
        'nik',
        'matched_member_id',
        'status',
        'processed_by',
        'processed_at',
        'notes'
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    /**
     * Get the member that matches this request
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'matched_member_id');
    }

    /**
     * Get the admin who processed this request
     */
    public function processedBy()
    {
        return $this->belongsTo(Member::class, 'processed_by');
    }
    
    /**
     * Scope a query to only include pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    /**
     * Scope a query to only include completed requests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    /**
     * Scope a query to only include rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}