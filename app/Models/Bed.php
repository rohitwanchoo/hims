<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class Bed extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'bed_id';

    protected $fillable = [
        'hospital_id',
        'bed_number',
        'room_number',
        'room_id',
        'floor',
        'ward_id',
        'bed_type',
        'bed_status',
        'charges_per_day',
        'status',
        'is_available',
        'is_isolation',
        'is_ventilator',
        'current_patient_id',
        'current_ipd_id',
    ];

    protected $casts = [
        'bed_number' => 'integer',
        'is_available' => 'boolean',
        'is_isolation' => 'boolean',
        'is_ventilator' => 'boolean',
        'charges_per_day' => 'decimal:2',
    ];

    protected $appends = ['ward_name', 'full_bed_number'];

    public function getWardNameAttribute()
    {
        return $this->ward ? $this->ward->ward_name : null;
    }

    public function getFullBedNumberAttribute()
    {
        $parts = [];
        if ($this->ward) {
            $parts[] = $this->ward->ward_name;
        }
        if ($this->room_number) {
            $parts[] = 'Room ' . $this->room_number;
        }
        $parts[] = 'Bed ' . $this->bed_number;
        return implode(' - ', $parts);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class, 'ward_id', 'ward_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'room_id');
    }

    public function currentAdmission()
    {
        return $this->hasOne(IpdAdmission::class, 'bed_id', 'bed_id')
            ->where('status', 'admitted');
    }

    public function currentPatient()
    {
        return $this->belongsTo(Patient::class, 'current_patient_id', 'patient_id');
    }

    public function admissions()
    {
        return $this->hasMany(IpdAdmission::class, 'bed_id', 'bed_id');
    }

    public function transfersFrom()
    {
        return $this->hasMany(BedTransfer::class, 'from_bed_id', 'bed_id');
    }

    public function transfersTo()
    {
        return $this->hasMany(BedTransfer::class, 'to_bed_id', 'bed_id');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('is_available', false)->orWhere('status', 'occupied');
    }

    public function scopeByWard($query, $wardId)
    {
        return $query->where('ward_id', $wardId);
    }

    public function scopeIsolation($query)
    {
        return $query->where('is_isolation', true);
    }

    public function scopeVentilator($query)
    {
        return $query->where('is_ventilator', true);
    }

    // Methods
    public function occupy($patientId, $ipdId)
    {
        $this->update([
            'is_available' => false,
            'status' => 'occupied',
            'current_patient_id' => $patientId,
            'current_ipd_id' => $ipdId,
        ]);
    }

    public function release()
    {
        $this->update([
            'is_available' => true,
            'status' => 'available',
            'current_patient_id' => null,
            'current_ipd_id' => null,
        ]);
    }
}
