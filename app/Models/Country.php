<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'country_id';

    protected $fillable = [
        'hospital_id',
        'country_name',
        'country_code',
        'phone_code',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'country_id';
    }

    public function states()
    {
        return $this->hasMany(State::class, 'country_id', 'country_id');
    }
}
