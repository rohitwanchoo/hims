<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientClass;
use App\Models\CashlessPriceList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientClassController extends Controller
{
    public function index(Request $request)
    {
        $query = PatientClass::with('client');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('class_name', 'like', "%{$request->search}%")
                  ->orWhere('class_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->is_cashless !== null) {
            $query->where('is_cashless', $request->is_cashless);
        }

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $classes = $query->orderBy('class_name')->get();

        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_code' => 'required|string|max:20|unique:classes',
            'class_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,client_id',
            'discount_ledger' => 'nullable|string|max:100',
            'is_cashless' => 'boolean',
            'apply_service_charges_on_cashless' => 'boolean',
            'is_cashless_reimbursement' => 'boolean',
            'is_copay' => 'boolean',
            'copay_patient_percent' => 'nullable|numeric|min:0|max:100',
            'copay_tpa_percent' => 'nullable|numeric|min:0|max:100',
            'cashless_applicable_on' => 'nullable|in:opd,ipd,both',
            'pharmacy_cash' => 'boolean',
            'apply_token' => 'boolean',
            'ipd_for_new' => 'boolean',
            'service_tax_applicable' => 'boolean',
            'service_tax_on' => 'nullable|in:opd,ipd,health_checkup',
            'service_tax_bill_type' => 'nullable|in:cash,credit,both',
            'grace_period_days' => 'nullable|integer|min:0',
            'prior_approval_required' => 'boolean',
            'prior_approval_limit' => 'nullable|numeric|min:0',
        ]);

        $patientClass = PatientClass::create($validated);

        return response()->json($patientClass->load('client'), 201);
    }

    public function show(PatientClass $patientClass)
    {
        return response()->json($patientClass->load(['client', 'cashlessPriceLists.service']));
    }

    public function update(Request $request, PatientClass $patientClass)
    {
        $validated = $request->validate([
            'class_name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,client_id',
            'discount_ledger' => 'nullable|string|max:100',
            'is_cashless' => 'boolean',
            'apply_service_charges_on_cashless' => 'boolean',
            'is_cashless_reimbursement' => 'boolean',
            'is_copay' => 'boolean',
            'copay_patient_percent' => 'nullable|numeric|min:0|max:100',
            'copay_tpa_percent' => 'nullable|numeric|min:0|max:100',
            'cashless_applicable_on' => 'nullable|in:opd,ipd,both',
            'pharmacy_cash' => 'boolean',
            'apply_token' => 'boolean',
            'ipd_for_new' => 'boolean',
            'service_tax_applicable' => 'boolean',
            'service_tax_on' => 'nullable|in:opd,ipd,health_checkup',
            'service_tax_bill_type' => 'nullable|in:cash,credit,both',
            'grace_period_days' => 'nullable|integer|min:0',
            'prior_approval_required' => 'boolean',
            'prior_approval_limit' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $patientClass->update($validated);

        return response()->json($patientClass->load('client'));
    }

    public function destroy(PatientClass $patientClass)
    {
        $patientClass->delete();
        return response()->json(['message' => 'Class deleted successfully']);
    }

    /**
     * Copy rates from one class to another
     */
    public function copyRates(Request $request, PatientClass $patientClass)
    {
        $validated = $request->validate([
            'source_class_id' => 'required|exists:classes,class_id',
            'rate_type' => 'required|in:normal,increase,decrease',
            'adjustment_percent' => 'nullable|numeric|min:0|max:100',
            'service_type' => 'nullable|string',
            'effective_from' => 'required|date',
        ]);

        $sourceClass = PatientClass::findOrFail($validated['source_class_id']);
        $adjustmentPercent = $validated['adjustment_percent'] ?? 0;

        DB::transaction(function () use ($patientClass, $sourceClass, $validated, $adjustmentPercent) {
            $query = CashlessPriceList::where('class_id', $sourceClass->class_id)
                ->where('is_active', true);

            if (!empty($validated['service_type'])) {
                $query->whereHas('service', function ($q) use ($validated) {
                    $q->where('service_type', $validated['service_type']);
                });
            }

            $sourcePriceLists = $query->get();

            foreach ($sourcePriceLists as $sourcePriceList) {
                $multiplier = 1;
                if ($validated['rate_type'] === 'increase') {
                    $multiplier = 1 + ($adjustmentPercent / 100);
                } elseif ($validated['rate_type'] === 'decrease') {
                    $multiplier = 1 - ($adjustmentPercent / 100);
                }

                CashlessPriceList::updateOrCreate(
                    [
                        'class_id' => $patientClass->class_id,
                        'service_id' => $sourcePriceList->service_id,
                        'effective_from' => $validated['effective_from'],
                    ],
                    [
                        'opd_rate' => round($sourcePriceList->opd_rate * $multiplier, 2),
                        'ipd_rate' => round($sourcePriceList->ipd_rate * $multiplier, 2),
                        'day_emergency_rate' => round($sourcePriceList->day_emergency_rate * $multiplier, 2),
                        'night_emergency_rate' => round($sourcePriceList->night_emergency_rate * $multiplier, 2),
                        'is_approval_required' => $sourcePriceList->is_approval_required,
                        'is_not_applicable' => $sourcePriceList->is_not_applicable,
                        'is_active' => true,
                    ]
                );
            }
        });

        return response()->json([
            'message' => 'Rates copied successfully',
            'class' => $patientClass->load('cashlessPriceLists.service'),
        ]);
    }

    /**
     * Revise rates for a class
     */
    public function reviseRates(Request $request, PatientClass $patientClass)
    {
        $validated = $request->validate([
            'current_effective_to' => 'required|date',
            'new_effective_from' => 'required|date|after:current_effective_to',
            'adjustment_percent' => 'nullable|numeric',
            'adjustment_type' => 'nullable|in:increase,decrease',
        ]);

        DB::transaction(function () use ($patientClass, $validated) {
            // End current rates
            CashlessPriceList::where('class_id', $patientClass->class_id)
                ->whereNull('effective_to')
                ->update(['effective_to' => $validated['current_effective_to']]);

            // Create new rates if adjustment is specified
            if (!empty($validated['adjustment_percent']) && !empty($validated['adjustment_type'])) {
                $currentRates = CashlessPriceList::where('class_id', $patientClass->class_id)
                    ->where('effective_to', $validated['current_effective_to'])
                    ->get();

                $multiplier = $validated['adjustment_type'] === 'increase'
                    ? 1 + ($validated['adjustment_percent'] / 100)
                    : 1 - ($validated['adjustment_percent'] / 100);

                foreach ($currentRates as $rate) {
                    CashlessPriceList::create([
                        'class_id' => $patientClass->class_id,
                        'service_id' => $rate->service_id,
                        'opd_rate' => round($rate->opd_rate * $multiplier, 2),
                        'ipd_rate' => round($rate->ipd_rate * $multiplier, 2),
                        'day_emergency_rate' => round($rate->day_emergency_rate * $multiplier, 2),
                        'night_emergency_rate' => round($rate->night_emergency_rate * $multiplier, 2),
                        'is_approval_required' => $rate->is_approval_required,
                        'is_not_applicable' => $rate->is_not_applicable,
                        'effective_from' => $validated['new_effective_from'],
                        'is_active' => true,
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Rates revised successfully',
            'class' => $patientClass->load('cashlessPriceLists.service'),
        ]);
    }
}
