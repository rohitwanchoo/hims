<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapping between enum role values and role names
     */
    private array $roleMapping = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'receptionist' => 'Receptionist',
        'doctor' => 'Doctor',
        'nurse' => 'Nurse',
        'pharmacist' => 'Pharmacist',
        'lab_technician' => 'Lab Technician',
        'radiologist' => 'Radiologist',
        'accountant' => 'Accountant',
        'mrd' => 'MRD',
        'inventory' => 'Inventory Manager',
        'ot_staff' => 'OT Staff',
    ];

    /**
     * Run the migrations.
     * Sync existing users' role_id based on their enum role value
     */
    public function up(): void
    {
        // Get all users with enum role but no role_id
        $users = DB::table('users')
            ->whereNotNull('role')
            ->whereNull('role_id')
            ->get();

        foreach ($users as $user) {
            if (!isset($this->roleMapping[$user->role])) {
                continue;
            }

            $roleName = $this->roleMapping[$user->role];

            // Find the corresponding role
            $role = DB::table('roles')
                ->where('role_name', $roleName)
                ->first();

            if ($role) {
                // Update user's role_id
                DB::table('users')
                    ->where('user_id', $user->user_id)
                    ->update(['role_id' => $role->role_id]);

                echo "Synced user {$user->user_id}: {$user->role} -> role_id {$role->role_id}\n";
            } else {
                echo "Warning: Role '{$roleName}' not found for user {$user->user_id}\n";
            }
        }

        echo "Role synchronization complete!\n";
    }

    /**
     * Reverse the migrations.
     * This migration is data-only, no schema changes to reverse
     */
    public function down(): void
    {
        // Set role_id to null for users where it was synced
        // This is optional - you might want to keep the data
        DB::table('users')
            ->whereNotNull('role_id')
            ->whereNotNull('role')
            ->update(['role_id' => null]);

        echo "Role synchronization reversed.\n";
    }
};
