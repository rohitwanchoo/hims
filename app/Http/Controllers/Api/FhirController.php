<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\FhirResource;
use App\Models\Hl7Message;
use App\Models\IcdCode;
use Illuminate\Http\Request;

class FhirController extends Controller
{
    // FHIR Capability Statement
    public function capability()
    {
        return response()->json([
            'resourceType' => 'CapabilityStatement',
            'status' => 'active',
            'date' => now()->toIso8601String(),
            'kind' => 'instance',
            'fhirVersion' => '4.0.1',
            'format' => ['application/fhir+json'],
            'rest' => [
                [
                    'mode' => 'server',
                    'resource' => [
                        ['type' => 'Patient', 'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Observation', 'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'DiagnosticReport', 'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'MedicationRequest', 'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                        ['type' => 'Encounter', 'interaction' => [['code' => 'read'], ['code' => 'search-type']]],
                    ],
                ],
            ],
        ]);
    }

    // Patient Resource
    public function searchPatients(Request $request)
    {
        $query = Patient::query();

        if ($request->identifier) {
            $query->where('pcd', $request->identifier);
        }

        if ($request->name) {
            $query->where('patient_name', 'like', '%' . $request->name . '%');
        }

        if ($request->birthdate) {
            $query->whereDate('date_of_birth', $request->birthdate);
        }

        if ($request->gender) {
            $query->where('gender', $request->gender);
        }

        if ($request->phone) {
            $query->where('mobile', $request->phone);
        }

        $patients = $query->limit(50)->get();

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $patients->count(),
            'entry' => $patients->map(fn ($p) => [
                'resource' => $this->patientToFhir($p),
            ]),
        ]);
    }

    public function readPatient(string $id)
    {
        $patient = Patient::findOrFail($id);
        return response()->json($this->patientToFhir($patient));
    }

    protected function patientToFhir(Patient $patient): array
    {
        return [
            'resourceType' => 'Patient',
            'id' => (string) $patient->patient_id,
            'identifier' => [
                [
                    'system' => 'urn:hims:patient-id',
                    'value' => $patient->pcd,
                ],
            ],
            'name' => [
                [
                    'use' => 'official',
                    'text' => $patient->patient_name,
                ],
            ],
            'gender' => strtolower($patient->gender),
            'birthDate' => $patient->date_of_birth?->format('Y-m-d'),
            'telecom' => array_filter([
                $patient->mobile ? [
                    'system' => 'phone',
                    'value' => $patient->mobile,
                    'use' => 'mobile',
                ] : null,
                $patient->email ? [
                    'system' => 'email',
                    'value' => $patient->email,
                ] : null,
            ]),
            'address' => $patient->address ? [
                [
                    'text' => $patient->address,
                    'city' => $patient->city ?? null,
                    'state' => $patient->state ?? null,
                    'postalCode' => $patient->pincode ?? null,
                    'country' => 'IN',
                ],
            ] : null,
        ];
    }

    // Observation Resource (Lab Results)
    public function searchObservations(Request $request)
    {
        $query = FhirResource::where('resource_type', 'Observation');

        if ($request->patient) {
            $query->where('local_reference_type', 'lab_result')
                ->whereHas('localReference', function ($q) use ($request) {
                    $q->whereHas('labOrder', function ($q2) use ($request) {
                        $q2->where('patient_id', $request->patient);
                    });
                });
        }

        if ($request->code) {
            $query->whereJsonContains('resource_json->code->coding', ['code' => $request->code]);
        }

        $resources = $query->limit(50)->get();

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $resources->count(),
            'entry' => $resources->map(fn ($r) => [
                'resource' => $r->resource_json,
            ]),
        ]);
    }

    // DiagnosticReport Resource
    public function searchDiagnosticReports(Request $request)
    {
        $query = FhirResource::where('resource_type', 'DiagnosticReport');

        if ($request->patient) {
            $query->whereJsonContains('resource_json->subject->reference', 'Patient/' . $request->patient);
        }

        if ($request->category) {
            $query->whereJsonContains('resource_json->category', ['coding' => [['code' => $request->category]]]);
        }

        $resources = $query->limit(50)->get();

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $resources->count(),
            'entry' => $resources->map(fn ($r) => [
                'resource' => $r->resource_json,
            ]),
        ]);
    }

    // MedicationRequest Resource
    public function searchMedicationRequests(Request $request)
    {
        $query = FhirResource::where('resource_type', 'MedicationRequest');

        if ($request->patient) {
            $query->whereJsonContains('resource_json->subject->reference', 'Patient/' . $request->patient);
        }

        $resources = $query->limit(50)->get();

        return response()->json([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $resources->count(),
            'entry' => $resources->map(fn ($r) => [
                'resource' => $r->resource_json,
            ]),
        ]);
    }

    // HL7 Message Processing
    public function hl7Messages(Request $request)
    {
        $query = Hl7Message::query();

        if ($request->message_type) {
            $query->where('message_type', $request->message_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->direction) {
            $query->where('direction', $request->direction);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($messages);
    }

    public function processHl7Message(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'source_system' => 'required|string',
        ]);

        // Parse HL7 message
        $parsed = $this->parseHl7Message($validated['message']);

        $hl7Message = Hl7Message::create([
            'message_control_id' => $parsed['MSH']['message_control_id'] ?? uniqid(),
            'message_type' => $parsed['MSH']['message_type'] ?? 'UNKNOWN',
            'trigger_event' => $parsed['MSH']['trigger_event'] ?? null,
            'direction' => 'inbound',
            'source_system' => $validated['source_system'],
            'raw_message' => $validated['message'],
            'parsed_message' => $parsed,
            'status' => 'pending',
        ]);

        // Process message based on type
        $this->processMessageByType($hl7Message);

        return response()->json([
            'message' => 'HL7 message received',
            'message_id' => $hl7Message->message_id,
            'status' => $hl7Message->status,
        ], 201);
    }

    protected function parseHl7Message(string $message): array
    {
        $segments = explode("\r", $message);
        $parsed = [];

        foreach ($segments as $segment) {
            $fields = explode('|', $segment);
            $segmentName = $fields[0];

            if ($segmentName === 'MSH') {
                $parsed['MSH'] = [
                    'field_separator' => '|',
                    'encoding_characters' => $fields[1] ?? '^~\\&',
                    'sending_application' => $fields[2] ?? null,
                    'sending_facility' => $fields[3] ?? null,
                    'receiving_application' => $fields[4] ?? null,
                    'receiving_facility' => $fields[5] ?? null,
                    'datetime' => $fields[6] ?? null,
                    'message_type' => explode('^', $fields[8] ?? '')[0] ?? null,
                    'trigger_event' => explode('^', $fields[8] ?? '')[1] ?? null,
                    'message_control_id' => $fields[9] ?? null,
                    'processing_id' => $fields[10] ?? null,
                    'version_id' => $fields[11] ?? null,
                ];
            } else {
                $parsed[$segmentName][] = $fields;
            }
        }

        return $parsed;
    }

    protected function processMessageByType(Hl7Message $message): void
    {
        $type = $message->message_type;
        $event = $message->trigger_event;

        try {
            match ($type) {
                'ADT' => $this->processAdtMessage($message),
                'ORM' => $this->processOrmMessage($message),
                'ORU' => $this->processOruMessage($message),
                default => null,
            };

            $message->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);
        } catch (\Exception $e) {
            $message->update([
                'status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);
        }
    }

    protected function processAdtMessage(Hl7Message $message): void
    {
        // ADT - Admit/Discharge/Transfer message processing
        // Implementation would go here
    }

    protected function processOrmMessage(Hl7Message $message): void
    {
        // ORM - Order message processing
        // Implementation would go here
    }

    protected function processOruMessage(Hl7Message $message): void
    {
        // ORU - Observation result message processing
        // Implementation would go here
    }

    // ICD Code Lookup
    public function searchIcdCodes(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
            'version' => 'nullable|in:10,11',
        ]);

        $query = IcdCode::where('is_active', true);

        if ($request->version) {
            $query->where('icd_version', 'ICD-' . $request->version);
        }

        $codes = $query->search($validated['q'])
            ->limit(50)
            ->get();

        return response()->json(['codes' => $codes]);
    }
}
