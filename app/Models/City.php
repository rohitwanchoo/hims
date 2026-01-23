<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'city_id';

    protected $fillable = [
        'hospital_id',
        'district_id',
        'city_name',
        'city_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'city_id';
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id', 'district_id');
    }

    public function areas()
    {
        return $this->hasMany(Area::class, 'city_id', 'city_id');
    }
}
