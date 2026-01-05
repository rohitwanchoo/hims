<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMode;
use App\Models\PaymentModeDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentModeController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentMode::with('details');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('mode_name', 'like', "%{$request->search}%")
                  ->orWhere('mode_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->use_for_refund !== null) {
            $query->where('use_for_refund', $request->use_for_refund);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $paymentModes = $query->orderBy('mode_name')->get();

        return response()->json($paymentModes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mode_code' => 'required|string|max:20|unique:payment_modes',
            'mode_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'has_financial_details' => 'boolean',
            'use_for_refund' => 'boolean',
            'is_title_mandatory' => 'boolean',
            'value_type' => 'nullable|in:text,number,date',
            'value_max_length' => 'nullable|integer|min:1',
            'post_charges_applicable' => 'boolean',
            'post_charge_percent' => 'nullable|numeric|min:0|max:100',
            'details' => 'nullable|array',
            'details.*.field_name' => 'required_with:details|string|max:50',
            'details.*.field_label' => 'required_with:details|string|max:100',
            'details.*.field_type' => 'nullable|in:text,number,date',
            'details.*.is_required' => 'boolean',
            'details.*.max_length' => 'nullable|integer|min:1',
            'details.*.display_order' => 'nullable|integer|min:0',
        ]);

        $paymentMode = DB::transaction(function () use ($validated) {
            $details = $validated['details'] ?? [];
            unset($validated['details']);

            $paymentMode = PaymentMode::create($validated);

            foreach ($details as $detail) {
                $paymentMode->details()->create($detail);
            }

            return $paymentMode;
        });

        return response()->json($paymentMode->load('details'), 201);
    }

    public function show(PaymentMode $paymentMode)
    {
        return response()->json($paymentMode->load('details'));
    }

    public function update(Request $request, PaymentMode $paymentMode)
    {
        $validated = $request->validate([
            'mode_name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'has_financial_details' => 'boolean',
            'use_for_refund' => 'boolean',
            'is_title_mandatory' => 'boolean',
            'value_type' => 'nullable|in:text,number,date',
            'value_max_length' => 'nullable|integer|min:1',
            'post_charges_applicable' => 'boolean',
            'post_charge_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'details' => 'nullable|array',
            'details.*.detail_id' => 'nullable|exists:payment_mode_details,detail_id',
            'details.*.field_name' => 'required_with:details|string|max:50',
            'details.*.field_label' => 'required_with:details|string|max:100',
            'details.*.field_type' => 'nullable|in:text,number,date',
            'details.*.is_required' => 'boolean',
            'details.*.max_length' => 'nullable|integer|min:1',
            'details.*.display_order' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($paymentMode, $validated) {
            $details = $validated['details'] ?? null;
            unset($validated['details']);

            $paymentMode->update($validated);

            if ($details !== null) {
                // Get existing detail IDs
                $existingIds = collect($details)
                    ->pluck('detail_id')
                    ->filter()
                    ->toArray();

                // Delete removed details
                $paymentMode->details()
                    ->whereNotIn('detail_id', $existingIds)
                    ->delete();

                // Update or create details
                foreach ($details as $detail) {
                    if (!empty($detail['detail_id'])) {
                        PaymentModeDetail::where('detail_id', $detail['detail_id'])
                            ->update($detail);
                    } else {
                        $paymentMode->details()->create($detail);
                    }
                }
            }
        });

        return response()->json($paymentMode->load('details'));
    }

    public function destroy(PaymentMode $paymentMode)
    {
        $paymentMode->delete();
        return response()->json(['message' => 'Payment mode deleted successfully']);
    }
}
