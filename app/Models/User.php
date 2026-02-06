<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use App\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, BelongsToHospital, HasRoles;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'hospital_id',
        'username',
        'password',
        'full_name',
        'email',
        'role',
        'department_id',
        'is_active',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class, 'user_id', 'user_id');
    }
}
