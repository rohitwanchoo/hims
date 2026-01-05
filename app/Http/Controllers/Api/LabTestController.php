<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    public function index()
    {
        return response()->json(
            LabTest::with('category')->where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_code' => 'required|unique:lab_tests',
            'test_name' => 'required',
            'category_id' => 'required|exists:lab_categories,category_id',
            'rate' => 'required|numeric|min:0',
            'sample_type' => 'nullable|string',
            'normal_range' => 'nullable|string',
            'unit' => 'nullable|string',
            'tat_hours' => 'nullable|integer',
        ]);

        $test = LabTest::create($validated);
        return response()->json($test, 201);
    }

    public function show(string $id)
    {
        $test = LabTest::with('category')->findOrFail($id);
        return response()->json($test);
    }

    public function update(Request $request, string $id)
    {
        $test = LabTest::findOrFail($id);

        $validated = $request->validate([
            'test_code' => 'required|unique:lab_tests,test_code,' . $id . ',test_id',
            'test_name' => 'required',
            'category_id' => 'required|exists:lab_categories,category_id',
            'rate' => 'required|numeric|min:0',
            'sample_type' => 'nullable|string',
            'normal_range' => 'nullable|string',
            'unit' => 'nullable|string',
            'tat_hours' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $test->update($validated);
        return response()->json($test);
    }

    public function destroy(string $id)
    {
        $test = LabTest::findOrFail($id);
        $test->update(['is_active' => false]);
        return response()->json(['message' => 'Test deactivated']);
    }
}
