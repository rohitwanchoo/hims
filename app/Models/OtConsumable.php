<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtConsumable extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'consumable_id';

    protected $fillable = [
        'hospital_id',
        'procedure_id',
        'item_id',
        'item_name',
        'batch_number',
        'quantity',
        'unit',
        'rate',
        'amount',
        'is_implant',
        'implant_serial_number',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_implant' => 'boolean',
    ];

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(OtProcedure::class, 'procedure_id', 'procedure_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }
}
