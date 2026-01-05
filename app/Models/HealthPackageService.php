<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthPackageService extends Model
{
    protected $primaryKey = 'package_service_id';

    protected $fillable = [
        'package_id',
        'service_id',
        'quantity',
        'is_mandatory',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
    ];

    public function package()
    {
        return $this->belongsTo(HealthPackage::class, 'package_id', 'package_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id', 'service_id');
    }
}
