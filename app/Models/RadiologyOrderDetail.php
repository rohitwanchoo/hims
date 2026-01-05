<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadiologyOrderDetail extends Model
{
    protected $primaryKey = 'detail_id';

    protected $fillable = [
        'radiology_order_id',
        'radiology_test_id',
        'modality_id',
        'rate',
        'with_contrast',
        'contrast_rate',
        'amount',
        'scheduled_datetime',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'contrast_rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'with_contrast' => 'boolean',
        'scheduled_datetime' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(RadiologyOrder::class, 'radiology_order_id', 'radiology_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(RadiologyTest::class, 'radiology_test_id', 'radiology_test_id');
    }

    public function modality(): BelongsTo
    {
        return $this->belongsTo(RadiologyModality::class, 'modality_id', 'modality_id');
    }
}
