<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdConfiguration;
use Illuminate\Http\Request;

class OpdConfigurationController extends Controller
{
    /**
     * Get current OPD configuration
     */
    public function index()
    {
        $config = OpdConfiguration::where('is_active', true)->first();

        if (!$config) {
            // Return default configuration
            return response()->json([
                'config' => null,
                'message' => 'No configuration found. Using defaults.',
                'defaults' => $this->getDefaults(),
            ]);
        }

        return response()->json($config);
    }

    /**
     * Store or update OPD configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Entry Card Settings
            'charge_entry_card' => 'boolean',
            'entry_card_amount' => 'nullable|numeric|min:0',
            'entry_card_validity_type' => 'nullable|in:one_time,daily,monthly,half_yearly,yearly,lifetime',
            'entry_card_validity_days' => 'nullable|integer|min:1',
            // Consultation Charge Settings
            'consultation_charge_mode' => 'nullable|in:at_registration,after_consultation,both',
            // Rate Settings
            'rate_type' => 'nullable|in:fixed,editable,doctor_approval',
            'allow_rate_override' => 'boolean',
            'require_doctor_approval_for_rate_change' => 'boolean',
            'use_doctor_wise_rates' => 'boolean',
            'use_class_wise_rates' => 'boolean',
            // Registration Mode
            'registration_mode' => 'nullable|in:doctor,department,unit,group',
            // Appointment Settings
            'appointment_expiry_days' => 'nullable|integer|min:1',
            'auto_cancel_expired_appointments' => 'boolean',
            'appointment_cancel_time' => 'nullable',
            // Follow-up Settings
            'enable_free_followup' => 'boolean',
            'default_followup_validity_days' => 'nullable|integer|min:1',
            'default_free_followup_days' => 'nullable|integer|min:0',
            // Token Settings
            'token_generation' => 'nullable|in:auto,manual,slot_based',
            'token_per_doctor' => 'boolean',
            'token_per_department' => 'boolean',
            // Other Settings
            'mandatory_vitals' => 'boolean',
            'mandatory_chief_complaint' => 'boolean',
            'allow_multiple_visits_per_day' => 'boolean',
            'require_payment_before_consultation' => 'boolean',
        ]);

        // Deactivate any existing configuration
        OpdConfiguration::where('is_active', true)->update(['is_active' => false]);

        // Create new configuration
        $config = OpdConfiguration::create(array_merge($validated, ['is_active' => true]));

        return response()->json([
            'message' => 'OPD Configuration saved successfully',
            'config' => $config,
        ], 201);
    }

    /**
     * Update existing configuration
     */
    public function update(Request $request, string $id)
    {
        $config = OpdConfiguration::findOrFail($id);

        $validated = $request->validate([
            'charge_entry_card' => 'boolean',
            'entry_card_amount' => 'nullable|numeric|min:0',
            'entry_card_validity_type' => 'nullable|in:one_time,daily,monthly,half_yearly,yearly,lifetime',
            'entry_card_validity_days' => 'nullable|integer|min:1',
            'consultation_charge_mode' => 'nullable|in:at_registration,after_consultation,both',
            'rate_type' => 'nullable|in:fixed,editable,doctor_approval',
            'allow_rate_override' => 'boolean',
            'require_doctor_approval_for_rate_change' => 'boolean',
            'use_doctor_wise_rates' => 'boolean',
            'use_class_wise_rates' => 'boolean',
            'registration_mode' => 'nullable|in:doctor,department,unit,group',
            'appointment_expiry_days' => 'nullable|integer|min:1',
            'auto_cancel_expired_appointments' => 'boolean',
            'appointment_cancel_time' => 'nullable',
            'enable_free_followup' => 'boolean',
            'default_followup_validity_days' => 'nullable|integer|min:1',
            'default_free_followup_days' => 'nullable|integer|min:0',
            'token_generation' => 'nullable|in:auto,manual,slot_based',
            'token_per_doctor' => 'boolean',
            'token_per_department' => 'boolean',
            'mandatory_vitals' => 'boolean',
            'mandatory_chief_complaint' => 'boolean',
            'allow_multiple_visits_per_day' => 'boolean',
            'require_payment_before_consultation' => 'boolean',
        ]);

        $config->update($validated);

        return response()->json([
            'message' => 'OPD Configuration updated successfully',
            'config' => $config,
        ]);
    }

    /**
     * Get default configuration values
     */
    private function getDefaults()
    {
        return [
            'charge_entry_card' => true,
            'entry_card_amount' => 0,
            'entry_card_validity_type' => 'yearly',
            'consultation_charge_mode' => 'at_registration',
            'rate_type' => 'fixed',
            'allow_rate_override' => false,
            'require_doctor_approval_for_rate_change' => false,
            'use_doctor_wise_rates' => true,
            'use_class_wise_rates' => true,
            'registration_mode' => 'doctor',
            'appointment_expiry_days' => 1,
            'auto_cancel_expired_appointments' => true,
            'enable_free_followup' => true,
            'default_followup_validity_days' => 7,
            'default_free_followup_days' => 3,
            'token_generation' => 'auto',
            'token_per_doctor' => true,
            'token_per_department' => false,
            'mandatory_vitals' => false,
            'mandatory_chief_complaint' => true,
            'allow_multiple_visits_per_day' => false,
            'require_payment_before_consultation' => false,
        ];
    }
}
