<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin user
        User::withoutGlobalScopes()->updateOrCreate(
            ['username' => 'superadmin'],
            [
                'full_name' => 'Super Administrator',
                'email' => 'superadmin@hims.com',
                'password' => Hash::make('superadmin123'),
                'role' => 'admin',
                'is_active' => true,
                'is_super_admin' => true,
                'hospital_id' => null, // Super admin doesn't belong to any hospital
            ]
        );

        $this->command->info('Super Admin created: superadmin / superadmin123');
    }
}
