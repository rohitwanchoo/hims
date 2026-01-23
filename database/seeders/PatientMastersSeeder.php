<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PatientMastersSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Genders
        $genders = [
            ['gender_name' => 'Male', 'description' => 'Male gender'],
            ['gender_name' => 'Female', 'description' => 'Female gender'],
            ['gender_name' => 'Trans', 'description' => 'Transgender'],
        ];

        foreach ($genders as $gender) {
            DB::table('genders')->updateOrInsert(
                ['gender_name' => $gender['gender_name'], 'hospital_id' => null],
                array_merge($gender, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Seed Blood Groups
        $bloodGroups = [
            ['blood_group_name' => 'A+', 'description' => 'A Positive'],
            ['blood_group_name' => 'A-', 'description' => 'A Negative'],
            ['blood_group_name' => 'B+', 'description' => 'B Positive'],
            ['blood_group_name' => 'B-', 'description' => 'B Negative'],
            ['blood_group_name' => 'AB+', 'description' => 'AB Positive'],
            ['blood_group_name' => 'AB-', 'description' => 'AB Negative'],
            ['blood_group_name' => 'O+', 'description' => 'O Positive'],
            ['blood_group_name' => 'O-', 'description' => 'O Negative'],
        ];

        foreach ($bloodGroups as $bloodGroup) {
            DB::table('blood_groups')->updateOrInsert(
                ['blood_group_name' => $bloodGroup['blood_group_name'], 'hospital_id' => null],
                array_merge($bloodGroup, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Seed Patient Types
        $patientTypes = [
            ['patient_type_name' => 'Dr', 'description' => 'Doctor'],
            ['patient_type_name' => 'Management', 'description' => 'Management staff'],
            ['patient_type_name' => 'Staff', 'description' => 'Hospital staff'],
        ];

        foreach ($patientTypes as $patientType) {
            DB::table('patient_types')->updateOrInsert(
                ['patient_type_name' => $patientType['patient_type_name'], 'hospital_id' => null],
                array_merge($patientType, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }

        // Seed Marital Statuses
        $maritalStatuses = [
            ['marital_status_name' => 'Single', 'description' => 'Unmarried'],
            ['marital_status_name' => 'Married', 'description' => 'Married'],
            ['marital_status_name' => 'Divorced', 'description' => 'Divorced'],
            ['marital_status_name' => 'Widowed', 'description' => 'Widowed'],
        ];

        foreach ($maritalStatuses as $maritalStatus) {
            DB::table('marital_statuses')->updateOrInsert(
                ['marital_status_name' => $maritalStatus['marital_status_name'], 'hospital_id' => null],
                array_merge($maritalStatus, ['is_active' => true, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
