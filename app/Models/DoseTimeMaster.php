<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoseTimeMaster extends Model
{
    protected $table = 'dose_time_masters';
    protected $primaryKey = 'dose_time_id';

    protected $fillable = [
        'hospital_id',
        'dose_time_text',
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
