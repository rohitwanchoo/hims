<?php

namespace App\Models\Pathology;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathoSensitivity extends Model
{
    use HasFactory, BelongsToHospital;

    protected $table = 'patho_sensitivity';
    protected $primaryKey = 'sensitivity_id';

    protected $fillable = [
        'hospital_id',
        'sensitivity_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
