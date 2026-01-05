<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IpdAdmission;
use App\Models\IpdProgressNote;
use App\Models\IpdNursingChart;
use App\Models\IpdService;
use App\Models\IpdInvestigation;
use App\Models\IpdMedication;
use App\Models\IpdAdvancePayment;
use App\Models\BedTransfer;
use App\Models\Bed;
use App\Models\Ward;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class IpdAdmissionController extends Controller
{
    /**
     * Display a listing of IPD admissions with filters
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = IpdAdmission::where('hospital_id', $hospitalId)
            ->with(['patient', 'treatingDoctor', 'ward', 'bed', 'department']);

        // Status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Ward filter
        if ($request->has('ward_id') && $request->ward_id) {
            $query->where('ward_id', $request->ward_id);
        }

        // Department filter
        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        // Doctor filter
        if ($request->has('doctor_id') && $request->doctor_id) {
            $query->where(function($q) use ($request) {
                $q->where('treating_doctor_id', $request->doctor_id)
                  ->orWhere('consultant_doctor_id', $request->doctor_id);
            });
        }

        // Date range filter
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('admission_date', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('admission_date', '<=', $request->to_date);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ipd_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('patient_id', 'like', "%{$search}%")
                         ->orWhere('mobile', 'like', "%{$search}%");
                  });
            });
        }

        // Insurance filter
        if ($request->has('insurance_applicable') && $request->insurance_applicable !== '') {
            $query->where('insurance_applicable', $request->insurance_applicable);
        }

        // MLC filter
        if ($request->has('is_mlc') && $request->is_mlc !== '') {
            $query->where('mlc_case', $request->is_mlc);
        }

        // Emergency filter
        if ($request->has('is_emergency') && $request->is_emergency !== '') {
            $query->where('is_emergency', $request->is_emergency);
        }

        $admissions = $query->orderBy('admission_date', 'desc')
            ->orderBy('ipd_id', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($admissions);
    }

    /**
     * Get IPD summary statistics
     */
    public function summary(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;
        $today = now()->toDateString();

        $summary = [
            'total_admitted' => IpdAdmission::where('hospital_id', $hospitalId)
                ->where('status', 'admitted')->count(),
            'today_admissions' => IpdAdmission::where('hospital_id', $hospitalId)
                ->whereDate('admission_date', $today)->count(),
            'today_discharges' => IpdAdmission::where('hospital_id', $hospitalId)
                ->whereDate('discharge_date', $today)->count(),
            'pending_discharge' => IpdAdmission::where('hospital_id', $hospitalId)
                ->where('status', 'discharge_initiated')->count(),
            'mlc_cases' => IpdAdmission::where('hospital_id', $hospitalId)
                ->where('status', 'admitted')
                ->where('mlc_case', true)->count(),
            'emergency_admissions' => IpdAdmission::where('hospital_id', $hospitalId)
                ->where('status', 'admitted')
                ->where('is_emergency', true)->count(),
            'insurance_cases' => IpdAdmission::where('hospital_id', $hospitalId)
                ->where('status', 'admitted')
                ->where('insurance_applicable', true)->count(),
        ];

        return response()->json($summary);
    }

    /**
     * Get ward-wise bed availability
     */
    public function bedAvailability(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $wards = Ward::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->with(['beds' => function($q) {
                $q->select('bed_id', 'ward_id', 'bed_number', 'room_number', 'bed_type',
                           'status', 'is_available', 'is_isolation', 'is_ventilator',
                           'charges_per_day', 'current_patient_id')
                  ->with(['currentPatient:patient_id,first_name,last_name,gender']);
            }])
            ->get()
            ->map(function($ward) {
                $beds = $ward->beds;
                return [
                    'ward_id' => $ward->ward_id,
                    'ward_name' => $ward->ward_name,
                    'ward_type' => $ward->ward_type,
                    'total_beds' => $beds->count(),
                    'available_beds' => $beds->where('is_available', true)->count(),
                    'occupied_beds' => $beds->where('is_available', false)->count(),
                    'charges_per_day' => $ward->charges_per_day,
                    'beds' => $beds,
                ];
            });

        return response()->json($wards);
    }

    /**
     * Get available beds for a ward
     */
    public function availableBeds(Request $request, $wardId = null)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = Bed::where('hospital_id', $hospitalId)
            ->where('is_available', true)
            ->with('ward:ward_id,ward_name,ward_type,charges_per_day');

        if ($wardId) {
            $query->where('ward_id', $wardId);
        }

        // Filter by bed type
        if ($request->has('bed_type') && $request->bed_type) {
            $query->where('bed_type', $request->bed_type);
        }

        // Filter for isolation beds
        if ($request->has('is_isolation') && $request->is_isolation) {
            $query->where('is_isolation', true);
        }

        // Filter for ventilator beds
        if ($request->has('is_ventilator') && $request->is_ventilator) {
            $query->where('is_ventilator', true);
        }

        $beds = $query->orderBy('bed_number')->get();

        return response()->json($beds);
    }

    /**
     * Store a new IPD admission
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'admission_date' => 'required|date',
            'ward_id' => 'required|exists:wards,ward_id',
            'bed_id' => 'required|exists:beds,bed_id',
            'treating_doctor_id' => 'required|exists:doctors,doctor_id',
            'department_id' => 'required|exists:departments,department_id',
            'admission_type' => 'required|in:elective,emergency',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Check bed availability
        $bed = Bed::where('bed_id', $request->bed_id)
            ->where('hospital_id', $hospitalId)
            ->first();

        if (!$bed || !$bed->is_available) {
            return response()->json(['message' => 'Selected bed is not available'], 422);
        }

        // Check if patient already admitted
        $existingAdmission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('patient_id', $request->patient_id)
            ->where('status', 'admitted')
            ->first();

        if ($existingAdmission) {
            return response()->json(['message' => 'Patient is already admitted with IPD Number: ' . $existingAdmission->ipd_number], 422);
        }

        DB::beginTransaction();
        try {
            // Generate IPD number
            $ipdNumber = IpdAdmission::generateIpdNumber($hospitalId);

            $admission = IpdAdmission::create([
                'hospital_id' => $hospitalId,
                'ipd_number' => $ipdNumber,
                'patient_id' => $request->patient_id,
                'admission_date' => $request->admission_date,
                'admission_time' => $request->admission_time ?? now()->format('H:i:s'),
                'admission_type' => $request->admission_type,
                'admission_source' => $request->admission_source ?? 'direct',
                'opd_id' => $request->opd_id,
                'diagnosis_at_admission' => $request->diagnosis_at_admission,
                'provisional_diagnosis' => $request->provisional_diagnosis,
                'icd_code' => $request->icd_code,
                'admission_notes' => $request->admission_notes,
                'treatment_plan' => $request->treatment_plan,
                'department_id' => $request->department_id,
                'ward_id' => $request->ward_id,
                'bed_id' => $request->bed_id,
                'treating_doctor_id' => $request->treating_doctor_id,
                'consultant_doctor_id' => $request->consultant_doctor_id,
                'referral_from' => $request->referral_from,
                'mlc_case' => $request->is_mlc ?? false,
                'mlc_number' => $request->mlc_number,
                'is_emergency' => $request->is_emergency ?? false,
                'police_station' => $request->police_station,
                'brought_by' => $request->brought_by,
                'insurance_applicable' => $request->insurance_applicable ?? false,
                'insurance_id' => $request->insurance_id,
                'class_id' => $request->class_id,
                'reference_doctor_id' => $request->reference_doctor_id,
                'insurance_approval_number' => $request->insurance_approval_number,
                'tpa_name' => $request->tpa_name,
                'approved_amount' => $request->approved_amount ?? 0,
                'pre_auth_amount' => $request->pre_auth_amount ?? 0,
                'scheme_type' => $request->scheme_type ?? 'none',
                'credit_limit' => $request->credit_limit ?? 0,
                'attendant_name' => $request->attendant_name,
                'attendant_relation' => $request->attendant_relation,
                'attendant_mobile' => $request->attendant_mobile,
                'expected_los' => $request->expected_los,
                'status' => 'admitted',
                'created_by' => Auth::id(),
            ]);

            // Occupy the bed
            $bed->occupy($request->patient_id, $admission->ipd_id);

            DB::commit();

            return response()->json([
                'message' => 'Patient admitted successfully',
                'admission' => $admission->load(['patient', 'treatingDoctor', 'ward', 'bed', 'department']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create admission: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified IPD admission (Case Sheet)
     */
    public function show(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->with([
                'patient',
                'treatingDoctor',
                'consultantDoctor',
                'department',
                'ward',
                'bed',
                'opdVisit',
                'progressNotes' => fn($q) => $q->limit(10)->with('doctor'),
                'nursingCharts' => fn($q) => $q->limit(10),
                'services' => fn($q) => $q->limit(20),
                'investigations' => fn($q) => $q->limit(20),
                'medications' => fn($q) => $q->active(),
                'advancePayments',
                'bedTransfers',
            ])
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'IPD admission not found'], 404);
        }

        // Calculate running bill
        $runningBill = $admission->calculateRunningBill();

        return response()->json([
            'admission' => $admission,
            'running_bill' => $runningBill,
        ]);
    }

    /**
     * Update the specified IPD admission
     */
    public function update(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'IPD admission not found'], 404);
        }

        if ($admission->status === 'discharged') {
            return response()->json(['message' => 'Cannot update discharged admission'], 422);
        }

        $updateData = $request->only([
            'diagnosis_at_admission',
            'provisional_diagnosis',
            'icd_code',
            'admission_notes',
            'treatment_plan',
            'treating_doctor_id',
            'consultant_doctor_id',
            'mlc_number',
            'police_station',
            'brought_by',
            'insurance_applicable',
            'policy_number',
            'insurance_approval_number',
            'tpa_name',
            'approved_amount',
            'pre_auth_amount',
            'scheme_type',
            'credit_limit',
        ]);
        if ($request->has('is_mlc')) {
            $updateData['mlc_case'] = $request->is_mlc;
        }
        $admission->update($updateData);

        return response()->json([
            'message' => 'Admission updated successfully',
            'admission' => $admission->fresh(['patient', 'treatingDoctor', 'ward', 'bed']),
        ]);
    }

    /**
     * Transfer patient to another bed/ward
     */
    public function transferBed(Request $request, string $id)
    {
        $request->validate([
            'to_bed_id' => 'required|exists:beds,bed_id',
            'transfer_reason' => 'required|string|max:255',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('status', 'admitted')
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        $newBed = Bed::where('bed_id', $request->to_bed_id)
            ->where('hospital_id', $hospitalId)
            ->where('is_available', true)
            ->first();

        if (!$newBed) {
            return response()->json(['message' => 'Selected bed is not available'], 422);
        }

        $oldBed = $admission->bed;
        $oldWard = $admission->ward;

        DB::beginTransaction();
        try {
            // Create transfer record
            BedTransfer::create([
                'hospital_id' => $hospitalId,
                'ipd_id' => $admission->ipd_id,
                'from_bed_id' => $admission->bed_id,
                'to_bed_id' => $request->to_bed_id,
                'from_ward_id' => $admission->ward_id,
                'to_ward_id' => $newBed->ward_id,
                'transfer_datetime' => now(),
                'transfer_reason' => $request->transfer_reason,
                'remarks' => $request->remarks,
                'transferred_by' => Auth::id(),
            ]);

            // Release old bed
            $oldBed->release();

            // Occupy new bed
            $newBed->occupy($admission->patient_id, $admission->ipd_id);

            // Update admission
            $admission->update([
                'bed_id' => $request->to_bed_id,
                'ward_id' => $newBed->ward_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Patient transferred successfully',
                'admission' => $admission->fresh(['patient', 'treatingDoctor', 'ward', 'bed']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transfer failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Add progress note
     */
    public function addProgressNote(Request $request, string $id)
    {
        $request->validate([
            'note_type' => 'required|in:round,consultation,procedure,handover,other',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('status', 'admitted')
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        // Get doctor_id from auth user or request
        $doctorId = $request->doctor_id;
        if (!$doctorId) {
            $doctor = Doctor::where('user_id', Auth::id())->first();
            $doctorId = $doctor ? $doctor->doctor_id : null;
        }

        if (!$doctorId) {
            return response()->json(['message' => 'Doctor ID is required'], 422);
        }

        $note = IpdProgressNote::create([
            'hospital_id' => $hospitalId,
            'ipd_id' => $id,
            'doctor_id' => $doctorId,
            'note_date' => now()->toDateString(),
            'note_time' => now()->format('H:i:s'),
            'subjective' => $request->subjective,
            'objective' => $request->objective,
            'assessment' => $request->assessment,
            'plan' => $request->plan,
            'general_notes' => $request->general_notes,
            'instructions' => $request->instructions,
            'note_type' => $request->note_type,
        ]);

        return response()->json([
            'message' => 'Progress note added successfully',
            'note' => $note->load('doctor'),
        ], 201);
    }

    /**
     * Get progress notes for an admission
     */
    public function getProgressNotes(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $notes = IpdProgressNote::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->with('doctor')
            ->orderBy('note_date', 'desc')
            ->orderBy('note_time', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($notes);
    }

    /**
     * Add nursing chart entry
     */
    public function addNursingChart(Request $request, string $id)
    {
        $request->validate([
            'shift' => 'required|in:morning,evening,night',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('status', 'admitted')
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        $chart = IpdNursingChart::create([
            'hospital_id' => $hospitalId,
            'ipd_id' => $id,
            'nurse_id' => Auth::id(),
            'chart_date' => $request->chart_date ?? now()->toDateString(),
            'shift' => $request->shift,
            'bp_systolic' => $request->bp_systolic,
            'bp_diastolic' => $request->bp_diastolic,
            'pulse' => $request->pulse,
            'temperature' => $request->temperature,
            'spo2' => $request->spo2,
            'respiratory_rate' => $request->respiratory_rate,
            'blood_sugar' => $request->blood_sugar,
            'oral_intake_ml' => $request->oral_intake_ml,
            'iv_intake_ml' => $request->iv_intake_ml,
            'urine_output_ml' => $request->urine_output_ml,
            'drain_output_ml' => $request->drain_output_ml,
            'vomit_ml' => $request->vomit_ml,
            'general_condition' => $request->general_condition,
            'pain_assessment' => $request->pain_assessment,
            'wound_assessment' => $request->wound_assessment,
            'iv_site_assessment' => $request->iv_site_assessment,
            'nursing_notes' => $request->nursing_notes,
            'medications_given' => $request->medications_given,
            'patient_response' => $request->patient_response,
        ]);

        return response()->json([
            'message' => 'Nursing chart added successfully',
            'chart' => $chart,
        ], 201);
    }

    /**
     * Get nursing charts for an admission
     */
    public function getNursingCharts(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = IpdNursingChart::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->with('nurse');

        if ($request->has('date') && $request->date) {
            $query->whereDate('chart_date', $request->date);
        }

        if ($request->has('shift') && $request->shift) {
            $query->where('shift', $request->shift);
        }

        $charts = $query->orderBy('chart_date', 'desc')
            ->orderByRaw("FIELD(shift, 'morning', 'evening', 'night')")
            ->paginate($request->per_page ?? 20);

        return response()->json($charts);
    }

    /**
     * Add service charge
     */
    public function addService(Request $request, string $id)
    {
        $request->validate([
            'service_name' => 'required|string|max:200',
            'service_type' => 'required|in:bed,doctor_visit,nursing,procedure,lab,radiology,pharmacy,ot,icu,consumable,other',
            'quantity' => 'required|integer|min:1',
            'rate' => 'required|numeric|min:0',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->whereIn('status', ['admitted', 'discharge_initiated'])
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        $service = IpdService::create([
            'hospital_id' => $hospitalId,
            'ipd_id' => $id,
            'service_date' => $request->service_date ?? now()->toDateString(),
            'service_type' => $request->service_type,
            'service_id' => $request->service_id,
            'service_name' => $request->service_name,
            'quantity' => $request->quantity,
            'rate' => $request->rate,
            'discount' => $request->discount ?? 0,
            'is_package' => $request->is_package ?? false,
            'remarks' => $request->remarks,
            'created_by' => Auth::id(),
        ]);

        // Update admission totals
        $this->updateAdmissionBilling($admission);

        return response()->json([
            'message' => 'Service added successfully',
            'service' => $service,
        ], 201);
    }

    /**
     * Get services for an admission
     */
    public function getServices(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = IpdService::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id);

        if ($request->has('service_type') && $request->service_type) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->has('is_billed') && $request->is_billed !== '') {
            $query->where('is_billed', $request->is_billed);
        }

        $services = $query->orderBy('service_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($services);
    }

    /**
     * Add medication order
     */
    public function addMedication(Request $request, string $id)
    {
        $request->validate([
            'drug_name' => 'required|string|max:200',
            'dosage' => 'required|string|max:50',
            'route' => 'required|in:oral,iv,im,sc,topical,inhalation,pr,sl',
            'frequency' => 'required|string|max:50',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('status', 'admitted')
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        // Get doctor_id from auth user or request
        $doctorId = $request->doctor_id;
        if (!$doctorId) {
            $doctor = Doctor::where('user_id', Auth::id())->first();
            $doctorId = $doctor ? $doctor->doctor_id : null;
        }

        $medication = IpdMedication::create([
            'hospital_id' => $hospitalId,
            'ipd_id' => $id,
            'order_date' => now()->toDateString(),
            'order_time' => now()->format('H:i:s'),
            'drug_id' => $request->drug_id,
            'drug_name' => $request->drug_name,
            'dosage' => $request->dosage,
            'route' => $request->route,
            'frequency' => $request->frequency,
            'duration_days' => $request->duration_days,
            'start_date' => $request->start_date ?? now()->toDateString(),
            'end_date' => $request->end_date,
            'quantity_ordered' => $request->quantity_ordered ?? 0,
            'instructions' => $request->instructions,
            'status' => 'ordered',
            'ordered_by' => $doctorId,
        ]);

        return response()->json([
            'message' => 'Medication order added successfully',
            'medication' => $medication,
        ], 201);
    }

    /**
     * Get medications for an admission
     */
    public function getMedications(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = IpdMedication::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('active_only') && $request->active_only) {
            $query->active();
        }

        $medications = $query->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->get();

        return response()->json($medications);
    }

    /**
     * Update medication status
     */
    public function updateMedication(Request $request, string $id, string $medicationId)
    {
        $hospitalId = Auth::user()->hospital_id;

        $medication = IpdMedication::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('medication_id', $medicationId)
            ->first();

        if (!$medication) {
            return response()->json(['message' => 'Medication not found'], 404);
        }

        $medication->update($request->only([
            'status',
            'quantity_issued',
            'quantity_returned',
            'end_date',
            'instructions',
        ]));

        return response()->json([
            'message' => 'Medication updated successfully',
            'medication' => $medication,
        ]);
    }

    /**
     * Add investigation order
     */
    public function addInvestigation(Request $request, string $id)
    {
        $request->validate([
            'investigation_name' => 'required|string|max:200',
            'investigation_type' => 'required|in:pathology,radiology,procedure',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('status', 'admitted')
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        // Get doctor_id from auth user or request
        $doctorId = $request->doctor_id;
        if (!$doctorId) {
            $doctor = Doctor::where('user_id', Auth::id())->first();
            $doctorId = $doctor ? $doctor->doctor_id : null;
        }

        $investigation = IpdInvestigation::create([
            'hospital_id' => $hospitalId,
            'ipd_id' => $id,
            'order_date' => now()->toDateString(),
            'order_time' => now()->format('H:i:s'),
            'investigation_type' => $request->investigation_type,
            'test_id' => $request->test_id,
            'investigation_name' => $request->investigation_name,
            'priority' => $request->priority ?? 'routine',
            'clinical_notes' => $request->clinical_notes,
            'rate' => $request->rate ?? 0,
            'status' => 'ordered',
            'ordered_by' => $doctorId,
        ]);

        return response()->json([
            'message' => 'Investigation ordered successfully',
            'investigation' => $investigation,
        ], 201);
    }

    /**
     * Get investigations for an admission
     */
    public function getInvestigations(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = IpdInvestigation::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id);

        if ($request->has('investigation_type') && $request->investigation_type) {
            $query->where('investigation_type', $request->investigation_type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $investigations = $query->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc')
            ->get();

        return response()->json($investigations);
    }

    /**
     * Collect advance payment
     */
    public function collectAdvance(Request $request, string $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:cash,card,upi,neft,cheque,dd',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->whereIn('status', ['admitted', 'discharge_initiated'])
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        $receiptNumber = IpdAdvancePayment::generateReceiptNumber($hospitalId);

        $payment = IpdAdvancePayment::create([
            'hospital_id' => $hospitalId,
            'ipd_id' => $id,
            'receipt_number' => $receiptNumber,
            'payment_date' => now()->toDateString(),
            'amount' => $request->amount,
            'payment_mode' => $request->payment_mode,
            'reference_number' => $request->reference_number,
            'remarks' => $request->remarks,
            'received_by' => Auth::id(),
        ]);

        // Update admission advance amount
        $totalAdvance = IpdAdvancePayment::where('ipd_id', $id)
            ->where('is_refunded', false)
            ->sum('amount');

        $admission->update(['advance_amount' => $totalAdvance]);

        return response()->json([
            'message' => 'Advance payment collected successfully',
            'payment' => $payment,
            'receipt_number' => $receiptNumber,
        ], 201);
    }

    /**
     * Get advance payments for an admission
     */
    public function getAdvancePayments(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $payments = IpdAdvancePayment::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->with('receivedByUser')
            ->orderBy('payment_date', 'desc')
            ->get();

        $summary = [
            'total_collected' => $payments->sum('amount'),
            'total_refunded' => $payments->sum('refund_amount'),
            'net_advance' => $payments->sum('amount') - $payments->sum('refund_amount'),
        ];

        return response()->json([
            'payments' => $payments,
            'summary' => $summary,
        ]);
    }

    /**
     * Get running bill for an admission
     */
    public function getRunningBill(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->with(['patient', 'bed', 'ward', 'services'])
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'IPD admission not found'], 404);
        }

        $services = IpdService::where('ipd_id', $id)
            ->orderBy('service_date', 'desc')
            ->get()
            ->groupBy('service_type');

        // Calculate bed charges
        $losDays = $admission->los_days;
        $bedChargesPerDay = $admission->bed->charges_per_day ?? 0;
        $bedCharges = $losDays * $bedChargesPerDay;

        // Services by type
        $servicesSummary = [];
        foreach ($services as $type => $items) {
            $servicesSummary[$type] = [
                'count' => $items->count(),
                'total' => $items->sum('net_amount'),
            ];
        }

        // Calculate totals
        $servicesTotal = IpdService::where('ipd_id', $id)->sum('net_amount');
        $grossTotal = $bedCharges + $servicesTotal;
        $discount = $admission->discount_amount ?? 0;
        $netTotal = $grossTotal - $discount;
        $advancePaid = $admission->advance_amount ?? 0;
        $balanceDue = $netTotal - $advancePaid;

        return response()->json([
            'patient' => [
                'name' => $admission->patient->full_name,
                'ipd_number' => $admission->ipd_number,
                'admission_date' => $admission->admission_date,
                'los_days' => $losDays,
            ],
            'bed_details' => [
                'ward' => $admission->ward->ward_name ?? null,
                'bed' => $admission->bed->bed_number ?? null,
                'charges_per_day' => $bedChargesPerDay,
                'total_days' => $losDays,
                'total_charges' => $bedCharges,
            ],
            'services_summary' => $servicesSummary,
            'services' => $services,
            'billing' => [
                'bed_charges' => $bedCharges,
                'services_total' => $servicesTotal,
                'gross_total' => $grossTotal,
                'discount' => $discount,
                'net_total' => $netTotal,
                'advance_paid' => $advancePaid,
                'balance_due' => $balanceDue,
            ],
        ]);
    }

    /**
     * Initiate discharge process
     */
    public function initiateDischarge(Request $request, string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->where('status', 'admitted')
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        $admission->update([
            'status' => 'discharge_initiated',
            'discharge_type' => $request->discharge_type ?? 'normal',
            'condition_at_discharge' => $request->condition_at_discharge,
        ]);

        return response()->json([
            'message' => 'Discharge process initiated',
            'admission' => $admission,
        ]);
    }

    /**
     * Complete discharge
     */
    public function completeDischarge(Request $request, string $id)
    {
        $request->validate([
            'discharge_summary' => 'required|string',
            'discharge_type' => 'required|in:normal,lama,dor,absconded,referred,expired',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->whereIn('status', ['admitted', 'discharge_initiated'])
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'Active IPD admission not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Update admission
            $admission->update([
                'status' => 'discharged',
                'discharge_date' => now()->toDateString(),
                'discharge_time' => now()->format('H:i:s'),
                'discharge_type' => $request->discharge_type,
                'discharge_summary' => $request->discharge_summary,
                'condition_at_discharge' => $request->condition_at_discharge,
                'followup_advice' => $request->followup_advice,
                'followup_date' => $request->followup_date,
                'discharged_by' => Auth::id(),
            ]);

            // Handle death case
            if ($request->discharge_type === 'expired') {
                $admission->update([
                    'death_date' => $request->death_date ?? now()->toDateString(),
                    'death_time' => $request->death_time ?? now()->format('H:i:s'),
                    'cause_of_death' => $request->cause_of_death,
                ]);
            }

            // Release bed
            if ($admission->bed) {
                $admission->bed->release();
            }

            DB::commit();

            return response()->json([
                'message' => 'Patient discharged successfully',
                'admission' => $admission->fresh(['patient', 'treatingDoctor']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Discharge failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update admission billing totals
     */
    private function updateAdmissionBilling(IpdAdmission $admission)
    {
        $totalServices = IpdService::where('ipd_id', $admission->ipd_id)->sum('net_amount');
        $losDays = $admission->los_days;
        $bedCharges = $losDays * ($admission->bed->charges_per_day ?? 0);

        $totalCharges = $totalServices + $bedCharges;
        $discount = $admission->discount_amount ?? 0;
        $tax = $admission->tax_amount ?? 0;
        $netAmount = $totalCharges - $discount + $tax;
        $dueAmount = $netAmount - ($admission->advance_amount ?? 0) - ($admission->paid_amount ?? 0);

        $admission->update([
            'total_charges' => $totalCharges,
            'net_amount' => $netAmount,
            'due_amount' => $dueAmount,
        ]);
    }

    /**
     * Get doctor's patient list
     */
    public function doctorPatientList(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        // Get doctor_id from request or from authenticated user
        $doctorId = $request->doctor_id;
        if (!$doctorId) {
            $doctor = Doctor::where('user_id', Auth::id())->first();
            $doctorId = $doctor ? $doctor->doctor_id : null;
        }

        if (!$doctorId) {
            return response()->json(['message' => 'Doctor ID is required'], 422);
        }

        $admissions = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('status', 'admitted')
            ->where(function($q) use ($doctorId) {
                $q->where('treating_doctor_id', $doctorId)
                  ->orWhere('consultant_doctor_id', $doctorId);
            })
            ->with(['patient', 'ward', 'bed', 'department'])
            ->orderBy('admission_date', 'desc')
            ->get();

        return response()->json($admissions);
    }

    /**
     * Remove the specified resource from storage (Cancel admission)
     */
    public function destroy(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->first();

        if (!$admission) {
            return response()->json(['message' => 'IPD admission not found'], 404);
        }

        if ($admission->status !== 'admitted') {
            return response()->json(['message' => 'Only active admissions can be cancelled'], 422);
        }

        // Check for any services or charges
        $hasServices = IpdService::where('ipd_id', $id)->exists();
        if ($hasServices) {
            return response()->json(['message' => 'Cannot cancel admission with services. Please remove services first.'], 422);
        }

        DB::beginTransaction();
        try {
            // Release bed
            if ($admission->bed) {
                $admission->bed->release();
            }

            // Update status
            $admission->update(['status' => 'cancelled']);

            DB::commit();

            return response()->json(['message' => 'Admission cancelled successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Cancel failed: ' . $e->getMessage()], 500);
        }
    }
}
