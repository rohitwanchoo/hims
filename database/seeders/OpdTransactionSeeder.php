<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpdTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hospitalId = 1;

        // Skill Sets (Doctor Specialties)
        $skillSets = [
            ['skill_code' => 'GEN', 'skill_name' => 'General Medicine', 'description' => 'General physician consultations'],
            ['skill_code' => 'PED', 'skill_name' => 'Pediatrics', 'description' => 'Child healthcare'],
            ['skill_code' => 'GYN', 'skill_name' => 'Gynecology', 'description' => 'Women health and obstetrics'],
            ['skill_code' => 'ORT', 'skill_name' => 'Orthopedics', 'description' => 'Bone and joint care'],
            ['skill_code' => 'CAR', 'skill_name' => 'Cardiology', 'description' => 'Heart and cardiovascular care'],
            ['skill_code' => 'DER', 'skill_name' => 'Dermatology', 'description' => 'Skin care and treatment'],
            ['skill_code' => 'ENT', 'skill_name' => 'ENT', 'description' => 'Ear, Nose, Throat specialist'],
            ['skill_code' => 'OPH', 'skill_name' => 'Ophthalmology', 'description' => 'Eye care specialist'],
            ['skill_code' => 'DEN', 'skill_name' => 'Dental', 'description' => 'Dental care and treatment'],
            ['skill_code' => 'PHY', 'skill_name' => 'Physiotherapy', 'description' => 'Physical therapy and rehabilitation'],
            ['skill_code' => 'PSY', 'skill_name' => 'Psychiatry', 'description' => 'Mental health care'],
            ['skill_code' => 'NEU', 'skill_name' => 'Neurology', 'description' => 'Nervous system disorders'],
            ['skill_code' => 'SUR', 'skill_name' => 'Surgery', 'description' => 'General surgical procedures'],
        ];

        foreach ($skillSets as $skillSet) {
            DB::table('skill_sets')->insert(array_merge($skillSet, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Get skill set IDs for visit validity
        $skillSetIds = DB::table('skill_sets')
            ->pluck('skill_set_id', 'skill_code');

        // Skill Set Visit Validity
        $visitValidities = [
            ['skill_code' => 'GEN', 'followup_validity_days' => 7, 'free_followup_validity_days' => 3],
            ['skill_code' => 'PED', 'followup_validity_days' => 7, 'free_followup_validity_days' => 3],
            ['skill_code' => 'GYN', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
            ['skill_code' => 'ORT', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
            ['skill_code' => 'CAR', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
            ['skill_code' => 'DER', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
            ['skill_code' => 'ENT', 'followup_validity_days' => 7, 'free_followup_validity_days' => 3],
            ['skill_code' => 'OPH', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
            ['skill_code' => 'DEN', 'followup_validity_days' => 7, 'free_followup_validity_days' => 3],
            ['skill_code' => 'PHY', 'followup_validity_days' => 3, 'free_followup_validity_days' => 0],
            ['skill_code' => 'PSY', 'followup_validity_days' => 30, 'free_followup_validity_days' => 14],
            ['skill_code' => 'NEU', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
            ['skill_code' => 'SUR', 'followup_validity_days' => 14, 'free_followup_validity_days' => 7],
        ];

        foreach ($visitValidities as $validity) {
            if (isset($skillSetIds[$validity['skill_code']])) {
                DB::table('skill_set_visit_validities')->insert([
                    'skill_set_id' => $skillSetIds[$validity['skill_code']],
                    'followup_validity_days' => $validity['followup_validity_days'],
                    'free_followup_validity_days' => $validity['free_followup_validity_days'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Get service IDs for health packages
        $serviceIds = DB::table('services')
            ->where('hospital_id', $hospitalId)
            ->pluck('service_id', 'service_name');

        // Health Packages (matching migration schema)
        $healthPackages = [
            [
                'package_code' => 'BHC',
                'package_name' => 'Basic Health Checkup',
                'description' => 'Basic preventive health screening package',
                'package_type' => 'hospital',
                'package_rate' => 999.00,
            ],
            [
                'package_code' => 'EHC',
                'package_name' => 'Executive Health Checkup',
                'description' => 'Comprehensive health screening for executives',
                'package_type' => 'hospital',
                'package_rate' => 2999.00,
            ],
            [
                'package_code' => 'SHC',
                'package_name' => 'Senior Citizen Health Checkup',
                'description' => 'Health screening package for senior citizens',
                'package_type' => 'hospital',
                'package_rate' => 2499.00,
            ],
            [
                'package_code' => 'WHC',
                'package_name' => 'Women Wellness Package',
                'description' => 'Complete health checkup for women',
                'package_type' => 'hospital',
                'package_rate' => 3499.00,
            ],
            [
                'package_code' => 'PHC',
                'package_name' => 'Pre-Employment Health Checkup',
                'description' => 'Health checkup required for employment',
                'package_type' => 'corporate',
                'package_rate' => 799.00,
            ],
            [
                'package_code' => 'DHC',
                'package_name' => 'Diabetic Health Checkup',
                'description' => 'Specialized checkup for diabetic patients',
                'package_type' => 'hospital',
                'package_rate' => 1999.00,
            ],
            [
                'package_code' => 'CHC',
                'package_name' => 'Cardiac Health Checkup',
                'description' => 'Heart health screening package',
                'package_type' => 'hospital',
                'package_rate' => 4999.00,
            ],
            [
                'package_code' => 'PPC',
                'package_name' => 'Pre-Marriage Package',
                'description' => 'Health checkup before marriage',
                'package_type' => 'hospital',
                'package_rate' => 1499.00,
            ],
            [
                'package_code' => 'ANC',
                'package_name' => 'Antenatal Care Package',
                'description' => 'Complete pregnancy care package',
                'package_type' => 'hospital',
                'package_rate' => 5999.00,
            ],
            [
                'package_code' => 'CHK',
                'package_name' => 'Child Health Checkup',
                'description' => 'Health screening for children',
                'package_type' => 'hospital',
                'package_rate' => 699.00,
            ],
        ];

        foreach ($healthPackages as $package) {
            DB::table('health_packages')->insert(array_merge($package, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Health Package Services (sample - add services to packages if services exist)
        $packageIds = DB::table('health_packages')
            ->pluck('package_id', 'package_code');

        // Define package services mapping
        $packageServices = [
            'BHC' => [
                ['service_name' => 'Complete Blood Count', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Blood Sugar Fasting', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Urine Routine', 'quantity' => 1, 'is_mandatory' => true],
            ],
            'EHC' => [
                ['service_name' => 'Complete Blood Count', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Blood Sugar Fasting', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Lipid Profile', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Liver Function Test', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Kidney Function Test', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'ECG', 'quantity' => 1, 'is_mandatory' => true],
            ],
            'DHC' => [
                ['service_name' => 'Blood Sugar Fasting', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'HbA1c', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Kidney Function Test', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Lipid Profile', 'quantity' => 1, 'is_mandatory' => true],
            ],
            'CHC' => [
                ['service_name' => 'Complete Blood Count', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'Lipid Profile', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'ECG', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => '2D Echo', 'quantity' => 1, 'is_mandatory' => true],
                ['service_name' => 'TMT', 'quantity' => 1, 'is_mandatory' => false],
            ],
        ];

        foreach ($packageServices as $packageCode => $services) {
            if (isset($packageIds[$packageCode])) {
                foreach ($services as $service) {
                    if (isset($serviceIds[$service['service_name']])) {
                        DB::table('health_package_services')->insert([
                            'package_id' => $packageIds[$packageCode],
                            'service_id' => $serviceIds[$service['service_name']],
                            'quantity' => $service['quantity'],
                            'is_mandatory' => $service['is_mandatory'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }

        $this->command->info('OPD Transaction tables seeded successfully!');
    }
}
