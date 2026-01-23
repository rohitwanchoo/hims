<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\PatientVaccination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query()
            ->with(['genderRelation', 'permanentCity', 'bloodGroupRelation']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('pcd', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('permanent_mobile', 'like', "%{$search}%");
            });
        }

        // Gender filter
        if ($request->filled('gender_id')) {
            $query->where('gender_id', $request->gender_id);
        }

        // Blood Group filter
        if ($request->filled('blood_group_id')) {
            $query->where('blood_group_id', $request->blood_group_id);
        }

        // City filter
        if ($request->filled('city_id')) {
            $query->where('permanent_city_id', $request->city_id);
        }

        // Age Range filter
        if ($request->filled('age_range')) {
            $range = explode('-', $request->age_range);
            if (count($range) == 2) {
                $minAge = (int)$range[0];
                $maxAge = (int)$range[1];
                $query->where('age_years', '>=', $minAge)
                      ->where('age_years', '<=', $maxAge);
            }
        }

        // Date Range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Status filter
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'patient_name' => 'required|string|max:100',
            'first_name' => 'nullable|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'guardian_name' => 'nullable|string|max:100',
            'relation' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer',
            'age_years' => 'nullable|integer',
            'age_months' => 'nullable|integer',
            'age_days' => 'nullable|integer',
            'age_unit' => 'nullable|in:days,months,years',
            // Gender - accept both formats
            'gender' => 'nullable|in:male,female,other',
            'gender_id' => 'nullable|integer|exists:genders,gender_id',
            // Prefix
            'prefix_id' => 'nullable|integer|exists:prefixes,prefix_id',
            // Marital Status - accept both formats
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'marital_status_id' => 'nullable|integer|exists:marital_statuses,marital_status_id',
            // Blood Group - accept both formats
            'blood_group' => 'nullable|string|max:5',
            'blood_group_id' => 'nullable|integer|exists:blood_groups,blood_group_id',
            // Patient Type
            'patient_type' => 'nullable|in:Staff,Doctor',
            'patient_type_id' => 'nullable|integer|exists:patient_types,patient_type_id',
            // Contact
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            // Subscribe options (accept any truthy/falsy value)
            'subscribe_sms' => 'nullable',
            'subscribe_whatsapp' => 'nullable',
            'subscribe_email' => 'nullable',
            // Permanent Address
            'permanent_address' => 'nullable|string',
            'permanent_country_id' => 'nullable|integer|exists:countries,country_id',
            'permanent_state_id' => 'nullable|integer|exists:states,state_id',
            'permanent_district_id' => 'nullable|integer|exists:districts,district_id',
            'permanent_city_id' => 'nullable|integer|exists:cities,city_id',
            'permanent_area_id' => 'nullable|integer|exists:areas,area_id',
            'permanent_pincode' => 'nullable|string|max:10',
            'permanent_mobile' => 'nullable|string|max:15',
            'permanent_email' => 'nullable|email|max:100',
            // Current Address
            'same_as_permanent' => 'nullable',
            'current_address' => 'nullable|string',
            'current_country_id' => 'nullable|integer|exists:countries,country_id',
            'current_state_id' => 'nullable|integer|exists:states,state_id',
            'current_district_id' => 'nullable|integer|exists:districts,district_id',
            'current_city_id' => 'nullable|integer|exists:cities,city_id',
            'current_area_id' => 'nullable|integer|exists:areas,area_id',
            'current_pincode' => 'nullable|string|max:10',
            'current_mobile' => 'nullable|string|max:15',
            'current_email' => 'nullable|email|max:100',
            // Legacy address fields
            'address' => 'nullable|string',
            'area' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            // Identity
            'aadhaar_number' => 'nullable|string|max:12',
            'pan_number' => 'nullable|string|max:10',
            'occupation' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|in:general,vip',
            'charges_type' => 'nullable|in:normal,day_emergency,night_emergency',
            // Medical
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
            // Emergency Contact
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:15',
            'emergency_contact_relation' => 'nullable|string|max:50',
            // Insurance & Reference
            'insurance_id' => 'nullable|exists:insurance_companies,insurance_id',
            'insurance_company_id' => 'nullable|exists:insurance_companies,insurance_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',
            'insurance_policy_number' => 'nullable|string|max:50',
            'insurance_policy_no' => 'nullable|string|max:50',
            'cashless_referral_no' => 'nullable|string|max:50',
            'tpa_id' => 'nullable|string|max:50',
            // Photo and Documents
            'photo' => 'nullable|image|max:5120', // 5MB max
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240', // 10MB max per document
        ]);

        // Convert boolean fields
        $booleanFields = ['subscribe_sms', 'subscribe_whatsapp', 'subscribe_email', 'same_as_permanent'];
        foreach ($booleanFields as $field) {
            if (isset($validatedData[$field])) {
                $validatedData[$field] = filter_var($validatedData[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
        }

        $validatedData['hospital_id'] = auth()->user()->hospital_id;
        $validatedData['pcd'] = $this->generatePatientCode();
        $validatedData['registration_date'] = now()->toDateString();
        $validatedData['is_active'] = true;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '_' . $photo->getClientOriginalName();
            $photoPath = $photo->storeAs('patient_photos', $photoName, 'public');
            $validatedData['photo'] = $photoPath;
        }

        // Remove documents from validated data (handled separately)
        unset($validatedData['documents']);

        $patient = Patient::create($validatedData);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $fileName = time() . '_' . $document->getClientOriginalName();
                $filePath = $document->storeAs('patient_documents/' . $patient->patient_id, $fileName, 'public');

                PatientDocument::create([
                    'hospital_id' => auth()->user()->hospital_id,
                    'patient_id' => $patient->patient_id,
                    'document_type' => 'other',
                    'document_category' => 'patient',
                    'document_date' => now(),
                    'document_title' => $document->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_type' => $document->getClientMimeType(),
                    'file_size' => $document->getSize(),
                    'is_confidential' => false,
                    'uploaded_by' => auth()->user()->user_id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully',
            'data' => $patient
        ], 201);
    }

    public function show(Patient $patient)
    {
        // Load all relationships for complete patient view
        $patient->load([
            'genderRelation',
            'bloodGroupRelation',
            'maritalStatusRelation',
            'patientTypeRelation',
            'prefix',
            'permanentCountry',
            'permanentState',
            'permanentDistrict',
            'permanentCity',
            'permanentArea',
            'currentCountry',
            'currentState',
            'currentDistrict',
            'currentCity',
            'currentArea',
            'insuranceCompanyRelation',
            'referenceDoctor',
        ]);

        return response()->json([
            'success' => true,
            'data' => $patient
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $validatedData = $request->validate([
            'patient_name' => 'required|string|max:100',
            'first_name' => 'nullable|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'guardian_name' => 'nullable|string|max:100',
            'relation' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'age' => 'nullable|integer',
            'age_years' => 'nullable|integer',
            'age_months' => 'nullable|integer',
            'age_days' => 'nullable|integer',
            'age_unit' => 'nullable|in:days,months,years',
            // Gender - accept both formats
            'gender' => 'nullable|in:male,female,other',
            'gender_id' => 'nullable|integer|exists:genders,gender_id',
            // Prefix
            'prefix_id' => 'nullable|integer|exists:prefixes,prefix_id',
            // Marital Status - accept both formats
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'marital_status_id' => 'nullable|integer|exists:marital_statuses,marital_status_id',
            // Blood Group - accept both formats
            'blood_group' => 'nullable|string|max:5',
            'blood_group_id' => 'nullable|integer|exists:blood_groups,blood_group_id',
            // Patient Type
            'patient_type' => 'nullable|in:Staff,Doctor',
            'patient_type_id' => 'nullable|integer|exists:patient_types,patient_type_id',
            // Contact
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            // Subscribe options (accept any truthy/falsy value)
            'subscribe_sms' => 'nullable',
            'subscribe_whatsapp' => 'nullable',
            'subscribe_email' => 'nullable',
            // Permanent Address
            'permanent_address' => 'nullable|string',
            'permanent_country_id' => 'nullable|integer|exists:countries,country_id',
            'permanent_state_id' => 'nullable|integer|exists:states,state_id',
            'permanent_district_id' => 'nullable|integer|exists:districts,district_id',
            'permanent_city_id' => 'nullable|integer|exists:cities,city_id',
            'permanent_area_id' => 'nullable|integer|exists:areas,area_id',
            'permanent_pincode' => 'nullable|string|max:10',
            'permanent_mobile' => 'nullable|string|max:15',
            'permanent_email' => 'nullable|email|max:100',
            // Current Address
            'same_as_permanent' => 'nullable',
            'current_address' => 'nullable|string',
            'current_country_id' => 'nullable|integer|exists:countries,country_id',
            'current_state_id' => 'nullable|integer|exists:states,state_id',
            'current_district_id' => 'nullable|integer|exists:districts,district_id',
            'current_city_id' => 'nullable|integer|exists:cities,city_id',
            'current_area_id' => 'nullable|integer|exists:areas,area_id',
            'current_pincode' => 'nullable|string|max:10',
            'current_mobile' => 'nullable|string|max:15',
            'current_email' => 'nullable|email|max:100',
            // Legacy address fields
            'address' => 'nullable|string',
            'area' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            // Identity
            'aadhaar_number' => 'nullable|string|max:12',
            'pan_number' => 'nullable|string|max:10',
            'occupation' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'religion' => 'nullable|string|max:50',
            'category' => 'nullable|in:general,vip',
            'charges_type' => 'nullable|in:normal,day_emergency,night_emergency',
            // Medical
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
            // Emergency Contact
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:15',
            'emergency_contact_relation' => 'nullable|string|max:50',
            // Insurance & Reference
            'insurance_id' => 'nullable|exists:insurance_companies,insurance_id',
            'insurance_company_id' => 'nullable|exists:insurance_companies,insurance_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',
            'insurance_policy_number' => 'nullable|string|max:50',
            'insurance_policy_no' => 'nullable|string|max:50',
            'cashless_referral_no' => 'nullable|string|max:50',
            'tpa_id' => 'nullable|string|max:50',
            'is_active' => 'nullable',
            // Photo and Documents
            'photo' => 'nullable|image|max:5120', // 5MB max
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:10240', // 10MB max per document
        ]);

        // Convert boolean fields
        $booleanFields = ['subscribe_sms', 'subscribe_whatsapp', 'subscribe_email', 'same_as_permanent', 'is_active'];
        foreach ($booleanFields as $field) {
            if (isset($validatedData[$field])) {
                $validatedData[$field] = filter_var($validatedData[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
        }

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($patient->photo && Storage::disk('public')->exists($patient->photo)) {
                Storage::disk('public')->delete($patient->photo);
            }

            $photo = $request->file('photo');
            $photoName = time() . '_' . $photo->getClientOriginalName();
            $photoPath = $photo->storeAs('patient_photos', $photoName, 'public');
            $validatedData['photo'] = $photoPath;
        }

        // Remove documents from validated data (handled separately)
        unset($validatedData['documents']);

        $patient->update($validatedData);

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $fileName = time() . '_' . $document->getClientOriginalName();
                $filePath = $document->storeAs('patient_documents/' . $patient->patient_id, $fileName, 'public');

                PatientDocument::create([
                    'hospital_id' => auth()->user()->hospital_id,
                    'patient_id' => $patient->patient_id,
                    'document_type' => 'other',
                    'document_category' => 'patient',
                    'document_date' => now(),
                    'document_title' => $document->getClientOriginalName(),
                    'file_path' => $filePath,
                    'file_name' => $fileName,
                    'file_type' => $document->getClientMimeType(),
                    'file_size' => $document->getSize(),
                    'is_confidential' => false,
                    'uploaded_by' => auth()->user()->user_id,
                ]);
            }
        }

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

    public function visits(Patient $patient)
    {
        $visits = $patient->opdVisits()
            ->with(['doctor', 'department'])
            ->orderBy('visit_date', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $visits
        ]);
    }

    public function admissions(Patient $patient)
    {
        $admissions = $patient->ipdAdmissions()
            ->with(['ward', 'bed', 'doctor', 'department'])
            ->orderBy('admission_date', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $admissions
        ]);
    }

    public function vaccinations(Patient $patient)
    {
        $vaccinations = PatientVaccination::where('patient_id', $patient->patient_id)
            ->with(['vaccination'])
            ->orderBy('scheduled_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vaccinations
        ]);
    }

    public function history(Patient $patient)
    {
        $history = [
            'patient' => $patient->load(['patientClass', 'referenceDoctor']),
            'opd_visits' => $patient->opdVisits()
                ->with(['doctor', 'department'])
                ->orderBy('visit_date', 'desc')
                ->limit(10)
                ->get(),
            'ipd_admissions' => $patient->ipdAdmissions()
                ->with(['ward', 'bed', 'doctor'])
                ->orderBy('admission_date', 'desc')
                ->limit(10)
                ->get(),
            'appointments' => $patient->appointments()
                ->with(['doctor', 'department'])
                ->orderBy('appointment_date', 'desc')
                ->limit(10)
                ->get(),
            'vaccinations' => PatientVaccination::where('patient_id', $patient->patient_id)
                ->with(['vaccination'])
                ->orderBy('scheduled_date', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function clientDocuments(Patient $patient)
    {
        $documents = PatientDocument::where('patient_id', $patient->patient_id)
            ->where('document_category', 'client')
            ->with(['uploadedBy'])
            ->orderBy('document_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function documents(Patient $patient)
    {
        $documents = PatientDocument::where('patient_id', $patient->patient_id)
            ->with(['uploadedBy'])
            ->orderBy('document_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function deleteDocument($documentId)
    {
        $document = PatientDocument::findOrFail($documentId);

        // Delete the file from storage
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully'
        ]);
    }

    public function markVip(Patient $patient)
    {
        $patient->update(['category' => 'vip']);

        return response()->json([
            'success' => true,
            'message' => 'Patient marked as VIP successfully',
            'data' => $patient
        ]);
    }

    public function markUrgent(Patient $patient)
    {
        $patient->update(['is_urgent' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Patient marked as urgent successfully',
            'data' => $patient
        ]);
    }

    public function uploadDocument(Request $request, Patient $patient)
    {
        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_category' => 'nullable|string|max:50',
            'document_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'document_date' => 'nullable|date',
            'file' => 'required|file|max:10240', // 10MB max
            'is_confidential' => 'nullable|boolean',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('patient_documents/' . $patient->patient_id, $fileName, 'public');

        $document = PatientDocument::create([
            'hospital_id' => auth()->user()->hospital_id,
            'patient_id' => $patient->patient_id,
            'document_type' => $request->document_type,
            'document_category' => $request->document_category ?? 'general',
            'document_date' => $request->document_date ?? now(),
            'document_title' => $request->document_title,
            'description' => $request->description,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_confidential' => $request->is_confidential ?? false,
            'uploaded_by' => auth()->user()->user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'data' => $document
        ], 201);
    }

    public function search(Request $request)
    {
        $query = Patient::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('pcd', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('aadhaar_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->patient_name) {
            $query->where('patient_name', 'like', "%{$request->patient_name}%");
        }

        if ($request->mobile) {
            $query->where('mobile', 'like', "%{$request->mobile}%");
        }

        if ($request->pcd) {
            $query->where('pcd', 'like', "%{$request->pcd}%");
        }

        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->patient_type) {
            $query->where('patient_type', $request->patient_type);
        }

        $patients = $query->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $patients
        ]);
    }

    private function generatePatientCode()
    {
        $hospitalId = auth()->user()->hospital_id;
        $lastPatient = Patient::where('hospital_id', $hospitalId)
            ->orderBy('patient_id', 'desc')
            ->first();

        if ($lastPatient && $lastPatient->pcd) {
            // Extract numeric part from existing PCD (e.g., PAT-0001 -> 1)
            preg_match('/\d+$/', $lastPatient->pcd, $matches);
            $nextNumber = isset($matches[0]) ? (int)$matches[0] + 1 : 1;
        } else {
            $nextNumber = 1;
        }

        return 'PAT-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}