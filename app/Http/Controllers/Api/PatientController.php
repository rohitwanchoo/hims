<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'phone' => 'nullable|string|max:15',
            'patient_type' => 'nullable|in:Staff,Doctor',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:15',
            'blood_group' => 'nullable|string|max:5',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $validatedData['hospital_id'] = auth()->user()->hospital_id;
        $validatedData['patient_id'] = $this->generatePatientId();

        $patient = Patient::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully',
            'data' => $patient
        ], 201);
    }

    public function show(Patient $patient)
    {
        return response()->json([
            'success' => true,
            'data' => $patient
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:Male,Female,Other',
            'phone' => 'nullable|string|max:15',
            'patient_type' => 'nullable|in:Staff,Doctor',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:15',
            'blood_group' => 'nullable|string|max:5',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $patient->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Patient updated successfully',
            'data' => $patient
        ]);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Patient deleted successfully'
        ]);
    }

    private function generatePatientId()
    {
        $hospitalId = auth()->user()->hospital_id;
        $lastPatient = Patient::where('hospital_id', $hospitalId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastPatient ? (int) substr($lastPatient->patient_id, -4) + 1 : 1;
        
        return 'PAT' . str_pad($hospitalId, 2, '0', STR_PAD_LEFT) . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}