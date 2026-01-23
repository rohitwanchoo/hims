<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultMaster;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConsultMasterController extends Controller
{
    /**
     * Display a listing of consult masters.
     */
    public function index(Request $request)
    {
        $query = ConsultMaster::with(['doctor', 'department']);

        // Search by doctor name or department name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('doctor', function ($dq) use ($search) {
                    $dq->where('full_name', 'like', "%{$search}%");
                })
                ->orWhereHas('department', function ($dq) use ($search) {
                    $dq->where('department_name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by doctor
        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // Filter by time period
        if ($request->has('time_period')) {
            $query->where('time_period', $request->time_period);
        }

        // Filter by day of week
        if ($request->has('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by specific date
        if ($request->has('specific_date')) {
            $query->where('specific_date', $request->specific_date);
        }

        $consultMasters = $query->orderBy('department_id')
            ->orderBy('doctor_id')
            ->orderBy('start_time')
            ->get();

        return response()->json($consultMasters);
    }

    /**
     * Store a newly created consult master.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'specific_date' => 'nullable|date|after_or_equal:today',
            'time_period' => 'required|in:morning,afternoon,evening',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|in:5,10,15,20,30',
            'max_patients_per_slot' => 'nullable|integer|min:1|max:10',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check for overlapping schedules
        $overlap = $this->checkOverlap(
            $validated['doctor_id'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['day_of_week'] ?? null,
            $validated['specific_date'] ?? null
        );

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule overlaps with an existing consult schedule for this doctor.',
            ], 422);
        }

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['max_patients_per_slot'] = $validated['max_patients_per_slot'] ?? 1;

        // Create consult master
        $consultMaster = ConsultMaster::create($validated);

        // Generate and store time slots
        $timeSlots = $consultMaster->generateTimeSlots();
        $consultMaster->update(['time_slots' => $timeSlots]);

        // Load relationships
        $consultMaster->load(['doctor', 'department']);

        return response()->json([
            'success' => true,
            'message' => 'Consult schedule created successfully',
            'data' => $consultMaster,
        ], 201);
    }

    /**
     * Display the specified consult master.
     */
    public function show(ConsultMaster $consultMaster)
    {
        $consultMaster->load(['doctor', 'department']);
        return response()->json($consultMaster);
    }

    /**
     * Update the specified consult master.
     */
    public function update(Request $request, ConsultMaster $consultMaster)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'specific_date' => 'nullable|date|after_or_equal:today',
            'time_period' => 'required|in:morning,afternoon,evening',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|in:5,10,15,20,30',
            'max_patients_per_slot' => 'nullable|integer|min:1|max:10',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check for overlapping schedules (excluding current schedule)
        $overlap = $this->checkOverlap(
            $validated['doctor_id'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['day_of_week'] ?? null,
            $validated['specific_date'] ?? null,
            $consultMaster->consult_master_id
        );

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule overlaps with an existing consult schedule for this doctor.',
            ], 422);
        }

        $validated['max_patients_per_slot'] = $validated['max_patients_per_slot'] ?? 1;

        // Update consult master
        $consultMaster->update($validated);

        // Regenerate time slots
        $timeSlots = $consultMaster->generateTimeSlots();
        $consultMaster->update(['time_slots' => $timeSlots]);

        // Load relationships
        $consultMaster->load(['doctor', 'department']);

        return response()->json([
            'success' => true,
            'message' => 'Consult schedule updated successfully',
            'data' => $consultMaster,
        ]);
    }

    /**
     * Remove the specified consult master.
     */
    public function destroy(ConsultMaster $consultMaster)
    {
        // Check if there are future appointments using this schedule
        $futureAppointments = \App\Models\Appointment::where('doctor_id', $consultMaster->doctor_id)
            ->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();

        if ($futureAppointments > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete this schedule. There are ' . $futureAppointments . ' future appointments scheduled.',
            ], 422);
        }

        $consultMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consult schedule deleted successfully',
        ]);
    }

    /**
     * Get active consult masters for dropdowns.
     */
    public function active()
    {
        return response()->json(
            ConsultMaster::with(['doctor:doctor_id,full_name', 'department:department_id,department_name'])
                ->where('is_active', true)
                ->orderBy('department_id')
                ->orderBy('start_time')
                ->get()
        );
    }

    /**
     * Get available doctors by department.
     */
    public function doctorsByDepartment($departmentId)
    {
        $doctors = Doctor::where('department_id', $departmentId)
            ->where('is_active', true)
            ->where('opd_available', true)
            ->orderBy('full_name')
            ->get(['doctor_id', 'full_name', 'qualification', 'consultation_fee']);

        return response()->json($doctors);
    }

    /**
     * Get consult schedules for a specific doctor.
     */
    public function doctorSchedules($doctorId)
    {
        $schedules = ConsultMaster::with(['department'])
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json($schedules);
    }

    /**
     * Get available time slots for a doctor on a specific date.
     */
    public function availableSlots(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $doctorId = $validated['doctor_id'];
        $date = $validated['date'];

        // Get consult schedules for the doctor on this date
        $schedules = ConsultMaster::forDoctor($doctorId)
            ->forDate($date)
            ->active()
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No consultation schedule found for this doctor on the selected date.',
                'slots' => [],
            ]);
        }

        // Get booked appointments for this doctor on this date
        $bookedAppointments = \App\Models\Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['scheduled', 'confirmed', 'checked_in'])
            ->get(['appointment_time', 'status']);

        $allSlots = [];

        foreach ($schedules as $schedule) {
            $slots = $schedule->time_slots ?? $schedule->generateTimeSlots();

            foreach ($slots as $slot) {
                $slotTime = $slot['start'];

                // Count how many appointments are booked for this slot
                $bookedCount = $bookedAppointments->where('appointment_time', $slotTime)->count();

                $isAvailable = $bookedCount < $schedule->max_patients_per_slot;

                $allSlots[] = [
                    'time' => $slotTime,
                    'label' => $slot['label'],
                    'time_period' => $schedule->time_period,
                    'is_available' => $isAvailable,
                    'booked_count' => $bookedCount,
                    'max_patients' => $schedule->max_patients_per_slot,
                    'schedule_id' => $schedule->consult_master_id,
                ];
            }
        }

        // Sort slots by time
        usort($allSlots, function ($a, $b) {
            return strcmp($a['time'], $b['time']);
        });

        return response()->json([
            'success' => true,
            'slots' => $allSlots,
            'doctor_id' => $doctorId,
            'date' => $date,
        ]);
    }

    /**
     * Check for overlapping schedules.
     */
    private function checkOverlap($doctorId, $startTime, $endTime, $dayOfWeek = null, $specificDate = null, $excludeId = null)
    {
        $query = ConsultMaster::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($sq) use ($startTime, $endTime) {
                    // New schedule starts within existing schedule
                    $sq->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })
                ->orWhere(function ($sq) use ($startTime, $endTime) {
                    // New schedule ends within existing schedule
                    $sq->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })
                ->orWhere(function ($sq) use ($startTime, $endTime) {
                    // New schedule encompasses existing schedule
                    $sq->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            });

        // Check for specific date or day of week overlap
        if ($specificDate) {
            $query->where(function ($q) use ($specificDate, $dayOfWeek) {
                $q->where('specific_date', $specificDate)
                    ->orWhere('day_of_week', $dayOfWeek)
                    ->orWhereNull('day_of_week');
            });
        } elseif ($dayOfWeek) {
            $query->where(function ($q) use ($dayOfWeek) {
                $q->where('day_of_week', $dayOfWeek)
                    ->orWhereNull('day_of_week');
            });
        }

        if ($excludeId) {
            $query->where('consult_master_id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Generate preview of time slots without saving.
     */
    public function previewSlots(Request $request)
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|in:5,10,15,20,30',
        ]);

        $consultMaster = new ConsultMaster($validated);
        $slots = $consultMaster->generateTimeSlots();

        return response()->json([
            'success' => true,
            'slots' => $slots,
            'total_slots' => count($slots),
        ]);
    }
}
