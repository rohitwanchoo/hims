<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::withCount('doctors');

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $departments = $query->orderBy('department_name')->get();

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:20|unique:departments',
            'department_name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $department = Department::create($validated);

        return response()->json($department, 201);
    }

    public function show(Department $department)
    {
        return response()->json($department->load('doctors'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return response()->json($department);
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully']);
    }
}
