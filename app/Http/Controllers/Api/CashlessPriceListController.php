<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashlessPriceList;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashlessPriceListController extends Controller
{
    public function index(Request $request)
    {
        $query = CashlessPriceList::with(['patientClass', 'service']);

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->service_id) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->service_type) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('service_type', $request->service_type);
            });
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        // Filter by effective date
        if ($request->effective_date) {
            $query->where(function ($q) use ($request) {
                $q->where('effective_from', '<=', $request->effective_date)
                  ->where(function ($q2) use ($request) {
                      $q2->whereNull('effective_to')
                         ->orWhere('effective_to', '>=', $request->effective_date);
                  });
            });
        }

        $priceLists = $query->orderBy('class_id')->orderBy('service_id')->get();

        return response()->json($priceLists);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,class_id',
            'service_id' => 'required|exists:services,service_id',
            'opd_rate' => 'nullable|numeric|min:0',
            'ipd_rate' => 'nullable|numeric|min:0',
            'day_emergency_rate' => 'nullable|numeric|min:0',
            'night_emergency_rate' => 'nullable|numeric|min:0',
            'is_approval_required' => 'boolean',
            'is_not_applicable' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        $priceList = CashlessPriceList::create($validated);

        return response()->json($priceList->load(['patientClass', 'service']), 201);
    }

    public function show(CashlessPriceList $cashlessPriceList)
    {
        return response()->json($cashlessPriceList->load(['patientClass', 'service']));
    }

    public function update(Request $request, CashlessPriceList $cashlessPriceList)
    {
        $validated = $request->validate([
            'opd_rate' => 'nullable|numeric|min:0',
            'ipd_rate' => 'nullable|numeric|min:0',
            'day_emergency_rate' => 'nullable|numeric|min:0',
            'night_emergency_rate' => 'nullable|numeric|min:0',
            'is_approval_required' => 'boolean',
            'is_not_applicable' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
        ]);

        $cashlessPriceList->update($validated);

        return response()->json($cashlessPriceList->load(['patientClass', 'service']));
    }

    public function destroy(CashlessPriceList $cashlessPriceList)
    {
        $cashlessPriceList->delete();
        return response()->json(['message' => 'Price list entry deleted successfully']);
    }

    /**
     * Bulk update rates from standard tariff
     */
    public function copyFromStandard(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,class_id',
            'rate_type' => 'required|in:normal,increase,decrease',
            'adjustment_percent' => 'nullable|numeric|min:0|max:100',
            'service_type' => 'nullable|string',
            'effective_from' => 'required|date',
        ]);

        $adjustmentPercent = $validated['adjustment_percent'] ?? 0;

        DB::transaction(function () use ($validated, $adjustmentPercent) {
            $query = Service::where('is_active', true);

            if (!empty($validated['service_type'])) {
                $query->where('service_type', $validated['service_type']);
            }

            $services = $query->get();

            foreach ($services as $service) {
                $multiplier = 1;
                if ($validated['rate_type'] === 'increase') {
                    $multiplier = 1 + ($adjustmentPercent / 100);
                } elseif ($validated['rate_type'] === 'decrease') {
                    $multiplier = 1 - ($adjustmentPercent / 100);
                }

                CashlessPriceList::updateOrCreate(
                    [
                        'class_id' => $validated['class_id'],
                        'service_id' => $service->service_id,
                        'effective_from' => $validated['effective_from'],
                    ],
                    [
                        'opd_rate' => round($service->rate * $multiplier, 2),
                        'ipd_rate' => round($service->rate * $multiplier, 2),
                        'day_emergency_rate' => round(($service->day_emergency_rate ?? $service->rate) * $multiplier, 2),
                        'night_emergency_rate' => round(($service->night_emergency_rate ?? $service->rate) * $multiplier, 2),
                        'is_active' => true,
                    ]
                );
            }
        });

        return response()->json(['message' => 'Rates copied from standard tariff successfully']);
    }

    /**
     * Get rate for a specific service and class
     */
    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,class_id',
            'service_id' => 'required|exists:services,service_id',
            'date' => 'nullable|date',
        ]);

        $date = $validated['date'] ?? now()->toDateString();

        $priceList = CashlessPriceList::where('class_id', $validated['class_id'])
            ->where('service_id', $validated['service_id'])
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date);
            })
            ->first();

        if (!$priceList) {
            // Return standard rate if no cashless rate found
            $service = Service::find($validated['service_id']);
            return response()->json([
                'rate_type' => 'standard',
                'opd_rate' => $service->rate,
                'ipd_rate' => $service->rate,
                'day_emergency_rate' => $service->day_emergency_rate ?? $service->rate,
                'night_emergency_rate' => $service->night_emergency_rate ?? $service->rate,
            ]);
        }

        return response()->json([
            'rate_type' => 'cashless',
            'price_list' => $priceList->load(['patientClass', 'service']),
        ]);
    }
}
