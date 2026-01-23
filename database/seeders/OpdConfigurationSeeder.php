<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpdConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hospitalId = 1;

        // Create default OPD configuration
        DB::table('opd_configurations')->insert([
            'hospital_id' => $hospitalId,
            // Entry Card Settings
            'charge_entry_card' => true,
            'entry_card_amount' => 50.00,
            'entry_card_validity_type' => 'yearly',
            'entry_card_validity_days' => null,
            // Consultation Charge Settings
            'consultation_charge_mode' => 'at_registration',
            // Rate Settings
            'rate_type' => 'fixed',
            'allow_rate_override' => false,
            'require_doctor_approval_for_rate_change' => false,
            'use_doctor_wise_rates' => true,
            'use_class_wise_rates' => true,
            // Registration Mode
            'registration_mode' => 'doctor',
            // Appointment Settings
            'appointment_expiry_days' => 1,
            'auto_cancel_expired_appointments' => true,
            'appointment_cancel_time' => '23:59:00',
            // Follow-up Settings
            'enable_free_followup' => true,
            'default_followup_validity_days' => 7,
            'default_free_followup_days' => 3,
            // Token Settings
            'token_generation' => 'auto',
            'token_per_doctor' => true,
            'token_per_department' => false,
            // Other Settings
            'mandatory_vitals' => false,
            'mandatory_chief_complaint' => true,
            'allow_multiple_visits_per_day' => false,
            'require_payment_before_consultation' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get doctor IDs and class IDs
        $doctors = DB::table('doctors')->where('hospital_id', $hospitalId)->get();
        $classes = DB::table('classes')->get();

        // Create doctor OPD rates
        foreach ($doctors as $doctor) {
            // Standard rates (no class)
            $baseRate = $doctor->consultation_fee ?? 100;

            // New visit - normal
            DB::table('doctor_opd_rates')->insert([
                'hospital_id' => $hospitalId,
                'doctor_id' => $doctor->doctor_id,
                'class_id' => null,
                'visit_type' => 'new',
                'charge_type' => 'normal',
                'rate' => $baseRate,
                'free_followup_rate' => 0,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Follow-up visit - normal
            DB::table('doctor_opd_rates')->insert([
                'hospital_id' => $hospitalId,
                'doctor_id' => $doctor->doctor_id,
                'class_id' => null,
                'visit_type' => 'followup',
                'charge_type' => 'normal',
                'rate' => $baseRate * 0.5, // 50% of new visit
                'free_followup_rate' => 0,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Emergency - day
            DB::table('doctor_opd_rates')->insert([
                'hospital_id' => $hospitalId,
                'doctor_id' => $doctor->doctor_id,
                'class_id' => null,
                'visit_type' => 'new',
                'charge_type' => 'day_emergency',
                'rate' => $baseRate * 1.5, // 150% of normal
                'free_followup_rate' => 0,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Emergency - night
            DB::table('doctor_opd_rates')->insert([
                'hospital_id' => $hospitalId,
                'doctor_id' => $doctor->doctor_id,
                'class_id' => null,
                'visit_type' => 'new',
                'charge_type' => 'night_emergency',
                'rate' => $baseRate * 2, // 200% of normal
                'free_followup_rate' => 0,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create doctor groups (units)
        $groups = [
            [
                'group_code' => 'MED-UNIT1',
                'group_name' => 'Medicine Unit 1',
                'group_type' => 'unit',
                'department_id' => 1, // General Medicine
            ],
            [
                'group_code' => 'CARD-TEAM',
                'group_name' => 'Cardiology Team',
                'group_type' => 'team',
                'department_id' => 2, // Cardiology
            ],
            [
                'group_code' => 'SURG-UNIT',
                'group_name' => 'Surgery Unit',
                'group_type' => 'unit',
                'department_id' => 4, // Orthopedics
            ],
        ];

        foreach ($groups as $group) {
            $groupId = DB::table('doctor_groups')->insertGetId(array_merge($group, [
                'hospital_id' => $hospitalId,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Add doctors from the department to the group
            $deptDoctors = DB::table('doctors')
                ->where('hospital_id', $hospitalId)
                ->where('department_id', $group['department_id'])
                ->get();

            $isFirst = true;
            foreach ($deptDoctors as $doctor) {
                DB::table('doctor_group_members')->insert([
                    'group_id' => $groupId,
                    'doctor_id' => $doctor->doctor_id,
                    'role' => $isFirst ? 'head' : 'member',
                    'can_consult' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Set first doctor as head
                if ($isFirst) {
                    DB::table('doctor_groups')
                        ->where('group_id', $groupId)
                        ->update(['head_doctor_id' => $doctor->doctor_id]);
                    $isFirst = false;
                }
            }
        }

        // Create OPD time slots for doctors
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach ($doctors as $doctor) {
            // Morning session (9 AM - 1 PM)
            foreach ($days as $day) {
                if ($day === 'saturday') continue; // Skip Saturday afternoon

                DB::table('opd_time_slots')->insert([
                    'hospital_id' => $hospitalId,
                    'doctor_id' => $doctor->doctor_id,
                    'department_id' => $doctor->department_id,
                    'day_of_week' => $day,
                    'start_time' => '09:00:00',
                    'end_time' => '13:00:00',
                    'slot_duration_minutes' => 15,
                    'max_patients_per_slot' => 1,
                    'max_patients_per_session' => 20,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Evening session (5 PM - 8 PM) - Monday to Friday only
            $eveningDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            foreach ($eveningDays as $day) {
                DB::table('opd_time_slots')->insert([
                    'hospital_id' => $hospitalId,
                    'doctor_id' => $doctor->doctor_id,
                    'department_id' => $doctor->department_id,
                    'day_of_week' => $day,
                    'start_time' => '17:00:00',
                    'end_time' => '20:00:00',
                    'slot_duration_minutes' => 15,
                    'max_patients_per_slot' => 1,
                    'max_patients_per_session' => 12,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('OPD Configuration seeded successfully!');
    }
}
