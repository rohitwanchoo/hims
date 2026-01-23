<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prefix extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'prefix_id';

    public function getRouteKeyName()
    {
        return 'prefix_id';
    }

    protected $fillable = [
        'hospital_id',
        'prefix_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'prefix_id', 'prefix_id');
    }
}
