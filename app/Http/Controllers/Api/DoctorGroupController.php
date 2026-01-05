<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorGroup;
use App\Models\DoctorGroupMember;
use Illuminate\Http\Request;

class DoctorGroupController extends Controller
{
    /**
     * Display all doctor groups
     */
    public function index(Request $request)
    {
        $query = DoctorGroup::with(['department', 'headDoctor', 'members.doctor']);

        if ($request->group_type) {
            $query->where('group_type', $request->group_type);
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->search) {
            $query->where('group_name', 'like', "%{$request->search}%");
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $groups = $query->orderBy('group_name')->get();

        return response()->json($groups);
    }

    /**
     * Store new doctor group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'group_code' => 'nullable|string|max:20',
            'group_name' => 'required|string|max:100',
            'group_type' => 'nullable|in:unit,department,specialty,team',
            'department_id' => 'nullable|exists:departments,department_id',
            'head_doctor_id' => 'nullable|exists:doctors,doctor_id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            // Initial members
            'members' => 'nullable|array',
            'members.*.doctor_id' => 'required|exists:doctors,doctor_id',
            'members.*.role' => 'nullable|in:head,senior,member,consultant',
            'members.*.can_consult' => 'boolean',
        ]);

        $group = DoctorGroup::create([
            'group_code' => $validated['group_code'] ?? null,
            'group_name' => $validated['group_name'],
            'group_type' => $validated['group_type'] ?? 'unit',
            'department_id' => $validated['department_id'] ?? null,
            'head_doctor_id' => $validated['head_doctor_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Add members if provided
        if (!empty($validated['members'])) {
            foreach ($validated['members'] as $memberData) {
                DoctorGroupMember::create([
                    'group_id' => $group->group_id,
                    'doctor_id' => $memberData['doctor_id'],
                    'role' => $memberData['role'] ?? 'member',
                    'can_consult' => $memberData['can_consult'] ?? true,
                    'is_active' => true,
                ]);
            }
        }

        // Add head doctor as member if not already added
        if ($validated['head_doctor_id'] ?? null) {
            $headExists = DoctorGroupMember::where('group_id', $group->group_id)
                ->where('doctor_id', $validated['head_doctor_id'])
                ->exists();

            if (!$headExists) {
                DoctorGroupMember::create([
                    'group_id' => $group->group_id,
                    'doctor_id' => $validated['head_doctor_id'],
                    'role' => 'head',
                    'can_consult' => true,
                    'is_active' => true,
                ]);
            }
        }

        return response()->json($group->load(['department', 'headDoctor', 'members.doctor']), 201);
    }

    /**
     * Display specific group
     */
    public function show(string $id)
    {
        $group = DoctorGroup::with(['department', 'headDoctor', 'members.doctor'])
            ->findOrFail($id);

        return response()->json($group);
    }

    /**
     * Update group
     */
    public function update(Request $request, string $id)
    {
        $group = DoctorGroup::findOrFail($id);

        $validated = $request->validate([
            'group_code' => 'nullable|string|max:20',
            'group_name' => 'string|max:100',
            'group_type' => 'nullable|in:unit,department,specialty,team',
            'department_id' => 'nullable|exists:departments,department_id',
            'head_doctor_id' => 'nullable|exists:doctors,doctor_id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $group->update($validated);

        return response()->json($group->load(['department', 'headDoctor', 'members.doctor']));
    }

    /**
     * Delete group
     */
    public function destroy(string $id)
    {
        $group = DoctorGroup::findOrFail($id);

        // Check if group has OPD visits or appointments
        if ($group->opdVisits()->exists() || $group->appointments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete group with existing visits or appointments',
            ], 422);
        }

        $group->members()->delete();
        $group->delete();

        return response()->json(['message' => 'Group deleted successfully']);
    }

    /**
     * Add member to group
     */
    public function addMember(Request $request, string $id)
    {
        $group = DoctorGroup::findOrFail($id);

        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'role' => 'nullable|in:head,senior,member,consultant',
            'can_consult' => 'boolean',
        ]);

        // Check if already a member
        $existing = DoctorGroupMember::where('group_id', $group->group_id)
            ->where('doctor_id', $validated['doctor_id'])
            ->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json(['message' => 'Doctor is already a member'], 422);
            }
            // Reactivate
            $existing->update([
                'role' => $validated['role'] ?? 'member',
                'can_consult' => $validated['can_consult'] ?? true,
                'is_active' => true,
            ]);
            return response()->json($existing->load('doctor'));
        }

        $member = DoctorGroupMember::create([
            'group_id' => $group->group_id,
            'doctor_id' => $validated['doctor_id'],
            'role' => $validated['role'] ?? 'member',
            'can_consult' => $validated['can_consult'] ?? true,
            'is_active' => true,
        ]);

        return response()->json($member->load('doctor'), 201);
    }

    /**
     * Remove member from group
     */
    public function removeMember(string $groupId, string $doctorId)
    {
        $member = DoctorGroupMember::where('group_id', $groupId)
            ->where('doctor_id', $doctorId)
            ->firstOrFail();

        $member->update(['is_active' => false]);

        return response()->json(['message' => 'Member removed from group']);
    }

    /**
     * Get consulting doctors for a group
     */
    public function consultingDoctors(string $id)
    {
        $group = DoctorGroup::findOrFail($id);

        $doctors = $group->consultingDoctors()->get();

        return response()->json([
            'group' => $group,
            'doctors' => $doctors,
        ]);
    }
}
