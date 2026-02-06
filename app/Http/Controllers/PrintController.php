<?php

namespace App\Http\Controllers;

use App\Models\IpdAdmission;
use App\Models\OpdVisit;
use App\Models\Payment;
use App\Models\Hospital;
use App\Models\DischargeSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrintController extends Controller
{
    /**
     * Print Discharge Summary
     */
    public function dischargeSummary(Request $request, $id)
    {
        // Find admission first to get hospital_id from the record
        $admission = IpdAdmission::where('ipd_id', $id)
            ->with([
                'patient',
                'treatingDoctor',
                'consultantDoctor',
                'department',
                'ward',
                'bed',
            ])
            ->first();

        if (!$admission) {
            abort(404, 'IPD admission not found');
        }

        // Get hospital from admission record
        $hospital = Hospital::find($admission->hospital_id);

        if (!$hospital) {
            abort(404, 'Hospital not found');
        }

        // Get discharge summary if exists
        $dischargeSummary = DischargeSummary::where('hospital_id', $admission->hospital_id)
            ->where('ipd_id', $id)
            ->with(['treatingDoctor', 'consultantDoctor'])
            ->first();

        // Get medications at discharge (active medications)
        $medications = $admission->medications()
            ->where(function($q) {
                $q->where('is_active', true)
                  ->orWhereNull('end_date')
                  ->orWhere('end_date', '>=', $admission->discharge_date ?? now());
            })
            ->get();

        // Get investigations
        $investigations = $admission->investigations()
            ->where('status', '!=', 'cancelled')
            ->orderBy('order_date', 'desc')
            ->get();

        // Calculate billing summary
        $totalCharges = $admission->total_charges ?? 0;
        $discount = $admission->discount_amount ?? 0;
        $netAmount = $admission->net_amount ?? ($totalCharges - $discount);
        $advancePaid = $admission->advance_amount ?? 0;
        $balance = $netAmount - $advancePaid;

        $billing = [
            'total_charges' => $totalCharges,
            'discount' => $discount,
            'net_amount' => $netAmount,
            'advance_paid' => $advancePaid,
            'balance' => $balance,
        ];

        return view('prints.discharge-summary', compact(
            'admission',
            'hospital',
            'dischargeSummary',
            'medications',
            'investigations',
            'billing'
        ));
    }

    /**
     * Print IPD Case Sheet
     */
    public function ipdCaseSheet(Request $request, $id)
    {
        $hospitalId = $request->query('hospital_id') ?? (Auth::check() ? Auth::user()->hospital_id : null);

        if (!$hospitalId) {
            abort(403, 'Hospital ID is required');
        }

        $admission = IpdAdmission::where('hospital_id', $hospitalId)
            ->where('ipd_id', $id)
            ->with([
                'patient',
                'treatingDoctor',
                'consultantDoctor',
                'admittingDoctor',
                'department',
                'ward',
                'bed',
                'progressNotes' => fn($q) => $q->with('doctor'),
                'medications',
                'investigations',
                'services',
            ])
            ->first();

        if (!$admission) {
            abort(404, 'IPD admission not found');
        }

        $hospital = Hospital::find($hospitalId);

        return view('prints.ipd-case-sheet', compact('admission', 'hospital'));
    }

    /**
     * Print Advance Receipt
     */
    public function advanceReceipt(Request $request, $paymentId)
    {
        $hospitalId = $request->query('hospital_id') ?? (Auth::check() ? Auth::user()->hospital_id : null);

        if (!$hospitalId) {
            abort(403, 'Hospital ID is required');
        }

        $payment = \App\Models\IpdAdvancePayment::where('hospital_id', $hospitalId)
            ->where('advance_id', $paymentId)
            ->with(['ipdAdmission.patient'])
            ->first();

        if (!$payment) {
            abort(404, 'Payment not found');
        }

        $hospital = Hospital::find($hospitalId);

        return view('prints.advance-receipt', compact('payment', 'hospital'));
    }

    /**
     * Print OPD Visit
     */
    public function opdVisit(Request $request, $id)
    {
        // Find OPD visit first to get hospital_id from the record
        $opdVisit = OpdVisit::where('opd_id', $id)
            ->with([
                'patient',
                'doctor',
                'department',
                'patientClass',
                'referenceDoctor',
                'services.service',
                'healthPackage',
            ])
            ->first();

        if (!$opdVisit) {
            abort(404, 'OPD visit not found');
        }

        // Get hospital from OPD visit record
        $hospital = Hospital::find($opdVisit->hospital_id);

        if (!$hospital) {
            abort(404, 'Hospital not found');
        }

        return view('prints.opd-visit', compact('opdVisit', 'hospital'));
    }

    /**
     * Print Payment Receipt
     */
    public function paymentReceipt(Request $request, $id)
    {
        // Find payment first to get hospital_id from the record
        $payment = Payment::where('payment_id', $id)
            ->with([
                'bill.patient',
                'bill.details',
                'patient',
                'receivedByUser'
            ])
            ->first();

        if (!$payment) {
            abort(404, 'Payment not found');
        }

        // Get hospital from payment record
        $hospital = Hospital::find($payment->hospital_id);

        if (!$hospital) {
            abort(404, 'Hospital not found');
        }

        return view('prints.payment-receipt', compact('payment', 'hospital'));
    }

    /**
     * Print Bill
     */
    public function bill(Request $request, $id)
    {
        // Find bill first to get hospital_id from the record
        $bill = \App\Models\Bill::where('bill_id', $id)
            ->with([
                'patient',
                'details.doctor',
                'details.costHead',
                'opdVisit',
                'ipdAdmission'
            ])
            ->first();

        if (!$bill) {
            abort(404, 'Bill not found');
        }

        // Get hospital from bill record
        $hospital = Hospital::find($bill->hospital_id);

        if (!$hospital) {
            abort(404, 'Hospital not found');
        }

        return view('prints.bill', compact('bill', 'hospital'));
    }

    /**
     * Print IPD Discharge Receipt
     */
    public function ipdDischargeReceipt(Request $request, $id)
    {
        // Find admission first to get hospital_id from the record
        $admission = IpdAdmission::where('ipd_id', $id)
            ->with([
                'patient',
                'treatingDoctor',
                'consultantDoctor',
                'admittingDoctor',
                'department',
                'ward',
                'bed',
                'services',
                'advancePayments',
                'bill'
            ])
            ->first();

        if (!$admission) {
            abort(404, 'IPD admission not found');
        }

        // Get hospital from admission record
        $hospital = Hospital::find($admission->hospital_id);

        if (!$hospital) {
            abort(404, 'Hospital not found');
        }

        // Get all services grouped by type
        $services = \App\Models\IpdService::where('ipd_id', $id)
            ->orderBy('service_date', 'asc')
            ->orderBy('service_type', 'asc')
            ->get()
            ->groupBy('service_type');

        // Calculate bed charges
        $losDays = $admission->los_days ?? 0;
        $bedChargesPerDay = $admission->bed->charges_per_day ?? 0;
        $bedCharges = $losDays * $bedChargesPerDay;

        // Calculate service totals
        $servicesAmount = \App\Models\IpdService::where('ipd_id', $id)->sum('amount');
        $servicesDiscount = \App\Models\IpdService::where('ipd_id', $id)->sum('discount');
        $servicesTotal = \App\Models\IpdService::where('ipd_id', $id)->sum('net_amount');

        // Calculate totals
        $grossTotal = $bedCharges + $servicesAmount;
        $totalDiscount = ($admission->discount_amount ?? 0) + $servicesDiscount;
        $netTotal = $grossTotal - $totalDiscount;
        $taxAmount = $admission->tax_amount ?? 0;
        $finalTotal = $netTotal + $taxAmount;

        // Get advance payments
        $advancePayments = $admission->advancePayments;
        $totalAdvance = $advancePayments->sum('amount');
        $totalRefunded = $advancePayments->sum('refund_amount');
        $netAdvance = $totalAdvance - $totalRefunded;

        // Calculate balance
        $balanceDue = $finalTotal - $netAdvance;

        $billing = [
            'bed_charges' => $bedCharges,
            'bed_days' => $losDays,
            'bed_rate' => $bedChargesPerDay,
            'services_amount' => $servicesAmount,
            'services_discount' => $servicesDiscount,
            'services_total' => $servicesTotal,
            'gross_total' => $grossTotal,
            'discount' => $totalDiscount,
            'net_total' => $netTotal,
            'tax_amount' => $taxAmount,
            'final_total' => $finalTotal,
            'advance_paid' => $netAdvance,
            'total_advance' => $totalAdvance,
            'total_refunded' => $totalRefunded,
            'balance_due' => $balanceDue,
        ];

        return view('prints.ipd-discharge-receipt', compact(
            'admission',
            'hospital',
            'services',
            'billing',
            'advancePayments'
        ));
    }
}
