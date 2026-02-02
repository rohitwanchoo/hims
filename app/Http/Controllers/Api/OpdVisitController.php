<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdVisit;
use App\Models\OpdVisitService;
use App\Models\OpdInvestigation;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\SkillSetVisitValidity;
use App\Models\HealthPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpdVisitController extends Controller
{
    /**
     * Display today's OPD registrations
     */
    public function index(Request $request)
    {
        $query = OpdVisit::with([
            'patient',
            'doctor',
            'department',
            'patientClass',
            'referenceDoctor',
            'services.service',
            'bill'
        ]);

        // Filter by date (default: today)
        $date = $request->date ?? now()->toDateString();
        $query->whereDate('visit_date', $date);

        // Filter by doctor
        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by department
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by registration purpose
        if ($request->registration_purpose) {
            $query->where('registration_purpose', $request->registration_purpose);
        }

        // Filter by class
        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        // Search by patient name, CR No, mobile
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('pcd', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $visits = $query->orderBy('opd_id', 'desc')->get();

        // Summary counts
        $summary = [
            'total' => $visits->count(),
            'waiting' => $visits->where('status', 'waiting')->count(),
            'in_consultation' => $visits->where('status', 'in_consultation')->count(),
            'completed' => $visits->where('status', 'completed')->count(),
            'cancelled' => $visits->where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'visits' => $visits,
            'summary' => $summary,
        ]);
    }

    /**
     * Create new OPD registration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Patient (new or existing)
            'patient_id' => 'nullable|exists:patients,patient_id',
            'patient' => 'required_without:patient_id|array',
            'patient.patient_name' => 'required_without:patient_id|string|max:100',
            'patient.gender' => 'required_without:patient_id|in:male,female,other',
            'patient.mobile' => 'nullable|string|max:15',
            'patient.age' => 'nullable|integer',
            'patient.age_unit' => 'nullable|in:days,months,years',

            // Registration Details
            'registration_purpose' => 'required|in:normal,direct,health_checkup,emergency',
            'visit_date' => 'nullable|date',
            'visit_time' => 'nullable',
            'department_id' => 'nullable|exists:departments,department_id',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'visit_type' => 'nullable|in:new,followup,referral,emergency',
            'charges_type' => 'nullable|in:normal,day_emergency,night_emergency',

            // Health Checkup
            'health_package_id' => 'nullable|exists:health_packages,package_id',

            // MLC/Insurance
            'is_mlc' => 'boolean',
            'mlc_number' => 'nullable|string|max:50',
            'police_station' => 'nullable|string|max:100',
            'is_insurance' => 'boolean',
            'insurance_company_name' => 'nullable|string|max:150',

            // Services
            'services' => 'nullable|array',
            'services.*.service_id' => 'required|exists:services,service_id',
            'services.*.quantity' => 'nullable|integer|min:1',

            // Chief complaints
            'chief_complaints' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // Create or get patient
            if (empty($validated['patient_id'])) {
                $lastPatient = Patient::orderBy('patient_id', 'desc')->first();
                $nextId = $lastPatient ? $lastPatient->patient_id + 1 : 1;

                $patientData = $validated['patient'];
                $patientData['pcd'] = 'PAT' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
                $patientData['registration_date'] = now()->toDateString();

                if (!empty($validated['class_id'])) {
                    $patientData['class_id'] = $validated['class_id'];
                }
                if (!empty($validated['reference_doctor_id'])) {
                    $patientData['reference_doctor_id'] = $validated['reference_doctor_id'];
                }

                $patient = Patient::create($patientData);
                $patientId = $patient->patient_id;
            } else {
                $patientId = $validated['patient_id'];
                $patient = Patient::find($patientId);
            }

            // Check if patient already registered today
            $visitDate = $validated['visit_date'] ?? now()->toDateString();
            $existingVisit = OpdVisit::where('patient_id', $patientId)
                ->whereDate('visit_date', $visitDate)
                ->whereIn('status', ['waiting', 'in_consultation'])
                ->first();

            if ($existingVisit) {
                return response()->json([
                    'message' => 'Patient already registered today',
                    'error' => 'This patient already has an active OPD visit for today (Token #' . $existingVisit->token_number . '). Please check the OPD list.',
                    'existing_visit' => $existingVisit
                ], 422);
            }

            // Generate OPD number
            $todayCount = OpdVisit::whereDate('visit_date', now()->toDateString())->count();
            $opdNumber = 'OPD' . now()->format('Ymd') . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

            // Generate token number
            $tokenNumber = $todayCount + 1;
            if (!empty($validated['doctor_id'])) {
                $tokenNumber = OpdVisit::whereDate('visit_date', now()->toDateString())
                    ->where('doctor_id', $validated['doctor_id'])
                    ->count() + 1;
            }

            // Check for free followup
            $isFreeFollowup = false;
            $previousVisitId = null;
            if (($validated['visit_type'] ?? 'new') !== 'new' && !empty($validated['doctor_id'])) {
                $doctor = Doctor::find($validated['doctor_id']);
                if ($doctor && $doctor->skill_set_id) {
                    $validity = SkillSetVisitValidity::where('skill_set_id', $doctor->skill_set_id)->first();
                    if ($validity) {
                        $lastVisit = OpdVisit::where('patient_id', $patientId)
                            ->where('doctor_id', $validated['doctor_id'])
                            ->where('status', 'completed')
                            ->orderBy('visit_date', 'desc')
                            ->first();

                        if ($lastVisit) {
                            $daysSinceLastVisit = now()->diffInDays($lastVisit->visit_date);
                            if ($daysSinceLastVisit <= $validity->free_followup_validity_days) {
                                $isFreeFollowup = true;
                                $previousVisitId = $lastVisit->opd_id;
                            }
                        }
                    }
                }
            }

            // Calculate registration expiry
            $expiryDate = now()->addDays(1)->toDateString();
            if (!empty($validated['doctor_id'])) {
                $doctor = Doctor::find($validated['doctor_id']);
                if ($doctor && $doctor->skill_set_id) {
                    $validity = SkillSetVisitValidity::where('skill_set_id', $doctor->skill_set_id)->first();
                    if ($validity) {
                        $expiryDate = now()->addDays($validity->followup_validity_days)->toDateString();
                    }
                }
            }

            // Get consultation fee
            $consultationFee = 0;
            if (!empty($validated['doctor_id']) && !$isFreeFollowup) {
                $doctor = Doctor::find($validated['doctor_id']);
                $consultationFee = $doctor->consultation_fee ?? 0;
            }

            // Create OPD visit
            $opdVisit = OpdVisit::create([
                'opd_number' => $opdNumber,
                'registration_purpose' => $validated['registration_purpose'],
                'patient_id' => $patientId,
                'visit_date' => $validated['visit_date'] ?? now()->toDateString(),
                'visit_time' => $validated['visit_time'] ?? now()->toTimeString(),
                'registration_expiry_date' => $expiryDate,
                'token_number' => $tokenNumber,
                'department_id' => $validated['department_id'] ?? null,
                'doctor_id' => $validated['doctor_id'] ?? null,
                'reference_doctor_id' => $validated['reference_doctor_id'] ?? null,
                'class_id' => $validated['class_id'] ?? $patient->class_id,
                'health_package_id' => $validated['health_package_id'] ?? null,
                'visit_type' => $validated['visit_type'] ?? 'new',
                'chief_complaints' => $validated['chief_complaints'] ?? null,
                'is_free_followup' => $isFreeFollowup,
                'previous_visit_id' => $previousVisitId,
                'consultation_fee' => $consultationFee,
                'is_mlc' => $validated['is_mlc'] ?? false,
                'mlc_number' => $validated['mlc_number'] ?? null,
                'police_station' => $validated['police_station'] ?? null,
                'is_insurance' => $validated['is_insurance'] ?? false,
                'insurance_company_name' => $validated['insurance_company_name'] ?? null,
                'status' => 'waiting',
                'payment_status' => 'pending',
                'created_by' => $request->user()->id ?? null,
            ]);

            // Add services
            $totalAmount = $consultationFee;

            // If health checkup, add package services
            if (!empty($validated['health_package_id'])) {
                $package = HealthPackage::with('services.service')->find($validated['health_package_id']);
                foreach ($package->services as $packageService) {
                    OpdVisitService::create([
                        'opd_id' => $opdVisit->opd_id,
                        'service_id' => $packageService->service_id,
                        'quantity' => $packageService->quantity,
                        'rate' => $packageService->service->rate,
                        'amount' => $packageService->service->rate * $packageService->quantity,
                    ]);
                }
                $totalAmount = $package->package_rate;
            } elseif (!empty($validated['services'])) {
                foreach ($validated['services'] as $serviceData) {
                    $service = Service::find($serviceData['service_id']);
                    $quantity = $serviceData['quantity'] ?? 1;
                    $rate = $service->rate;

                    // Check if this is a free followup service
                    $serviceFreeFollowup = $isFreeFollowup && $service->is_free_followup;
                    if ($serviceFreeFollowup) {
                        $rate = 0;
                    }

                    OpdVisitService::create([
                        'opd_id' => $opdVisit->opd_id,
                        'service_id' => $serviceData['service_id'],
                        'quantity' => $quantity,
                        'rate' => $rate,
                        'amount' => $rate * $quantity,
                        'is_free_followup' => $serviceFreeFollowup,
                    ]);

                    $totalAmount += $rate * $quantity;
                }
            }

            // Update totals
            $opdVisit->update([
                'total_amount' => $totalAmount,
                'net_amount' => $totalAmount,
                'due_amount' => $totalAmount,
            ]);

            return response()->json($opdVisit->load([
                'patient',
                'doctor',
                'department',
                'patientClass',
                'referenceDoctor',
                'services.service',
                'healthPackage',
            ]), 201);
        });
    }

    /**
     * Display the specified OPD visit
     */
    public function show(string $id)
    {
        $opdVisit = OpdVisit::with([
            'patient',
            'doctor',
            'department',
            'patientClass',
            'patientClass.client',
            'referenceDoctor',
            'healthPackage',
            'services.service',
            'investigations',
            'prescriptions.items',
            'labOrders.details',
            'previousVisit',
            'followupVisits',
            'bill',
        ])->findOrFail($id);

        return response()->json($opdVisit);
    }

    /**
     * Update OPD visit (consultation entry)
     */
    public function update(Request $request, string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);

        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,department_id',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'chief_complaints' => 'nullable|string',
            'history_of_illness' => 'nullable|string',
            'examination_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'advice' => 'nullable|string',
            'followup_date' => 'nullable|date',
            'followup_instructions' => 'nullable|string',
            'vitals_bp_systolic' => 'nullable|integer',
            'vitals_bp_diastolic' => 'nullable|integer',
            'vitals_pulse' => 'nullable|integer',
            'vitals_temperature' => 'nullable|numeric',
            'vitals_spo2' => 'nullable|integer',
            'vitals_weight' => 'nullable|numeric',
            'vitals_height' => 'nullable|numeric',
            'status' => 'nullable|in:waiting,in_consultation,completed,cancelled',
            'cancel_reason_id' => 'nullable|exists:cancel_reasons,cancel_reason_id',
        ]);

        // Recalculate consultation fee if doctor is changed and not a free followup
        if (isset($validated['doctor_id']) && !$opdVisit->is_free_followup) {
            $doctor = \App\Models\Doctor::find($validated['doctor_id']);
            if ($doctor) {
                $validated['consultation_fee'] = $doctor->consultation_fee ?? 0;

                // Recalculate totals
                $servicesTotal = $opdVisit->services()->sum('amount') ?? 0;
                $totalAmount = $validated['consultation_fee'] + $servicesTotal;
                $discount = $opdVisit->discount_amount ?? 0;
                $netAmount = $totalAmount - $discount;
                $paidAmount = $opdVisit->paid_amount ?? 0;
                $dueAmount = $netAmount - $paidAmount;

                $validated['total_amount'] = $totalAmount;
                $validated['net_amount'] = $netAmount;
                $validated['due_amount'] = $dueAmount;
            }
        }

        $opdVisit->update($validated);

        return response()->json($opdVisit->load([
            'patient',
            'doctor',
            'services.service',
            'investigations',
        ]));
    }

    /**
     * Cancel OPD visit
     */
    public function destroy(string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);

        $opdVisit->update([
            'status' => 'cancelled',
        ]);

        return response()->json(['message' => 'OPD visit cancelled']);
    }

    /**
     * Start consultation
     */
    public function startConsultation(string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);
        $opdVisit->update(['status' => 'in_consultation']);

        return response()->json([
            'message' => 'Consultation started',
            'visit' => $opdVisit,
        ]);
    }

    /**
     * Complete consultation
     */
    public function completeConsultation(Request $request, string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);

        $validated = $request->validate([
            'diagnosis' => 'nullable|string',
            'advice' => 'nullable|string',
            'followup_date' => 'nullable|date',
            'followup_instructions' => 'nullable|string',
        ]);

        $opdVisit->update(array_merge($validated, ['status' => 'completed']));

        return response()->json([
            'message' => 'Consultation completed',
            'visit' => $opdVisit->load(['patient', 'doctor', 'services.service']),
        ]);
    }

    /**
     * Add investigation to OPD visit
     */
    public function addInvestigation(Request $request, string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);

        $validated = $request->validate([
            'investigation_type' => 'required|in:pathology,radiology,procedure',
            'test_id' => 'nullable|exists:lab_tests,test_id',
            'service_id' => 'nullable|exists:services,service_id',
            'investigation_name' => 'required|string|max:200',
            'rate' => 'nullable|numeric|min:0',
            'clinical_notes' => 'nullable|string',
            'priority' => 'nullable|in:routine,urgent,stat',
        ]);

        $validated['opd_id'] = $opdVisit->opd_id;
        $validated['ordered_by'] = $request->user()->id ?? null;
        $validated['ordered_at'] = now();

        $investigation = OpdInvestigation::create($validated);

        return response()->json($investigation, 201);
    }

    /**
     * Add service to OPD visit
     */
    public function addService(Request $request, string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);

        $validated = $request->validate([
            'service_id' => 'required|exists:services,service_id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $service = Service::find($validated['service_id']);
        $quantity = $validated['quantity'] ?? 1;

        $visitService = OpdVisitService::create([
            'opd_id' => $opdVisit->opd_id,
            'service_id' => $validated['service_id'],
            'quantity' => $quantity,
            'rate' => $service->rate,
            'amount' => $service->rate * $quantity,
        ]);

        // Update totals
        $opdVisit->increment('total_amount', $visitService->amount);
        $opdVisit->increment('net_amount', $visitService->amount);
        $opdVisit->increment('due_amount', $visitService->amount);

        return response()->json($visitService->load('service'), 201);
    }

    /**
     * Get patient's previous visits
     */
    public function patientHistory(string $patientId, Request $request)
    {
        $query = OpdVisit::with(['doctor', 'department', 'services.service'])
            ->where('patient_id', $patientId);

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        $visits = $query->orderBy('visit_date', 'desc')
            ->limit($request->limit ?? 10)
            ->get();

        return response()->json($visits);
    }

    /**
     * Get doctor's today appointments
     */
    public function doctorQueue(string $doctorId, Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $visits = OpdVisit::with(['patient', 'patientClass'])
            ->where('doctor_id', $doctorId)
            ->whereDate('visit_date', $date)
            ->whereIn('status', ['waiting', 'in_consultation'])
            ->orderBy('token_number')
            ->get();

        return response()->json([
            'doctor_id' => $doctorId,
            'date' => $date,
            'queue' => $visits,
            'total_waiting' => $visits->where('status', 'waiting')->count(),
            'current' => $visits->where('status', 'in_consultation')->first(),
        ]);
    }

    /**
     * Record payment for OPD visit
     */
    public function recordPayment(Request $request, string $id)
    {
        $opdVisit = OpdVisit::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_mode' => 'required|string',
            'reference_number' => 'nullable|string',
        ]);

        $opdVisit->increment('paid_amount', $validated['amount']);
        $opdVisit->decrement('due_amount', $validated['amount']);

        if ($opdVisit->due_amount <= 0) {
            $opdVisit->update(['payment_status' => 'paid']);
        } else {
            $opdVisit->update(['payment_status' => 'partial']);
        }

        return response()->json([
            'message' => 'Payment recorded',
            'visit' => $opdVisit->fresh(),
        ]);
    }

    /**
     * Print case paper
     */
    public function casePaper(string $id)
    {
        $opdVisit = OpdVisit::with([
            'patient',
            'doctor',
            'department',
            'patientClass',
            'referenceDoctor',
            'services.service',
        ])->findOrFail($id);

        return response()->json([
            'type' => 'case_paper',
            'data' => $opdVisit,
        ]);
    }

    /**
     * Print receipt
     */
    public function receipt(string $id)
    {
        $opdVisit = OpdVisit::with([
            'patient',
            'doctor',
            'services.service',
        ])->findOrFail($id);

        return response()->json([
            'type' => 'receipt',
            'data' => $opdVisit,
        ]);
    }
}
