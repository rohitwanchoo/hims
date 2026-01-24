<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandardRx extends Model
{
    protected $table = 'standard_rx';
    protected $primaryKey = 'standard_rx_id';

    protected $fillable = [
        'hospital_id',
        'disease_name',
        'advice',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }

    public function drugs()
    {
        return $this->hasMany(StandardRxDrug::class, 'standard_rx_id', 'standard_rx_id')->orderBy('display_order');
    }
}
