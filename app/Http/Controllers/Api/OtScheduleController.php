<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtSchedule;
use App\Models\OtPreOpChecklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = OtSchedule::with([
            'patient',
            'operationTheater',
            'surgeryType',
            'surgeon',
            'anesthetist',
            'ipdAdmission',
        ]);

        if ($request->date) {
            $query->whereDate('scheduled_date', $request->date);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('scheduled_date', [$request->from_date, $request->to_date]);
        }

        if ($request->ot_id) {
            $query->where('ot_id', $request->ot_id);
        }

        if ($request->surgeon_id) {
            $query->where('surgeon_id', $request->surgeon_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('scheduled_date')
            ->orderBy('scheduled_start_time')
            ->paginate($request->per_page ?? 50);

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'ipd_id' => 'nullable|exists:ipd_admissions,ipd_id',
            'ot_id' => 'required|exists:operation_theaters,ot_id',
            'surgery_type_id' => 'required|exists:surgery_types,surgery_type_id',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'scheduled_start_time' => 'required',
            'estimated_duration_mins' => 'nullable|integer|min:15',
            'surgeon_id' => 'required|exists:doctors,doctor_id',
            'anesthetist_id' => 'nullable|exists:doctors,doctor_id',
            'priority' => 'nullable|in:elective,urgent,emergency',
            'pre_op_diagnosis' => 'nullable|string',
            'planned_procedure' => 'nullable|string',
            'special_requirements' => 'nullable|string',
            'blood_units_required' => 'nullable|integer|min:0',
        ]);

        // Check for OT availability
        $conflict = OtSchedule::where('ot_id', $validated['ot_id'])
            ->whereDate('scheduled_date', $validated['scheduled_date'])
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where(function ($q) use ($validated) {
                $q->where('scheduled_start_time', $validated['scheduled_start_time']);
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'OT is not available at the selected time',
            ], 422);
        }

        $schedule = OtSchedule::create([
            ...$validated,
            'status' => 'scheduled',
            'scheduled_by' => auth()->id(),
        ]);

        // Create pre-op checklist
        OtPreOpChecklist::create([
            'schedule_id' => $schedule->schedule_id,
            'patient_id' => $validated['patient_id'],
        ]);

        return response()->json([
            'message' => 'OT schedule created successfully',
            'schedule' => $schedule->load(['patient', 'operationTheater', 'surgeryType']),
        ], 201);
    }

    public function show(OtSchedule $schedule)
    {
        return response()->json([
            'schedule' => $schedule->load([
                'patient',
                'operationTheater',
                'surgeryType',
                'surgeon',
                'anesthetist',
                'preOpChecklist',
                'procedure',
            ]),
        ]);
    }

    public function update(Request $request, OtSchedule $schedule)
    {
        if (in_array($schedule->status, ['in_progress', 'completed'])) {
            return response()->json([
                'message' => 'Cannot update schedule that is in progress or completed',
            ], 422);
        }

        $validated = $request->validate([
            'ot_id' => 'sometimes|exists:operation_theaters,ot_id',
            'scheduled_date' => 'sometimes|date|after_or_equal:today',
            'scheduled_start_time' => 'sometimes',
            'estimated_duration_mins' => 'nullable|integer|min:15',
            'surgeon_id' => 'sometimes|exists:doctors,doctor_id',
            'anesthetist_id' => 'nullable|exists:doctors,doctor_id',
            'priority' => 'nullable|in:elective,urgent,emergency',
            'pre_op_diagnosis' => 'nullable|string',
            'planned_procedure' => 'nullable|string',
            'special_requirements' => 'nullable|string',
        ]);

        $schedule->update($validated);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'schedule' => $schedule,
        ]);
    }

    public function updateStatus(Request $request, OtSchedule $schedule)
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,confirmed,in_progress,completed,postponed,cancelled',
            'reason' => 'required_if:status,postponed,cancelled|nullable|string',
            'new_date' => 'required_if:status,postponed|nullable|date|after_or_equal:today',
        ]);

        $schedule->update([
            'status' => $validated['status'],
            'postponement_reason' => $validated['reason'] ?? null,
        ]);

        if ($validated['status'] === 'postponed' && $validated['new_date']) {
            // Create new schedule for postponed date
            $newSchedule = $schedule->replicate();
            $newSchedule->scheduled_date = $validated['new_date'];
            $newSchedule->status = 'scheduled';
            $newSchedule->save();

            return response()->json([
                'message' => 'Schedule postponed and new schedule created',
                'old_schedule' => $schedule,
                'new_schedule' => $newSchedule,
            ]);
        }

        return response()->json([
            'message' => 'Schedule status updated successfully',
            'schedule' => $schedule,
        ]);
    }

    public function updateChecklist(Request $request, OtSchedule $schedule)
    {
        $validated = $request->validate([
            'consent_surgical' => 'nullable|boolean',
            'consent_anesthesia' => 'nullable|boolean',
            'consent_blood' => 'nullable|boolean',
            'site_marked' => 'nullable|boolean',
            'nil_by_mouth_confirmed' => 'nullable|boolean',
            'blood_available' => 'nullable|boolean',
            'investigations_reviewed' => 'nullable|boolean',
            'medications_reviewed' => 'nullable|boolean',
            'allergies_noted' => 'nullable|boolean',
            'implants_available' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        $checklist = $schedule->preOpChecklist;
        if (!$checklist) {
            $checklist = OtPreOpChecklist::create([
                'schedule_id' => $schedule->schedule_id,
                'patient_id' => $schedule->patient_id,
                ...$validated,
            ]);
        } else {
            $checklist->update([
                ...$validated,
                'checked_by' => auth()->id(),
                'checked_at' => now(),
            ]);
        }

        // Update schedule if checklist is complete
        $isComplete = $checklist->consent_surgical
            && $checklist->consent_anesthesia
            && $checklist->nil_by_mouth_confirmed;

        $schedule->update(['pre_op_checklist_complete' => $isComplete]);

        return response()->json([
            'message' => 'Checklist updated successfully',
            'checklist' => $checklist,
        ]);
    }

    public function todaySchedule(Request $request)
    {
        $schedules = OtSchedule::with([
            'patient',
            'operationTheater',
            'surgeryType',
            'surgeon',
        ])
            ->whereDate('scheduled_date', today())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('scheduled_start_time')
            ->get()
            ->groupBy('ot_id');

        return response()->json(['schedules' => $schedules]);
    }
}
