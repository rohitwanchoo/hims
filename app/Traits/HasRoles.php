<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'user_roles',
            'user_id',
            'role_id'
        )->withPivot('assigned_by', 'assigned_at');
    }

    public function primaryRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions',
            'user_id',
            'permission_id'
        )->withPivot('granted', 'assigned_by', 'assigned_at');
    }

    public function assignRole(Role $role, ?int $assignedBy = null): void
    {
        $this->roles()->syncWithoutDetaching([
            $role->role_id => [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]
        ]);
        $this->clearPermissionCache();
    }

    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->role_id);
        $this->clearPermissionCache();
    }

    public function syncRoles(array $roleIds, ?int $assignedBy = null): void
    {
        $syncData = [];
        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = [
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ];
        }
        $this->roles()->sync($syncData);
        $this->clearPermissionCache();
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles()->where('role_code', $roleCode)->exists()
            || ($this->primaryRole && $this->primaryRole->role_code === $roleCode);
    }

    public function hasAnyRole(array $roleCodes): bool
    {
        foreach ($roleCodes as $roleCode) {
            if ($this->hasRole($roleCode)) {
                return true;
            }
        }
        return false;
    }

    public function givePermission(Permission $permission, ?int $assignedBy = null): void
    {
        $this->directPermissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'granted' => true,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]
        ]);
        $this->clearPermissionCache();
    }

    public function revokePermission(Permission $permission): void
    {
        $this->directPermissions()->detach($permission->permission_id);
        $this->clearPermissionCache();
    }

    public function denyPermission(Permission $permission, ?int $assignedBy = null): void
    {
        $this->directPermissions()->syncWithoutDetaching([
            $permission->permission_id => [
                'granted' => false,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
            ]
        ]);
        $this->clearPermissionCache();
    }

    public function hasPermission(string $permissionCode): bool
    {
        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getAllPermissions();
        return isset($permissions[$permissionCode]) && $permissions[$permissionCode] === true;
    }

    public function hasAnyPermission(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if ($this->hasPermission($code)) {
                return true;
            }
        }
        return false;
    }

    public function hasAllPermissions(array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if (!$this->hasPermission($code)) {
                return false;
            }
        }
        return true;
    }

    public function getAllPermissions(): array
    {
        $cacheKey = "user_permissions_{$this->user_id}";

        return Cache::remember($cacheKey, 3600, function () {
            $permissions = [];

            // Get permissions from all roles
            $roles = $this->roles()->with('permissions')->get();
            foreach ($roles as $role) {
                foreach ($role->permissions as $permission) {
                    $permissions[$permission->permission_code] = true;
                }
            }

            // Get permissions from primary role
            if ($this->primaryRole) {
                $this->primaryRole->load('permissions');
                foreach ($this->primaryRole->permissions as $permission) {
                    $permissions[$permission->permission_code] = true;
                }
            }

            // Apply direct permissions (can grant or deny)
            $directPermissions = $this->directPermissions()->get();
            foreach ($directPermissions as $permission) {
                $permissions[$permission->permission_code] = $permission->pivot->granted;
            }

            return $permissions;
        });
    }

    public function getPermissionsByModule(): array
    {
        $allPermissions = $this->getAllPermissions();
        $byModule = [];

        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            if (isset($allPermissions[$permission->permission_code]) && $allPermissions[$permission->permission_code]) {
                $byModule[$permission->module][] = $permission->permission_code;
            }
        }

        return $byModule;
    }

    public function getRoleNames(): array
    {
        $roleNames = [];

        // Get roles from many-to-many relationship
        $roles = $this->roles()->get();
        foreach ($roles as $role) {
            $roleNames[] = $role->role_name;
        }

        // Get primary role if exists and not already in the list
        if ($this->primaryRole && !in_array($this->primaryRole->role_name, $roleNames)) {
            $roleNames[] = $this->primaryRole->role_name;
        }

        return array_unique($roleNames);
    }

    public function getPermissionCodes(): array
    {
        $allPermissions = $this->getAllPermissions();
        $permissionCodes = [];

        foreach ($allPermissions as $code => $granted) {
            if ($granted === true) {
                $permissionCodes[] = $code;
            }
        }

        return $permissionCodes;
    }

    protected function clearPermissionCache(): void
    {
        Cache::forget("user_permissions_{$this->user_id}");
    }

    public function can($ability, $arguments = []): bool
    {
        // Check if it's a permission code format (module.action)
        if (is_string($ability) && str_contains($ability, '.')) {
            return $this->hasPermission($ability);
        }

        // Fall back to Laravel's default authorization
        return parent::can($ability, $arguments);
    }
}
