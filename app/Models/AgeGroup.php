<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeGroup extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'age_group_id';

    public function getRouteKeyName()
    {
        return 'age_group_id';
    }

    protected $fillable = [
        'hospital_id',
        'age_group_caption',
        'from_age',
        'to_age',
        'age_unit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'from_age' => 'integer',
        'to_age' => 'integer',
    ];
}
