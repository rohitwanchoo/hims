<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qualification extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'qualification_id';

    protected $fillable = [
        'hospital_id',
        'qualification_name',
        'qualification_code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'qualification_id';
    }

    public function referenceDoctors()
    {
        return $this->hasMany(ReferenceDoctor::class, 'qualification_id', 'qualification_id');
    }
}
