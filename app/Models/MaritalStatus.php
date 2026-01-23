<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaritalStatus extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'marital_status_id';
    protected $table = 'marital_statuses';

    public function getRouteKeyName()
    {
        return 'marital_status_id';
    }

    protected $fillable = [
        'hospital_id',
        'marital_status_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'marital_status_id', 'marital_status_id');
    }
}
