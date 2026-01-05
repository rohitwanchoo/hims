<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbhaRegistration;
use App\Models\AbhaAuthToken;
use App\Models\AbdmHealthRecord;
use App\Models\AbdmConsentRequest;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbhaController extends Controller
{
    protected string $gatewayUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->gatewayUrl = config('services.abdm.gateway_url', 'https://dev.abdm.gov.in/gateway');
        $this->clientId = config('services.abdm.client_id');
        $this->clientSecret = config('services.abdm.client_secret');
    }

    // ABHA Registration Flow
    public function generateAadhaarOtp(Request $request)
    {
        $validated = $request->validate([
            'aadhaar' => 'required|string|size:12',
        ]);

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post($this->gatewayUrl . '/v1/registration/aadhaar/generateOtp', [
                    'aadhaar' => $validated['aadhaar'],
                ]);

            if ($response->successful()) {
                return response()->json([
                    'message' => 'OTP sent to registered mobile',
                    'txn_id' => $response->json('txnId'),
                ]);
            }

            return response()->json([
                'message' => 'Failed to generate OTP',
                'error' => $response->json('message'),
            ], 400);
        } catch (\Exception $e) {
            Log::error('ABHA OTP generation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
            ], 503);
        }
    }

    public function verifyAadhaarOtp(Request $request)
    {
        $validated = $request->validate([
            'txn_id' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post($this->gatewayUrl . '/v1/registration/aadhaar/verifyOTP', [
                    'txnId' => $validated['txn_id'],
                    'otp' => $validated['otp'],
                ]);

            if ($response->successful()) {
                return response()->json([
                    'message' => 'OTP verified successfully',
                    'txn_id' => $response->json('txnId'),
                ]);
            }

            return response()->json([
                'message' => 'OTP verification failed',
                'error' => $response->json('message'),
            ], 400);
        } catch (\Exception $e) {
            Log::error('ABHA OTP verification failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
            ], 503);
        }
    }

    public function createAbha(Request $request)
    {
        $validated = $request->validate([
            'txn_id' => 'required|string',
            'consent' => 'required|accepted',
        ]);

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->post($this->gatewayUrl . '/v1/registration/aadhaar/createHealthIdWithPreVerified', [
                    'txnId' => $validated['txn_id'],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'message' => 'ABHA created successfully',
                    'abha_number' => $data['healthIdNumber'],
                    'abha_address' => $data['healthId'],
                    'name' => $data['name'],
                ]);
            }

            return response()->json([
                'message' => 'ABHA creation failed',
                'error' => $response->json('message'),
            ], 400);
        } catch (\Exception $e) {
            Log::error('ABHA creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Service unavailable',
            ], 503);
        }
    }

    public function linkToPatient(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'abha_number' => 'required|string|size:14',
            'abha_address' => 'required|string',
            'name' => 'required|string',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'mobile' => 'nullable|string',
            'consent' => 'required|accepted',
        ]);

        // Check if ABHA already linked
        $existing = AbhaRegistration::where('abha_number', $validated['abha_number'])->first();
        if ($existing) {
            return response()->json([
                'message' => 'ABHA number already linked to another patient',
            ], 422);
        }

        $registration = AbhaRegistration::create([
            'hospital_id' => $patient->hospital_id,
            'patient_id' => $patient->patient_id,
            'abha_number' => $validated['abha_number'],
            'abha_address' => $validated['abha_address'],
            'name' => $validated['name'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'kyc_status' => 'verified',
            'kyc_type' => 'aadhaar',
            'linked_at' => now(),
            'consent_given' => true,
            'consent_datetime' => now(),
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'ABHA linked to patient successfully',
            'registration' => $registration,
        ], 201);
    }

    public function getPatientAbha(Patient $patient)
    {
        $registration = AbhaRegistration::where('patient_id', $patient->patient_id)
            ->with('authToken')
            ->first();

        if (!$registration) {
            return response()->json([
                'message' => 'No ABHA linked to this patient',
            ], 404);
        }

        return response()->json([
            'registration' => $registration,
        ]);
    }

    // Health Records Sharing
    public function shareHealthRecords(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'care_context_type' => 'required|in:opd,ipd,lab,radiology',
            'care_context_id' => 'required|integer',
            'hi_types' => 'required|array',
            'hi_types.*' => 'in:Prescription,DiagnosticReport,OPConsultation,DischargeSummary,ImmunizationRecord,HealthDocumentRecord',
        ]);

        $registration = AbhaRegistration::where('patient_id', $patient->patient_id)
            ->where('is_active', true)
            ->firstOrFail();

        // Generate FHIR bundle (simplified)
        $fhirBundle = $this->generateFhirBundle(
            $validated['care_context_type'],
            $validated['care_context_id'],
            $validated['hi_types']
        );

        $record = AbdmHealthRecord::create([
            'hospital_id' => $patient->hospital_id,
            'abha_registration_id' => $registration->abha_registration_id,
            'patient_id' => $patient->patient_id,
            'care_context_type' => $validated['care_context_type'],
            'care_context_id' => $validated['care_context_id'],
            'care_context_reference' => $validated['care_context_type'] . '-' . $validated['care_context_id'],
            'hi_type' => implode(',', $validated['hi_types']),
            'fhir_bundle' => $fhirBundle,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Health records prepared for sharing',
            'record' => $record,
        ], 201);
    }

    // Consent Management
    public function consentRequests(Request $request, Patient $patient)
    {
        $consents = AbdmConsentRequest::where('patient_id', $patient->patient_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['consents' => $consents]);
    }

    public function processConsent(Request $request, AbdmConsentRequest $consent)
    {
        $validated = $request->validate([
            'action' => 'required|in:grant,deny',
        ]);

        if ($validated['action'] === 'grant') {
            $consent->update([
                'status' => 'granted',
                'granted_at' => now(),
            ]);
        } else {
            $consent->update([
                'status' => 'denied',
                'denied_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Consent ' . $validated['action'] . 'ed successfully',
            'consent' => $consent,
        ]);
    }

    // ABDM Callbacks
    public function onGenerateOtp(Request $request)
    {
        Log::info('ABDM Callback: on-generate-otp', $request->all());
        return response()->json(['status' => 'acknowledged']);
    }

    public function onConsentRequest(Request $request)
    {
        Log::info('ABDM Callback: on-consent-request', $request->all());

        $data = $request->all();

        // Find patient by ABHA
        $registration = AbhaRegistration::where('abha_number', $data['notification']['consentDetail']['patient']['id'])
            ->first();

        if ($registration) {
            AbdmConsentRequest::create([
                'hospital_id' => $registration->hospital_id,
                'abha_registration_id' => $registration->abha_registration_id,
                'patient_id' => $registration->patient_id,
                'consent_request_id_abdm' => $data['notification']['consentRequestId'],
                'purpose' => $data['notification']['consentDetail']['purpose']['text'],
                'purpose_code' => $data['notification']['consentDetail']['purpose']['code'],
                'hi_types' => $data['notification']['consentDetail']['hiTypes'],
                'requester_name' => $data['notification']['consentDetail']['requester']['name'],
                'hiu_id' => $data['notification']['consentDetail']['hiu']['id'],
                'status' => 'requested',
            ]);
        }

        return response()->json(['status' => 'acknowledged']);
    }

    // Helper Methods
    protected function getAccessToken(): string
    {
        $response = Http::post($this->gatewayUrl . '/v0.5/sessions', [
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json('accessToken');
        }

        throw new \Exception('Failed to get ABDM access token');
    }

    protected function generateFhirBundle(string $contextType, int $contextId, array $hiTypes): array
    {
        // Simplified FHIR bundle generation
        // In production, this would generate proper FHIR R4 resources
        return [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'entry' => [],
        ];
    }
}
