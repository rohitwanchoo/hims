<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthPackage extends Model
{
    protected $primaryKey = 'package_id';

    protected $fillable = [
        'package_code',
        'package_name',
        'description',
        'package_type',
        'client_id',
        'package_rate',
        'discount_percent',
        'is_active',
    ];

    protected $casts = [
        'package_rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function services()
    {
        return $this->hasMany(HealthPackageService::class, 'package_id', 'package_id');
    }

    public function getServicesListAttribute()
    {
        return $this->services()->with('service')->get();
    }

    public function getTotalServicesValueAttribute()
    {
        return $this->services()->with('service')->get()->sum(function ($item) {
            return $item->service->rate * $item->quantity;
        });
    }
}
