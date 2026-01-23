<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'area_id';

    protected $fillable = [
        'hospital_id',
        'city_id',
        'area_name',
        'pincode',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'area_id';
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id', 'city_id');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'permanent_area_id', 'area_id');
    }

    // Get full hierarchy
    public function getFullHierarchy()
    {
        $city = $this->city;
        $district = $city ? $city->district : null;
        $state = $district ? $district->state : null;
        $country = $state ? $state->country : null;

        return [
            'area_id' => $this->area_id,
            'area_name' => $this->area_name,
            'pincode' => $this->pincode,
            'city_id' => $city ? $city->city_id : null,
            'city_name' => $city ? $city->city_name : null,
            'district_id' => $district ? $district->district_id : null,
            'district_name' => $district ? $district->district_name : null,
            'state_id' => $state ? $state->state_id : null,
            'state_name' => $state ? $state->state_name : null,
            'country_id' => $country ? $country->country_id : null,
            'country_name' => $country ? $country->country_name : null,
        ];
    }
}
