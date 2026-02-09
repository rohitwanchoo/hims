@extends('prints.layout')

@section('title', 'Payment Receipt')

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
        <div class="document-title">Payment Receipt</div>
    </div>

    <!-- Receipt Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Receipt Number</div>
                <div class="info-value" style="font-weight: bold; font-size: 14px;">{{ $payment->payment_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Payment Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Payment Time</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($payment->created_at)->format('h:i A') }}</div>
            </div>
        </div>
    </div>

    <!-- Patient Information -->
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Patient Name</div>
                <div class="info-value">{{ $payment->patient->first_name ?? '' }} {{ $payment->patient->last_name ?? '' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Patient ID</div>
                <div class="info-value">{{ $payment->patient->pcd ?? 'N/A' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Mobile</div>
                <div class="info-value">{{ $payment->patient->mobile ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Bill Information -->
    @if($payment->bill)
    <div class="patient-info">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Bill Number</div>
                <div class="info-value">{{ $payment->bill->bill_number }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Bill Date</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($payment->bill->bill_date)->format('d-m-Y') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Bill Type</div>
                <div class="info-value">{{ strtoupper($payment->bill->bill_type) }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Bill Details -->
    @if($payment->bill && $payment->bill->details && $payment->bill->details->count() > 0)
    <div class="section">
        <div class="section-title">Bill Details</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th style="text-align: center;">Quantity</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment->bill->details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->item_name }}</td>
                    <td style="text-align: center;">{{ $detail->quantity }}</td>
                    <td style="text-align: right;">₹{{ number_format($detail->rate, 2) }}</td>
                    <td style="text-align: right;">₹{{ number_format($detail->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Payment Summary -->
    <div class="section">
        <div class="section-title">Payment Summary</div>
        <table class="data-table">
            @if($payment->bill)
            <tr>
                <td style="text-align: right; font-weight: 600;">Bill Total:</td>
                <td style="text-align: right; width: 150px;">₹{{ number_format($payment->bill->total_amount, 2) }}</td>
            </tr>
            @if($payment->bill->discount_amount > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">Discount:</td>
                <td style="text-align: right;">- ₹{{ number_format($payment->bill->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($payment->bill->tax_amount > 0)
            <tr>
                <td style="text-align: right; font-weight: 600;">GST:</td>
                <td style="text-align: right;">₹{{ number_format($payment->bill->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr style="background: #f5f5f5;">
                <td style="text-align: right; font-weight: 600;">Net Amount:</td>
                <td style="text-align: right;">₹{{ number_format($payment->bill->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="text-align: right; font-weight: 600;">Previous Payments:</td>
                <td style="text-align: right;">₹{{ number_format($payment->bill->paid_amount - $payment->amount, 2) }}</td>
            </tr>
            @endif
            <tr style="background: #e8f5e9; font-weight: bold; font-size: 14px;">
                <td style="text-align: right;">Amount Paid Now:</td>
                <td style="text-align: right;">₹{{ number_format($payment->amount, 2) }}</td>
            </tr>
            @if($payment->bill)
            <tr>
                <td style="text-align: right; font-weight: 600;">Total Paid:</td>
                <td style="text-align: right;">₹{{ number_format($payment->bill->paid_amount, 2) }}</td>
            </tr>
            <tr style="background: #fff4e6; font-weight: bold;">
                <td style="text-align: right;">Balance Due:</td>
                <td style="text-align: right;">₹{{ number_format($payment->bill->due_amount, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    <!-- Payment Details -->
    <div class="section">
        <div class="section-title">Payment Details</div>
        <table class="data-table">
            @if($payment->payment_mode === 'multi' && $payment->payment_modes)
            <tr>
                <td colspan="2" style="font-weight: 600; background: #f5f5f5;">Payment Modes (Multiple):</td>
            </tr>
            @foreach($payment->payment_modes as $mode)
            <tr>
                <td style="width: 200px; padding-left: 20px;">
                    <i class="bi bi-arrow-right"></i> {{ ucfirst(str_replace('_', ' ', $mode['payment_mode'])) }}
                </td>
                <td>
                    ₹{{ number_format($mode['amount'], 2) }}
                    @if(!empty($mode['reference_number']))
                        <small class="text-muted">(Ref: {{ $mode['reference_number'] }})</small>
                    @endif
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td style="width: 200px; font-weight: 600;">Payment Mode:</td>
                <td style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->payment_mode) }}</td>
            </tr>
            @if($payment->reference_number)
            <tr>
                <td style="font-weight: 600;">Reference Number:</td>
                <td>{{ $payment->reference_number }}</td>
            </tr>
            @endif
            @endif
            @if($payment->transaction_id)
            <tr>
                <td style="font-weight: 600;">Transaction ID:</td>
                <td>{{ $payment->transaction_id }}</td>
            </tr>
            @endif
            @if($payment->receivedByUser)
            <tr>
                <td style="font-weight: 600;">Received By:</td>
                <td>{{ $payment->receivedByUser->full_name }}</td>
            </tr>
            @endif
            @if($payment->notes)
            <tr>
                <td style="font-weight: 600; vertical-align: top;">Notes:</td>
                <td>{{ $payment->notes }}</td>
            </tr>
            @endif
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
