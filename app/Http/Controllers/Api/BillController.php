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
        $hospitalId = auth()->user()->hospital_id;

        $query = Bill::where('hospital_id', $hospitalId)
            ->with(['patient.insuranceCompanyRelation', 'details', 'payments']);

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

        if ($request->bill_type) {
            $query->where('bill_type', $request->bill_type);
        }

        if ($request->from_date) {
            $query->whereDate('bill_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('bill_date', '<=', $request->to_date);
        }

        if ($request->opd_id) {
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        return response()->json(
            $query->orderBy('bill_date', 'desc')->paginate($request->per_page ?? 15)
        );
    }

    public function store(Request $request)
    {
        $hospitalId = auth()->user()->hospital_id;

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'opd_id' => 'nullable|exists:opd_visits,opd_id',
            'ipd_id' => 'nullable|exists:ipd_admissions,ipd_id',
            'bill_date' => 'required|date',
            'bill_type' => 'required|in:opd,ipd,pharmacy,lab,general',
            'payment_mode' => 'nullable|in:cash,cashless,insurance',
            'insurance_company' => 'nullable|string|max:255',
            'policy_number' => 'nullable|string|max:100',
            'approved_amount' => 'nullable|numeric|min:0',
            'copay_amount' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'adjustment' => 'nullable|numeric',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'nullable|in:service,procedure,consumable,room,medicine,lab,other',
            'items.*.item_id' => 'nullable|integer',
            'items.*.cost_head_id' => 'nullable|exists:cost_heads,cost_head_id',
            'items.*.item_name' => 'required|string',
            'items.*.ward_name' => 'nullable|string|max:100',
            'items.*.bed_name' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.amount' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $hospitalId) {
            // Generate bill number
            $lastBill = Bill::where('hospital_id', $hospitalId)
                ->whereDate('created_at', today())->count();
            $billNumber = 'BILL-' . date('Ymd') . '-' . str_pad($lastBill + 1, 4, '0', STR_PAD_LEFT);

            // Calculate amounts
            $subtotal = collect($validated['items'])->sum('amount');
            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxAmount = $validated['tax_amount'] ?? 0;
            $adjustment = $validated['adjustment'] ?? 0;
            $totalAmount = $subtotal - $discountAmount + $taxAmount + $adjustment;

            // Create bill
            $bill = Bill::create([
                'hospital_id' => $hospitalId,
                'bill_number' => $billNumber,
                'patient_id' => $validated['patient_id'],
                'opd_id' => $validated['opd_id'] ?? null,
                'ipd_id' => $validated['ipd_id'] ?? null,
                'bill_date' => $validated['bill_date'],
                'bill_type' => $validated['bill_type'],
                'payment_mode' => $validated['payment_mode'] ?? 'cash',
                'insurance_company' => $validated['insurance_company'] ?? null,
                'policy_number' => $validated['policy_number'] ?? null,
                'approved_amount' => $validated['approved_amount'] ?? 0,
                'copay_amount' => $validated['copay_amount'] ?? 0,
                'insurance_amount' => $validated['insurance_amount'] ?? 0,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_percent' => $validated['discount_percent'] ?? 0,
                'tax_amount' => $taxAmount,
                'adjustment' => $adjustment,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'due_amount' => $totalAmount,
                'payment_status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            // Create bill details
            foreach ($validated['items'] as $item) {
                BillDetail::create([
                    'bill_id' => $bill->bill_id,
                    'item_type' => $item['item_type'] ?? 'service',
                    'item_id' => $item['item_id'] ?? null,
                    'cost_head_id' => $item['cost_head_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'ward_name' => $item['ward_name'] ?? null,
                    'bed_name' => $item['bed_name'] ?? null,
                    'service_date' => $item['service_date'] ?? null,
                    'doctor_id' => $item['doctor_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'rate' => $item['unit_price'],
                    'amount' => $item['amount'],
                ]);
            }

            return response()->json($bill->load('details'), 201);
        });
    }

    public function show(string $id)
    {
        $bill = Bill::with(['patient.insuranceCompanyRelation', 'details.costHead', 'details.doctor', 'payments.receivedByUser', 'opdVisit', 'ipdAdmission'])
            ->findOrFail($id);
        return response()->json($bill);
    }

    public function update(Request $request, string $id)
    {
        $bill = Bill::findOrFail($id);

        if ($bill->payment_status === 'paid') {
            return response()->json(['message' => 'Cannot update a paid bill'], 400);
        }

        $validated = $request->validate([
            'payment_mode' => 'nullable|in:cash,cashless,insurance',
            'insurance_company' => 'nullable|string|max:255',
            'policy_number' => 'nullable|string|max:100',
            'approved_amount' => 'nullable|numeric|min:0',
            'copay_amount' => 'nullable|numeric|min:0',
            'insurance_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'adjustment' => 'nullable|numeric',
            'refund_amount' => 'nullable|numeric',
            'items' => 'nullable|array',
            'items.*.bill_detail_id' => 'nullable|exists:bill_details,detail_id',
            'items.*.service_date' => 'nullable|date',
            'items.*.doctor_id' => 'nullable|exists:doctors,doctor_id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.rate' => 'nullable|numeric|min:0',
            'items.*.amount' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $bill) {
            // Update bill items if provided
            if (isset($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    if (isset($item['bill_detail_id'])) {
                        $detail = BillDetail::find($item['bill_detail_id']);
                        if ($detail && $detail->bill_id == $bill->bill_id) {
                            $detail->update([
                                'service_date' => $item['service_date'] ?? $detail->service_date,
                                'doctor_id' => $item['doctor_id'] ?? $detail->doctor_id,
                                'quantity' => $item['quantity'] ?? $detail->quantity,
                                'rate' => $item['rate'] ?? $detail->rate,
                                'amount' => $item['amount'] ?? $detail->amount,
                            ]);
                        }
                    }
                }

                // Recalculate subtotal from updated items
                $bill->refresh();
                $subtotal = $bill->details()->sum('amount');
                $bill->subtotal = $subtotal;
            }

            $discountAmount = $validated['discount_amount'] ?? $bill->discount_amount;
            $taxAmount = $validated['tax_amount'] ?? $bill->tax_amount;
            $adjustment = $validated['adjustment'] ?? $validated['refund_amount'] ?? $bill->adjustment;

            $totalAmount = $bill->subtotal - $discountAmount + $taxAmount + $adjustment;

            $bill->update([
                'payment_mode' => $validated['payment_mode'] ?? $bill->payment_mode,
                'insurance_company' => $validated['insurance_company'] ?? $bill->insurance_company,
                'policy_number' => $validated['policy_number'] ?? $bill->policy_number,
                'approved_amount' => $validated['approved_amount'] ?? $bill->approved_amount,
                'copay_amount' => $validated['copay_amount'] ?? $bill->copay_amount,
                'insurance_amount' => $validated['insurance_amount'] ?? $bill->insurance_amount,
                'discount_amount' => $discountAmount,
                'discount_percent' => $validated['discount_percent'] ?? $bill->discount_percent,
                'tax_amount' => $taxAmount,
                'adjustment' => $adjustment,
                'total_amount' => $totalAmount,
                'due_amount' => $totalAmount - $bill->paid_amount,
            ]);

            // Update payment status
            if ($bill->due_amount == 0 && $bill->paid_amount > 0) {
                $bill->payment_status = 'paid';
            } elseif ($bill->paid_amount > 0) {
                $bill->payment_status = 'partial';
            } else {
                $bill->payment_status = 'pending';
            }
            $bill->save();

            return response()->json($bill->load('details'));
        });
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
