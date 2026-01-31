<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateService extends Model
{
    use HasCuid;

    protected $table = 'estate_services';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ticket_number',
        'resident_id',
        'house_number',
        'reporter_name',
        'reporter_phone',
        'category',
        'sub_category',
        'title',
        'description',
        'location',
        'priority',
        'status',
        'assigned_to',
        'assigned_at',
        'resolved_at',
        'resolution_notes',
        'photos',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'photos' => 'array',
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'rating' => 'integer',
    ];

    // Relationships
    public function resident(): BelongsTo
    {
        return $this->belongsTo(EstateResident::class, 'resident_id');
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['submitted', 'acknowledged', 'in_progress']);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    // Methods
    public static function generateTicketNumber()
    {
        $year = date('Y');
        $lastTicket = self::whereYear('created_at', $year)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "EST-{$year}-{$newNumber}";
    }
}
