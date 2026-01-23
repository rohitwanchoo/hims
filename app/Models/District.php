<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'district_id';

    protected $fillable = [
        'hospital_id',
        'state_id',
        'district_name',
        'district_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'district_id';
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id', 'state_id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'district_id', 'district_id');
    }
}
