<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConsultationForm;
use App\Models\ConsultationFormField;

class OpdConsultationFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find or create the General OPD Consultation form
        $form = ConsultationForm::where('form_name', 'General OPD Consultation')
            ->where('form_type', 'opd')
            ->first();

        if (!$form) {
            $this->command->info('Creating General OPD Consultation form...');

            // Get first hospital and user from database
            $hospital = \App\Models\Hospital::first();
            $user = \App\Models\User::first();

            if (!$hospital || !$user) {
                $this->command->error('No hospital or user found in database. Please ensure hospitals and users exist.');
                return;
            }

            $form = ConsultationForm::create([
                'hospital_id' => $hospital->hospital_id,
                'form_name' => 'General OPD Consultation',
                'description' => 'Standard OPD consultation form with comprehensive clinical fields',
                'form_type' => 'opd',
                'department_id' => null,
                'is_active' => true,
                'is_default' => true,
            ]);

            $this->command->info("✅ Created form: {$form->form_name} (ID: {$form->form_id})");
        } else {
            $this->command->info("Found existing form: {$form->form_name} (ID: {$form->form_id})");
        }

        $this->command->info("Adding standard fields to form...");

        // Delete existing fields if any
        ConsultationFormField::where('form_id', $form->form_id)->delete();

        // Standard OPD Consultation Fields
        $fields = [
            // History Section
            [
                'field_label' => 'Chief Complaint',
                'field_key' => 'chief_complaint',
                'field_type' => 'textarea',
                'section' => 'History',
                'field_order' => 0,
                'is_required' => true,
                'placeholder' => 'Enter patient\'s main complaint',
                'help_text' => 'Primary reason for visit',
            ],
            [
                'field_label' => 'History of Present Illness',
                'field_key' => 'history_present_illness',
                'field_type' => 'textarea',
                'section' => 'History',
                'field_order' => 1,
                'is_required' => false,
                'placeholder' => 'Detailed history of the current illness',
                'help_text' => 'Duration, progression, associated symptoms',
            ],
            [
                'field_label' => 'Past Medical History',
                'field_key' => 'past_medical_history',
                'field_type' => 'textarea',
                'section' => 'History',
                'field_order' => 2,
                'is_required' => false,
                'placeholder' => 'Previous illnesses, surgeries, hospitalizations',
            ],
            [
                'field_label' => 'Family History',
                'field_key' => 'family_history',
                'field_type' => 'textarea',
                'section' => 'History',
                'field_order' => 3,
                'is_required' => false,
                'placeholder' => 'Relevant family medical history',
            ],
            [
                'field_label' => 'Allergies',
                'field_key' => 'allergies',
                'field_type' => 'text',
                'section' => 'History',
                'field_order' => 4,
                'is_required' => false,
                'placeholder' => 'Drug/food allergies if any',
            ],
            [
                'field_label' => 'Current Medications',
                'field_key' => 'current_medications',
                'field_type' => 'textarea',
                'section' => 'History',
                'field_order' => 5,
                'is_required' => false,
                'placeholder' => 'List of current medications',
            ],

            // Vital Signs Section
            [
                'field_label' => 'Temperature (°F)',
                'field_key' => 'temperature',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 6,
                'is_required' => true,
                'placeholder' => '98.6',
                'field_config' => ['min' => 95, 'max' => 110, 'step' => 0.1],
            ],
            [
                'field_label' => 'Blood Pressure (mmHg)',
                'field_key' => 'blood_pressure',
                'field_type' => 'text',
                'section' => 'Vital Signs',
                'field_order' => 7,
                'is_required' => true,
                'placeholder' => '120/80',
                'help_text' => 'Format: Systolic/Diastolic',
            ],
            [
                'field_label' => 'Pulse Rate (bpm)',
                'field_key' => 'pulse_rate',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 8,
                'is_required' => true,
                'placeholder' => '72',
                'field_config' => ['min' => 40, 'max' => 200],
            ],
            [
                'field_label' => 'Respiratory Rate (breaths/min)',
                'field_key' => 'respiratory_rate',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 9,
                'is_required' => false,
                'placeholder' => '16',
                'field_config' => ['min' => 10, 'max' => 60],
            ],
            [
                'field_label' => 'SpO2 (%)',
                'field_key' => 'spo2',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 10,
                'is_required' => false,
                'placeholder' => '98',
                'field_config' => ['min' => 50, 'max' => 100],
            ],
            [
                'field_label' => 'Weight (kg)',
                'field_key' => 'weight',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 11,
                'is_required' => false,
                'placeholder' => '70',
                'field_config' => ['min' => 1, 'max' => 300, 'step' => 0.1],
            ],
            [
                'field_label' => 'Height (cm)',
                'field_key' => 'height',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 12,
                'is_required' => false,
                'placeholder' => '170',
                'field_config' => ['min' => 50, 'max' => 250],
            ],
            [
                'field_label' => 'BMI',
                'field_key' => 'bmi',
                'field_type' => 'number',
                'section' => 'Vital Signs',
                'field_order' => 13,
                'is_required' => false,
                'placeholder' => 'Auto-calculated or manual',
                'field_config' => ['step' => 0.1],
            ],

            // Physical Examination Section
            [
                'field_label' => 'General Appearance',
                'field_key' => 'general_appearance',
                'field_type' => 'textarea',
                'section' => 'Physical Examination',
                'field_order' => 14,
                'is_required' => false,
                'placeholder' => 'Well-nourished, alert, in no acute distress',
            ],
            [
                'field_label' => 'Cardiovascular System',
                'field_key' => 'cvs_examination',
                'field_type' => 'textarea',
                'section' => 'Physical Examination',
                'field_order' => 15,
                'is_required' => false,
                'placeholder' => 'Heart sounds, rhythm, murmurs',
            ],
            [
                'field_label' => 'Respiratory System',
                'field_key' => 'respiratory_examination',
                'field_type' => 'textarea',
                'section' => 'Physical Examination',
                'field_order' => 16,
                'is_required' => false,
                'placeholder' => 'Breath sounds, air entry, added sounds',
            ],
            [
                'field_label' => 'Gastrointestinal System',
                'field_key' => 'git_examination',
                'field_type' => 'textarea',
                'section' => 'Physical Examination',
                'field_order' => 17,
                'is_required' => false,
                'placeholder' => 'Abdomen examination findings',
            ],
            [
                'field_label' => 'Central Nervous System',
                'field_key' => 'cns_examination',
                'field_type' => 'textarea',
                'section' => 'Physical Examination',
                'field_order' => 18,
                'is_required' => false,
                'placeholder' => 'Mental status, cranial nerves, motor/sensory',
            ],
            [
                'field_label' => 'Other Systems',
                'field_key' => 'other_examination',
                'field_type' => 'textarea',
                'section' => 'Physical Examination',
                'field_order' => 19,
                'is_required' => false,
                'placeholder' => 'ENT, Musculoskeletal, Skin, etc.',
            ],

            // Assessment & Plan Section
            [
                'field_label' => 'Provisional Diagnosis',
                'field_key' => 'provisional_diagnosis',
                'field_type' => 'textarea',
                'section' => 'Assessment & Plan',
                'field_order' => 20,
                'is_required' => true,
                'placeholder' => 'Primary and differential diagnoses',
            ],
            [
                'field_label' => 'Investigations Ordered',
                'field_key' => 'investigations',
                'field_type' => 'textarea',
                'section' => 'Assessment & Plan',
                'field_order' => 21,
                'is_required' => false,
                'placeholder' => 'Lab tests, imaging, other investigations',
            ],
            [
                'field_label' => 'Treatment/Prescription',
                'field_key' => 'treatment',
                'field_type' => 'textarea',
                'section' => 'Assessment & Plan',
                'field_order' => 22,
                'is_required' => false,
                'placeholder' => 'Medications prescribed with dosage',
            ],
            [
                'field_label' => 'Advice to Patient',
                'field_key' => 'advice',
                'field_type' => 'textarea',
                'section' => 'Assessment & Plan',
                'field_order' => 23,
                'is_required' => false,
                'placeholder' => 'Lifestyle modifications, precautions, instructions',
            ],
            [
                'field_label' => 'Follow-up Required',
                'field_key' => 'followup_required',
                'field_type' => 'radio',
                'section' => 'Assessment & Plan',
                'field_order' => 24,
                'is_required' => false,
                'field_options' => ['Yes', 'No'],
                'default_value' => 'No',
            ],
            [
                'field_label' => 'Follow-up Date',
                'field_key' => 'followup_date',
                'field_type' => 'date',
                'section' => 'Assessment & Plan',
                'field_order' => 25,
                'is_required' => false,
                'help_text' => 'Next consultation date if follow-up required',
            ],
            [
                'field_label' => 'Referral to Specialist',
                'field_key' => 'referral',
                'field_type' => 'text',
                'section' => 'Assessment & Plan',
                'field_order' => 26,
                'is_required' => false,
                'placeholder' => 'Specialist name/department if referred',
            ],
        ];

        // Insert all fields
        foreach ($fields as $fieldData) {
            $fieldData['form_id'] = $form->form_id;
            $fieldData['is_visible'] = true;

            // Convert field_config array to JSON if present
            if (isset($fieldData['field_config']) && is_array($fieldData['field_config'])) {
                $fieldData['field_config'] = json_encode($fieldData['field_config']);
            }

            // Convert field_options array to JSON if present
            if (isset($fieldData['field_options']) && is_array($fieldData['field_options'])) {
                $fieldData['field_options'] = json_encode($fieldData['field_options']);
            }

            ConsultationFormField::create($fieldData);
        }

        $this->command->info("✅ Successfully added " . count($fields) . " standard fields to the form!");
        $this->command->info("Fields are organized in sections:");
        $this->command->info("  - History (6 fields)");
        $this->command->info("  - Vital Signs (8 fields)");
        $this->command->info("  - Physical Examination (6 fields)");
        $this->command->info("  - Assessment & Plan (7 fields)");
    }
}
