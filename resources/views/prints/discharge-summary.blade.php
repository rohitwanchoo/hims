@extends('prints.layout')

@section('title', 'Discharge Summary')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="hospital-name">{{ $hospital->hospital_name ?? 'Hospital Name' }}</div>
        <div class="hospital-address">{{ $hospital->address ?? '' }}{{ $hospital->city ? ', ' . $hospital->city : '' }}{{ $hospital->state ? ', ' . $hospital->state : '' }} {{ $hospital->pincode ?? '' }}</div>
        <div class="hospital-contact">
            @if($hospital->phone ?? false)Phone: {{ $hospital->phone }}@endif
            @if($hospital->email ?? false) | Email: {{ $hospital->email }}@endif
        </div>
        <div class="document-title">Discharge Summary</div>
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
                <div class="info-label">Discharge Date</div>
                <div class="info-value">{{ $admission->discharge_date ? \Carbon\Carbon::parse($admission->discharge_date)->format('d-M-Y') : 'N/A' }} {{ $admission->discharge_time ?? '' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Length of Stay</div>
                <div class="info-value">{{ $admission->los_days ?? 0 }} Days</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Discharge Type</div>
                <div class="info-value">{{ ucfirst($admission->discharge_type ?? 'Normal') }}</div>
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
                <div class="info-label">Condition at Discharge</div>
                <div class="info-value">{{ ucfirst($admission->condition_at_discharge ?? 'N/A') }}</div>
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

    <!-- Clinical Summary / Discharge Summary -->
    <div class="section">
        <div class="section-title">Clinical Summary</div>
        <div class="section-content">
            {!! nl2br(e($admission->discharge_summary ?? 'N/A')) !!}
        </div>
    </div>

    <!-- Treatment Given -->
    @if($admission->treatment_plan)
    <div class="section">
        <div class="section-title">Treatment Plan</div>
        <div class="section-content">
            {!! nl2br(e($admission->treatment_plan)) !!}
        </div>
    </div>
    @endif

    <!-- Medications at Discharge -->
    @if(isset($medications) && count($medications) > 0)
    <div class="section">
        <div class="section-title">Medications at Discharge</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Medicine</th>
                    <th style="width: 15%;">Dosage</th>
                    <th style="width: 15%;">Route</th>
                    <th style="width: 15%;">Frequency</th>
                    <th style="width: 15%;">Duration</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medications as $index => $med)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $med->medicine_name ?? $med->drug_name ?? 'N/A' }}</td>
                    <td>{{ $med->dosage ?? 'N/A' }}</td>
                    <td>{{ ucfirst($med->route ?? 'Oral') }}</td>
                    <td>{{ $med->frequency ?? 'N/A' }}</td>
                    <td>{{ $med->duration_days ?? '-' }} days</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Investigations Done -->
    @if(isset($investigations) && count($investigations) > 0)
    <div class="section">
        <div class="section-title">Investigations Summary</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 20%;">Date</th>
                    <th style="width: 35%;">Investigation</th>
                    <th style="width: 40%;">Result</th>
                </tr>
            </thead>
            <tbody>
                @foreach($investigations as $index => $inv)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($inv->order_date)->format('d-M-Y') }}</td>
                    <td>{{ $inv->investigation_name }}</td>
                    <td>{{ $inv->result ?? 'Pending' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Follow-up Advice -->
    <div class="section">
        <div class="section-title">Follow-up Advice</div>
        <div class="section-content">
            @if($admission->followup_advice)
                {!! nl2br(e($admission->followup_advice)) !!}
            @else
                <p>No specific follow-up advice provided.</p>
            @endif
            @if($admission->followup_date)
                <p style="margin-top: 10px;"><strong>Follow-up Date:</strong> {{ \Carbon\Carbon::parse($admission->followup_date)->format('d-M-Y') }}</p>
            @endif
        </div>
    </div>

    <!-- Billing Summary -->
    @if(isset($billing))
    <div class="section">
        <div class="section-title">Billing Summary</div>
        <table class="data-table" style="width: 50%;">
            <tr>
                <td>Total Charges</td>
                <td style="text-align: right;">Rs {{ number_format($billing['total_charges'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td style="text-align: right;">Rs {{ number_format($billing['discount'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td><strong>Net Amount</strong></td>
                <td style="text-align: right;"><strong>Rs {{ number_format($billing['net_amount'] ?? 0, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Advance Paid</td>
                <td style="text-align: right;">Rs {{ number_format($billing['advance_paid'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td><strong>{{ ($billing['balance'] ?? 0) < 0 ? 'Refund Due' : 'Balance Due' }}</strong></td>
                <td style="text-align: right;"><strong>Rs {{ number_format(abs($billing['balance'] ?? 0), 2) }}</strong></td>
            </tr>
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
            <div class="signature-label">Patient / Attendant Signature</div>
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
