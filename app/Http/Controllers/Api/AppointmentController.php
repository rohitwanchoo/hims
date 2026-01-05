<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\OpdTimeSlot;
use App\Models\OpdVisit;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    /**
     * Display appointments with filters
     */
    public function index(Request $request)
    {
        $query = Appointment::with([
            'patient',
            'doctor',
            'department',
            'patientClass',
            'referenceDoctor',
        ]);

        // Filter by date (default: today)
        $date = $request->date ?? now()->toDateString();
        $query->whereDate('appointment_date', $date);

        // Filter by date range
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('appointment_date', [$request->from_date, $request->to_date]);
        }

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

        // Filter by appointment type
        if ($request->appointment_type) {
            $query->where('appointment_type', $request->appointment_type);
        }

        // Filter by patient
        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
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

        $appointments = $query->orderBy('appointment_date')
            ->orderBy('slot_number')
            ->orderBy('appointment_time')
            ->get();

        // Summary counts
        $summary = [
            'total' => $appointments->count(),
            'scheduled' => $appointments->where('status', 'scheduled')->count(),
            'confirmed' => $appointments->where('status', 'confirmed')->count(),
            'checked_in' => $appointments->where('status', 'checked_in')->count(),
            'in_consultation' => $appointments->where('status', 'in_consultation')->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
            'cancelled' => $appointments->where('status', 'cancelled')->count(),
            'no_show' => $appointments->where('status', 'no_show')->count(),
        ];

        return response()->json([
            'appointments' => $appointments,
            'summary' => $summary,
        ]);
    }

    /**
     * Store a newly created appointment
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

            // Appointment Details
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'department_id' => 'nullable|exists:departments,department_id',
            'skill_set_id' => 'nullable|exists:skill_sets,skill_set_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'nullable',
            'appointment_type' => 'nullable|in:consultation,followup,health_checkup,procedure',
            'booking_mode' => 'nullable|in:walk_in,telephonic,online',
            'service_type' => 'nullable|in:first,followup',
            'is_online' => 'nullable|boolean',
            'priority' => 'nullable|in:normal,urgent,emergency',
            'slot_number' => 'nullable|integer',
            'slot_start_time' => 'nullable',
            'slot_end_time' => 'nullable',
            'duration_minutes' => 'nullable|integer|min:5',
            'booking_source' => 'nullable|in:reception,portal,mobile_app,call_center',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
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
            }

            // Get doctor's department if not provided
            if (empty($validated['department_id'])) {
                $doctor = Doctor::find($validated['doctor_id']);
                $validated['department_id'] = $doctor->department_id ?? null;
                $validated['skill_set_id'] = $validated['skill_set_id'] ?? $doctor->skill_set_id;
            }

            // Calculate slot number if not provided
            if (empty($validated['slot_number'])) {
                $validated['slot_number'] = Appointment::where('doctor_id', $validated['doctor_id'])
                    ->whereDate('appointment_date', $validated['appointment_date'])
                    ->count() + 1;
            }

            // Generate appointment number
            $todayCount = Appointment::whereDate('created_at', now()->toDateString())->count();
            $appointmentNumber = 'APT' . now()->format('Ymd') . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

            // Create appointment
            $appointment = Appointment::create([
                'appointment_number' => $appointmentNumber,
                'patient_id' => $patientId,
                'doctor_id' => $validated['doctor_id'],
                'department_id' => $validated['department_id'] ?? null,
                'skill_set_id' => $validated['skill_set_id'] ?? null,
                'class_id' => $validated['class_id'] ?? null,
                'reference_doctor_id' => $validated['reference_doctor_id'] ?? null,
                'appointment_date' => $validated['appointment_date'],
                'appointment_time' => $validated['appointment_time'] ?? null,
                'appointment_type' => $validated['appointment_type'] ?? 'consultation',
                'booking_mode' => $validated['booking_mode'] ?? 'walk_in',
                'service_type' => $validated['service_type'] ?? 'first',
                'is_online' => $validated['is_online'] ?? false,
                'priority' => $validated['priority'] ?? 'normal',
                'slot_number' => $validated['slot_number'],
                'slot_start_time' => $validated['slot_start_time'] ?? null,
                'slot_end_time' => $validated['slot_end_time'] ?? null,
                'duration_minutes' => $validated['duration_minutes'] ?? 15,
                'booking_source' => $validated['booking_source'] ?? 'reception',
                'status' => 'scheduled',
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->user_id ?? null,
            ]);

            return response()->json($appointment->load([
                'patient',
                'doctor',
                'department',
                'patientClass',
                'referenceDoctor',
            ]), 201);
        });
    }

    /**
     * Display the specified appointment
     */
    public function show(string $id)
    {
        $appointment = Appointment::with([
            'patient',
            'doctor',
            'department',
            'skillSet',
            'patientClass',
            'referenceDoctor',
            'opdVisit',
        ])->findOrFail($id);

        return response()->json($appointment);
    }

    /**
     * Update the specified appointment
     */
    public function update(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'doctor_id' => 'exists:doctors,doctor_id',
            'department_id' => 'nullable|exists:departments,department_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'reference_doctor_id' => 'nullable|exists:reference_doctors,reference_doctor_id',
            'appointment_date' => 'date',
            'appointment_time' => 'nullable',
            'appointment_type' => 'nullable|in:consultation,followup,health_checkup,procedure',
            'slot_number' => 'nullable|integer',
            'slot_start_time' => 'nullable',
            'slot_end_time' => 'nullable',
            'status' => 'nullable|in:scheduled,confirmed,checked_in,in_consultation,completed,cancelled,no_show',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $appointment->update($validated);

        return response()->json($appointment->load([
            'patient',
            'doctor',
            'department',
        ]));
    }

    /**
     * Cancel the specified appointment with reason
     */
    public function destroy(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        if (!$appointment->canBeCancelled()) {
            return response()->json([
                'message' => 'This appointment cannot be cancelled',
            ], 422);
        }

        $validated = $request->validate([
            'cancel_reason_id' => 'nullable|exists:cancel_reasons,cancel_reason_id',
            'cancel_remarks' => 'nullable|string|max:500',
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'cancel_reason_id' => $validated['cancel_reason_id'] ?? null,
            'cancel_remarks' => $validated['cancel_remarks'] ?? null,
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->user_id ?? null,
        ]);

        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'appointment' => $appointment->load('cancelReason'),
        ]);
    }

    /**
     * Cancel appointment with detailed reason (separate endpoint)
     */
    public function cancel(Request $request, string $id)
    {
        return $this->destroy($request, $id);
    }

    /**
     * Confirm appointment (mark as arrived)
     */
    public function confirm(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        if ($appointment->status !== 'scheduled') {
            return response()->json([
                'message' => 'Only scheduled appointments can be confirmed',
            ], 422);
        }

        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'arrived_at' => now(),
            'confirmed_by' => $request->user()->user_id ?? null,
        ]);

        return response()->json([
            'message' => 'Appointment confirmed - Patient arrived',
            'appointment' => $appointment->load(['patient', 'doctor']),
        ]);
    }

    /**
     * Check-in patient for appointment
     */
    public function checkIn(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        if (!in_array($appointment->status, ['scheduled', 'confirmed'])) {
            return response()->json([
                'message' => 'Only scheduled or confirmed appointments can be checked in',
            ], 422);
        }

        $appointment->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        return response()->json([
            'message' => 'Patient checked in',
            'appointment' => $appointment->load('patient'),
        ]);
    }

    /**
     * Convert appointment to OPD registration
     */
    public function convertToOpd(Request $request, string $id)
    {
        $appointment = Appointment::with('patient')->findOrFail($id);

        if (!in_array($appointment->status, ['scheduled', 'confirmed', 'checked_in'])) {
            return response()->json([
                'message' => 'Cannot convert this appointment to OPD registration',
            ], 422);
        }

        $validated = $request->validate([
            'registration_purpose' => 'nullable|in:normal,direct,health_checkup,emergency',
            'services' => 'nullable|array',
            'services.*.service_id' => 'required|exists:services,service_id',
            'services.*.quantity' => 'nullable|integer|min:1',
        ]);

        return DB::transaction(function () use ($appointment, $validated, $request) {
            // Generate OPD number
            $todayCount = OpdVisit::whereDate('visit_date', now()->toDateString())->count();
            $opdNumber = 'OPD' . now()->format('Ymd') . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

            // Generate token number
            $tokenNumber = OpdVisit::whereDate('visit_date', now()->toDateString())
                ->where('doctor_id', $appointment->doctor_id)
                ->count() + 1;

            // Create OPD visit
            $opdVisit = OpdVisit::create([
                'opd_number' => $opdNumber,
                'registration_purpose' => $validated['registration_purpose'] ?? 'normal',
                'patient_id' => $appointment->patient_id,
                'visit_date' => now()->toDateString(),
                'visit_time' => now()->toTimeString(),
                'token_number' => $tokenNumber,
                'department_id' => $appointment->department_id,
                'doctor_id' => $appointment->doctor_id,
                'reference_doctor_id' => $appointment->reference_doctor_id,
                'class_id' => $appointment->class_id ?? $appointment->patient->class_id,
                'visit_type' => $appointment->appointment_type === 'followup' ? 'followup' : 'new',
                'status' => 'waiting',
                'payment_status' => 'pending',
                'created_by' => $request->user()->id ?? null,
            ]);

            // Update appointment with OPD reference
            $appointment->update([
                'status' => 'checked_in',
                'checked_in_at' => $appointment->checked_in_at ?? now(),
                'opd_id' => $opdVisit->opd_id,
            ]);

            return response()->json([
                'message' => 'OPD registration created',
                'opd_visit' => $opdVisit->load(['patient', 'doctor']),
                'appointment' => $appointment,
            ]);
        });
    }

    /**
     * Mark appointment as no-show
     */
    public function noShow(string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $appointment->update(['status' => 'no_show']);

        return response()->json([
            'message' => 'Appointment marked as no-show',
            'appointment' => $appointment,
        ]);
    }

    /**
     * Reschedule appointment
     */
    public function reschedule(Request $request, string $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'nullable',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'slot_number' => 'nullable|integer',
        ]);

        $doctorId = $validated['doctor_id'] ?? $appointment->doctor_id;

        // Calculate new slot number if not provided
        if (empty($validated['slot_number'])) {
            $validated['slot_number'] = Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $validated['appointment_date'])
                ->count() + 1;
        }

        $appointment->update([
            'appointment_date' => $validated['appointment_date'],
            'appointment_time' => $validated['appointment_time'] ?? $appointment->appointment_time,
            'doctor_id' => $doctorId,
            'slot_number' => $validated['slot_number'],
            'status' => 'scheduled',
        ]);

        return response()->json([
            'message' => 'Appointment rescheduled',
            'appointment' => $appointment->load(['patient', 'doctor']),
        ]);
    }

    /**
     * Get doctor's schedule/slots for a date
     */
    public function doctorSchedule(string $doctorId, Request $request)
    {
        $date = $request->date ?? now()->toDateString();

        $appointments = Appointment::with(['patient', 'patientClass'])
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->orderBy('slot_number')
            ->orderBy('appointment_time')
            ->get();

        $doctor = Doctor::find($doctorId);

        return response()->json([
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->doctor_name ?? null,
            'date' => $date,
            'appointments' => $appointments,
            'total_booked' => $appointments->count(),
            'pending' => $appointments->whereIn('status', ['scheduled', 'confirmed'])->count(),
            'checked_in' => $appointments->where('status', 'checked_in')->count(),
            'completed' => $appointments->where('status', 'completed')->count(),
        ]);
    }

    /**
     * Get available slots for a doctor on a date
     */
    public function availableSlots(string $doctorId, Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $maxSlots = $request->max_slots ?? 20;

        $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->pluck('slot_number')
            ->toArray();

        $availableSlots = [];
        for ($i = 1; $i <= $maxSlots; $i++) {
            if (!in_array($i, $bookedSlots)) {
                $availableSlots[] = $i;
            }
        }

        return response()->json([
            'doctor_id' => $doctorId,
            'date' => $date,
            'booked_slots' => $bookedSlots,
            'available_slots' => $availableSlots,
            'total_available' => count($availableSlots),
        ]);
    }

    /**
     * Get patient's upcoming appointments
     */
    public function patientAppointments(string $patientId, Request $request)
    {
        $query = Appointment::with(['doctor', 'department'])
            ->where('patient_id', $patientId);

        if ($request->upcoming) {
            $query->where('appointment_date', '>=', now()->toDateString())
                  ->whereNotIn('status', ['completed', 'cancelled', 'no_show']);
        }

        $appointments = $query->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit($request->limit ?? 20)
            ->get();

        return response()->json($appointments);
    }

    /**
     * Transfer all appointments from one doctor to another
     * Used when a doctor is unavailable
     */
    public function transferToDoctor(Request $request)
    {
        $validated = $request->validate([
            'from_doctor_id' => 'required|exists:doctors,doctor_id',
            'to_doctor_id' => 'required|exists:doctors,doctor_id|different:from_doctor_id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255',
        ]);

        $fromDoctor = Doctor::find($validated['from_doctor_id']);
        $toDoctor = Doctor::find($validated['to_doctor_id']);

        // Check if doctors are in same specialty/department
        if ($fromDoctor->skill_set_id && $toDoctor->skill_set_id) {
            if ($fromDoctor->skill_set_id !== $toDoctor->skill_set_id) {
                return response()->json([
                    'message' => 'Doctors should be of the same specialty for transfer',
                    'warning' => true,
                ], 422);
            }
        }

        // Get appointments to transfer
        $appointments = Appointment::where('doctor_id', $validated['from_doctor_id'])
            ->whereDate('appointment_date', $validated['date'])
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('opd_id')
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json([
                'message' => 'No appointments found to transfer',
            ], 404);
        }

        $transferred = 0;
        $errors = [];

        foreach ($appointments as $appointment) {
            try {
                $appointment->update([
                    'original_doctor_id' => $appointment->doctor_id,
                    'doctor_id' => $validated['to_doctor_id'],
                    'department_id' => $toDoctor->department_id ?? $appointment->department_id,
                    'status' => 'transferred',
                    'notes' => ($appointment->notes ? $appointment->notes . "\n" : '') .
                              "Transferred from Dr. {$fromDoctor->full_name} to Dr. {$toDoctor->full_name}. " .
                              ($validated['reason'] ?? 'Doctor unavailable'),
                ]);
                // Reset status to scheduled after transfer
                $appointment->update(['status' => 'scheduled']);
                $transferred++;
            } catch (\Exception $e) {
                $errors[] = "Failed to transfer appointment #{$appointment->appointment_id}";
            }
        }

        return response()->json([
            'message' => "{$transferred} appointment(s) transferred successfully",
            'from_doctor' => $fromDoctor->full_name,
            'to_doctor' => $toDoctor->full_name,
            'date' => $validated['date'],
            'transferred_count' => $transferred,
            'errors' => $errors,
        ]);
    }

    /**
     * Create multiple appointments (bulk booking)
     */
    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'appointments' => 'required|array|min:1|max:10',
            'appointments.*.patient_id' => 'required|exists:patients,patient_id',
            'appointments.*.doctor_id' => 'required|exists:doctors,doctor_id',
            'appointments.*.appointment_date' => 'required|date|after_or_equal:today',
            'appointments.*.appointment_time' => 'nullable',
            'appointments.*.appointment_type' => 'nullable|in:consultation,followup,health_checkup,procedure',
            'appointments.*.booking_mode' => 'nullable|in:walk_in,telephonic,online',
            'appointments.*.service_type' => 'nullable|in:first,followup',
            'appointments.*.slot_number' => 'nullable|integer',
            'appointments.*.reason' => 'nullable|string',
        ]);

        $created = [];
        $errors = [];

        return DB::transaction(function () use ($validated, $request, &$created, &$errors) {
            foreach ($validated['appointments'] as $index => $appointmentData) {
                try {
                    // Generate appointment number
                    $todayCount = Appointment::whereDate('created_at', now()->toDateString())->count() + count($created);
                    $appointmentNumber = 'APT' . now()->format('Ymd') . str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

                    // Get doctor's department
                    $doctor = Doctor::find($appointmentData['doctor_id']);

                    // Calculate slot number if not provided
                    if (empty($appointmentData['slot_number'])) {
                        $appointmentData['slot_number'] = Appointment::where('doctor_id', $appointmentData['doctor_id'])
                            ->whereDate('appointment_date', $appointmentData['appointment_date'])
                            ->count() + 1;
                    }

                    $appointment = Appointment::create([
                        'appointment_number' => $appointmentNumber,
                        'patient_id' => $appointmentData['patient_id'],
                        'doctor_id' => $appointmentData['doctor_id'],
                        'department_id' => $doctor->department_id ?? null,
                        'skill_set_id' => $doctor->skill_set_id ?? null,
                        'appointment_date' => $appointmentData['appointment_date'],
                        'appointment_time' => $appointmentData['appointment_time'] ?? null,
                        'appointment_type' => $appointmentData['appointment_type'] ?? 'consultation',
                        'booking_mode' => $appointmentData['booking_mode'] ?? 'walk_in',
                        'service_type' => $appointmentData['service_type'] ?? 'first',
                        'slot_number' => $appointmentData['slot_number'],
                        'status' => 'scheduled',
                        'reason' => $appointmentData['reason'] ?? null,
                        'created_by' => $request->user()->user_id ?? null,
                    ]);

                    $created[] = $appointment->load(['patient', 'doctor']);
                } catch (\Exception $e) {
                    $errors[] = "Failed to create appointment at index {$index}: " . $e->getMessage();
                }
            }

            return response()->json([
                'message' => count($created) . ' appointment(s) created successfully',
                'appointments' => $created,
                'errors' => $errors,
            ], 201);
        });
    }

    /**
     * Get doctor's duty schedule (time slots)
     */
    public function doctorDutySchedule(string $doctorId, Request $request)
    {
        $doctor = Doctor::findOrFail($doctorId);

        // Get time slots for the doctor
        $slots = OpdTimeSlot::where(function ($query) use ($doctorId, $doctor) {
                $query->where('doctor_id', $doctorId)
                      ->orWhere(function ($q) use ($doctor) {
                          $q->whereNull('doctor_id')
                            ->where('department_id', $doctor->department_id);
                      });
            })
            ->where('is_active', true)
            ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
            ->orderBy('start_time')
            ->get();

        // Group by day
        $schedule = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $daySlots = $slots->where('day_of_week', $day)->values();
            if ($daySlots->isNotEmpty()) {
                $schedule[$day] = $daySlots->map(function ($slot) {
                    return [
                        'slot_id' => $slot->slot_id,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'duration_minutes' => $slot->slot_duration_minutes,
                        'max_patients_per_slot' => $slot->max_patients_per_slot,
                        'max_patients_per_session' => $slot->max_patients_per_session,
                    ];
                });
            }
        }

        return response()->json([
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->full_name,
            'specialization' => $doctor->specialization,
            'schedule' => $schedule,
        ]);
    }

    /**
     * Get available time slots for a doctor on a specific date
     */
    public function availableTimeSlots(string $doctorId, Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $dayOfWeek = strtolower(date('l', strtotime($date)));

        $doctor = Doctor::findOrFail($doctorId);

        // Get configured time slots for this day
        $timeSlots = OpdTimeSlot::where(function ($query) use ($doctorId, $doctor) {
                $query->where('doctor_id', $doctorId)
                      ->orWhere(function ($q) use ($doctor) {
                          $q->whereNull('doctor_id')
                            ->where('department_id', $doctor->department_id);
                      });
            })
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        if ($timeSlots->isEmpty()) {
            return response()->json([
                'doctor_id' => $doctorId,
                'date' => $date,
                'day' => $dayOfWeek,
                'message' => 'No time slots configured for this day',
                'slots' => [],
            ]);
        }

        // Get existing appointments for this date
        $existingAppointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->get();

        // Generate available slots
        $availableSlots = [];
        foreach ($timeSlots as $timeSlot) {
            $start = strtotime($timeSlot->start_time);
            $end = strtotime($timeSlot->end_time);
            $duration = $timeSlot->slot_duration_minutes * 60;
            $slotNumber = 1;

            while ($start + $duration <= $end) {
                $slotTime = date('H:i:s', $start);
                $slotEndTime = date('H:i:s', $start + $duration);

                // Count bookings for this slot
                $bookingsInSlot = $existingAppointments->filter(function ($apt) use ($slotTime, $slotEndTime) {
                    return $apt->appointment_time >= $slotTime && $apt->appointment_time < $slotEndTime;
                })->count();

                $availableSlots[] = [
                    'slot_number' => $slotNumber,
                    'start_time' => $slotTime,
                    'end_time' => $slotEndTime,
                    'booked' => $bookingsInSlot,
                    'max_patients' => $timeSlot->max_patients_per_slot,
                    'available' => $bookingsInSlot < $timeSlot->max_patients_per_slot,
                ];

                $start += $duration;
                $slotNumber++;
            }
        }

        return response()->json([
            'doctor_id' => $doctorId,
            'doctor_name' => $doctor->full_name,
            'date' => $date,
            'day' => $dayOfWeek,
            'slots' => $availableSlots,
            'total_slots' => count($availableSlots),
            'available_count' => collect($availableSlots)->where('available', true)->count(),
        ]);
    }
}
