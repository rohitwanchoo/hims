<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdviceMaster extends Model
{
    protected $table = 'advice_masters';
    protected $primaryKey = 'advice_id';

    protected $fillable = [
        'hospital_id',
        'advice_text',
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
