<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = Doctor::with('department');

        if ($request->search) {
            $query->where('full_name', 'like', "%{$request->search}%");
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $doctors = $query->orderBy('full_name')->get();

        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_code' => 'required|string|max:20|unique:doctors',
            'full_name' => 'required|string|max:100',
            'qualification' => 'nullable|string|max:200',
            'specialization' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,department_id',
            'mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'consultation_fee' => 'nullable|numeric|min:0',
            'opd_available' => 'boolean',
            'ipd_available' => 'boolean',
        ]);

        $doctor = Doctor::create($validated);

        return response()->json($doctor->load('department'), 201);
    }

    public function show(Doctor $doctor)
    {
        return response()->json($doctor->load('department'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:100',
            'qualification' => 'nullable|string|max:200',
            'specialization' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,department_id',
            'mobile' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'consultation_fee' => 'nullable|numeric|min:0',
            'opd_available' => 'boolean',
            'ipd_available' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $doctor->update($validated);

        return response()->json($doctor->load('department'));
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return response()->json(['message' => 'Doctor deleted successfully']);
    }
}
