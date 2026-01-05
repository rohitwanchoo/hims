<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class IpdProgressNote extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'note_id';

    protected $fillable = [
        'hospital_id',
        'ipd_id',
        'doctor_id',
        'note_date',
        'note_time',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'general_notes',
        'instructions',
        'note_type',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    protected $appends = ['doctor_name', 'note_datetime'];

    public function getDoctorNameAttribute()
    {
        return $this->doctor ? $this->doctor->doctor_name : null;
    }

    public function getNoteDatetimeAttribute()
    {
        return $this->note_date->format('Y-m-d') . ' ' . ($this->note_time ?? '00:00:00');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    // Scopes
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('note_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('note_date', today());
    }
}
