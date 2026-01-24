<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseMaster extends Model
{
    protected $table = 'disease_masters';
    protected $primaryKey = 'disease_id';

    protected $fillable = [
        'hospital_id',
        'disease_name',
        'language',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id', 'hospital_id');
    }
}
