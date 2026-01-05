<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\Request;

class WardController extends Controller
{
    public function index()
    {
        return response()->json(
            Ward::with('department')->withCount('beds')->where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ward_code' => 'required|unique:wards',
            'ward_name' => 'required',
            'ward_type' => 'nullable|string',
            'total_beds' => 'required|integer|min:1',
            'charges_per_day' => 'required|numeric|min:0',
            'department_id' => 'nullable|exists:departments,department_id',
        ]);

        $ward = Ward::create($validated);
        return response()->json($ward, 201);
    }

    public function show(string $id)
    {
        $ward = Ward::with(['department', 'beds'])->findOrFail($id);
        return response()->json($ward);
    }

    public function update(Request $request, string $id)
    {
        $ward = Ward::findOrFail($id);

        $validated = $request->validate([
            'ward_code' => 'required|unique:wards,ward_code,' . $id . ',ward_id',
            'ward_name' => 'required',
            'ward_type' => 'nullable|string',
            'total_beds' => 'required|integer|min:1',
            'charges_per_day' => 'required|numeric|min:0',
            'department_id' => 'nullable|exists:departments,department_id',
            'is_active' => 'boolean',
        ]);

        $ward->update($validated);
        return response()->json($ward);
    }

    public function destroy(string $id)
    {
        $ward = Ward::findOrFail($id);
        $ward->update(['is_active' => false]);
        return response()->json(['message' => 'Ward deactivated']);
    }
}
