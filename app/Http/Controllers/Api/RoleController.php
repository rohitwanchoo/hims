<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::with('permissions');

        if ($request->boolean('system_only')) {
            $query->whereNull('hospital_id');
        }

        if ($request->boolean('custom_only')) {
            $query->whereNotNull('hospital_id');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $roles = $query->orderBy('role_name')->get();

        return response()->json(['roles' => $roles]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_code' => 'required|string|max:50|unique:roles,role_code',
            'role_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,permission_id',
        ]);

        return DB::transaction(function () use ($validated) {
            $role = Role::create([
                'role_code' => $validated['role_code'],
                'role_name' => $validated['role_name'],
                'description' => $validated['description'] ?? null,
                'is_system_role' => false,
                'is_active' => true,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->attach($validated['permissions']);
            }

            return response()->json([
                'message' => 'Role created successfully',
                'role' => $role->load('permissions'),
            ], 201);
        });
    }

    public function show(Role $role)
    {
        return response()->json([
            'role' => $role->load('permissions', 'users'),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->is_system_role) {
            return response()->json([
                'message' => 'Cannot modify system roles',
            ], 403);
        }

        $validated = $request->validate([
            'role_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,permission_id',
        ]);

        return DB::transaction(function () use ($role, $validated) {
            $role->update([
                'role_name' => $validated['role_name'] ?? $role->role_name,
                'description' => $validated['description'] ?? $role->description,
                'is_active' => $validated['is_active'] ?? $role->is_active,
            ]);

            if (isset($validated['permissions'])) {
                $role->syncPermissions($validated['permissions']);
            }

            return response()->json([
                'message' => 'Role updated successfully',
                'role' => $role->load('permissions'),
            ]);
        });
    }

    public function destroy(Role $role)
    {
        if ($role->is_system_role) {
            return response()->json([
                'message' => 'Cannot delete system roles',
            ], 403);
        }

        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    public function permissions()
    {
        $permissions = Permission::orderBy('module')
            ->orderBy('permission_name')
            ->get()
            ->groupBy('module');

        return response()->json(['permissions' => $permissions]);
    }

    public function assignToUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,user_id',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,role_id',
        ]);

        DB::table('user_roles')->where('user_id', $validated['user_id'])->delete();

        foreach ($validated['role_ids'] as $roleId) {
            DB::table('user_roles')->insert([
                'user_id' => $validated['user_id'],
                'role_id' => $roleId,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Roles assigned successfully',
        ]);
    }
}
