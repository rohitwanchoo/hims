<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\CashlessPriceList;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with('department');

        // Search by name or code
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('service_name', 'like', "%{$search}%")
                  ->orWhere('service_code', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by service type
        if ($request->service_type) {
            $query->where('service_type', $request->service_type);
        }

        // Filter by service used for (opd/ipd/direct/all)
        if ($request->service_used_for) {
            $query->where(function ($q) use ($request) {
                $q->where('service_used_for', $request->service_used_for)
                  ->orWhere('service_used_for', 'all');
            });
        }

        // Filter by health checkup services
        if ($request->is_health_checkup !== null) {
            $query->where('is_health_checkup', $request->is_health_checkup);
        }

        // Filter by special services
        if ($request->is_special_service !== null) {
            $query->where('is_special_service', $request->is_special_service);
        }

        // Filter by followup services
        if ($request->is_followup_service !== null) {
            $query->where('is_followup_service', $request->is_followup_service);
        }

        // Filter by premium services
        if ($request->is_premium_service !== null) {
            $query->where('is_premium_service', $request->is_premium_service);
        }

        // Filter by active status
        if ($request->active_only !== false) {
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

        $services = $query->orderBy('service_name')->get();

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Basic Information
            'service_code' => 'required|string|max:20|unique:services',
            'service_name' => 'required|string|max:100',
            'ledger_name' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,department_id',

            // Service Type & Classification
            'service_type' => 'nullable|in:opd,ipd,lab,radiology,procedure,other',
            'sub_service_type' => 'nullable|string|max:100',
            'service_used_for' => 'nullable|in:opd,ipd,direct,all',

            // Service Flags
            'is_health_checkup' => 'boolean',
            'apply_service_charges' => 'boolean',
            'use_for_service_bill' => 'boolean',
            'is_special_service' => 'boolean',
            'is_followup_service' => 'boolean',
            'is_free_followup' => 'boolean',
            'is_premium_service' => 'boolean',
            'use_for_srn' => 'boolean',

            // Tax Information
            'service_tax_applicable' => 'boolean',
            'service_tax_plan' => 'nullable|string|max:50',

            // Rates
            'rate' => 'required|numeric|min:0',
            'day_emergency_rate' => 'nullable|numeric|min:0',
            'night_emergency_rate' => 'nullable|numeric|min:0',

            // Effective Dates
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);

        // Set default values
        $validated['service_used_for'] = $validated['service_used_for'] ?? 'all';
        $validated['use_for_service_bill'] = $validated['use_for_service_bill'] ?? true;

        // Set emergency rates to base rate if not provided
        if (empty($validated['day_emergency_rate'])) {
            $validated['day_emergency_rate'] = $validated['rate'];
        }
        if (empty($validated['night_emergency_rate'])) {
            $validated['night_emergency_rate'] = $validated['rate'] * 1.5; // 50% higher for night
        }

        $service = Service::create($validated);

        return response()->json($service->load('department'), 201);
    }

    public function show(string $id)
    {
        $service = Service::with(['department', 'cashlessPriceLists.patientClass'])
            ->findOrFail($id);

        // Get price list history
        $priceListHistory = CashlessPriceList::where('service_id', $id)
            ->with('patientClass')
            ->orderBy('effective_from', 'desc')
            ->get()
            ->groupBy('class_id');

        return response()->json([
            'service' => $service,
            'price_list_by_class' => $priceListHistory,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            // Basic Information
            'service_code' => 'sometimes|required|string|max:20|unique:services,service_code,' . $id . ',service_id',
            'service_name' => 'sometimes|required|string|max:100',
            'ledger_name' => 'nullable|string|max:100',
            'department_id' => 'nullable|exists:departments,department_id',

            // Service Type & Classification
            'service_type' => 'nullable|in:opd,ipd,lab,radiology,procedure,other',
            'sub_service_type' => 'nullable|string|max:100',
            'service_used_for' => 'nullable|in:opd,ipd,direct,all',

            // Service Flags
            'is_health_checkup' => 'boolean',
            'apply_service_charges' => 'boolean',
            'use_for_service_bill' => 'boolean',
            'is_special_service' => 'boolean',
            'is_followup_service' => 'boolean',
            'is_free_followup' => 'boolean',
            'is_premium_service' => 'boolean',
            'use_for_srn' => 'boolean',

            // Tax Information
            'service_tax_applicable' => 'boolean',
            'service_tax_plan' => 'nullable|string|max:50',

            // Rates
            'rate' => 'sometimes|required|numeric|min:0',
            'day_emergency_rate' => 'nullable|numeric|min:0',
            'night_emergency_rate' => 'nullable|numeric|min:0',

            // Effective Dates
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',

            // Status
            'is_active' => 'boolean',
        ]);

        $service->update($validated);

        return response()->json($service->load('department'));
    }

    public function destroy(string $id)
    {
        $service = Service::findOrFail($id);
        $service->update(['is_active' => false]);
        return response()->json(['message' => 'Service deactivated']);
    }

    /**
     * Revise rate for a service with effective date
     */
    public function reviseRate(Request $request, string $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'current_effective_to' => 'required|date',
            'new_rate' => 'required|numeric|min:0',
            'new_day_emergency_rate' => 'nullable|numeric|min:0',
            'new_night_emergency_rate' => 'nullable|numeric|min:0',
            'new_effective_from' => 'required|date|after:current_effective_to',
        ]);

        // Update current rate's effective_to
        $service->update(['effective_to' => $validated['current_effective_to']]);

        // Create new service entry with revised rate
        $newService = $service->replicate();
        $newService->rate = $validated['new_rate'];
        $newService->day_emergency_rate = $validated['new_day_emergency_rate'] ?? $validated['new_rate'];
        $newService->night_emergency_rate = $validated['new_night_emergency_rate'] ?? ($validated['new_rate'] * 1.5);
        $newService->effective_from = $validated['new_effective_from'];
        $newService->effective_to = null;
        $newService->save();

        return response()->json([
            'message' => 'Rate revised successfully',
            'old_service' => $service,
            'new_service' => $newService,
        ]);
    }

    /**
     * Get service rate for a specific class and date
     */
    public function getRate(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,service_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'date' => 'nullable|date',
            'rate_type' => 'nullable|in:opd,ipd,day_emergency,night_emergency',
        ]);

        $date = $validated['date'] ?? now()->toDateString();
        $rateType = $validated['rate_type'] ?? 'opd';

        $service = Service::findOrFail($validated['service_id']);

        // If class_id is provided, check for cashless price
        if (!empty($validated['class_id'])) {
            $cashlessPrice = CashlessPriceList::where('class_id', $validated['class_id'])
                ->where('service_id', $validated['service_id'])
                ->where('is_active', true)
                ->where('is_not_applicable', false)
                ->where('effective_from', '<=', $date)
                ->where(function ($q) use ($date) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $date);
                })
                ->first();

            if ($cashlessPrice) {
                $rate = match($rateType) {
                    'ipd' => $cashlessPrice->ipd_rate,
                    'day_emergency' => $cashlessPrice->day_emergency_rate,
                    'night_emergency' => $cashlessPrice->night_emergency_rate,
                    default => $cashlessPrice->opd_rate,
                };

                return response()->json([
                    'rate_source' => 'cashless_price_list',
                    'rate' => $rate,
                    'is_approval_required' => $cashlessPrice->is_approval_required,
                    'service' => $service,
                    'cashless_price' => $cashlessPrice,
                ]);
            }
        }

        // Return standard rate
        $rate = match($rateType) {
            'day_emergency' => $service->day_emergency_rate ?? $service->rate,
            'night_emergency' => $service->night_emergency_rate ?? $service->rate,
            default => $service->rate,
        };

        return response()->json([
            'rate_source' => 'standard',
            'rate' => $rate,
            'is_approval_required' => false,
            'service' => $service,
        ]);
    }

    /**
     * Get all services with their cashless rates for a specific class
     */
    public function getClassRates(Request $request, string $classId)
    {
        $date = $request->date ?? now()->toDateString();

        $services = Service::with('department')
            ->where('is_active', true)
            ->get()
            ->map(function ($service) use ($classId, $date) {
                $cashlessPrice = CashlessPriceList::where('class_id', $classId)
                    ->where('service_id', $service->service_id)
                    ->where('is_active', true)
                    ->where('effective_from', '<=', $date)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', $date);
                    })
                    ->first();

                return [
                    'service' => $service,
                    'standard_rate' => $service->rate,
                    'cashless_rate' => $cashlessPrice ? [
                        'opd_rate' => $cashlessPrice->opd_rate,
                        'ipd_rate' => $cashlessPrice->ipd_rate,
                        'day_emergency_rate' => $cashlessPrice->day_emergency_rate,
                        'night_emergency_rate' => $cashlessPrice->night_emergency_rate,
                        'is_approval_required' => $cashlessPrice->is_approval_required,
                        'is_not_applicable' => $cashlessPrice->is_not_applicable,
                    ] : null,
                ];
            });

        return response()->json($services);
    }

    /**
     * Get services by type
     */
    public function byType(string $type)
    {
        $services = Service::with('department')
            ->where('service_type', $type)
            ->where('is_active', true)
            ->orderBy('service_name')
            ->get();

        return response()->json($services);
    }

    /**
     * Get followup services
     */
    public function followupServices()
    {
        $services = Service::with('department')
            ->where('is_followup_service', true)
            ->where('is_active', true)
            ->orderBy('service_name')
            ->get();

        return response()->json($services);
    }

    /**
     * Get health checkup services
     */
    public function healthCheckupServices()
    {
        $services = Service::with('department')
            ->where('is_health_checkup', true)
            ->where('is_active', true)
            ->orderBy('service_name')
            ->get();

        return response()->json($services);
    }
}
