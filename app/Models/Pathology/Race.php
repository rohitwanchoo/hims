<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'race';
    protected $primaryKey = 'race_id';

    protected $fillable = [
        'hospital_id',
        'race_description',
        'race_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
