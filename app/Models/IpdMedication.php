<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpdMedication extends Model
{

    protected $primaryKey = 'medication_id';

    protected $fillable = [
        'ipd_id',
        'drug_id',
        'medicine_name',
        'dosage',
        'route',
        'frequency',
        'start_date',
        'end_date',
        'instructions',
        'is_active',
        'prescribed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class, 'drug_id', 'drug_id');
    }

    public function prescribedByDoctor()
    {
        return $this->belongsTo(Doctor::class, 'prescribed_by', 'doctor_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', today());
            });
    }

    public function scopeStopped($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeByRoute($query, $route)
    {
        return $query->where('route', $route);
    }
}
