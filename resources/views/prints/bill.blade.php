@extends('prints.layout')

@section('title', 'Bill')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="hospital-name">{{ $hospital->name ?? 'Hospital Management System' }}</div>
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
        <div class="document-title">{{ strtoupper($bill->bill_type) }} Bill</div>
    </div>

    <!-- Bill Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Bill Number</div>
                <div class="info-value" style="font-weight: bold; font-size: 14px;">{{ $bill->bill_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Bill Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($bill->bill_date)->format('d-m-Y') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Bill Time</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($bill->created_at)->format('h:i A') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Bill Type</div>
                <div class="info-value">{{ strtoupper($bill->bill_type) }}</div>
            </div>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Patient Name</div>
                <div class="info-value">{{ $bill->patient->first_name ?? '' }} {{ $bill->patient->last_name ?? '' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient ID</div>
                <div class="info-value">{{ $bill->patient->pcd ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Mobile</div>
                <div class="info-value">{{ $bill->patient->mobile ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="info-row">
            @if($bill->patient->insuranceCompanyRelation)
            <div class="info-cell">
                <div class="info-label">Patient Class</div>
                <div class="info-value">{{ $bill->patient->insuranceCompanyRelation->company_name }}</div>
            </div>
            @endif
            @if($bill->patient->insurance_policy_no)
            <div class="info-cell">
                <div class="info-label">Policy Number</div>
                <div class="info-value">{{ $bill->patient->insurance_policy_no }}</div>
            </div>
            @elseif($bill->policy_number)
            <div class="info-cell">
                <div class="info-label">Policy Number</div>
                <div class="info-value">{{ $bill->policy_number }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Bill Details -->
    @if($bill->details && $bill->details->count() > 0)
    <div class="section">
        <div class="section-title">Bill Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Service Date</th>
                    <th>Doctor</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bill->details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->item_name }}</td>
                    <td><small>{{ $detail->service_date ? \Carbon\Carbon::parse($detail->service_date)->format('d-m-Y h:i A') : '-' }}</small></td>
                    <td>{{ $detail->doctor->full_name ?? '-' }}</td>
                    <td style="text-align: center;">{{ $detail->quantity }}</td>
                    <td style="text-align: right;">₹{{ number_format($detail->rate, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($detail->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Bill Summary -->
    <div class="section">
        <div class="section-title">Bill Summary</div>
        <table class="data-table">
            <tr>
                <td style="text-align: right; font-weight: 600;">Sub Total:</td>
                <td style="text-align: right; width: 150px;">₹{{ number_format($bill->total_amount - ($bill->tax_amount ?? 0) + ($bill->discount_amount ?? 0), 2) }}</td>
            </tr>
            @if($bill->discount_amount > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">Discount:</td>
                <td style="text-align: right;">- ₹{{ number_format($bill->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($bill->tax_amount > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">GST:</td>
                <td style="text-align: right;">₹{{ number_format($bill->tax_amount, 2) }}</td>
            </tr>
            @endif
            @if($bill->refund_amount > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">Refund:</td>
                <td style="text-align: right;">- ₹{{ number_format($bill->refund_amount, 2) }}</td>
            </tr>
            @endif
            <tr style="background: #f5f5f5; font-weight: bold; font-size: 13px;">
                <td style="text-align: right;">Total Amount:</td>
                <td style="text-align: right;">₹{{ number_format($bill->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align: right; font-weight: 600;">Paid Amount:</td>
                <td style="text-align: right; color: green;">₹{{ number_format($bill->paid_amount, 2) }}</td>
            </tr>
            <tr style="background: #fff4e6; font-weight: bold;">
                <td style="text-align: right;">Balance Due:</td>
                <td style="text-align: right; color: {{ $bill->due_amount > 0 ? 'red' : 'green' }};">₹{{ number_format($bill->due_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div style="margin-top: 60px; text-align: right;">
            <div style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; min-width: 200px;">
                Authorized Signature
            </div>
        </div>
        <p style="margin-top: 30px;">This is a computer-generated document. No signature is required.</p>
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
