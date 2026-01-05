<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpdTimeSlot;
use Illuminate\Http\Request;

class OpdTimeSlotController extends Controller
{
    /**
     * Display time slots
     */
    public function index(Request $request)
    {
        $query = OpdTimeSlot::with(['doctor', 'department']);

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->day_of_week) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $slots = $query->orderBy('doctor_id')
            ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
            ->orderBy('start_time')
            ->get();

        return response()->json($slots);
    }

    /**
     * Store new time slot
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'department_id' => 'nullable|exists:departments,department_id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'max_patients_per_slot' => 'nullable|integer|min:1',
            'max_patients_per_session' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Require either doctor_id or department_id
        if (empty($validated['doctor_id']) && empty($validated['department_id'])) {
            return response()->json([
                'message' => 'Either doctor_id or department_id is required',
            ], 422);
        }

        $slot = OpdTimeSlot::create($validated);

        return response()->json($slot->load(['doctor', 'department']), 201);
    }

    /**
     * Display specific time slot
     */
    public function show(string $id)
    {
        $slot = OpdTimeSlot::with(['doctor', 'department'])->findOrFail($id);

        return response()->json([
            'slot' => $slot,
            'generated_slots' => $slot->generateSlots(),
        ]);
    }

    /**
     * Update time slot
     */
    public function update(Request $request, string $id)
    {
        $slot = OpdTimeSlot::findOrFail($id);

        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'department_id' => 'nullable|exists:departments,department_id',
            'day_of_week' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'date_format:H:i:s',
            'end_time' => 'date_format:H:i:s',
            'slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'max_patients_per_slot' => 'nullable|integer|min:1',
            'max_patients_per_session' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $slot->update($validated);

        return response()->json($slot->load(['doctor', 'department']));
    }

    /**
     * Delete time slot
     */
    public function destroy(string $id)
    {
        $slot = OpdTimeSlot::findOrFail($id);
        $slot->delete();

        return response()->json(['message' => 'Time slot deleted successfully']);
    }

    /**
     * Get available slots for a doctor on a date
     */
    public function availableSlots(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'date' => 'required|date',
        ]);

        $slots = OpdTimeSlot::getAvailableSlots($request->doctor_id, $request->date);

        return response()->json([
            'doctor_id' => $request->doctor_id,
            'date' => $request->date,
            'day_of_week' => strtolower(date('l', strtotime($request->date))),
            'available_slots' => $slots,
        ]);
    }

    /**
     * Get doctor's weekly schedule
     */
    public function doctorSchedule(string $doctorId)
    {
        $slots = OpdTimeSlot::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return response()->json([
            'doctor_id' => $doctorId,
            'schedule' => $slots,
        ]);
    }

    /**
     * Bulk create time slots for a doctor
     */
    public function bulkCreate(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'slots' => 'required|array',
            'slots.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'slots.*.start_time' => 'required|date_format:H:i:s',
            'slots.*.end_time' => 'required|date_format:H:i:s',
            'slots.*.slot_duration_minutes' => 'nullable|integer|min:5|max:120',
            'slots.*.max_patients_per_slot' => 'nullable|integer|min:1',
        ]);

        $createdSlots = [];
        foreach ($validated['slots'] as $slotData) {
            $createdSlots[] = OpdTimeSlot::create(array_merge($slotData, [
                'doctor_id' => $validated['doctor_id'],
                'is_active' => true,
            ]));
        }

        return response()->json([
            'message' => 'Time slots created successfully',
            'slots' => $createdSlots,
        ], 201);
    }
}
