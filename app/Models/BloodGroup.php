<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodGroup extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'blood_group_id';

    public function getRouteKeyName()
    {
        return 'blood_group_id';
    }

    protected $fillable = [
        'hospital_id',
        'blood_group_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'blood_group_id', 'blood_group_id');
    }
}
