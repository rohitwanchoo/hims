<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultationRecord extends Model
{
    protected $primaryKey = 'record_id';

    protected $fillable = [
        'opd_id',
        'ipd_id',
        'patient_id',
        'doctor_id',
        'form_id',
        'consultation_date',
        'form_data',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'form_data' => 'array',
        'consultation_date' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    public function form()
    {
        return $this->belongsTo(ConsultationForm::class, 'form_id', 'form_id');
    }

    public function opdVisit()
    {
        return $this->belongsTo(OpdVisit::class, 'opd_id', 'opd_id');
    }

    public function ipdAdmission()
    {
        return $this->belongsTo(IpdAdmission::class, 'ipd_id', 'ipd_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    /**
     * Get a specific field value from form_data
     */
    public function getFieldValue($fieldKey, $default = null)
    {
        return $this->form_data[$fieldKey] ?? $default;
    }

    /**
     * Set a field value in form_data
     */
    public function setFieldValue($fieldKey, $value)
    {
        $formData = $this->form_data ?? [];
        $formData[$fieldKey] = $value;
        $this->form_data = $formData;
    }
}
