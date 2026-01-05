<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\GoodsReceiptNote;
use App\Models\GrnDetail;
use App\Models\ItemStock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'store', 'createdBy']);

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('po_date', [$request->from_date, $request->to_date]);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'store_id' => 'required|exists:stores,store_id',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:po_date',
            'payment_terms' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,item_id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.gst_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        return DB::transaction(function () use ($validated) {
            $lastPo = PurchaseOrder::whereDate('created_at', today())->count();
            $poNumber = 'PO' . now()->format('Ymd') . str_pad($lastPo + 1, 4, '0', STR_PAD_LEFT);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($validated['items'] as $item) {
                $amount = $item['quantity'] * $item['rate'];
                $discount = $amount * (($item['discount_percent'] ?? 0) / 100);
                $taxable = $amount - $discount;
                $tax = $taxable * (($item['gst_percent'] ?? 0) / 100);

                $subtotal += $taxable;
                $taxAmount += $tax;
            }

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'store_id' => $validated['store_id'],
                'po_date' => $validated['po_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $amount = $item['quantity'] * $item['rate'];
                $discount = $amount * (($item['discount_percent'] ?? 0) / 100);

                $po->details()->create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'discount_percent' => $item['discount_percent'] ?? 0,
                    'gst_percent' => $item['gst_percent'] ?? 0,
                    'amount' => $amount - $discount,
                ]);
            }

            return response()->json([
                'message' => 'Purchase order created successfully',
                'purchase_order' => $po->load('details.item'),
            ], 201);
        });
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return response()->json([
            'purchase_order' => $purchaseOrder->load(['supplier', 'store', 'details.item', 'grns']),
        ]);
    }

    public function approve(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft POs can be approved',
            ], 422);
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Purchase order approved successfully',
            'purchase_order' => $purchaseOrder,
        ]);
    }

    public function receiveGoods(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (!in_array($purchaseOrder->status, ['approved', 'partially_received'])) {
            return response()->json([
                'message' => 'PO must be approved to receive goods',
            ], 422);
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:50',
            'invoice_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.detail_id' => 'required|exists:purchase_order_details,detail_id',
            'items.*.received_quantity' => 'required|numeric|min:0.01',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.mrp' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($purchaseOrder, $validated) {
            $lastGrn = GoodsReceiptNote::whereDate('created_at', today())->count();
            $grnNumber = 'GRN' . now()->format('Ymd') . str_pad($lastGrn + 1, 4, '0', STR_PAD_LEFT);

            $totalAmount = 0;

            $grn = GoodsReceiptNote::create([
                'grn_number' => $grnNumber,
                'po_id' => $purchaseOrder->po_id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'store_id' => $purchaseOrder->store_id,
                'invoice_number' => $validated['invoice_number'],
                'invoice_date' => $validated['invoice_date'],
                'received_by' => auth()->id(),
                'status' => 'received',
            ]);

            foreach ($validated['items'] as $item) {
                $poDetail = PurchaseOrderDetail::find($item['detail_id']);
                $amount = $item['received_quantity'] * $poDetail->rate;

                $grn->details()->create([
                    'item_id' => $poDetail->item_id,
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'quantity' => $item['received_quantity'],
                    'purchase_rate' => $poDetail->rate,
                    'mrp' => $item['mrp'] ?? null,
                    'amount' => $amount,
                ]);

                $totalAmount += $amount;

                // Update stock
                $stock = ItemStock::firstOrCreate([
                    'store_id' => $purchaseOrder->store_id,
                    'item_id' => $poDetail->item_id,
                    'batch_number' => $item['batch_number'] ?? 'DEFAULT',
                ], [
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'quantity' => 0,
                    'purchase_rate' => $poDetail->rate,
                    'mrp' => $item['mrp'] ?? null,
                ]);

                $stock->increment('quantity', $item['received_quantity']);

                // Record stock movement
                StockMovement::create([
                    'store_id' => $purchaseOrder->store_id,
                    'item_id' => $poDetail->item_id,
                    'movement_type' => 'receipt',
                    'quantity' => $item['received_quantity'],
                    'balance_after' => $stock->quantity,
                    'reference_type' => 'grn',
                    'reference_id' => $grn->grn_id,
                    'batch_number' => $item['batch_number'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                // Update PO detail received quantity
                $poDetail->increment('received_quantity', $item['received_quantity']);
            }

            $grn->update(['total_amount' => $totalAmount]);

            // Check if fully received
            $allReceived = $purchaseOrder->details->every(function ($detail) {
                return $detail->received_quantity >= $detail->quantity;
            });

            $purchaseOrder->update([
                'status' => $allReceived ? 'received' : 'partially_received',
            ]);

            return response()->json([
                'message' => 'Goods received successfully',
                'grn' => $grn->load('details.item'),
            ], 201);
        });
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return response()->json([
                'message' => 'Cannot cancel fully received PO',
            ], 422);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        $purchaseOrder->update([
            'status' => 'cancelled',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return response()->json([
            'message' => 'Purchase order cancelled',
            'purchase_order' => $purchaseOrder,
        ]);
    }
}
