<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Role;

class UserObserver
{
    /**
     * Mapping between enum role values and role names
     * This keeps the old enum 'role' field synchronized with the new 'role_id' FK
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
     * Handle the User "creating" event.
     * Sync role_id based on enum role when creating a new user
     */
    public function creating(User $user): void
    {
        // If role is set but role_id is not, sync it
        if ($user->role && !$user->role_id) {
            $this->syncRoleIdFromEnum($user);
        }

        // If role_id is set but role is not, sync it
        if ($user->role_id && !$user->role) {
            $this->syncEnumFromRoleId($user);
        }
    }

    /**
     * Handle the User "updating" event.
     * Keep enum role and role_id synchronized on updates
     */
    public function updating(User $user): void
    {
        // Check if role enum changed
        if ($user->isDirty('role')) {
            $this->syncRoleIdFromEnum($user);
        }

        // Check if role_id FK changed
        if ($user->isDirty('role_id')) {
            $this->syncEnumFromRoleId($user);
        }
    }

    /**
     * Sync role_id foreign key based on enum role value
     */
    private function syncRoleIdFromEnum(User $user): void
    {
        if (!$user->role || !isset($this->roleMapping[$user->role])) {
            return;
        }

        $roleName = $this->roleMapping[$user->role];
        $role = Role::where('role_name', $roleName)->first();

        if ($role) {
            $user->role_id = $role->role_id;
        }
    }

    /**
     * Sync enum role based on role_id foreign key
     */
    private function syncEnumFromRoleId(User $user): void
    {
        if (!$user->role_id) {
            return;
        }

        $role = Role::find($user->role_id);

        if (!$role) {
            return;
        }

        // Reverse lookup: find enum value for role name
        $enumValue = array_search($role->role_name, $this->roleMapping);

        if ($enumValue !== false) {
            $user->role = $enumValue;
        }
    }
}
