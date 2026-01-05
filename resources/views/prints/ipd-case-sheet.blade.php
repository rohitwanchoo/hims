@extends('prints.layout')

@section('title', 'IPD Case Sheet')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="hospital-name">{{ $hospital->hospital_name ?? 'Hospital Name' }}</div>
        <div class="hospital-address">{{ $hospital->address ?? '' }}{{ $hospital->city ? ', ' . $hospital->city : '' }}{{ $hospital->state ? ', ' . $hospital->state : '' }} {{ $hospital->pincode ?? '' }}</div>
        <div class="hospital-contact">
            @if($hospital->phone ?? false)Phone: {{ $hospital->phone }}@endif
            @if($hospital->email ?? false) | Email: {{ $hospital->email }}@endif
        </div>
        <div class="document-title">IPD Case Sheet</div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">IPD Number</div>
                <div class="info-value">{{ $admission->ipd_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient Name</div>
                <div class="info-value">{{ $admission->patient->patient_name ?? $admission->patient->full_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Age / Gender</div>
                <div class="info-value">{{ $admission->patient->age ?? 'N/A' }} {{ $admission->patient->age_unit ?? 'Yrs' }} / {{ ucfirst($admission->patient->gender ?? 'N/A') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient ID</div>
                <div class="info-value">{{ $admission->patient->pcd ?? $admission->patient->patient_id }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Contact Number</div>
                <div class="info-value">{{ $admission->patient->mobile ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Address</div>
                <div class="info-value">{{ $admission->patient->address ?? '' }}{{ $admission->patient->city ? ', ' . $admission->patient->city : '' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Blood Group</div>
                <div class="info-value">{{ $admission->patient->blood_group ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Allergies</div>
                <div class="info-value">{{ $admission->patient->allergies ?? 'None Known' }}</div>
            </div>
        </div>
    </div>

    <!-- Admission Details -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Admission Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($admission->admission_date)->format('d-M-Y') }} {{ $admission->admission_time ?? '' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Admission Type</div>
                <div class="info-value">{{ ucfirst($admission->admission_type ?? 'Elective') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Source</div>
                <div class="info-value">{{ ucfirst($admission->admission_source ?? 'Direct') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Status</div>
                <div class="info-value">{{ ucfirst($admission->status ?? 'Admitted') }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Ward / Bed</div>
                <div class="info-value">{{ $admission->ward->ward_name ?? 'N/A' }} / {{ $admission->bed->bed_number ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Department</div>
                <div class="info-value">{{ $admission->department->department_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Treating Doctor</div>
                <div class="info-value">{{ $admission->treatingDoctor->full_name ?? $admission->treatingDoctor->doctor_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Consultant</div>
                <div class="info-value">{{ $admission->consultantDoctor->full_name ?? $admission->consultantDoctor->doctor_name ?? 'N/A' }}</div>
            </div>
        </div>
        @if($admission->attendant_name)
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Attendant Name</div>
                <div class="info-value">{{ $admission->attendant_name }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Relation</div>
                <div class="info-value">{{ $admission->attendant_relation ?? 'N/A' }}</div>
            </div>
            <div class="info-cell" style="flex: 2;">
                <div class="info-label">Attendant Contact</div>
                <div class="info-value">{{ $admission->attendant_mobile ?? 'N/A' }}</div>
            </div>
        </div>
        @endif
    </div>

    <!-- Diagnosis -->
    <div class="section">
        <div class="section-title">Diagnosis</div>
        <div class="section-content">
            <p><strong>Provisional Diagnosis:</strong> {{ $admission->provisional_diagnosis ?? $admission->diagnosis_at_admission ?? 'N/A' }}</p>
            @if($admission->final_diagnosis)
            <p><strong>Final Diagnosis:</strong> {{ $admission->final_diagnosis }}</p>
            @endif
            @if($admission->icd_code)
            <p><strong>ICD Code:</strong> {{ $admission->icd_code }}</p>
            @endif
        </div>
    </div>

    <!-- Treatment Plan -->
    @if($admission->treatment_plan)
    <div class="section">
        <div class="section-title">Treatment Plan</div>
        <div class="section-content">
            {!! nl2br(e($admission->treatment_plan)) !!}
        </div>
    </div>
    @endif

    <!-- Progress Notes -->
    @if($admission->progressNotes && count($admission->progressNotes) > 0)
    <div class="section">
        <div class="section-title">Progress Notes</div>
        <div class="section-content" style="padding: 0;">
            @foreach($admission->progressNotes as $note)
            <div style="padding: 8px 10px; border-bottom: 1px dashed #ddd;">
                <div style="font-size: 10px; color: #666; margin-bottom: 4px;">
                    {{ \Carbon\Carbon::parse($note->note_date)->format('d-M-Y') }} {{ $note->note_time }} |
                    {{ $note->doctor->full_name ?? $note->doctor->doctor_name ?? 'Doctor' }} |
                    <em>{{ ucfirst($note->note_type) }}</em>
                </div>
                @if($note->subjective)<p style="margin: 2px 0;"><strong>S:</strong> {{ $note->subjective }}</p>@endif
                @if($note->objective)<p style="margin: 2px 0;"><strong>O:</strong> {{ $note->objective }}</p>@endif
                @if($note->assessment)<p style="margin: 2px 0;"><strong>A:</strong> {{ $note->assessment }}</p>@endif
                @if($note->plan)<p style="margin: 2px 0;"><strong>P:</strong> {{ $note->plan }}</p>@endif
                @if($note->instructions)<p style="margin: 2px 0;"><strong>Instructions:</strong> {{ $note->instructions }}</p>@endif
                @if($note->general_notes)<p style="margin: 2px 0;">{{ $note->general_notes }}</p>@endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Current Medications -->
    @if($admission->medications && count($admission->medications) > 0)
    <div class="section">
        <div class="section-title">Medications</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Medicine</th>
                    <th style="width: 15%;">Dosage</th>
                    <th style="width: 10%;">Route</th>
                    <th style="width: 15%;">Frequency</th>
                    <th style="width: 10%;">Duration</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admission->medications as $index => $med)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $med->medicine_name ?? $med->drug_name ?? 'N/A' }}</td>
                    <td>{{ $med->dosage ?? 'N/A' }}</td>
                    <td>{{ ucfirst($med->route ?? 'Oral') }}</td>
                    <td>{{ $med->frequency ?? 'N/A' }}</td>
                    <td>{{ $med->duration_days ?? '-' }} days</td>
                    <td>{{ $med->is_active ? 'Active' : 'Stopped' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Investigations -->
    @if($admission->investigations && count($admission->investigations) > 0)
    <div class="section">
        <div class="section-title">Investigations</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 30%;">Investigation</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 20%;">Result</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admission->investigations as $index => $inv)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($inv->order_date)->format('d-M-Y') }}</td>
                    <td>{{ ucfirst($inv->investigation_type) }}</td>
                    <td>{{ $inv->investigation_name }}</td>
                    <td>{{ ucfirst($inv->status) }}</td>
                    <td>{{ $inv->result ?? 'Pending' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Services/Charges -->
    @if($admission->services && count($admission->services) > 0)
    <div class="section">
        <div class="section-title">Services & Charges</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 35%;">Service</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 10%;">Rate</th>
                    <th style="width: 10%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admission->services as $index => $svc)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($svc->service_date)->format('d-M-Y') }}</td>
                    <td>{{ ucfirst($svc->service_type) }}</td>
                    <td>{{ $svc->service_name }}</td>
                    <td>{{ $svc->quantity }}</td>
                    <td>Rs {{ number_format($svc->rate, 2) }}</td>
                    <td>Rs {{ number_format($svc->net_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong>Rs {{ number_format($admission->services->sum('net_amount'), 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    <!-- MLC Information -->
    @if($admission->mlc_case)
    <div class="section">
        <div class="section-title">Medico-Legal Case (MLC) Information</div>
        <div class="section-content">
            <p><strong>MLC Number:</strong> {{ $admission->mlc_number ?? 'N/A' }}</p>
            <p><strong>Police Station:</strong> {{ $admission->police_station ?? 'N/A' }}</p>
            <p><strong>Brought By:</strong> {{ $admission->brought_by ?? 'N/A' }}</p>
        </div>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Nursing Staff</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Treating Doctor</div>
            <div style="font-size: 10px; margin-top: 3px;">{{ $admission->treatingDoctor->full_name ?? $admission->treatingDoctor->doctor_name ?? '' }}</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated document. Printed on: {{ now()->format('d-M-Y H:i:s') }}</p>
        <p>{{ $hospital->hospital_name ?? 'HIMS' }} - Hospital Information Management System</p>
    </div>
@endsection
