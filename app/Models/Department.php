<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'department_id';

    protected $fillable = [
        'hospital_id',
        'department_code',
        'department_name',
        'description',
        'service_type',
        'imparted_service',
        'is_active',
    ];

    protected $casts = [
        'imparted_service' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'department_id', 'department_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'department_id', 'department_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'department_id', 'department_id');
    }

    public function wards()
    {
        return $this->hasMany(Ward::class, 'department_id', 'department_id');
    }
}
