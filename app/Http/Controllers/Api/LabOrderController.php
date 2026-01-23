<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabOrder;
use App\Models\LabOrderDetail;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabOrderController extends Controller
{
    /**
     * Display a listing of lab orders
     */
    public function index(Request $request)
    {
        $query = LabOrder::with([
            'patient',
            'referredBy',
            'details.test',
            'createdBy'
        ]);

        // Filter by date
        if ($request->date) {
            $query->whereDate('order_date', $request->date);
        }

        // Filter by date range
        if ($request->from_date && $request->to_date) {
            $query->whereBetween('order_date', [$request->from_date, $request->to_date]);
        }

        // Filter by patient
        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        // Filter by referring doctor
        if ($request->referred_by) {
            $query->where('referred_by', $request->referred_by);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        // Filter by visit or admission
        if ($request->opd_id) {
            $query->where('opd_id', $request->opd_id);
        }

        if ($request->ipd_id) {
            $query->where('ipd_id', $request->ipd_id);
        }

        $orders = $query->orderBy('order_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created lab order
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'referred_by' => 'nullable|exists:doctors,doctor_id',
            'opd_id' => 'nullable|exists:opd_visits,visit_id',
            'ipd_id' => 'nullable|exists:ipd_admissions,admission_id',
            'order_date' => 'required|date',
            'priority' => 'nullable|in:routine,urgent,stat',
            'tests' => 'required|array|min:1',
            'tests.*.test_id' => 'required|exists:lab_tests,test_id',
            'tests.*.notes' => 'nullable|string',
            'clinical_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate order number
            $lastOrder = LabOrder::where('hospital_id', auth()->user()->hospital_id)
                ->orderBy('order_id', 'desc')
                ->first();

            if ($lastOrder && $lastOrder->order_number) {
                preg_match('/\d+$/', $lastOrder->order_number, $matches);
                $nextNumber = isset($matches[0]) ? (int)$matches[0] + 1 : 1;
            } else {
                $nextNumber = 1;
            }
            $orderNumber = 'LAB-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            // Calculate total amount from tests
            $totalAmount = 0;
            $testIds = array_column($request->tests, 'test_id');
            $tests = LabTest::whereIn('test_id', $testIds)->get()->keyBy('test_id');

            foreach ($request->tests as $testData) {
                $test = $tests->get($testData['test_id']);
                if ($test) {
                    $totalAmount += $test->rate;
                }
            }

            // Create lab order
            $order = LabOrder::create([
                'hospital_id' => auth()->user()->hospital_id,
                'order_number' => $orderNumber,
                'patient_id' => $request->patient_id,
                'referred_by' => $request->referred_by,
                'opd_id' => $request->opd_id,
                'ipd_id' => $request->ipd_id,
                'order_date' => $request->order_date,
                'priority' => $request->priority ?? 'routine',
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'clinical_notes' => $request->clinical_notes,
                'created_by' => auth()->user()->user_id,
            ]);

            // Create order details for each test
            foreach ($request->tests as $testData) {
                $test = $tests->get($testData['test_id']);
                if ($test) {
                    LabOrderDetail::create([
                        'order_id' => $order->order_id,
                        'test_id' => $testData['test_id'],
                        'rate' => $test->rate,
                        'remarks' => $testData['notes'] ?? null,
                        // result_value, result_status, verified_by, verified_at will be set when results are stored
                    ]);
                }
            }

            DB::commit();

            // Load relationships
            $order->load(['patient', 'referredBy', 'details.test']);

            return response()->json([
                'success' => true,
                'message' => 'Lab order created successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lab order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified lab order
     */
    public function show(LabOrder $labOrder)
    {
        $labOrder->load([
            'patient',
            'referredBy',
            'opdVisit',
            'ipdAdmission',
            'details.test.category',
            'details.verifiedBy',
            'createdBy'
        ]);

        return response()->json([
            'success' => true,
            'data' => $labOrder
        ]);
    }

    /**
     * Update the specified lab order
     */
    public function update(Request $request, LabOrder $labOrder)
    {
        $request->validate([
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:routine,urgent,stat',
            'clinical_notes' => 'nullable|string',
        ]);

        $updateData = $request->only(['status', 'priority', 'clinical_notes']);

        // If marking as completed, set completed_at timestamp
        if ($request->status === 'completed') {
            $updateData['completed_at'] = now();
        }

        $labOrder->update($updateData);

        $labOrder->load(['patient', 'referredBy', 'details.test']);

        return response()->json([
            'success' => true,
            'message' => 'Lab order updated successfully',
            'data' => $labOrder
        ]);
    }

    /**
     * Remove the specified lab order
     */
    public function destroy(LabOrder $labOrder)
    {
        // Only allow deletion of pending orders
        if ($labOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete lab order. Only pending orders can be deleted.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Delete order details
            $labOrder->details()->delete();

            // Delete the order
            $labOrder->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lab order deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lab order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store results for lab order tests
     */
    public function storeResults(Request $request, LabOrder $order)
    {
        $request->validate([
            'results' => 'required|array|min:1',
            'results.*.detail_id' => 'required|exists:lab_order_details,detail_id',
            'results.*.result_value' => 'required|string',
            'results.*.result_status' => 'required|in:normal,abnormal,critical',
            'results.*.remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->results as $resultData) {
                $detail = LabOrderDetail::where('detail_id', $resultData['detail_id'])
                    ->where('order_id', $order->order_id)
                    ->first();

                if ($detail) {
                    $detail->update([
                        'result_value' => $resultData['result_value'],
                        'result_status' => $resultData['result_status'],
                        'remarks' => $resultData['remarks'] ?? $detail->remarks,
                        'verified_by' => auth()->user()->user_id,
                        'verified_at' => now(),
                    ]);
                }
            }

            // Check if all tests have results (completed = result_value is not null)
            $pendingCount = $order->details()->whereNull('result_value')->count();
            if ($pendingCount === 0) {
                $order->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
            } else {
                $order->update(['status' => 'in_progress']);
            }

            DB::commit();

            // Reload order with relationships
            $order->load(['patient', 'referredBy', 'details.test', 'details.verifiedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Lab results stored successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to store lab results',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
