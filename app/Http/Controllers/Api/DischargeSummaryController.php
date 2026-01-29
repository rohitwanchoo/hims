<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DischargeSummary;
use App\Models\DischargeSummaryCustomFieldValue;
use App\Models\IpdAdmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DischargeSummaryController extends Controller
{
    /**
     * Display a listing of discharge summaries
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DischargeSummary::where('hospital_id', $hospitalId)
            ->with([
                'patient',
                'ipdAdmission',
                'treatingDoctor',
                'consultantDoctor'
            ])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('discharge_summary_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->whereDate('discharge_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('discharge_date', '<=', $request->to_date);
        }

        if ($request->has('ipd_id')) {
            $query->where('ipd_id', $request->ipd_id);
        }

        $perPage = $request->get('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get IPD patients for discharge summary creation
     */
    public function getDischargedPatients()
    {
        $hospitalId = Auth::user()->hospital_id;

        $patients = IpdAdmission::where('hospital_id', $hospitalId)
            ->whereIn('status', ['admitted', 'discharged'])
            ->whereNotIn('ipd_id', function ($query) {
                $query->select('ipd_id')
                      ->from('discharge_summaries')
                      ->whereNull('deleted_at');
            })
            ->with(['patient', 'bed', 'ward'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($patients);
    }

    /**
     * Get doctors for discharge summary
     */
    public function getDoctors()
    {
        $hospitalId = Auth::user()->hospital_id;

        $doctors = \App\Models\User::where('hospital_id', $hospitalId)
            ->where('role', 'doctor')
            ->where('is_active', true)
            ->select('user_id', 'full_name', 'email', 'department_id')
            ->orderBy('full_name')
            ->get();

        return response()->json($doctors);
    }

    /**
     * Store a newly created discharge summary
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ipd_id' => 'required|exists:ipd_admissions,ipd_id',
            'admission_date' => 'required|date',
            'discharge_date' => 'required|date|after_or_equal:admission_date',
            'admission_type' => 'required|in:emergency,planned',
            'chief_complaints' => 'nullable|string',
            'history_of_present_illness' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'vital_signs' => 'nullable|string',
            'provisional_diagnosis' => 'nullable|string',
            'final_diagnosis' => 'required|string',
            'secondary_diagnosis' => 'nullable|string',
            'icd_codes' => 'nullable|string',
            'course_in_hospital' => 'nullable|string',
            'procedures_performed' => 'nullable|string',
            'operation_notes' => 'nullable|string',
            'investigations' => 'nullable|string',
            'treatment_given' => 'nullable|string',
            'medications_on_admission' => 'nullable|string',
            'medications_on_discharge' => 'nullable|string',
            'condition_at_discharge' => 'required|in:improved,same,deteriorated,expired',
            'discharge_advice' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'dietary_instructions' => 'nullable|string',
            'activity_restrictions' => 'nullable|string',
            'treating_doctor_id' => 'nullable|exists:doctors,doctor_id',
            'consultant_doctor_id' => 'nullable|exists:doctors,doctor_id',
            'abha_address' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,completed,signed',
        ]);

        $ipdAdmission = IpdAdmission::findOrFail($validated['ipd_id']);
        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['patient_id'] = $ipdAdmission->patient_id;
        $validated['created_by'] = Auth::id();

        // Handle custom fields
        $customFields = $request->input('custom_fields', []);

        DB::beginTransaction();
        try {
            $summary = DischargeSummary::create($validated);

            // Save custom field values
            foreach ($customFields as $fieldId => $value) {
                if ($value !== null && $value !== '') {
                    DischargeSummaryCustomFieldValue::create([
                        'discharge_summary_id' => $summary->discharge_summary_id,
                        'field_id' => $fieldId,
                        'field_value' => is_array($value) ? json_encode($value) : $value,
                    ]);
                }
            }

            DB::commit();

            return response()->json($summary->load([
                'patient',
                'ipdAdmission',
                'treatingDoctor',
                'consultantDoctor',
                'customFieldValues.customField'
            ]), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating discharge summary: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified discharge summary
     */
    public function show(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $summary = DischargeSummary::where('hospital_id', $hospitalId)
            ->with([
                'patient',
                'ipdAdmission.bed',
                'ipdAdmission.ward',
                'treatingDoctor',
                'consultantDoctor',
                'creator',
                'customFieldValues.customField'
            ])
            ->findOrFail($id);

        return response()->json($summary);
    }

    /**
     * Update the specified discharge summary
     */
    public function update(Request $request, string $id)
    {
        $summary = DischargeSummary::findOrFail($id);

        if ($summary->status === 'signed') {
            return response()->json([
                'message' => 'Cannot update signed discharge summary'
            ], 400);
        }

        $validated = $request->validate([
            'admission_date' => 'sometimes|date',
            'discharge_date' => 'sometimes|date',
            'admission_type' => 'sometimes|in:emergency,planned',
            'chief_complaints' => 'nullable|string',
            'history_of_present_illness' => 'nullable|string',
            'past_medical_history' => 'nullable|string',
            'family_history' => 'nullable|string',
            'physical_examination' => 'nullable|string',
            'vital_signs' => 'nullable|string',
            'provisional_diagnosis' => 'nullable|string',
            'final_diagnosis' => 'sometimes|string',
            'secondary_diagnosis' => 'nullable|string',
            'icd_codes' => 'nullable|string',
            'course_in_hospital' => 'nullable|string',
            'procedures_performed' => 'nullable|string',
            'operation_notes' => 'nullable|string',
            'investigations' => 'nullable|string',
            'treatment_given' => 'nullable|string',
            'medications_on_admission' => 'nullable|string',
            'medications_on_discharge' => 'nullable|string',
            'condition_at_discharge' => 'sometimes|in:improved,same,deteriorated,expired',
            'discharge_advice' => 'nullable|string',
            'follow_up_instructions' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
            'dietary_instructions' => 'nullable|string',
            'activity_restrictions' => 'nullable|string',
            'treating_doctor_id' => 'nullable|exists:doctors,doctor_id',
            'consultant_doctor_id' => 'nullable|exists:doctors,doctor_id',
            'abha_address' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:draft,completed,signed',
        ]);

        // Handle custom fields
        $customFields = $request->input('custom_fields', []);

        DB::beginTransaction();
        try {
            $summary->update($validated);

            // Update custom field values
            foreach ($customFields as $fieldId => $value) {
                DischargeSummaryCustomFieldValue::updateOrCreate(
                    [
                        'discharge_summary_id' => $summary->discharge_summary_id,
                        'field_id' => $fieldId,
                    ],
                    [
                        'field_value' => is_array($value) ? json_encode($value) : $value,
                    ]
                );
            }

            DB::commit();

            return response()->json($summary->load([
                'patient',
                'ipdAdmission',
                'treatingDoctor',
                'consultantDoctor',
                'customFieldValues.customField'
            ]));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating discharge summary: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified discharge summary
     */
    public function destroy(string $id)
    {
        $summary = DischargeSummary::findOrFail($id);

        if ($summary->status === 'signed') {
            return response()->json([
                'message' => 'Cannot delete signed discharge summary'
            ], 400);
        }

        $summary->delete();

        return response()->json([
            'message' => 'Discharge summary deleted successfully'
        ]);
    }

    /**
     * Print discharge summary
     */
    public function print(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $summary = DischargeSummary::where('hospital_id', $hospitalId)
            ->with([
                'patient',
                'ipdAdmission',
                'treatingDoctor',
                'consultantDoctor',
                'hospital'
            ])
            ->findOrFail($id);

        // Return view for printing (you can create a PDF later)
        return response()->json([
            'summary' => $summary,
            'message' => 'Print data retrieved successfully'
        ]);
    }
}
