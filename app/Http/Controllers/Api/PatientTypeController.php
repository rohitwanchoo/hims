<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PatientTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = PatientType::withCount('patients as usage_count');

        if ($request->has('search')) {
            $query->where('patient_type_name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('patient_type_name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_type_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $patientType = PatientType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Patient type created successfully',
            'data' => $patientType
        ], 201);
    }

    public function show(PatientType $patientType)
    {
        return response()->json($patientType);
    }

    public function update(Request $request, PatientType $patientType)
    {
        $validated = $request->validate([
            'patient_type_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $patientType->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Patient type updated successfully',
            'data' => $patientType
        ]);
    }

    public function destroy(PatientType $patientType)
    {
        if ($patientType->patients()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete patient type. It is being used by patients.'
            ], 422);
        }

        $patientType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient type deleted successfully'
        ]);
    }

    public function active()
    {
        return response()->json(
            PatientType::where('is_active', true)->orderBy('patient_type_name')->get(['patient_type_id', 'patient_type_name'])
        );
    }
}
