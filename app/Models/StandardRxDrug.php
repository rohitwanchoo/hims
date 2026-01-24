<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardRxDrug extends Model
{
    protected $table = 'standard_rx_drugs';
    protected $primaryKey = 'standard_rx_drug_id';

    protected $fillable = [
        'standard_rx_id',
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

    public function standardRx()
    {
        return $this->belongsTo(StandardRx::class, 'standard_rx_id', 'standard_rx_id');
    }

    public function drugMaster()
    {
        return $this->belongsTo(DrugMaster::class, 'drug_master_id', 'drug_master_id');
    }
}
