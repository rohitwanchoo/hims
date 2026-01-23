<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\OpdVisit;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorWorkbenchController extends Controller
{
    /**
     * Get doctor's workbench dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check if user has admin role (check multiple possible role column names)
        $isAdmin = false;
        if (method_exists($user, 'hasRole')) {
            $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');
        } elseif (isset($user->role)) {
            $isAdmin = in_array($user->role, ['admin', 'super_admin']);
        }

        // Also check is_super_admin field
        if (!$isAdmin && isset($user->is_super_admin) && $user->is_super_admin) {
            $isAdmin = true;
        }

        // Check if user is a doctor
        $isDoctor = false;
        $doctor = null;
        if (method_exists($user, 'hasRole')) {
            $isDoctor = $user->hasRole('doctor');
        } elseif (isset($user->role)) {
            $isDoctor = $user->role === 'doctor';
        }

        // Debug logging
        \Log::info('Doctor Workbench Access', [
            'user_id' => $user->user_id,
            'user_email' => $user->email,
            'user_role' => $user->role ?? 'role_not_set',
            'isAdmin' => $isAdmin,
            'isDoctor' => $isDoctor,
        ]);

        // Get doctor based on role
        if ($isDoctor || !$isAdmin) {
            $doctor = Doctor::where('user_id', $user->user_id)->first();

            if (!$doctor) {
                // Try to find by email match
                $doctor = Doctor::where('email', $user->email)->first();
            }

            if ($doctor) {
                $doctorId = $doctor->doctor_id;
                $isDoctor = true;
            } else {
                $doctorId = null;
            }
        } else {
            // Admin: optional doctor filter
            $doctorId = $request->input('doctor_id');
        }

        // Get today's date
        $date = $request->input('date', today()->toDateString());

        // Build OPD visits query
        $visitsQuery = OpdVisit::with([
            'patient.genderRelation',
            'doctor',
            'department'
        ])->whereDate('visit_date', $date);

        // Filter by doctor for doctor role
        // For doctors: always filter by their doctor_id
        // For admins: only filter if a doctor is selected in the dropdown
        if ($isDoctor && $doctorId) {
            $visitsQuery->where('doctor_id', $doctorId);
        } elseif (!$isAdmin && $doctorId) {
            // Non-admin, non-doctor users with doctor association
            $visitsQuery->where('doctor_id', $doctorId);
        } elseif ($isAdmin && $doctorId) {
            // Admin with doctor filter selected
            $visitsQuery->where('doctor_id', $doctorId);
        }
        // For admin without doctor filter: show all OPD visits

        $visits = $visitsQuery->orderBy('token_number')->get();

        \Log::info('Doctor Workbench Visits Query', [
            'date' => $date,
            'isAdmin' => $isAdmin,
            'isDoctor' => $isDoctor,
            'doctorId' => $doctorId,
            'visits_count' => $visits->count(),
        ]);

        // Build patients query
        $patientsQuery = Patient::with([
            'genderRelation',
            'latestOpdVisit.doctor',
        ]);

        if ($isDoctor && $doctorId) {
            // Doctor: show only assigned patients
            $patientsQuery->whereHas('assignedDoctors', function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)
                  ->where('status', 'active');
            });
            $patients = $patientsQuery->latest('updated_at')->limit(50)->get();
        } else {
            // Admin: show all patients (no limit for admin, or higher limit)
            $patients = $patientsQuery->latest('updated_at')->limit(200)->get();
        }

        \Log::info('Doctor Workbench Patients Query', [
            'isAdmin' => $isAdmin,
            'isDoctor' => $isDoctor,
            'doctorId' => $doctorId,
            'patients_count' => $patients->count(),
        ]);

        // Dashboard statistics
        $stats = [
            'total_visits' => $visits->count(),
            'waiting' => $visits->where('status', 'waiting')->count(),
            'in_consultation' => $visits->where('status', 'in_consultation')->count(),
            'completed' => $visits->where('status', 'completed')->count(),
            'total_patients' => $patients->count(),
        ];

        // Get all doctors (for admin filter)
        $doctors = $isAdmin ? Doctor::with('department')->orderBy('full_name')->get() : [];

        \Log::info('Doctor Workbench Response Data', [
            'visits_count' => $visits->count(),
            'patients_count' => $patients->count(),
            'doctors_count' => $doctors->count(),
            'stats' => $stats,
            'is_admin' => $isAdmin,
            'is_doctor' => $isDoctor,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'visits' => $visits,
                'patients' => $patients,
                'stats' => $stats,
                'doctors' => $doctors,
                'current_doctor' => $doctor,
                'is_doctor' => $isDoctor,
                'is_admin' => $isAdmin,
            ]
        ]);
    }

    /**
     * Get consultation details for a patient
     */
    public function consultation(Request $request, $opdId)
    {
        $user = Auth::user();

        // Check if user is a doctor
        $isDoctor = false;
        if (method_exists($user, 'hasRole')) {
            $isDoctor = $user->hasRole('doctor');
        } elseif (isset($user->role)) {
            $isDoctor = $user->role === 'doctor';
        }

        $opdVisit = OpdVisit::with([
            'patient.genderRelation',
            'patient.bloodGroupRelation',
            'doctor',
            'department'
        ])->findOrFail($opdId);

        // Authorization check for doctors
        if ($isDoctor) {
            $doctor = Doctor::where('user_id', $user->id)->first();

            if ($doctor && $opdVisit->doctor_id !== $doctor->doctor_id) {
                return response()->json([
                    'error' => 'Unauthorized access to this consultation'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $opdVisit
        ]);
    }

    /**
     * Get patient's medical history
     */
    public function patientHistory(Request $request, $patientId)
    {
        $user = Auth::user();

        // Check if user is a doctor
        $isDoctor = false;
        if (method_exists($user, 'hasRole')) {
            $isDoctor = $user->hasRole('doctor');
        } elseif (isset($user->role)) {
            $isDoctor = $user->role === 'doctor';
        }

        $patient = Patient::with([
            'genderRelation',
            'bloodGroupRelation'
        ])->findOrFail($patientId);

        // Authorization check for doctors
        if ($isDoctor) {
            $doctor = Doctor::where('user_id', $user->id)->first();

            if ($doctor) {
                $isAssigned = $patient->assignedDoctors()
                    ->where('doctor_id', $doctor->doctor_id)
                    ->exists();

                if (!$isAssigned) {
                    return response()->json([
                        'error' => 'Unauthorized access to this patient'
                    ], 403);
                }
            }
        }

        // Get patient's OPD visits
        $opdVisits = OpdVisit::with(['doctor', 'department'])
            ->where('patient_id', $patientId)
            ->orderBy('visit_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'patient' => $patient,
                'opd_visits' => $opdVisits,
            ]
        ]);
    }

    /**
     * Start consultation
     */
    public function startConsultation(Request $request, $opdId)
    {
        $user = Auth::user();

        // Check if user is a doctor
        $isDoctor = false;
        if (method_exists($user, 'hasRole')) {
            $isDoctor = $user->hasRole('doctor');
        } elseif (isset($user->role)) {
            $isDoctor = $user->role === 'doctor';
        }

        $opdVisit = OpdVisit::findOrFail($opdId);

        // Authorization check
        if ($isDoctor) {
            $doctor = Doctor::where('user_id', $user->id)->first();

            if ($doctor && $opdVisit->doctor_id !== $doctor->doctor_id) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 403);
            }
        }

        // Update status
        $opdVisit->update([
            'status' => 'in_consultation',
            'consultation_start_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consultation started successfully',
            'data' => $opdVisit
        ]);
    }

    /**
     * Get today's queue for doctor
     */
    public function queue(Request $request)
    {
        $user = Auth::user();
        $doctor = Doctor::where('user_id', $user->id)->first();

        if (!$doctor) {
            return response()->json(['error' => 'Doctor profile not found'], 404);
        }

        $date = $request->input('date', today()->toDateString());

        $queue = OpdVisit::with([
            'patient.genderRelation',
            'department'
        ])
        ->where('doctor_id', $doctor->doctor_id)
        ->whereDate('visit_date', $date)
        ->whereIn('status', ['waiting', 'in_consultation'])
        ->orderBy('token_number')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $queue
        ]);
    }

    /**
     * Debug: Get current user role
     */
    public function debugUserRole(Request $request)
    {
        $user = Auth::user();
        return response()->json([
            'user_id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'role' => $user->role,
            'is_super_admin' => $user->is_super_admin,
            'department_id' => $user->department_id,
        ]);
    }

    /**
     * Debug: Get workbench data counts
     */
    public function debugWorkbenchData(Request $request)
    {
        $user = Auth::user();

        $isAdmin = false;
        if (method_exists($user, 'hasRole')) {
            $isAdmin = $user->hasRole('admin') || $user->hasRole('super_admin');
        } elseif (isset($user->role)) {
            $isAdmin = in_array($user->role, ['admin', 'super_admin']);
        }
        if (!$isAdmin && isset($user->is_super_admin) && $user->is_super_admin) {
            $isAdmin = true;
        }

        $totalPatients = Patient::count();
        $totalOpdToday = OpdVisit::whereDate('visit_date', today())->count();

        return response()->json([
            'user_info' => [
                'role' => $user->role,
                'is_super_admin' => $user->is_super_admin,
                'detected_as_admin' => $isAdmin,
            ],
            'database_counts' => [
                'total_patients' => $totalPatients,
                'total_opd_today' => $totalOpdToday,
            ]
        ]);
    }
}
