<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'gender_id';

    public function getRouteKeyName()
    {
        return 'gender_id';
    }

    protected $fillable = [
        'hospital_id',
        'gender_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'gender_id', 'gender_id');
    }
}
