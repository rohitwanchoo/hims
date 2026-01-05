<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory, BelongsToHospital;

    protected $fillable = [
        'hospital_id',
        'patient_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'patient_type',
        'email',
        'address',
        'emergency_contact',
        'emergency_phone',
        'blood_group',
        'allergies',
        'medical_history',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}