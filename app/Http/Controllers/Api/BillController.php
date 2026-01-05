<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with(['patient', 'details']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('patient_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->status) {
            $query->where('payment_status', $request->status);
        }

        if ($request->from_date) {
            $query->whereDate('bill_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('bill_date', '<=', $request->to_date);
        }

        return response()->json(
            $query->orderBy('bill_date', 'desc')->paginate($request->per_page ?? 15)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'bill_date' => 'required|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,service_id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $lastBill = Bill::whereDate('created_at', today())->count();
            $billNumber = 'BILL-' . date('Ymd') . '-' . str_pad($lastBill + 1, 4, '0', STR_PAD_LEFT);

            $totalAmount = collect($validated['items'])->sum('amount');
            $discountAmount = $validated['discount_amount'] ?? 0;

            $bill = Bill::create([
                'bill_number' => $billNumber,
                'patient_id' => $validated['patient_id'],
                'bill_date' => $validated['bill_date'],
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'net_amount' => $totalAmount - $discountAmount,
                'paid_amount' => 0,
                'balance_amount' => $totalAmount - $discountAmount,
                'payment_status' => 'unpaid',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                BillDetail::create([
                    'bill_id' => $bill->bill_id,
                    'service_id' => $item['service_id'],
                    'quantity' => $item['quantity'],
                    'unit_rate' => $item['rate'],
                    'amount' => $item['amount'],
                ]);
            }

            return response()->json($bill->load('details'), 201);
        });
    }

    public function show(string $id)
    {
        $bill = Bill::with(['patient', 'details.service', 'payments'])->findOrFail($id);
        return response()->json($bill);
    }

    public function update(Request $request, string $id)
    {
        $bill = Bill::findOrFail($id);

        if ($bill->payment_status === 'paid') {
            return response()->json(['message' => 'Cannot update a paid bill'], 400);
        }

        $validated = $request->validate([
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $bill->update([
            'discount_amount' => $validated['discount_amount'] ?? $bill->discount_amount,
            'net_amount' => $bill->total_amount - ($validated['discount_amount'] ?? $bill->discount_amount),
            'balance_amount' => $bill->total_amount - ($validated['discount_amount'] ?? $bill->discount_amount) - $bill->paid_amount,
        ]);

        return response()->json($bill);
    }

    public function destroy(string $id)
    {
        $bill = Bill::findOrFail($id);

        if ($bill->paid_amount > 0) {
            return response()->json(['message' => 'Cannot delete bill with payments'], 400);
        }

        $bill->details()->delete();
        $bill->delete();

        return response()->json(['message' => 'Bill deleted']);
    }
}
