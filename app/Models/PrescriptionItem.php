<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'prescription_id',
        'drug_id',
        'dosage',
        'frequency',
        'duration',
        'quantity',
        'instructions',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'prescription_id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drug_id', 'drug_id');
    }
}
