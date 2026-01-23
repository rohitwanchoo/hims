<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RadiologyOrder;
use App\Models\RadiologyOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RadiologyOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyOrder::with([
            'patient',
            'referringDoctor',
            'orderDetails.test.modality',
        ]);

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('patient_name', 'like', "%{$search}%")
                            ->orWhere('pcd', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'opd_id' => 'nullable|exists:opd_visits,opd_id',
            'ipd_id' => 'nullable|exists:ipd_admissions,ipd_id',
            'referring_doctor_id' => 'required|exists:doctors,doctor_id',
            'priority' => 'nullable|in:routine,urgent,stat',
            'clinical_indication' => 'nullable|string',
            'notes' => 'nullable|string',
            'tests' => 'required|array|min:1',
            'tests.*.radiology_test_id' => 'required|exists:radiology_tests,radiology_test_id',
            'tests.*.with_contrast' => 'nullable|boolean',
            'tests.*.scheduled_datetime' => 'nullable|date',
            'tests.*.special_instructions' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $lastOrder = RadiologyOrder::whereDate('created_at', today())->count();
            $orderNumber = 'RAD' . now()->format('Ymd') . str_pad($lastOrder + 1, 4, '0', STR_PAD_LEFT);

            $order = RadiologyOrder::create([
                'order_number' => $orderNumber,
                'patient_id' => $validated['patient_id'],
                'opd_id' => $validated['opd_id'] ?? null,
                'ipd_id' => $validated['ipd_id'] ?? null,
                'referring_doctor_id' => $validated['referring_doctor_id'],
                'priority' => $validated['priority'] ?? 'routine',
                'clinical_indication' => $validated['clinical_indication'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'ordered',
                'ordered_by' => auth()->id(),
            ]);

            foreach ($validated['tests'] as $testData) {
                $order->orderDetails()->create([
                    'radiology_test_id' => $testData['radiology_test_id'],
                    'with_contrast' => $testData['with_contrast'] ?? false,
                    'scheduled_datetime' => $testData['scheduled_datetime'] ?? null,
                    'special_instructions' => $testData['special_instructions'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'message' => 'Radiology order created successfully',
                'order' => $order->load('orderDetails.test'),
            ], 201);
        });
    }

    public function show(RadiologyOrder $order)
    {
        return response()->json([
            'order' => $order->load([
                'patient',
                'referringDoctor',
                'orderDetails.test.modality',
                'reports',
            ]),
        ]);
    }

    public function updateStatus(Request $request, RadiologyOrder $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:ordered,scheduled,in_progress,completed,cancelled',
            'cancellation_reason' => 'required_if:status,cancelled|nullable|string',
        ]);

        $order->update([
            'status' => $validated['status'],
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        if ($validated['status'] === 'cancelled') {
            $order->orderDetails()->update(['status' => 'cancelled']);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ]);
    }

    public function updateDetailStatus(Request $request, RadiologyOrderDetail $detail)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,scheduled,in_progress,completed,cancelled',
            'scheduled_datetime' => 'nullable|date',
            'performed_at' => 'nullable|date',
            'performed_by' => 'nullable|exists:users,user_id',
        ]);

        $detail->update($validated);

        // Update order status based on details
        $order = $detail->order;
        $allCompleted = $order->orderDetails()->where('status', '!=', 'completed')->count() === 0;
        $anyInProgress = $order->orderDetails()->where('status', 'in_progress')->count() > 0;

        if ($allCompleted) {
            $order->update(['status' => 'completed']);
        } elseif ($anyInProgress) {
            $order->update(['status' => 'in_progress']);
        }

        return response()->json([
            'message' => 'Detail status updated successfully',
            'detail' => $detail,
        ]);
    }

    public function worklist(Request $request)
    {
        $date = $request->date ?? today()->toDateString();

        $query = RadiologyOrderDetail::with([
            'order.patient',
            'order.referringDoctor',
            'test.modality',
        ])
            ->whereIn('status', ['pending', 'scheduled', 'in_progress'])
            ->whereHas('order', function ($q) use ($date) {
                $q->whereDate('created_at', $date)
                    ->whereIn('status', ['ordered', 'scheduled', 'in_progress']);
            });

        if ($request->modality_id) {
            $query->whereHas('test', function ($q) use ($request) {
                $q->where('modality_id', $request->modality_id);
            });
        }

        $worklist = $query->join('radiology_orders', 'radiology_order_details.radiology_order_id', '=', 'radiology_orders.radiology_order_id')
            ->orderBy('radiology_orders.priority', 'desc')
            ->orderBy('scheduled_datetime')
            ->select('radiology_order_details.*')
            ->get();

        return response()->json(['worklist' => $worklist]);
    }
}
