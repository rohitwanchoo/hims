<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpdVisitService extends Model
{
    protected $primaryKey = 'visit_service_id';

    protected $fillable = [
        'opd_id',
        'service_id',
        'quantity',
        'rate',
        'discount',
        'tax',
        'amount',
        'is_free_followup',
        'status',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_free_followup' => 'boolean',
    ];

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }
}
