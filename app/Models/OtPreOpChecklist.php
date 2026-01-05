<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtPreOpChecklist extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'checklist_id';

    protected $fillable = [
        'hospital_id',
        'schedule_id',
        'patient_id',
        'consent_surgical',
        'consent_anesthesia',
        'consent_blood_transfusion',
        'site_marked',
        'patient_identity_confirmed',
        'allergies_noted',
        'allergies_details',
        'nil_by_mouth_confirmed',
        'npo_hours',
        'investigations_reviewed',
        'blood_available',
        'blood_type_crossmatched',
        'pre_medications_given',
        'pre_medication_details',
        'jewelry_removed',
        'dentures_removed',
        'prosthesis_noted',
        'iv_line_secured',
        'foley_catheter',
        'skin_prep_done',
        'special_equipment_verified',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'consent_surgical' => 'boolean',
        'consent_anesthesia' => 'boolean',
        'consent_blood_transfusion' => 'boolean',
        'site_marked' => 'boolean',
        'patient_identity_confirmed' => 'boolean',
        'allergies_noted' => 'boolean',
        'nil_by_mouth_confirmed' => 'boolean',
        'investigations_reviewed' => 'boolean',
        'blood_available' => 'boolean',
        'pre_medications_given' => 'boolean',
        'jewelry_removed' => 'boolean',
        'dentures_removed' => 'boolean',
        'iv_line_secured' => 'boolean',
        'foley_catheter' => 'boolean',
        'skin_prep_done' => 'boolean',
        'special_equipment_verified' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(OtSchedule::class, 'schedule_id', 'schedule_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by', 'user_id');
    }

    public function isComplete(): bool
    {
        return $this->consent_surgical
            && $this->consent_anesthesia
            && $this->patient_identity_confirmed
            && $this->site_marked
            && $this->nil_by_mouth_confirmed;
    }
}
