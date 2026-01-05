<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\MrdFileMovement;
use App\Models\MedicalRecordRequest;
use App\Models\PatientConsent;
use App\Models\CodingDiagnosis;
use App\Models\IcdCode;
use App\Models\OpdVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MrdController extends Controller
{
    // Patient Documents
    public function documents(Request $request, Patient $patient)
    {
        $query = PatientDocument::where('patient_id', $patient->patient_id);

        if ($request->document_type) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('document_date', [$request->from_date, $request->to_date]);
        }

        $documents = $query->orderBy('document_date', 'desc')->get();

        return response()->json(['documents' => $documents]);
    }

    public function uploadDocument(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'document_type' => 'required|in:lab_report,radiology,discharge,prescription,consent,referral,other',
            'document_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'is_confidential' => 'nullable|boolean',
            'source_type' => 'nullable|string',
            'source_id' => 'nullable|integer',
        ]);

        $path = $request->file('document')->store('mrd/documents/' . $patient->patient_id, 'public');

        $document = PatientDocument::create([
            'patient_id' => $patient->patient_id,
            'document_type' => $validated['document_type'],
            'document_date' => $validated['document_date'],
            'file_path' => $path,
            'original_filename' => $request->file('document')->getClientOriginalName(),
            'description' => $validated['description'] ?? null,
            'is_confidential' => $validated['is_confidential'] ?? false,
            'source_type' => $validated['source_type'] ?? null,
            'source_id' => $validated['source_id'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document,
        ], 201);
    }

    // File Movements - All (for File Tracking page)
    public function allFileMovements(Request $request)
    {
        $query = MrdFileMovement::with(['patient', 'issuedBy', 'returnedBy']);

        // Filter by status: issued (files out) or returned
        if ($request->status === 'issued') {
            $query->where('movement_type', 'issued')
                  ->whereNull('returned_at');
        } elseif ($request->status === 'returned') {
            $query->whereNotNull('returned_at');
        }

        $query->orderBy('created_at', 'desc');

        if ($request->limit) {
            $movements = $query->limit((int) $request->limit)->get();
        } else {
            $movements = $query->paginate($request->per_page ?? 50);
            return response()->json($movements);
        }

        return response()->json(['movements' => $movements]);
    }

    // File Movements - Patient specific
    public function fileMovements(Request $request, Patient $patient)
    {
        $movements = MrdFileMovement::where('patient_id', $patient->patient_id)
            ->with(['issuedBy', 'returnedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['movements' => $movements]);
    }

    public function issueFile(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'to_location' => 'required|string|max:100',
            'to_department_id' => 'nullable|exists:departments,department_id',
            'purpose' => 'nullable|string|max:255',
            'expected_return_date' => 'nullable|date|after:today',
        ]);

        // Check if file is already issued
        $activeIssue = MrdFileMovement::where('patient_id', $patient->patient_id)
            ->where('movement_type', 'issued')
            ->whereNull('returned_at')
            ->first();

        if ($activeIssue) {
            return response()->json([
                'message' => 'File is already issued to ' . $activeIssue->to_location,
            ], 422);
        }

        $movement = MrdFileMovement::create([
            'patient_id' => $patient->patient_id,
            'file_number' => $patient->pcd,
            'movement_type' => 'issued',
            'to_location' => $validated['to_location'],
            'to_department_id' => $validated['to_department_id'] ?? null,
            'purpose' => $validated['purpose'],
            'expected_return_date' => $validated['expected_return_date'],
            'issued_by' => auth()->id(),
            'issued_at' => now(),
        ]);

        return response()->json([
            'message' => 'File issued successfully',
            'movement' => $movement,
        ], 201);
    }

    public function returnFile(Request $request, MrdFileMovement $movement)
    {
        if ($movement->returned_at) {
            return response()->json([
                'message' => 'File already returned',
            ], 422);
        }

        $validated = $request->validate([
            'return_notes' => 'nullable|string',
        ]);

        $movement->update([
            'movement_type' => 'returned',
            'returned_at' => now(),
            'received_by' => auth()->id(),
            'return_notes' => $validated['return_notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'File returned successfully',
            'movement' => $movement,
        ]);
    }

    // Medical Record Requests
    public function recordRequests(Request $request)
    {
        $query = MedicalRecordRequest::with(['patient', 'approvedBy']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->request_type) {
            $query->where('requester_type', $request->request_type);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('requester_name', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('patient_name', 'like', "%{$search}%")
                         ->orWhere('uhid', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        // Map fields for Vue compatibility
        $requests->getCollection()->transform(function ($item) {
            $item->request_type = $item->requester_type;
            $item->requester_relation = $item->consent_type ?? 'self';
            $item->purpose = $item->request_purpose;
            return $item;
        });

        return response()->json($requests);
    }

    public function createRecordRequest(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'request_type' => 'nullable|string|max:50',
            'requester_name' => 'required|string|max:100',
            'requester_relation' => 'nullable|string|max:50',
            'requester_phone' => 'nullable|string|max:50',
            'requester_email' => 'nullable|email|max:100',
            'purpose' => 'required|string',
            'records_from_date' => 'nullable|date',
            'records_to_date' => 'nullable|date',
            'records_needed' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Map Vue form fields to model fields
        $requestNumber = MedicalRecordRequest::generateRequestNumber(auth()->user()->hospital_id ?? 1);

        // Map Vue request types to valid database enum values
        $requestTypeMap = [
            'copy' => 'patient',
            'view' => 'patient',
            'transfer' => 'other',
            'legal' => 'legal',
            'insurance' => 'insurance',
        ];
        $requesterType = $requestTypeMap[$validated['request_type'] ?? 'patient'] ?? 'other';

        // Map Vue requester relation to valid consent_type enum values
        $consentTypeMap = [
            'self' => 'patient',
            'spouse' => 'patient',
            'parent' => 'legal_guardian',
            'child' => 'patient',
            'sibling' => 'patient',
            'legal_guardian' => 'legal_guardian',
            'attorney' => 'court_order',
            'insurance' => 'patient',
            'other' => 'patient',
        ];
        $consentType = $consentTypeMap[$validated['requester_relation'] ?? 'self'] ?? 'patient';

        $recordRequest = MedicalRecordRequest::create([
            'hospital_id' => auth()->user()->hospital_id ?? 1,
            'patient_id' => $validated['patient_id'],
            'request_number' => $requestNumber,
            'requester_type' => $requesterType,
            'requester_name' => $validated['requester_name'],
            'requester_contact' => $validated['requester_phone'] ?? $validated['requester_email'] ?? '',
            'requester_organization' => null,
            'request_purpose' => $validated['purpose'],
            'records_requested' => $validated['records_needed'] ?? [],
            'date_range_from' => $validated['records_from_date'] ?? null,
            'date_range_to' => $validated['records_to_date'] ?? null,
            'request_date' => now(),
            'consent_type' => $consentType,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Record request created successfully',
            'request' => $recordRequest,
        ], 201);
    }

    public function approveRecordRequest(Request $request, MedicalRecordRequest $recordRequest)
    {
        if ($recordRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be approved',
            ], 422);
        }

        $recordRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Request approved successfully',
            'request' => $recordRequest,
        ]);
    }

    public function rejectRecordRequest(Request $request, MedicalRecordRequest $recordRequest)
    {
        if ($recordRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be rejected',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $recordRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Request rejected successfully',
            'request' => $recordRequest,
        ]);
    }

    public function completeRecordRequest(Request $request, MedicalRecordRequest $recordRequest)
    {
        if ($recordRequest->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved requests can be marked as completed',
            ], 422);
        }

        $recordRequest->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Request marked as completed',
            'request' => $recordRequest,
        ]);
    }

    public function processRecordRequest(Request $request, MedicalRecordRequest $recordRequest)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
            'rejection_reason' => 'required_if:action,reject|nullable|string',
        ]);

        if ($validated['action'] === 'approve') {
            $recordRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        } else {
            $recordRequest->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        }

        return response()->json([
            'message' => 'Request ' . $validated['action'] . 'd successfully',
            'request' => $recordRequest,
        ]);
    }

    // Patient Consents - Global listing
    public function allConsents(Request $request)
    {
        $query = PatientConsent::with(['patient', 'doctor']);

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('pcd', 'like', "%{$search}%")
                  ->orWhere('uhid', 'like', "%{$search}%");
            });
        }

        if ($request->consent_type) {
            $query->where('consent_type', $request->consent_type);
        }

        if ($request->status === 'active') {
            $query->where('is_given', true)->whereNull('revoked_at');
        } elseif ($request->status === 'revoked') {
            $query->whereNotNull('revoked_at');
        }

        if ($request->from_date) {
            $query->whereDate('consent_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('consent_date', '<=', $request->to_date);
        }

        $consents = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($consents);
    }

    // Patient Consents - Patient specific
    public function consents(Request $request, Patient $patient)
    {
        $consents = PatientConsent::where('patient_id', $patient->patient_id)
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['consents' => $consents]);
    }

    public function recordConsent(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'consent_type' => 'required|in:treatment,surgery,anesthesia,blood_transfusion,research,data_sharing,photography,hiv_test,medico_legal,discharge_against_advice,high_risk_procedure,other',
            'consent_for' => 'nullable|string|max:255',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'consent_date' => 'nullable|date',
            'is_given' => 'required|boolean',
            'given_by' => 'required|string|max:100',
            'relationship' => 'nullable|string|max:50',
            'witness_name' => 'nullable|string|max:100',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'consent_form' => 'nullable|file|mimes:pdf|max:5120',
            'notes' => 'nullable|string',
        ]);

        $formPath = null;
        if ($request->hasFile('consent_form')) {
            $formPath = $request->file('consent_form')->store('mrd/consent-forms/' . $patient->patient_id, 'public');
        }

        $consent = PatientConsent::create([
            'hospital_id' => auth()->user()->hospital_id ?? 1,
            'patient_id' => $patient->patient_id,
            'consent_type' => $validated['consent_type'],
            'consent_for' => $validated['consent_for'] ?? null,
            'reference_type' => $validated['reference_type'] ?? null,
            'reference_id' => $validated['reference_id'] ?? null,
            'consent_date' => $validated['consent_date'] ?? now()->toDateString(),
            'consent_time' => now()->format('H:i:s'),
            'is_given' => $validated['is_given'],
            'given_by' => $validated['given_by'],
            'relationship' => $validated['relationship'] ?? 'self',
            'witness_name' => $validated['witness_name'] ?? null,
            'doctor_id' => $validated['doctor_id'] ?? null,
            'consent_form_path' => $formPath,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Consent recorded successfully',
            'consent' => $consent,
        ], 201);
    }

    public function revokeConsent(Request $request, PatientConsent $consent)
    {
        if ($consent->revoked_at) {
            return response()->json([
                'message' => 'Consent is already revoked',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $consent->update([
            'revoked_at' => now(),
            'revoked_by' => auth()->id(),
            'revocation_reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Consent revoked successfully',
            'consent' => $consent,
        ]);
    }

    // ICD Coding
    public function diagnoses(Request $request, Patient $patient)
    {
        $query = CodingDiagnosis::where('patient_id', $patient->patient_id);

        if ($request->reference_type && $request->reference_id) {
            $query->where('reference_type', $request->reference_type)
                ->where('reference_id', $request->reference_id);
        }

        $diagnoses = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['diagnoses' => $diagnoses]);
    }

    public function addDiagnosis(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'reference_type' => 'required|in:opd,ipd',
            'reference_id' => 'required|integer',
            'diagnosis_type' => 'required|in:primary,secondary,complication,comorbidity',
            'icd_code' => 'required|string|max:20',
            'icd_description' => 'required|string',
            'clinical_description' => 'nullable|string',
            'is_poa' => 'nullable|boolean', // Present on Admission
        ]);

        $diagnosis = CodingDiagnosis::create([
            'patient_id' => $patient->patient_id,
            ...$validated,
            'coded_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Diagnosis added successfully',
            'diagnosis' => $diagnosis,
        ], 201);
    }

    // Complete Medical Record View
    public function completeRecord(Patient $patient)
    {
        $record = [
            'patient' => $patient,
            'opd_visits' => $patient->opdVisits()->with(['doctor', 'prescriptions'])->latest()->take(10)->get(),
            'ipd_admissions' => $patient->ipdAdmissions()->with(['bed', 'doctor'])->latest()->take(5)->get(),
            'lab_orders' => $patient->labOrders()->with('orderDetails.labTest')->latest()->take(10)->get(),
            'radiology_orders' => $patient->radiologyOrders()->with('orderDetails.test')->latest()->take(10)->get(),
            'prescriptions' => $patient->prescriptions()->with('items')->latest()->take(10)->get(),
            'documents' => $patient->documents()->latest()->take(20)->get(),
            'consents' => $patient->consents()->latest()->get(),
            'diagnoses' => $patient->codingDiagnoses()->latest()->get(),
        ];

        return response()->json(['medical_record' => $record]);
    }

    // ============================================
    // ICD-10 Coding Features
    // ============================================

    /**
     * Search ICD-10 codes
     */
    public function searchIcdCodes(Request $request)
    {
        $query = IcdCode::active();

        if ($request->search) {
            $query->search($request->search);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $codes = $query->orderBy('icd_code')
                       ->limit($request->limit ?? 100)
                       ->get()
                       ->map(function ($code) {
                           return [
                               'code' => $code->icd_code,
                               'description' => $code->long_description ?? $code->short_description,
                               'short_desc' => $code->short_description,
                               'category' => $code->category ?? $code->chapter,
                           ];
                       });

        return response()->json(['codes' => $codes]);
    }

    /**
     * Get recent visits that need ICD coding
     */
    public function uncodedVisits(Request $request)
    {
        $limit = $request->limit ?? 50;

        // Get OPD visits without ICD codes
        $visits = OpdVisit::with(['patient', 'doctor'])
            ->whereDoesntHave('codingDiagnoses')
            ->orderBy('visit_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($visit) {
                return [
                    'visit_id' => $visit->opd_id,
                    'visit_type' => 'OPD',
                    'visit_date' => $visit->visit_date,
                    'patient_id' => $visit->patient_id,
                    'patient_name' => $visit->patient?->patient_name,
                    'uhid' => $visit->patient?->uhid,
                    'doctor_name' => $visit->doctor?->full_name,
                    'chief_complaints' => $visit->chief_complaints,
                ];
            });

        return response()->json(['visits' => $visits]);
    }

    /**
     * Get recently coded visits
     */
    public function codedVisits(Request $request)
    {
        $limit = $request->limit ?? 10;

        $visits = CodingDiagnosis::with(['patient', 'codedBy'])
            ->where('reference_type', 'opd')
            ->where('is_principal', true)
            ->orderBy('coded_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($diagnosis) {
                return [
                    'visit_id' => $diagnosis->reference_id,
                    'visit_date' => $diagnosis->coded_at,
                    'patient_name' => $diagnosis->patient?->patient_name,
                    'primary_icd_code' => $diagnosis->icd_code,
                    'coded_by' => $diagnosis->codedBy?->full_name,
                ];
            });

        return response()->json(['visits' => $visits]);
    }

    /**
     * Get ICD codes for a specific visit
     */
    public function getVisitCodes(Request $request, $visitId)
    {
        $codes = CodingDiagnosis::where('reference_type', 'opd')
            ->where('reference_id', $visitId)
            ->orderByDesc('is_principal')
            ->orderBy('created_at')
            ->get()
            ->map(function ($diagnosis) {
                return [
                    'code' => $diagnosis->icd_code,
                    'description' => $diagnosis->icd_description,
                    'is_primary' => $diagnosis->is_principal,
                ];
            });

        return response()->json(['codes' => $codes]);
    }

    /**
     * Save ICD codes for a visit
     */
    public function saveVisitCodes(Request $request, $visitId)
    {
        $validated = $request->validate([
            'codes' => 'required|array|min:1',
            'codes.*.icd_code' => 'required|string|max:20',
            'codes.*.description' => 'required|string',
            'codes.*.is_primary' => 'boolean',
        ]);

        // Get the visit to get patient_id
        $visit = OpdVisit::findOrFail($visitId);

        // Delete existing codes for this visit
        CodingDiagnosis::where('reference_type', 'opd')
            ->where('reference_id', $visitId)
            ->delete();

        // Save new codes
        foreach ($validated['codes'] as $codeData) {
            CodingDiagnosis::create([
                'hospital_id' => $visit->hospital_id,
                'patient_id' => $visit->patient_id,
                'reference_type' => 'opd',
                'reference_id' => $visitId,
                'diagnosis_type' => ($codeData['is_primary'] ?? false) ? 'principal' : 'secondary',
                'diagnosis_text' => $codeData['description'],
                'icd_code' => $codeData['icd_code'],
                'icd_description' => $codeData['description'],
                'is_principal' => $codeData['is_primary'] ?? false,
                'coded_by' => auth()->id(),
                'coded_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'ICD codes saved successfully',
            'count' => count($validated['codes']),
        ]);
    }
}
