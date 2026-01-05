<?php

namespace App\Models;

use App\Traits\BelongsToHospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use BelongsToHospital;

    protected $primaryKey = 'role_id';

    protected $fillable = [
        'hospital_id',
        'role_code',
        'role_name',
        'description',
        'is_system_role',
        'is_active',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )->withPivot('assigned_by', 'assigned_at');
    }

    public function hasPermission(string $permissionCode): bool
    {
        return $this->permissions()->where('permission_code', $permissionCode)->exists();
    }

    public function givePermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->permission_id]);
    }

    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->permission_id);
    }

    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }
}
