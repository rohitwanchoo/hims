<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Ward;
use App\Models\Bed;
use App\Models\LabCategory;
use App\Models\LabTest;
use App\Models\DrugCategory;
use App\Models\DrugSchedule;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create departments
        $departments = [
            ['department_name' => 'General Medicine', 'department_code' => 'GM', 'description' => 'General medical care and consultations'],
            ['department_name' => 'Cardiology', 'department_code' => 'CARD', 'description' => 'Heart and cardiovascular system'],
            ['department_name' => 'Neurology', 'department_code' => 'NEUR', 'description' => 'Brain and nervous system disorders'],
            ['department_name' => 'Orthopedics', 'department_code' => 'ORTH', 'description' => 'Bone and joint disorders'],
            ['department_name' => 'Pediatrics', 'department_code' => 'PEDS', 'description' => 'Child healthcare'],
            ['department_name' => 'Gynecology', 'department_code' => 'GYN', 'description' => 'Women\'s health and reproductive system'],
            ['department_name' => 'Dermatology', 'department_code' => 'DERM', 'description' => 'Skin conditions and disorders'],
            ['department_name' => 'ENT', 'department_code' => 'ENT', 'description' => 'Ear, Nose, and Throat'],
            ['department_name' => 'Ophthalmology', 'department_code' => 'OPTH', 'description' => 'Eye care and vision'],
            ['department_name' => 'Emergency', 'department_code' => 'EMRG', 'description' => 'Emergency medical services'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }

        // Create admin user
        $admin = User::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'full_name' => 'System Administrator',
            'email' => 'admin@hims.local',
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create sample doctors
        $doctorsData = [
            ['doctor_code' => 'DOC001', 'full_name' => 'Dr. John Smith', 'specialization' => 'General Physician', 'department_id' => 1],
            ['doctor_code' => 'DOC002', 'full_name' => 'Dr. Sarah Johnson', 'specialization' => 'Cardiologist', 'department_id' => 2],
            ['doctor_code' => 'DOC003', 'full_name' => 'Dr. Michael Brown', 'specialization' => 'Neurologist', 'department_id' => 3],
            ['doctor_code' => 'DOC004', 'full_name' => 'Dr. Emily Davis', 'specialization' => 'Pediatrician', 'department_id' => 5],
            ['doctor_code' => 'DOC005', 'full_name' => 'Dr. Robert Wilson', 'specialization' => 'Orthopedic Surgeon', 'department_id' => 4],
        ];

        foreach ($doctorsData as $doctorData) {
            Doctor::create([
                'doctor_code' => $doctorData['doctor_code'],
                'department_id' => $doctorData['department_id'],
                'full_name' => $doctorData['full_name'],
                'specialization' => $doctorData['specialization'],
                'qualification' => 'MBBS, MD',
                'mobile' => '555' . rand(1000000, 9999999),
                'email' => strtolower(str_replace([' ', '.'], '', $doctorData['full_name'])) . '@hims.local',
                'consultation_fee' => rand(50, 200),
                'opd_available' => true,
                'ipd_available' => true,
                'is_active' => true,
            ]);
        }

        // Create other staff
        User::create([
            'username' => 'nurse1',
            'password' => Hash::make('nurse123'),
            'full_name' => 'Jane Wilson',
            'email' => 'nurse1@hims.local',
            'role' => 'nurse',
            'department_id' => 1,
            'is_active' => true,
        ]);

        User::create([
            'username' => 'receptionist',
            'password' => Hash::make('reception123'),
            'full_name' => 'Mary Johnson',
            'email' => 'reception@hims.local',
            'role' => 'receptionist',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'labtech',
            'password' => Hash::make('lab123'),
            'full_name' => 'Tom Anderson',
            'email' => 'lab@hims.local',
            'role' => 'lab_technician',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'pharmacist',
            'password' => Hash::make('pharmacy123'),
            'full_name' => 'Lisa Chen',
            'email' => 'pharmacy@hims.local',
            'role' => 'pharmacist',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'accountant',
            'password' => Hash::make('account123'),
            'full_name' => 'David Miller',
            'email' => 'accounts@hims.local',
            'role' => 'accountant',
            'is_active' => true,
        ]);

        // Create wards and beds
        $wards = [
            ['ward_code' => 'GW001', 'ward_name' => 'General Ward', 'ward_type' => 'general', 'total_beds' => 20, 'charges_per_day' => 500],
            ['ward_code' => 'PW001', 'ward_name' => 'Private Ward', 'ward_type' => 'private', 'total_beds' => 10, 'charges_per_day' => 2000],
            ['ward_code' => 'ICU01', 'ward_name' => 'ICU', 'ward_type' => 'icu', 'total_beds' => 5, 'charges_per_day' => 5000],
            ['ward_code' => 'PEDS1', 'ward_name' => 'Pediatric Ward', 'ward_type' => 'general', 'total_beds' => 15, 'charges_per_day' => 600],
            ['ward_code' => 'MAT01', 'ward_name' => 'Maternity Ward', 'ward_type' => 'semi_private', 'total_beds' => 10, 'charges_per_day' => 1500],
        ];

        $bedTypes = ['general' => 'standard', 'private' => 'standard', 'icu' => 'icu', 'semi_private' => 'standard'];

        foreach ($wards as $wardData) {
            $ward = Ward::create($wardData);

            for ($i = 1; $i <= $wardData['total_beds']; $i++) {
                Bed::create([
                    'ward_id' => $ward->ward_id,
                    'bed_number' => $wardData['ward_code'] . '-' . str_pad($i, 2, '0', STR_PAD_LEFT),
                    'bed_type' => $bedTypes[$wardData['ward_type']],
                    'status' => 'available',
                    'is_available' => true,
                ]);
            }
        }

        // Create lab categories and tests
        $labCategories = [
            'Hematology' => [
                ['test_name' => 'Complete Blood Count (CBC)', 'test_code' => 'CBC001', 'rate' => 25, 'normal_range' => 'Varies', 'unit' => 'cells/mcL'],
                ['test_name' => 'Hemoglobin', 'test_code' => 'HGB001', 'rate' => 15, 'normal_range' => '12-17 g/dL', 'unit' => 'g/dL'],
                ['test_name' => 'Platelet Count', 'test_code' => 'PLT001', 'rate' => 20, 'normal_range' => '150,000-400,000', 'unit' => 'cells/mcL'],
            ],
            'Biochemistry' => [
                ['test_name' => 'Blood Glucose Fasting', 'test_code' => 'GLU001', 'rate' => 20, 'normal_range' => '70-100 mg/dL', 'unit' => 'mg/dL'],
                ['test_name' => 'Blood Urea Nitrogen', 'test_code' => 'BUN001', 'rate' => 25, 'normal_range' => '7-20 mg/dL', 'unit' => 'mg/dL'],
                ['test_name' => 'Creatinine', 'test_code' => 'CRE001', 'rate' => 25, 'normal_range' => '0.7-1.3 mg/dL', 'unit' => 'mg/dL'],
                ['test_name' => 'Lipid Profile', 'test_code' => 'LIP001', 'rate' => 50, 'normal_range' => 'Varies', 'unit' => 'mg/dL'],
            ],
            'Urine Analysis' => [
                ['test_name' => 'Urinalysis', 'test_code' => 'URA001', 'rate' => 15, 'normal_range' => 'Normal', 'unit' => '-'],
                ['test_name' => 'Urine Culture', 'test_code' => 'URC001', 'rate' => 40, 'normal_range' => 'No growth', 'unit' => 'CFU/mL'],
            ],
            'Microbiology' => [
                ['test_name' => 'Blood Culture', 'test_code' => 'BLC001', 'rate' => 60, 'normal_range' => 'No growth', 'unit' => '-'],
                ['test_name' => 'Stool Examination', 'test_code' => 'STL001', 'rate' => 25, 'normal_range' => 'Normal', 'unit' => '-'],
            ],
            'Radiology' => [
                ['test_name' => 'X-Ray Chest', 'test_code' => 'XRC001', 'rate' => 50, 'normal_range' => 'Normal', 'unit' => '-'],
                ['test_name' => 'X-Ray Abdomen', 'test_code' => 'XRA001', 'rate' => 60, 'normal_range' => 'Normal', 'unit' => '-'],
                ['test_name' => 'Ultrasound Abdomen', 'test_code' => 'USA001', 'rate' => 100, 'normal_range' => 'Normal', 'unit' => '-'],
                ['test_name' => 'CT Scan Head', 'test_code' => 'CTH001', 'rate' => 300, 'normal_range' => 'Normal', 'unit' => '-'],
                ['test_name' => 'MRI Brain', 'test_code' => 'MRB001', 'rate' => 500, 'normal_range' => 'Normal', 'unit' => '-'],
            ],
        ];

        foreach ($labCategories as $categoryName => $tests) {
            $category = LabCategory::create([
                'category_name' => $categoryName,
                'description' => $categoryName . ' tests',
                'is_active' => true,
            ]);

            foreach ($tests as $test) {
                LabTest::create(array_merge($test, [
                    'category_id' => $category->category_id,
                    'is_active' => true,
                ]));
            }
        }

        // Create drug categories
        $drugCategories = [
            'Antibiotics', 'Analgesics', 'Antipyretics', 'Antihypertensives',
            'Antidiabetics', 'Vitamins', 'Antacids', 'Antihistamines',
            'Cardiovascular', 'Respiratory', 'Gastrointestinal', 'Dermatological',
        ];

        foreach ($drugCategories as $cat) {
            DrugCategory::create([
                'category_name' => $cat,
                'description' => $cat . ' medications',
                'is_active' => true,
            ]);
        }

        // Create drug schedules
        $schedules = [
            ['schedule_name' => 'Schedule H', 'description' => 'Prescription drugs'],
            ['schedule_name' => 'Schedule H1', 'description' => 'Restricted prescription drugs'],
            ['schedule_name' => 'Schedule X', 'description' => 'Narcotic and psychotropic drugs'],
            ['schedule_name' => 'OTC', 'description' => 'Over the counter drugs'],
        ];

        foreach ($schedules as $schedule) {
            DrugSchedule::create($schedule);
        }

        // Create services
        $services = [
            ['service_name' => 'Consultation Fee', 'service_code' => 'CONS001', 'rate' => 100, 'department_id' => 1, 'service_type' => 'opd'],
            ['service_name' => 'Emergency Consultation', 'service_code' => 'EMRG001', 'rate' => 200, 'department_id' => 10, 'service_type' => 'opd'],
            ['service_name' => 'Minor Surgery', 'service_code' => 'SURG001', 'rate' => 500, 'department_id' => 4, 'service_type' => 'procedure'],
            ['service_name' => 'ECG', 'service_code' => 'ECG001', 'rate' => 50, 'department_id' => 2, 'service_type' => 'procedure'],
            ['service_name' => 'Dressing', 'service_code' => 'DRS001', 'rate' => 30, 'department_id' => 1, 'service_type' => 'procedure'],
            ['service_name' => 'Injection', 'service_code' => 'INJ001', 'rate' => 20, 'department_id' => 1, 'service_type' => 'procedure'],
            ['service_name' => 'Physiotherapy Session', 'service_code' => 'PHY001', 'rate' => 75, 'department_id' => 4, 'service_type' => 'procedure'],
            ['service_name' => 'Nebulization', 'service_code' => 'NEB001', 'rate' => 25, 'department_id' => 1, 'service_type' => 'procedure'],
        ];

        foreach ($services as $service) {
            Service::create(array_merge($service, [
                'is_active' => true,
            ]));
        }

        // Create settings
        $settings = [
            ['setting_key' => 'hospital_name', 'setting_value' => 'City General Hospital', 'setting_group' => 'general'],
            ['setting_key' => 'hospital_address', 'setting_value' => '123 Medical Center Drive', 'setting_group' => 'general'],
            ['setting_key' => 'hospital_phone', 'setting_value' => '555-0100', 'setting_group' => 'general'],
            ['setting_key' => 'hospital_email', 'setting_value' => 'info@hims.local', 'setting_group' => 'general'],
            ['setting_key' => 'currency_symbol', 'setting_value' => '$', 'setting_group' => 'billing'],
            ['setting_key' => 'tax_rate', 'setting_value' => '0', 'setting_group' => 'billing'],
            ['setting_key' => 'patient_code_prefix', 'setting_value' => 'PAT', 'setting_group' => 'patients'],
            ['setting_key' => 'bill_prefix', 'setting_value' => 'BILL', 'setting_group' => 'billing'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }

        // Call OPD Masters Seeder
        $this->call(OpdMastersSeeder::class);

        // Call OPD Transaction Seeder
        $this->call(OpdTransactionSeeder::class);

        // Call OPD Configuration Seeder
        $this->call(OpdConfigurationSeeder::class);

        // Call Radiology Seeder
        $this->call(RadiologySeeder::class);

        // Call Roles and Permissions Seeder
        $this->call(RolesAndPermissionsSeeder::class);
    }
}
