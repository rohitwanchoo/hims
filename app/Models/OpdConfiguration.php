<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;

class OpdConfiguration extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'config_id';

    protected $fillable = [
        'hospital_id',
        // Entry Card Settings
        'charge_entry_card',
        'entry_card_amount',
        'entry_card_validity_type',
        'entry_card_validity_days',
        // Consultation Charge Settings
        'consultation_charge_mode',
        // Rate Settings
        'rate_type',
        'allow_rate_override',
        'require_doctor_approval_for_rate_change',
        'use_doctor_wise_rates',
        'use_class_wise_rates',
        // Registration Mode
        'registration_mode',
        // Appointment Settings
        'appointment_expiry_days',
        'auto_cancel_expired_appointments',
        'appointment_cancel_time',
        // Follow-up Settings
        'enable_free_followup',
        'default_followup_validity_days',
        'default_free_followup_days',
        // Token Settings
        'token_generation',
        'token_per_doctor',
        'token_per_department',
        // Other Settings
        'mandatory_vitals',
        'mandatory_chief_complaint',
        'allow_multiple_visits_per_day',
        'require_payment_before_consultation',
        'show_continue_consultation_button',
        'is_active',
    ];

    protected $casts = [
        'charge_entry_card' => 'boolean',
        'entry_card_amount' => 'decimal:2',
        'allow_rate_override' => 'boolean',
        'require_doctor_approval_for_rate_change' => 'boolean',
        'use_doctor_wise_rates' => 'boolean',
        'use_class_wise_rates' => 'boolean',
        'auto_cancel_expired_appointments' => 'boolean',
        'enable_free_followup' => 'boolean',
        'token_per_doctor' => 'boolean',
        'token_per_department' => 'boolean',
        'mandatory_vitals' => 'boolean',
        'mandatory_chief_complaint' => 'boolean',
        'allow_multiple_visits_per_day' => 'boolean',
        'require_payment_before_consultation' => 'boolean',
        'show_continue_consultation_button' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get entry card validity in days based on type
     */
    public function getEntryCardValidityDaysAttribute($value)
    {
        if ($value) return $value;

        return match ($this->entry_card_validity_type) {
            'one_time' => 1,
            'daily' => 1,
            'monthly' => 30,
            'half_yearly' => 182,
            'yearly' => 365,
            'lifetime' => 36500, // 100 years
            default => 365,
        };
    }
}
