<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with(['insuranceCompany', 'patientClass', 'referenceDoctor']);

        // Search by name, pcd, mobile, aadhaar, or company/TPA number
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('pcd', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('aadhaar_number', 'like', "%{$search}%")
                  ->orWhere('tpa_id', 'like', "%{$search}%")
                  ->orWhere('insurance_policy_number', 'like', "%{$search}%");
            });
        }

        // Filter by gender
        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        // Filter by class
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        // Filter by category (general/vip)
        if ($request->category) {
            $query->where('category', $request->category);
        }

        // Filter by BPL status
        if ($request->is_bpl !== null) {
            $query->where('is_bpl', $request->is_bpl);
        }

        // Filter by foreigner status
        if ($request->is_foreigner !== null) {
            $query->where('is_foreigner', $request->is_foreigner);
        }

        // Filter by urgent status
        if ($request->is_urgent !== null) {
            $query->where('is_urgent', $request->is_urgent);
        }

        // Filter by reference doctor
        if ($request->reference_doctor_id) {
            $query->where('reference_doctor_id', $request->reference_doctor_id);
        }

        // Filter by active status
        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $patients = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Basic Information
            'patient_name' => 'required|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'relation' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer|min:0',
            'age_unit' => 'nullable|in:days,months,years',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'blood_group' => 'nullable|string|max:5',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|in:general,vip',
            'charges_type' => 'nullable|in:normal,day_emergency,night_emergency',

            // Contact Information
            'mobile' => 'nullable|string|max:15',
            'subscribe_sms' => 'boolean',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',

            // Current Address
            'address' => 'nullable|string',
            'area' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',

            // Permanent Address
            'permanent_address' => 'nullable|string',
            'permanent_city' => 'nullable|string|max:50',
            'permanent_district' => 'nullable|string|max:50',
            'permanent_state' => 'nullable|string|max:50',
            'permanent_country' => 'nullable|string|max:50',
            'permanent_pincode' => 'nullable|string|max:10',

            // ID Documents
            'aadhaar_number' => 'nullable|string|max:12',
            'pan_number' => 'nullable|string|max:10',

            // Additional Information
            'occupation' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'is_bpl' => 'boolean',
            'is_foreigner' => 'boolean',
            'is_urgent' => 'boolean',

            // Medical Information
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',

            // Emergency Contact
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:15',
            'emergency_contact_relation' => 'nullable|string|max:50',

            // Insurance/TPA Information
            'insurance_id' => 'nullable|exists:insurance_companies,insurance_id',
            'insurance_policy_number' => 'nullable|string|max:50',
            'cashless_referral_no' => 'nullable|string|max:50',
            'tpa_id' => 'nullable|string|max:50',
            'relation_with_ip' => 'nullable|string|max:50',
            'ip_name' => 'nullable|string|max:100',

            // Class & Reference Doctor
            'class_id' => 'nullable|exists:classes,class_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',

            // Registration & Documents
            'registration_date' => 'nullable|date',
            'photo' => 'nullable|string|max:255',
            'documents' => 'nullable|array',
        ]);

        // Generate patient code
        $lastPatient = Patient::orderBy('patient_id', 'desc')->first();
        $nextId = $lastPatient ? $lastPatient->patient_id + 1 : 1;
        $validated['pcd'] = 'PAT' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        // Set registration date if not provided
        if (empty($validated['registration_date'])) {
            $validated['registration_date'] = now()->toDateString();
        }

        // Set default values
        $validated['category'] = $validated['category'] ?? 'general';
        $validated['charges_type'] = $validated['charges_type'] ?? 'normal';
        $validated['nationality'] = $validated['nationality'] ?? 'Indian';
        $validated['country'] = $validated['country'] ?? 'India';
        $validated['permanent_country'] = $validated['permanent_country'] ?? 'India';

        $patient = Patient::create($validated);

        return response()->json($patient->load(['insuranceCompany', 'patientClass', 'referenceDoctor']), 201);
    }

    public function show(Patient $patient)
    {
        return response()->json($patient->load([
            'insuranceCompany',
            'patientClass',
            'patientClass.client',
            'referenceDoctor',
            'opdVisits' => function ($query) {
                $query->orderBy('visit_date', 'desc')->limit(5);
            },
            'ipdAdmissions' => function ($query) {
                $query->orderBy('admission_date', 'desc')->limit(5);
            },
        ]));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            // Basic Information
            'patient_name' => 'sometimes|required|string|max:100',
            'guardian_name' => 'nullable|string|max:100',
            'relation' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer|min:0',
            'age_unit' => 'nullable|in:days,months,years',
            'gender' => 'sometimes|required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'blood_group' => 'nullable|string|max:5',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|in:general,vip',
            'charges_type' => 'nullable|in:normal,day_emergency,night_emergency',

            // Contact Information
            'mobile' => 'nullable|string|max:15',
            'subscribe_sms' => 'boolean',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',

            // Current Address
            'address' => 'nullable|string',
            'area' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',

            // Permanent Address
            'permanent_address' => 'nullable|string',
            'permanent_city' => 'nullable|string|max:50',
            'permanent_district' => 'nullable|string|max:50',
            'permanent_state' => 'nullable|string|max:50',
            'permanent_country' => 'nullable|string|max:50',
            'permanent_pincode' => 'nullable|string|max:10',

            // ID Documents
            'aadhaar_number' => 'nullable|string|max:12',
            'pan_number' => 'nullable|string|max:10',

            // Additional Information
            'occupation' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'is_bpl' => 'boolean',
            'is_foreigner' => 'boolean',
            'is_urgent' => 'boolean',

            // Medical Information
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',

            // Emergency Contact
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:15',
            'emergency_contact_relation' => 'nullable|string|max:50',

            // Insurance/TPA Information
            'insurance_id' => 'nullable|exists:insurance_companies,insurance_id',
            'insurance_policy_number' => 'nullable|string|max:50',
            'cashless_referral_no' => 'nullable|string|max:50',
            'tpa_id' => 'nullable|string|max:50',
            'relation_with_ip' => 'nullable|string|max:50',
            'ip_name' => 'nullable|string|max:100',

            // Class & Reference Doctor
            'class_id' => 'nullable|exists:classes,class_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',

            // Documents
            'photo' => 'nullable|string|max:255',
            'documents' => 'nullable|array',

            // Status
            'is_active' => 'boolean',
        ]);

        $patient->update($validated);

        return response()->json($patient->load(['insuranceCompany', 'patientClass', 'referenceDoctor']));
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json(['message' => 'Patient deleted successfully']);
    }

    /**
     * Get patient's OPD visits
     */
    public function visits(Patient $patient)
    {
        $visits = $patient->opdVisits()
            ->with(['doctor', 'department', 'referenceDoctor', 'patientClass'])
            ->orderBy('visit_date', 'desc')
            ->get();

        return response()->json($visits);
    }

    /**
     * Get patient's IPD admissions
     */
    public function admissions(Patient $patient)
    {
        $admissions = $patient->ipdAdmissions()
            ->with(['department', 'ward', 'bed', 'patientClass'])
            ->orderBy('admission_date', 'desc')
            ->get();

        return response()->json($admissions);
    }

    /**
     * Get patient's vaccination records
     */
    public function vaccinations(Patient $patient)
    {
        $vaccinations = $patient->vaccinations()
            ->with('vaccination')
            ->orderBy('scheduled_date')
            ->get();

        return response()->json($vaccinations);
    }

    /**
     * Get patient history (combined visits, admissions, bills)
     */
    public function history(Patient $patient)
    {
        $history = [
            'patient' => $patient->load(['insuranceCompany', 'patientClass', 'referenceDoctor']),
            'opd_visits' => $patient->opdVisits()
                ->with(['doctor', 'department'])
                ->orderBy('visit_date', 'desc')
                ->limit(10)
                ->get(),
            'ipd_admissions' => $patient->ipdAdmissions()
                ->with(['department', 'ward'])
                ->orderBy('admission_date', 'desc')
                ->limit(10)
                ->get(),
            'bills' => $patient->bills()
                ->orderBy('bill_date', 'desc')
                ->limit(10)
                ->get(),
            'lab_orders' => $patient->labOrders()
                ->with('details.test')
                ->orderBy('order_date', 'desc')
                ->limit(10)
                ->get(),
            'vaccinations' => $patient->vaccinations()
                ->with('vaccination')
                ->orderBy('scheduled_date')
                ->get(),
        ];

        return response()->json($history);
    }

    /**
     * Search patients by various criteria
     */
    public function search(Request $request)
    {
        $query = Patient::with(['patientClass']);

        // Search by CR No (pcd)
        if ($request->cr_no) {
            $query->where('pcd', 'like', "%{$request->cr_no}%");
        }

        // Search by name
        if ($request->name) {
            $query->where('patient_name', 'like', "%{$request->name}%");
        }

        // Search by mobile
        if ($request->mobile) {
            $query->where('mobile', 'like', "%{$request->mobile}%");
        }

        // Search by company/TPA number
        if ($request->company_no) {
            $query->where(function ($q) use ($request) {
                $q->where('tpa_id', 'like', "%{$request->company_no}%")
                  ->orWhere('insurance_policy_number', 'like', "%{$request->company_no}%");
            });
        }

        // Filter by class
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        $patients = $query->where('is_active', true)
            ->orderBy('patient_name')
            ->limit(50)
            ->get();

        return response()->json($patients);
    }

    /**
     * Mark patient as VIP
     */
    public function markVip(Patient $patient)
    {
        $patient->update(['category' => 'vip']);
        return response()->json([
            'message' => 'Patient marked as VIP',
            'patient' => $patient,
        ]);
    }

    /**
     * Mark patient as urgent
     */
    public function markUrgent(Patient $patient)
    {
        $patient->update(['is_urgent' => true]);
        return response()->json([
            'message' => 'Patient marked as urgent',
            'patient' => $patient,
        ]);
    }

    /**
     * Upload patient document
     */
    public function uploadDocument(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_name' => 'required|string|max:100',
            'document_path' => 'required|string|max:255',
        ]);

        $documents = $patient->documents ?? [];
        $documents[] = [
            'type' => $validated['document_type'],
            'name' => $validated['document_name'],
            'path' => $validated['document_path'],
            'uploaded_at' => now()->toDateTimeString(),
        ];

        $patient->update(['documents' => $documents]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'documents' => $patient->documents,
        ]);
    }

    /**
     * Get client public documents (for cashless patients)
     */
    public function clientDocuments(Patient $patient)
    {
        if (!$patient->class_id) {
            return response()->json(['message' => 'Patient has no class assigned'], 400);
        }

        $class = $patient->patientClass;
        if (!$class || !$class->client_id) {
            return response()->json(['message' => 'Patient class has no client assigned'], 400);
        }

        // Return client information and any public documents
        return response()->json([
            'client' => $class->client,
            'class' => $class,
        ]);
    }
}
