<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory, BelongsToHospital;

    protected $primaryKey = 'state_id';

    protected $fillable = [
        'hospital_id',
        'country_id',
        'state_name',
        'state_code',
        'is_active',
        'is_default'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];

    public function getRouteKeyName()
    {
        return 'state_id';
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'country_id');
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'state_id', 'state_id');
    }
}
