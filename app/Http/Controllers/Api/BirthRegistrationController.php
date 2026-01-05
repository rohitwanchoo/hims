<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BirthRegistration;
use Illuminate\Http\Request;

class BirthRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = BirthRegistration::with(['mother', 'attendingDoctor']);

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('date_of_birth', [$request->from_date, $request->to_date]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('child_name', 'like', "%{$search}%")
                    ->orWhere('mother_name', 'like', "%{$search}%");
            });
        }

        $registrations = $query->orderBy('date_of_birth', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($registrations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ipd_id' => 'nullable|exists:ipd_admissions,ipd_id',
            'mother_patient_id' => 'required|exists:patients,patient_id',
            'father_name' => 'required|string|max:100',
            'father_aadhar' => 'nullable|string|size:12',
            'mother_name' => 'required|string|max:100',
            'mother_aadhar' => 'nullable|string|size:12',
            'mother_age' => 'nullable|integer|min:15|max:60',
            'date_of_birth' => 'required|date|before_or_equal:today',
            'time_of_birth' => 'required',
            'place_of_birth' => 'nullable|string|max:100',
            'birth_type' => 'required|in:live,stillbirth',
            'gender' => 'required|in:male,female,other',
            'weight_kg' => 'nullable|numeric|min:0.5|max:10',
            'delivery_type' => 'required|in:normal,cesarean,assisted,water',
            'attending_doctor_id' => 'required|exists:doctors,doctor_id',
            'child_name' => 'nullable|string|max:100',
            'informant_name' => 'required|string|max:100',
            'informant_relation' => 'required|string|max:50',
            'informant_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
        ]);

        $lastReg = BirthRegistration::whereYear('created_at', now()->year)->count();
        $registrationNumber = 'BR' . now()->format('Y') . str_pad($lastReg + 1, 6, '0', STR_PAD_LEFT);

        // Map mother_age to mother_age_at_delivery
        $data = $validated;
        if (isset($data['mother_age'])) {
            $data['mother_age_at_delivery'] = $data['mother_age'];
            unset($data['mother_age']);
        }

        $registration = BirthRegistration::create([
            ...$data,
            'registration_number' => $registrationNumber,
            'status' => 'registered',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Birth registration created successfully',
            'registration' => $registration,
        ], 201);
    }

    public function show(BirthRegistration $registration)
    {
        return response()->json([
            'registration' => $registration->load(['mother', 'attendingDoctor', 'ipdAdmission']),
        ]);
    }

    public function update(Request $request, BirthRegistration $registration)
    {
        $validated = $request->validate([
            'child_name' => 'nullable|string|max:100',
            'father_name' => 'sometimes|string|max:100',
            'father_aadhar' => 'nullable|string|size:12',
            'informant_name' => 'sometimes|string|max:100',
            'informant_relation' => 'sometimes|string|max:50',
            'permanent_address' => 'nullable|string',
        ]);

        $registration->update($validated);

        return response()->json([
            'message' => 'Birth registration updated successfully',
            'registration' => $registration,
        ]);
    }

    public function issueCertificate(BirthRegistration $registration)
    {
        if ($registration->certificate_number) {
            return response()->json([
                'message' => 'Certificate already issued',
            ], 422);
        }

        $lastCert = BirthRegistration::whereNotNull('certificate_number')
            ->whereYear('created_at', now()->year)
            ->count();

        $certificateNumber = 'BC' . now()->format('Y') . str_pad($lastCert + 1, 6, '0', STR_PAD_LEFT);

        $registration->update([
            'certificate_number' => $certificateNumber,
            'certificate_issued_at' => now(),
            'status' => 'certificate_issued',
        ]);

        return response()->json([
            'message' => 'Certificate issued successfully',
            'certificate_number' => $certificateNumber,
            'registration' => $registration,
        ]);
    }

    public function registerWithGovernment(Request $request, BirthRegistration $registration)
    {
        $validated = $request->validate([
            'govt_registration_number' => 'required|string|max:50',
            'govt_registration_date' => 'required|date',
        ]);

        $registration->update([
            'is_govt_registered' => true,
            'govt_registration_number' => $validated['govt_registration_number'],
            'govt_registration_date' => $validated['govt_registration_date'],
        ]);

        return response()->json([
            'message' => 'Government registration recorded',
            'registration' => $registration,
        ]);
    }
}
