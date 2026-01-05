<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeathRegistration;
use Illuminate\Http\Request;

class DeathRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = DeathRegistration::with(['patient', 'certifyingDoctor']);

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('date_of_death', [$request->from_date, $request->to_date]);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_mlc_case')) {
            $query->where('is_mlc_case', $request->boolean('is_mlc_case'));
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('deceased_name', 'like', "%{$search}%");
            });
        }

        $registrations = $query->orderBy('date_of_death', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($registrations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'ipd_id' => 'nullable|exists:ipd_admissions,ipd_id',
            'deceased_name' => 'required|string|max:100',
            'deceased_age' => 'nullable|integer|min:0',
            'deceased_gender' => 'required|in:male,female,other',
            'deceased_aadhar' => 'nullable|string|max:12',
            'date_of_death' => 'required|date|before_or_equal:now',
            'time_of_death' => 'required',
            'place_of_death' => 'nullable|string|max:100',
            'cause_of_death_immediate' => 'required|string',
            'cause_of_death_antecedent' => 'nullable|string',
            'cause_of_death_underlying' => 'nullable|string',
            'manner_of_death' => 'required|in:natural,accident,suicide,homicide,pending_investigation,unknown',
            'is_autopsy_performed' => 'nullable|boolean',
            'autopsy_findings' => 'nullable|string',
            'is_mlc_case' => 'nullable|boolean',
            'mlc_number' => 'nullable|string|max:50',
            'certifying_doctor_id' => 'required|exists:doctors,doctor_id',
            'informant_name' => 'required|string|max:100',
            'informant_relation' => 'required|string|max:50',
            'informant_address' => 'nullable|string',
            'permanent_address' => 'nullable|string',
        ]);

        $lastReg = DeathRegistration::whereYear('created_at', now()->year)->count();
        $registrationNumber = 'DR' . now()->format('Y') . str_pad($lastReg + 1, 6, '0', STR_PAD_LEFT);

        // Map form fields to database columns
        $data = [
            'patient_id' => $validated['patient_id'],
            'ipd_id' => $validated['ipd_id'] ?? null,
            'deceased_name' => $validated['deceased_name'],
            'deceased_aadhar' => $validated['deceased_aadhar'] ?? null,
            'gender' => $validated['deceased_gender'],
            'age_years' => $validated['deceased_age'] ?? 0,
            'nationality' => 'Indian',
            'date_of_death' => $validated['date_of_death'],
            'time_of_death' => $validated['time_of_death'],
            'place_of_death' => 'hospital',
            'place_details' => $validated['place_of_death'] ?? null,
            'cause_of_death_immediate' => $validated['cause_of_death_immediate'],
            'cause_of_death_antecedent' => $validated['cause_of_death_antecedent'] ?? null,
            'cause_of_death_underlying' => $validated['cause_of_death_underlying'] ?? null,
            'manner_of_death' => $validated['manner_of_death'],
            'is_autopsy_performed' => $validated['is_autopsy_performed'] ?? false,
            'autopsy_findings' => $validated['autopsy_findings'] ?? null,
            'is_mlc_case' => $validated['is_mlc_case'] ?? false,
            'mlc_number' => $validated['mlc_number'] ?? null,
            'certifying_doctor_id' => $validated['certifying_doctor_id'],
            'informant_name' => $validated['informant_name'],
            'informant_relation' => $validated['informant_relation'],
            'informant_address' => $validated['informant_address'] ?? null,
            'permanent_address' => $validated['permanent_address'] ?? 'N/A',
            'permanent_city' => 'N/A',
            'permanent_district' => 'N/A',
            'permanent_state' => 'N/A',
            'permanent_pincode' => '000000',
            'is_medically_certified' => true,
            'was_pregnant' => 'na',
            'pregnancy_contribution' => false,
            'is_govt_registered' => false,
            'registration_number' => $registrationNumber,
            'status' => 'registered',
            'created_by' => auth()->id(),
        ];

        $registration = DeathRegistration::create($data);

        return response()->json([
            'message' => 'Death registration created successfully',
            'registration' => $registration,
        ], 201);
    }

    public function show(DeathRegistration $registration)
    {
        return response()->json([
            'registration' => $registration->load(['patient', 'certifyingDoctor', 'ipdAdmission']),
        ]);
    }

    public function update(Request $request, DeathRegistration $registration)
    {
        $validated = $request->validate([
            'cause_of_death_immediate' => 'sometimes|string',
            'cause_of_death_antecedent' => 'nullable|string',
            'cause_of_death_underlying' => 'nullable|string',
            'is_autopsy_performed' => 'nullable|boolean',
            'autopsy_findings' => 'nullable|string',
            'informant_name' => 'sometimes|string|max:100',
            'informant_relation' => 'sometimes|string|max:50',
        ]);

        $registration->update($validated);

        return response()->json([
            'message' => 'Death registration updated successfully',
            'registration' => $registration,
        ]);
    }

    public function issueCertificate(DeathRegistration $registration)
    {
        if ($registration->certificate_number) {
            return response()->json([
                'message' => 'Certificate already issued',
            ], 422);
        }

        $lastCert = DeathRegistration::whereNotNull('certificate_number')
            ->whereYear('created_at', now()->year)
            ->count();

        $certificateNumber = 'DC' . now()->format('Y') . str_pad($lastCert + 1, 6, '0', STR_PAD_LEFT);

        $registration->update([
            'certificate_number' => $certificateNumber,
            'certificate_issued_at' => now(),
            'status' => 'certificate_issued',
        ]);

        return response()->json([
            'message' => 'Death certificate issued successfully',
            'certificate_number' => $certificateNumber,
            'registration' => $registration,
        ]);
    }

    public function registerWithGovernment(Request $request, DeathRegistration $registration)
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
