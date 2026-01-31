@extends('prints.layout')

@section('title', 'OPD Visit Receipt')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="hospital-name">{{ $hospital->hospital_name ?? 'Hospital Management System' }}</div>
        <div class="hospital-address">
            {{ $hospital->address ?? '' }}
            @if($hospital->city || $hospital->state || $hospital->pincode)
                , {{ $hospital->city ?? '' }} {{ $hospital->state ?? '' }} {{ $hospital->pincode ?? '' }}
            @endif
        </div>
        <div class="hospital-contact">
            @if($hospital->phone)
                Phone: {{ $hospital->phone }}
            @endif
            @if($hospital->email)
                | Email: {{ $hospital->email }}
            @endif
        </div>
        <div class="document-title">OPD Visit Receipt</div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Patient Name</div>
                <div class="info-value">{{ $opdVisit->patient->patient_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient ID</div>
                <div class="info-value">{{ $opdVisit->patient->pcd ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Age/Gender</div>
                <div class="info-value">
                    {{ $opdVisit->patient->age ?? '' }}
                    {{ $opdVisit->patient->age_unit ?? '' }}
                    / {{ ucfirst($opdVisit->patient->gender ?? 'N/A') }}
                </div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Mobile</div>
                <div class="info-value">{{ $opdVisit->patient->mobile ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">OPD Number</div>
                <div class="info-value">{{ $opdVisit->opd_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Token Number</div>
                <div class="info-value">{{ $opdVisit->token_number }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Visit Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($opdVisit->visit_date)->format('d-m-Y') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Visit Time</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($opdVisit->visit_time)->format('h:i A') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Registration Purpose</div>
                <div class="info-value">{{ ucfirst(str_replace('_', ' ', $opdVisit->registration_purpose)) }}</div>
            </div>
        </div>
    </div>

    <!-- Doctor & Department Information -->
    @if($opdVisit->doctor || $opdVisit->department)
    <div class="patient-info">
        <div class="info-row">
            @if($opdVisit->doctor)
            <div class="info-cell">
                <div class="info-label">Doctor</div>
                <div class="info-value">{{ $opdVisit->doctor->doctor_name ?? 'N/A' }}</div>
            </div>
            @endif
            @if($opdVisit->department)
            <div class="info-cell">
                <div class="info-label">Department</div>
                <div class="info-value">{{ $opdVisit->department->department_name ?? 'N/A' }}</div>
            </div>
            @endif
            @if($opdVisit->patientClass)
            <div class="info-cell">
                <div class="info-label">Class</div>
                <div class="info-value">{{ $opdVisit->patientClass->class_name ?? 'N/A' }}</div>
            </div>
            @endif
        </div>
        @if($opdVisit->referenceDoctor)
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Reference Doctor</div>
                <div class="info-value">{{ $opdVisit->referenceDoctor->doctor_name ?? 'N/A' }}</div>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Services -->
    @if($opdVisit->services && $opdVisit->services->count() > 0)
    <div class="section">
        <div class="section-title">Services</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service Name</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($opdVisit->services as $index => $visitService)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $visitService->service->service_name ?? 'N/A' }}</td>
                    <td style="text-align: center;">{{ $visitService->quantity }}</td>
                    <td style="text-align: right;">₹{{ number_format($visitService->rate, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($visitService->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Billing Summary -->
    <div class="section">
        <div class="section-title">Billing Summary</div>
        <table class="data-table">
            <tr>
                <td style="text-align: right; font-weight: 600;">Consultation Fee:</td>
                <td style="text-align: right; width: 150px;">₹{{ number_format($opdVisit->consultation_fee ?? 0, 2) }}</td>
            </tr>
            @if($opdVisit->services && $opdVisit->services->count() > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">Services Total:</td>
                <td style="text-align: right;">₹{{ number_format($opdVisit->services->sum('amount'), 2) }}</td>
            </tr>
            @endif
            @if($opdVisit->discount_amount > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">Discount:</td>
                <td style="text-align: right;">₹{{ number_format($opdVisit->discount_amount, 2) }}</td>
            </tr>
            @endif
            <tr style="background: #f5f5f5; font-weight: bold; font-size: 13px;">
                <td style="text-align: right;">Total Amount:</td>
                <td style="text-align: right;">₹{{ number_format($opdVisit->total_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align: right; font-weight: 600;">Paid Amount:</td>
                <td style="text-align: right;">₹{{ number_format($opdVisit->paid_amount ?? 0, 2) }}</td>
            </tr>
            <tr style="background: #fff4e6; font-weight: bold;">
                <td style="text-align: right;">Due Amount:</td>
                <td style="text-align: right;">₹{{ number_format($opdVisit->due_amount ?? 0, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Chief Complaints -->
    @if($opdVisit->chief_complaints)
    <div class="section">
        <div class="section-title">Chief Complaints</div>
        <div class="section-content">
            {{ $opdVisit->chief_complaints }}
        </div>
    </div>
    @endif

    <!-- MLC/Insurance Info -->
    @if($opdVisit->is_mlc || $opdVisit->is_insurance)
    <div class="section">
        <div class="section-title">Additional Information</div>
        <div class="section-content">
            @if($opdVisit->is_mlc)
            <p><strong>MLC Case:</strong> Yes</p>
            @if($opdVisit->mlc_number)
            <p><strong>MLC Number:</strong> {{ $opdVisit->mlc_number }}</p>
            @endif
            @if($opdVisit->police_station)
            <p><strong>Police Station:</strong> {{ $opdVisit->police_station }}</p>
            @endif
            @endif

            @if($opdVisit->is_insurance)
            <p><strong>Insurance Case:</strong> Yes</p>
            @if($opdVisit->insurance_company_name)
            <p><strong>Insurance Company:</strong> {{ $opdVisit->insurance_company_name }}</p>
            @endif
            @endif
        </div>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated document. No signature is required.</p>
        <p>Printed on {{ \Carbon\Carbon::now()->format('d-m-Y h:i A') }}</p>
    </div>
@endsection

@section('scripts')
<script>
    // Auto-print when page loads
    window.onload = function() {
        window.print();
    };
</script>
@endsection
