<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleHospitalSeeder extends Seeder
{
    public function run(): void
    {
        // Create a sample hospital
        $hospital = Hospital::firstOrCreate(
            ['code' => 'HOSP001'],
            [
                'name' => 'City General Hospital',
                'type' => 'general',
                'address' => '123 Healthcare Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'pincode' => '400001',
                'phone' => '+91 22 1234 5678',
                'email' => 'info@citygeneralhospital.com',
                'subscription_plan' => 'premium',
                'subscription_start' => now(),
                'subscription_end' => now()->addYear(),
                'is_active' => true,
            ]
        );

        // Assign existing admin user to this hospital (if exists and not super admin)
        User::withoutGlobalScopes()
            ->where('is_super_admin', false)
            ->whereNull('hospital_id')
            ->update(['hospital_id' => $hospital->hospital_id]);

        // Also update other tables if they don't have hospital_id
        $tables = [
            'patients', 'doctors', 'departments', 'appointments', 'opd_visits',
            'ipd_admissions', 'wards', 'beds', 'lab_categories', 'lab_tests',
            'lab_orders', 'drug_categories', 'drugs', 'drug_batches',
            'pharmacy_sales', 'services', 'bills', 'payments'
        ];

        foreach ($tables as $table) {
            \DB::table($table)->whereNull('hospital_id')->update(['hospital_id' => $hospital->hospital_id]);
        }

        $this->command->info("Sample hospital created: {$hospital->name}");
        $this->command->info("Existing data assigned to hospital ID: {$hospital->hospital_id}");
    }
}
