<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentConsultationFormsSeeder extends Seeder
{
    public function run(): void
    {
        $hospitalId = 1;
        $now = Carbon::now();

        echo "Creating department-specific consultation forms...\n";

        // Get department IDs
        $departments = DB::table('departments')->whereIn('department_name', [
            'Cardiology', 'Dermatology', 'Emergency', 'ENT', 'General Medicine',
            'Gynecology', 'Neurology', 'Ophthalmology', 'Orthopedics', 'Pediatrics'
        ])->pluck('department_id', 'department_name');

        if ($departments->isEmpty()) {
            echo "WARNING: No departments found. Please ensure departments exist first.\n";
            return;
        }

        $this->createCardiologyForm($hospitalId, $departments['Cardiology'] ?? null, $now);
        $this->createDermatologyForm($hospitalId, $departments['Dermatology'] ?? null, $now);
        $this->createEmergencyForm($hospitalId, $departments['Emergency'] ?? null, $now);
        $this->createENTForm($hospitalId, $departments['ENT'] ?? null, $now);
        $this->createGynecologyForm($hospitalId, $departments['Gynecology'] ?? null, $now);
        $this->createNeurologyForm($hospitalId, $departments['Neurology'] ?? null, $now);
        $this->createOphthalmologyForm($hospitalId, $departments['Ophthalmology'] ?? null, $now);
        $this->createOrthopedicsForm($hospitalId, $departments['Orthopedics'] ?? null, $now);
        $this->createPediatricsForm($hospitalId, $departments['Pediatrics'] ?? null, $now);

        echo "All department consultation forms created successfully!\n";
    }

    private function insertFields($formId, $fields, $now)
    {
        foreach ($fields as &$field) {
            $field['form_id'] = $formId;
            $field['field_key'] = $field['field_key'] ?? str_replace(' ', '_', strtolower($field['field_label']));
            $field['is_required'] = $field['is_required'] ?? false;
            $field['is_visible'] = $field['is_visible'] ?? true;
            $field['created_at'] = $now;
            $field['updated_at'] = $now;
        }

        DB::table('consultation_form_fields')->insert($fields);
    }
}
