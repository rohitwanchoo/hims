<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtProcedure;
use App\Models\OtSchedule;
use App\Models\OtConsumable;
use App\Models\OtAnesthesiaRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtProcedureController extends Controller
{
    public function index(Request $request)
    {
        $query = OtProcedure::with([
            'schedule.patient',
            'schedule.surgeryType',
            'surgeon',
            'anesthetist',
        ]);

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->surgeon_id) {
            $query->where('surgeon_id', $request->surgeon_id);
        }

        $procedures = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($procedures);
    }

    public function start(Request $request, OtSchedule $schedule)
    {
        if ($schedule->status !== 'confirmed') {
            return response()->json([
                'message' => 'Schedule must be confirmed before starting',
            ], 422);
        }

        if (!$schedule->pre_op_checklist_complete) {
            return response()->json([
                'message' => 'Pre-operative checklist must be completed',
            ], 422);
        }

        $validated = $request->validate([
            'surgeon_id' => 'required|exists:doctors,doctor_id',
            'anesthetist_id' => 'required|exists:doctors,doctor_id',
            'scrub_nurse_id' => 'nullable|exists:users,user_id',
            'circulating_nurse_id' => 'nullable|exists:users,user_id',
        ]);

        return DB::transaction(function () use ($schedule, $validated) {
            $lastProcedure = OtProcedure::whereDate('created_at', today())->count();
            $procedureNumber = 'OTP' . now()->format('Ymd') . str_pad($lastProcedure + 1, 4, '0', STR_PAD_LEFT);

            $procedure = OtProcedure::create([
                'schedule_id' => $schedule->schedule_id,
                'procedure_number' => $procedureNumber,
                'surgeon_id' => $validated['surgeon_id'],
                'anesthetist_id' => $validated['anesthetist_id'],
                'scrub_nurse_id' => $validated['scrub_nurse_id'] ?? null,
                'circulating_nurse_id' => $validated['circulating_nurse_id'] ?? null,
                'actual_start_time' => now(),
                'pre_op_diagnosis' => $schedule->pre_op_diagnosis,
            ]);

            $schedule->update(['status' => 'in_progress']);

            // Create anesthesia record
            OtAnesthesiaRecord::create([
                'procedure_id' => $procedure->procedure_id,
                'anesthetist_id' => $validated['anesthetist_id'],
                'induction_time' => now(),
            ]);

            return response()->json([
                'message' => 'Procedure started successfully',
                'procedure' => $procedure,
            ], 201);
        });
    }

    public function show(OtProcedure $procedure)
    {
        return response()->json([
            'procedure' => $procedure->load([
                'schedule.patient',
                'schedule.surgeryType',
                'surgeon',
                'anesthetist',
                'consumables.item',
                'anesthesiaRecord',
            ]),
        ]);
    }

    public function update(Request $request, OtProcedure $procedure)
    {
        $validated = $request->validate([
            'procedure_performed' => 'nullable|string',
            'post_op_diagnosis' => 'nullable|string',
            'findings' => 'nullable|string',
            'complications' => 'nullable|string',
            'blood_loss_ml' => 'nullable|integer|min:0',
            'wound_classification' => 'nullable|in:clean,clean_contaminated,contaminated,dirty',
            'drain_placed' => 'nullable|boolean',
            'drain_type' => 'nullable|string',
            'specimen_sent' => 'nullable|boolean',
            'specimen_description' => 'nullable|string',
            'icu_required' => 'nullable|boolean',
            'special_instructions' => 'nullable|string',
        ]);

        $procedure->update($validated);

        return response()->json([
            'message' => 'Procedure updated successfully',
            'procedure' => $procedure,
        ]);
    }

    public function complete(Request $request, OtProcedure $procedure)
    {
        $validated = $request->validate([
            'procedure_performed' => 'required|string',
            'post_op_diagnosis' => 'required|string',
            'blood_loss_ml' => 'nullable|integer|min:0',
            'wound_classification' => 'required|in:clean,clean_contaminated,contaminated,dirty',
            'icu_required' => 'nullable|boolean',
            'special_instructions' => 'nullable|string',
        ]);

        $procedure->update([
            ...$validated,
            'actual_end_time' => now(),
        ]);

        $procedure->schedule->update(['status' => 'completed']);

        // Update anesthesia record
        if ($procedure->anesthesiaRecord) {
            $procedure->anesthesiaRecord->update([
                'extubation_time' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Procedure completed successfully',
            'procedure' => $procedure,
        ]);
    }

    public function addConsumable(Request $request, OtProcedure $procedure)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,item_id',
            'quantity' => 'required|numeric|min:0.01',
            'rate' => 'nullable|numeric|min:0',
            'is_implant' => 'nullable|boolean',
            'serial_number' => 'nullable|string',
            'batch_number' => 'nullable|string',
        ]);

        $consumable = $procedure->consumables()->create($validated);

        return response()->json([
            'message' => 'Consumable added successfully',
            'consumable' => $consumable->load('item'),
        ], 201);
    }

    public function updateAnesthesia(Request $request, OtProcedure $procedure)
    {
        $validated = $request->validate([
            'asa_grade' => 'nullable|in:1,2,3,4,5,6',
            'anesthesia_type' => 'nullable|in:general,regional,local,sedation,combined',
            'airway_type' => 'nullable|in:lma,ett,mask,tracheostomy',
            'agents_used' => 'nullable|array',
            'monitoring_data' => 'nullable|array',
            'fluids_given' => 'nullable|array',
            'complications' => 'nullable|string',
            'recovery_score' => 'nullable|integer|min:0|max:10',
        ]);

        $record = $procedure->anesthesiaRecord;
        if (!$record) {
            $record = OtAnesthesiaRecord::create([
                'procedure_id' => $procedure->procedure_id,
                'anesthetist_id' => $procedure->anesthetist_id,
                ...$validated,
            ]);
        } else {
            $record->update($validated);
        }

        return response()->json([
            'message' => 'Anesthesia record updated successfully',
            'record' => $record,
        ]);
    }

    public function otNotes(OtProcedure $procedure)
    {
        return response()->json([
            'procedure' => $procedure->load([
                'schedule.patient',
                'schedule.surgeryType',
                'schedule.operationTheater',
                'surgeon',
                'anesthetist',
                'consumables.item',
                'anesthesiaRecord',
            ]),
        ]);
    }
}
