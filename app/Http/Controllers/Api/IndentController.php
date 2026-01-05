<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Indent;
use App\Models\IndentDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndentController extends Controller
{
    public function index(Request $request)
    {
        $query = Indent::with(['fromStore', 'toStore', 'requestedBy']);

        if ($request->from_store_id) {
            $query->where('from_store_id', $request->from_store_id);
        }

        if ($request->to_store_id) {
            $query->where('to_store_id', $request->to_store_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date && $request->to_date) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        $indents = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($indents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_store_id' => 'required|exists:stores,store_id',
            'to_store_id' => 'required|exists:stores,store_id|different:from_store_id',
            'required_by_date' => 'nullable|date|after_or_equal:today',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,item_id',
            'items.*.requested_quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $lastIndent = Indent::whereDate('created_at', today())->count();
            $indentNumber = 'IND' . now()->format('Ymd') . str_pad($lastIndent + 1, 4, '0', STR_PAD_LEFT);

            $indent = Indent::create([
                'indent_number' => $indentNumber,
                'from_store_id' => $validated['from_store_id'],
                'to_store_id' => $validated['to_store_id'],
                'required_by_date' => $validated['required_by_date'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'draft',
                'requested_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $indent->details()->create([
                    'item_id' => $item['item_id'],
                    'requested_quantity' => $item['requested_quantity'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'Indent created successfully',
                'indent' => $indent->load('details.item'),
            ], 201);
        });
    }

    public function show(Indent $indent)
    {
        return response()->json([
            'indent' => $indent->load(['fromStore', 'toStore', 'details.item', 'requestedBy', 'approvedBy']),
        ]);
    }

    public function submit(Indent $indent)
    {
        if ($indent->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft indents can be submitted',
            ], 422);
        }

        $indent->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Indent submitted successfully',
            'indent' => $indent,
        ]);
    }

    public function approve(Request $request, Indent $indent)
    {
        if ($indent->status !== 'submitted') {
            return response()->json([
                'message' => 'Only submitted indents can be approved',
            ], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.detail_id' => 'required|exists:indent_details,detail_id',
            'items.*.approved_quantity' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($indent, $validated) {
            foreach ($validated['items'] as $item) {
                IndentDetail::where('detail_id', $item['detail_id'])
                    ->update(['approved_quantity' => $item['approved_quantity']]);
            }

            $indent->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'message' => 'Indent approved successfully',
                'indent' => $indent->load('details'),
            ]);
        });
    }

    public function reject(Request $request, Indent $indent)
    {
        if ($indent->status !== 'submitted') {
            return response()->json([
                'message' => 'Only submitted indents can be rejected',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $indent->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Indent rejected',
            'indent' => $indent,
        ]);
    }

    public function fulfill(Request $request, Indent $indent)
    {
        if ($indent->status !== 'approved') {
            return response()->json([
                'message' => 'Only approved indents can be fulfilled',
            ], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.detail_id' => 'required|exists:indent_details,detail_id',
            'items.*.issued_quantity' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($indent, $validated) {
            foreach ($validated['items'] as $item) {
                IndentDetail::where('detail_id', $item['detail_id'])
                    ->update([
                        'issued_quantity' => $item['issued_quantity'],
                        'batch_number' => $item['batch_number'] ?? null,
                    ]);
            }

            $indent->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
            ]);

            return response()->json([
                'message' => 'Indent fulfilled successfully',
                'indent' => $indent->load('details'),
            ]);
        });
    }
}
