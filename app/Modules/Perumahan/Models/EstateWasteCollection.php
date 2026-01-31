<?php

namespace App\Modules\Perumahan\Models;

use App\Traits\HasCuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstateWasteCollection extends Model
{
    use HasCuid;

    protected $table = 'estate_waste_collections';
    
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'schedule_id',
        'collection_date',
        'collection_time',
        'collector_name',
        'houses_collected',
        'houses_skipped',
        'total_houses',
        'organic_bags',
        'non_organic_bags',
        'recyclable_bags',
        'total_weight',
        'status',
        'notes',
        'photos',
        'recorded_by',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'collection_time' => 'datetime:H:i',
        'houses_collected' => 'array',
        'houses_skipped' => 'array',
        'photos' => 'array',
        'total_houses' => 'integer',
        'organic_bags' => 'integer',
        'total_weight' => 'decimal:2',
        'non_organic_bags' => 'integer',
        'recyclable_bags' => 'integer',
    ];

    // Relationships
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EstateWasteSchedule::class, 'schedule_id');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('collection_date', $date);
    }
}
