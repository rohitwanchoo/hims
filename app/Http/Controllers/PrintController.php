<?php

namespace App\Http\Controllers;

use App\Models\IpdAdmission;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrintController extends Controller
{
    /**
     * Print Discharge Summary
     */
    public function dischargeSummary(Request $request, $id)
    {
        // Get hospital_id from query param or authenticated user
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
                'department',
                'ward',
                'bed',
            ])
            ->first();

        if (!$admission) {
            abort(404, 'IPD admission not found');
        }

        // Get hospital details
        $hospital = Hospital::find($hospitalId);

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
}
