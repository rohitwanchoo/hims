<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrescriptionDrug extends Model
{
    protected $table = 'prescription_drugs';
    protected $primaryKey = 'prescription_drug_id';

    protected $fillable = [
        'prescription_id',
        'drug_master_id',
        'drug_name',
        'drug_type',
        'language',
        'dose_advice',
        'days',
        'qty',
        'display_order',
    ];

    protected $casts = [
        'days' => 'integer',
        'qty' => 'integer',
        'display_order' => 'integer',
    ];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id', 'prescription_id');
    }

    public function drugMaster()
    {
        return $this->belongsTo(DrugMaster::class, 'drug_master_id', 'drug_master_id');
    }
}
