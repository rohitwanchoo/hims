@extends('prints.layout')

@section('title', 'IPD Discharge Receipt')

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
        <div class="document-title">IPD DISCHARGE RECEIPT</div>
    </div>

    <!-- Patient & Admission Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">IPD Number</div>
                <div class="info-value" style="font-weight: bold; font-size: 14px;">{{ $admission->ipd_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient Name</div>
                <div class="info-value">{{ $admission->patient->full_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient ID</div>
                <div class="info-value">{{ $admission->patient->patient_code ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Age/Gender</div>
                <div class="info-value">{{ $admission->patient->age ?? 'N/A' }} / {{ $admission->patient->gender ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Mobile</div>
                <div class="info-value">{{ $admission->patient->mobile ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Admission Type</div>
                <div class="info-value" style="text-transform: capitalize;">{{ $admission->admission_type ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Admission & Discharge Dates -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Admission Date & Time</div>
                <div class="info-value">
                    {{ \Carbon\Carbon::parse($admission->admission_date)->format('d-m-Y') }}
                    @if($admission->admission_time)
                        {{ \Carbon\Carbon::parse($admission->admission_time)->format('h:i A') }}
                    @endif
                </div>
            </div>
            <div class="info-cell">
                <div class="info-label">Discharge Date & Time</div>
                <div class="info-value">
                    {{ $admission->discharge_date ? \Carbon\Carbon::parse($admission->discharge_date)->format('d-m-Y') : 'N/A' }}
                    @if($admission->discharge_time)
                        {{ \Carbon\Carbon::parse($admission->discharge_time)->format('h:i A') }}
                    @endif
                </div>
            </div>
            <div class="info-cell">
                <div class="info-label">Length of Stay</div>
                <div class="info-value">{{ $billing['bed_days'] }} {{ $billing['bed_days'] == 1 ? 'Day' : 'Days' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Ward/Bed</div>
                <div class="info-value">{{ $admission->ward->ward_name ?? 'N/A' }} / {{ $admission->bed->bed_number ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Treating Doctor</div>
                <div class="info-value">Dr. {{ $admission->treatingDoctor->doctor_name ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Discharge Type</div>
                <div class="info-value" style="text-transform: uppercase;">{{ $admission->discharge_type ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Bed Charges -->
    <div class="section">
        <div class="section-title">Bed Charges</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Days</th>
                    <th style="text-align: right;">Rate/Day</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $admission->ward->ward_name ?? 'Ward' }} - {{ $admission->bed->bed_number ?? 'Bed' }}</td>
                    <td style="text-align: center;">{{ $billing['bed_days'] }}</td>
                    <td style="text-align: right;">₹{{ number_format($billing['bed_rate'], 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($billing['bed_charges'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Service Charges -->
    @if($services && $services->count() > 0)
    <div class="section">
        <div class="section-title">Service Charges</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Service Description</th>
                    <th>Type</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                    <th style="text-align: right;">Discount</th>
                    <th style="text-align: right;">Net Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $counter = 1; @endphp
                @foreach($services as $serviceType => $serviceItems)
                    @foreach($serviceItems as $service)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td>{{ \Carbon\Carbon::parse($service->service_date)->format('d-m-Y') }}</td>
                        <td>{{ $service->service_name }}</td>
                        <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $service->service_type) }}</td>
                        <td style="text-align: center;">{{ $service->quantity }}</td>
                        <td style="text-align: right;">₹{{ number_format($service->rate, 2) }}</td>
                        <td style="text-align: right;">₹{{ number_format($service->amount, 2) }}</td>
                        <td style="text-align: right;">₹{{ number_format($service->discount, 2) }}</td>
                        <td style="text-align: right;">₹{{ number_format($service->net_amount, 2) }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Advance Payments -->
    @if($advancePayments && $advancePayments->count() > 0)
    <div class="section">
        <div class="section-title">Advance Payments</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Receipt Number</th>
                    <th>Date</th>
                    <th>Payment Mode</th>
                    <th style="text-align: right;">Amount</th>
                    <th style="text-align: right;">Refunded</th>
                    <th style="text-align: right;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($advancePayments as $index => $advance)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $advance->receipt_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($advance->payment_date)->format('d-m-Y') }}</td>
                    <td style="text-transform: uppercase;">{{ $advance->payment_mode }}</td>
                    <td style="text-align: right;">₹{{ number_format($advance->amount, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($advance->refund_amount ?? 0, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($advance->amount - ($advance->refund_amount ?? 0), 2) }}</td>
                </tr>
                @endforeach
                <tr style="font-weight: bold; background-color: #f5f5f5;">
                    <td colspan="4" style="text-align: right;">Total Advance Paid:</td>
                    <td style="text-align: right;">₹{{ number_format($billing['total_advance'], 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($billing['total_refunded'], 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($billing['advance_paid'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Bill Summary -->
    <div class="section">
        <div class="section-title">Bill Summary</div>
        <table class="data-table">
            <tbody>
                <tr>
                    <td style="width: 70%; text-align: right; font-weight: bold;">Bed Charges:</td>
                    <td style="width: 30%; text-align: right;">₹{{ number_format($billing['bed_charges'], 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold;">Service Charges:</td>
                    <td style="text-align: right;">₹{{ number_format($billing['services_amount'], 2) }}</td>
                </tr>
                <tr style="border-top: 2px solid #ddd;">
                    <td style="text-align: right; font-weight: bold;">Gross Total:</td>
                    <td style="text-align: right; font-weight: bold;">₹{{ number_format($billing['gross_total'], 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold; color: #d9534f;">Discount:</td>
                    <td style="text-align: right; color: #d9534f;">₹{{ number_format($billing['discount'], 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold;">Net Total:</td>
                    <td style="text-align: right; font-weight: bold;">₹{{ number_format($billing['net_total'], 2) }}</td>
                </tr>
                @if($billing['tax_amount'] > 0)
                <tr>
                    <td style="text-align: right; font-weight: bold;">Tax:</td>
                    <td style="text-align: right;">₹{{ number_format($billing['tax_amount'], 2) }}</td>
                </tr>
                @endif
                <tr style="border-top: 2px solid #000;">
                    <td style="text-align: right; font-weight: bold; font-size: 16px;">Final Total:</td>
                    <td style="text-align: right; font-weight: bold; font-size: 16px;">₹{{ number_format($billing['final_total'], 2) }}</td>
                </tr>
                <tr>
                    <td style="text-align: right; font-weight: bold; color: #5cb85c;">Advance Paid:</td>
                    <td style="text-align: right; color: #5cb85c;">₹{{ number_format($billing['advance_paid'], 2) }}</td>
                </tr>
                <tr style="border-top: 2px solid #000; background-color: #f0f0f0;">
                    <td style="text-align: right; font-weight: bold; font-size: 18px;">
                        @if($billing['balance_due'] > 0)
                            Balance Due:
                        @elseif($billing['balance_due'] < 0)
                            Refundable Amount:
                        @else
                            Balance:
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: bold; font-size: 18px; color: {{ $billing['balance_due'] > 0 ? '#d9534f' : ($billing['balance_due'] < 0 ? '#5cb85c' : '#000') }};">
                        ₹{{ number_format(abs($billing['balance_due']), 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="section" style="margin-top: 40px;">
        <div class="info-row">
            <div class="info-cell" style="width: 50%;">
                <div style="margin-top: 60px; border-top: 1px solid #000; padding-top: 5px;">
                    Patient/Attendant Signature
                </div>
            </div>
            <div class="info-cell" style="width: 50%; text-align: right;">
                <div style="margin-top: 60px; border-top: 1px solid #000; padding-top: 5px;">
                    Authorized Signatory
                </div>
            </div>
        </div>
    </div>

    <!-- Print Information -->
    <div style="margin-top: 20px; text-align: center; font-size: 11px; color: #666;">
        <p>This is a computer-generated discharge receipt</p>
        <p>Printed on: {{ now()->format('d-m-Y h:i A') }}</p>
    </div>
@endsection
