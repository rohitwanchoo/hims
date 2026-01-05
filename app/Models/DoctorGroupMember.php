<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorGroupMember extends Model
{
    protected $primaryKey = 'member_id';

    protected $fillable = [
        'group_id',
        'doctor_id',
        'role',
        'can_consult',
        'is_active',
    ];

    protected $casts = [
        'can_consult' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(DoctorGroup::class, 'group_id', 'group_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }
}
