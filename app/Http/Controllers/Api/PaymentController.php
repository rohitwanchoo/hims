<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = Payment::where('hospital_id', $hospitalId)
            ->with(['bill.patient', 'patient', 'receivedByUser']);

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        // Filter by payment mode
        if ($request->payment_mode) {
            $query->where('payment_mode', $request->payment_mode);
        }

        // Search by receipt number, patient name, or bill number
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bill', function ($bq) use ($search) {
                      $bq->where('bill_number', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')
                          ->orderBy('payment_id', 'desc')
                          ->get();

        return response()->json([
            'data' => $payments,
            'summary' => [
                'total' => $payments->sum('amount'),
                'count' => $payments->count(),
                'today' => $payments->where('payment_date', today())->sum('amount'),
            ]
        ]);
    }

    /**
     * Store a newly created payment
     */
    public function store(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $validated = $request->validate([
            'bill_id' => 'required|exists:bills,bill_id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_mode' => 'required|in:cash,card,upi,cheque,bank_transfer,insurance',
            'reference_number' => 'nullable|string|max:50',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $hospitalId) {
            // Get bill
            $bill = Bill::findOrFail($validated['bill_id']);

            // Validate payment amount doesn't exceed due amount
            if ($validated['amount'] > $bill->due_amount) {
                return response()->json([
                    'message' => 'Payment amount cannot exceed due amount of ' . $bill->due_amount
                ], 400);
            }

            // Generate payment number
            $lastPayment = Payment::where('hospital_id', $hospitalId)
                ->whereDate('created_at', today())
                ->count();
            $paymentNumber = 'PMT-' . date('Ymd') . '-' . str_pad($lastPayment + 1, 4, '0', STR_PAD_LEFT);

            // Create payment
            $payment = Payment::create([
                'hospital_id' => $hospitalId,
                'payment_number' => $paymentNumber,
                'bill_id' => $validated['bill_id'],
                'patient_id' => $bill->patient_id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'status' => 'success',
                'notes' => $validated['notes'] ?? null,
                'received_by' => Auth::id(),
            ]);

            // Update bill amounts
            $bill->paid_amount += $validated['amount'];
            $bill->due_amount = $bill->total_amount - $bill->paid_amount;

            // Update payment status
            if ($bill->due_amount == 0) {
                $bill->payment_status = 'paid';
            } elseif ($bill->paid_amount > 0) {
                $bill->payment_status = 'partial';
            }

            $bill->save();

            return response()->json($payment->load(['bill.patient', 'receivedByUser']), 201);
        });
    }

    /**
     * Display the specified payment
     */
    public function show(string $id)
    {
        $payment = Payment::with(['bill.patient', 'bill.details', 'patient', 'receivedByUser'])
            ->findOrFail($id);

        return response()->json($payment);
    }

    /**
     * Update the specified payment
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        // Only allow updating notes and reference number
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'reference_number' => 'nullable|string|max:50',
        ]);

        $payment->update($validated);

        return response()->json($payment->load(['bill.patient', 'receivedByUser']));
    }

    /**
     * Remove the specified payment (soft cancel/refund)
     */
    public function destroy(string $id)
    {
        return DB::transaction(function () use ($id) {
            $payment = Payment::findOrFail($id);
            $bill = $payment->bill;

            // Update bill amounts
            $bill->paid_amount -= $payment->amount;
            $bill->due_amount = $bill->total_amount - $bill->paid_amount;

            // Update payment status
            if ($bill->due_amount == 0) {
                $bill->payment_status = 'paid';
            } elseif ($bill->paid_amount > 0) {
                $bill->payment_status = 'partial';
            } else {
                $bill->payment_status = 'pending';
            }

            $bill->save();

            // Mark payment as refunded instead of deleting
            $payment->update(['status' => 'refunded']);

            return response()->json(['message' => 'Payment refunded successfully']);
        });
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(string $id)
    {
        $payment = Payment::with(['bill.patient', 'bill.details.service', 'receivedByUser'])
            ->findOrFail($id);

        // Return payment data for printing
        // In production, this would generate a PDF or redirect to a print view
        return response()->json($payment);
    }
}
