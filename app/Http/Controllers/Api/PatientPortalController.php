<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientUser;
use App\Models\PatientAppointmentRequest;
use App\Models\PatientFeedback;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PatientPortalController extends Controller
{
    // Authentication
    public function register(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'username' => 'required|string|max:50|unique:patient_users,username',
            'email' => 'required|email|unique:patient_users,email',
            'mobile' => 'required|string|max:15',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);

        $user = PatientUser::create([
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $validated['patient_id'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        // Generate OTP for verification
        $user->generateOtp();

        return response()->json([
            'message' => 'Registration successful. Please verify your mobile number.',
            'user' => $user->only(['patient_user_id', 'username', 'email']),
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = PatientUser::where('username', $validated['username'])
            ->orWhere('email', $validated['username'])
            ->orWhere('mobile', $validated['username'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Your account is inactive. Please contact support.'],
            ]);
        }

        $token = $user->createToken('patient-portal')->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'token' => $token,
            'user' => $user->load('patient'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $user = PatientUser::where('mobile', $validated['mobile'])->firstOrFail();

        if (!$user->verifyOtp($validated['otp'])) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP.'],
            ]);
        }

        $user->update(['mobile_verified_at' => now()]);

        return response()->json([
            'message' => 'Mobile verified successfully',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'mobile' => 'required|string',
        ]);

        $user = PatientUser::where('mobile', $validated['mobile'])->firstOrFail();
        $user->generateOtp();

        return response()->json([
            'message' => 'OTP sent successfully',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    // Profile
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('patient'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'email' => 'sometimes|email|unique:patient_users,email,' . $request->user()->patient_user_id . ',patient_user_id',
            'current_password' => 'required_with:new_password',
            'new_password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['new_password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['Current password is incorrect.'],
                ]);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    // Appointment Requests
    public function appointmentRequests(Request $request)
    {
        $requests = PatientAppointmentRequest::where('patient_user_id', $request->user()->patient_user_id)
            ->with(['department', 'doctor', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['requests' => $requests]);
    }

    public function requestAppointment(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,department_id',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'preferred_date' => 'required|date|after:today',
            'preferred_time_slot' => 'nullable|in:morning,afternoon,evening',
            'alternate_date' => 'nullable|date|after:preferred_date',
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();

        $appointmentRequest = PatientAppointmentRequest::create([
            'hospital_id' => $user->hospital_id,
            'patient_id' => $user->patient_id,
            'patient_user_id' => $user->patient_user_id,
            ...$validated,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Appointment request submitted successfully',
            'request' => $appointmentRequest,
        ], 201);
    }

    public function cancelAppointmentRequest(PatientAppointmentRequest $appointmentRequest)
    {
        if ($appointmentRequest->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending requests can be cancelled',
            ], 422);
        }

        $appointmentRequest->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Request cancelled successfully',
        ]);
    }

    // View Medical Records
    public function appointments(Request $request)
    {
        $patient = $request->user()->patient;

        $appointments = $patient->appointments()
            ->with(['doctor', 'department'])
            ->orderBy('appointment_date', 'desc')
            ->paginate(20);

        return response()->json($appointments);
    }

    public function labReports(Request $request)
    {
        $patient = $request->user()->patient;

        $reports = $patient->labOrders()
            ->where('status', 'completed')
            ->with(['orderDetails.labTest', 'results'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reports);
    }

    public function prescriptions(Request $request)
    {
        $patient = $request->user()->patient;

        $prescriptions = $patient->prescriptions()
            ->with(['doctor', 'items.medicine'])
            ->orderBy('prescription_date', 'desc')
            ->paginate(20);

        return response()->json($prescriptions);
    }

    public function bills(Request $request)
    {
        $patient = $request->user()->patient;

        $bills = $patient->bills()
            ->with('items')
            ->orderBy('bill_date', 'desc')
            ->paginate(20);

        return response()->json($bills);
    }

    // Feedback
    public function submitFeedback(Request $request)
    {
        $validated = $request->validate([
            'feedback_type' => 'required|in:general,opd,ipd,lab,pharmacy,radiology',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'department_id' => 'nullable|exists:departments,department_id',
            'overall_rating' => 'required|integer|min:1|max:5',
            'cleanliness_rating' => 'nullable|integer|min:1|max:5',
            'staff_rating' => 'nullable|integer|min:1|max:5',
            'wait_time_rating' => 'nullable|integer|min:1|max:5',
            'doctor_rating' => 'nullable|integer|min:1|max:5',
            'comments' => 'nullable|string|max:1000',
            'suggestions' => 'nullable|string|max:1000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $user = $request->user();

        $feedback = PatientFeedback::create([
            'hospital_id' => $user->hospital_id,
            'patient_id' => $user->patient_id,
            'patient_user_id' => $user->patient_user_id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Thank you for your feedback',
            'feedback' => $feedback,
        ], 201);
    }

    public function myFeedback(Request $request)
    {
        $feedback = PatientFeedback::where('patient_user_id', $request->user()->patient_user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['feedback' => $feedback]);
    }
}
