<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HospitalService;
use App\Models\HospitalServicePrice;
use App\Models\Room;
use App\Models\Bed;
use App\Models\CashlessPriceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class HospitalServiceController extends Controller
{
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = HospitalService::where('hospital_id', $hospitalId)
            ->with(['insuranceCompany', 'costHead', 'prices.room', 'prices.bed', 'gstPlan']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('service_name', 'like', "%{$search}%");
        }

        // All services are now PRIVATE (insurance_id = null)
        // No need to filter by insurance_id

        if ($request->has('cost_head_id') && $request->cost_head_id) {
            $query->where('cost_head_id', $request->cost_head_id);
        }

        if ($request->has('active_only') && $request->active_only) {
            $query->where('is_active', true);
        }

        // Date range filter
        if ($request->has('from_date') && $request->from_date) {
            $query->where(function($q) use ($request) {
                $q->whereNull('to_date')
                  ->orWhere('to_date', '>=', $request->from_date);
            });
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->where(function($q) use ($request) {
                $q->whereNull('from_date')
                  ->orWhere('from_date', '<=', $request->to_date);
            });
        }

        $services = $query->orderBy('service_name')->get();

        // Calculate applicable price based on bed/room
        $bedId = $request->bed_id;
        $roomId = $request->room_id;

        if ($bedId || $roomId) {
            $services->each(function ($service) use ($bedId, $roomId) {
                $applicablePrice = $service->base_price;

                // Check for bed-specific price (highest priority)
                if ($bedId) {
                    $bedPrice = $service->prices->where('bed_id', $bedId)->first();
                    if ($bedPrice) {
                        $applicablePrice = $bedPrice->price;
                        $service->price_source = 'bed';
                        $service->applicable_price = $applicablePrice;
                        return;
                    }
                }

                // Check for room-specific price (medium priority)
                if ($roomId) {
                    $roomPrice = $service->prices->where('room_id', $roomId)->whereNull('bed_id')->first();
                    if ($roomPrice) {
                        $applicablePrice = $roomPrice->price;
                        $service->price_source = 'room';
                        $service->applicable_price = $applicablePrice;
                        return;
                    }
                }

                // Use base price (lowest priority)
                $service->price_source = 'base';
                $service->applicable_price = $applicablePrice;
            });
        } else {
            // No bed/room specified, use base price
            $services->each(function ($service) {
                $service->price_source = 'base';
                $service->applicable_price = $service->base_price;
            });
        }

        return response()->json($services);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'insurance_id' => 'nullable|exists:insurance_companies,insurance_id',
            'cost_head_id' => 'required|exists:cost_heads,cost_head_id',
            'service_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'base_price' => 'required|numeric|min:0',
            'day_emergency_rate' => 'nullable|numeric|min:0',
            'night_emergency_rate' => 'nullable|numeric|min:0',
            'price_unit' => 'required|in:per_day,per_service,per_hour',
            'is_active' => 'boolean',
            'is_free_followup' => 'boolean',
            'qty_rate_not_required' => 'boolean',
            'gst_plan_id' => 'nullable|exists:gst_plans,gst_plan_id',
            'gst_above_amount' => 'nullable|numeric|min:0',
            'prices' => 'nullable|array',
            'prices.*.room_id' => 'nullable|exists:rooms,room_id',
            'prices.*.bed_id' => 'nullable|exists:beds,bed_id',
            'prices.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $hospitalService = HospitalService::create([
                'hospital_id' => Auth::user()->hospital_id,
                'insurance_id' => null, // Always null - services are created as PRIVATE
                'cost_head_id' => $request->cost_head_id,
                'service_name' => $request->service_name,
                'description' => $request->description,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'base_price' => $request->base_price,
                'day_emergency_rate' => $request->day_emergency_rate ?? 0,
                'night_emergency_rate' => $request->night_emergency_rate ?? 0,
                'cashless_pricelist' => null,
                'cl_rate' => 0,
                'cl_day_emergency_rate' => 0,
                'cl_night_emergency_rate' => 0,
                'price_unit' => $request->price_unit,
                'is_active' => $request->is_active ?? true,
                'is_free_followup' => $request->is_free_followup ?? false,
                'qty_rate_not_required' => $request->qty_rate_not_required ?? false,
                'gst_plan_id' => $request->gst_plan_id,
                'gst_above_amount' => $request->gst_above_amount,
            ]);

            // Save prices if provided
            if ($request->has('prices') && is_array($request->prices)) {
                foreach ($request->prices as $priceData) {
                    HospitalServicePrice::create([
                        'hospital_service_id' => $hospitalService->hospital_service_id,
                        'room_id' => $priceData['room_id'] ?? null,
                        'bed_id' => $priceData['bed_id'] ?? null,
                        'price' => $priceData['price'],
                    ]);
                }
            }

            DB::commit();

            return response()->json($hospitalService->load(['insuranceCompany', 'costHead', 'prices', 'gstPlan']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create hospital service', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $service = HospitalService::where('hospital_id', $hospitalId)
            ->where('hospital_service_id', $id)
            ->with(['insuranceCompany', 'costHead', 'prices.room', 'prices.bed', 'gstPlan'])
            ->firstOrFail();

        return response()->json($service);
    }

    public function update(Request $request, $id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $hospitalService = HospitalService::where('hospital_id', $hospitalId)
            ->where('hospital_service_id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'insurance_id' => 'nullable|exists:insurance_companies,insurance_id',
            'cost_head_id' => 'required|exists:cost_heads,cost_head_id',
            'service_name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'base_price' => 'required|numeric|min:0',
            'day_emergency_rate' => 'nullable|numeric|min:0',
            'night_emergency_rate' => 'nullable|numeric|min:0',
            'price_unit' => 'required|in:per_day,per_service,per_hour',
            'is_active' => 'boolean',
            'is_free_followup' => 'boolean',
            'qty_rate_not_required' => 'boolean',
            'gst_plan_id' => 'nullable|exists:gst_plans,gst_plan_id',
            'gst_above_amount' => 'nullable|numeric|min:0',
            'prices' => 'nullable|array',
            'prices.*.room_id' => 'nullable|exists:rooms,room_id',
            'prices.*.bed_id' => 'nullable|exists:beds,bed_id',
            'prices.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $hospitalService->update([
                // insurance_id is not updated - stays as null (PRIVATE)
                'cost_head_id' => $request->cost_head_id,
                'service_name' => $request->service_name,
                'description' => $request->description,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'base_price' => $request->base_price,
                'day_emergency_rate' => $request->day_emergency_rate ?? 0,
                'night_emergency_rate' => $request->night_emergency_rate ?? 0,
                // Cashless prices are not updated here - use Cashless PriceList bulk update
                'price_unit' => $request->price_unit,
                'is_active' => $request->is_active ?? true,
                'is_free_followup' => $request->is_free_followup ?? false,
                'qty_rate_not_required' => $request->qty_rate_not_required ?? false,
                'gst_plan_id' => $request->gst_plan_id,
                'gst_above_amount' => $request->gst_above_amount,
            ]);

            // Update prices if provided
            if ($request->has('prices') && is_array($request->prices)) {
                // Delete existing prices
                HospitalServicePrice::where('hospital_service_id', $hospitalService->hospital_service_id)->delete();

                // Create new prices
                foreach ($request->prices as $priceData) {
                    HospitalServicePrice::create([
                        'hospital_service_id' => $hospitalService->hospital_service_id,
                        'room_id' => $priceData['room_id'] ?? null,
                        'bed_id' => $priceData['bed_id'] ?? null,
                        'price' => $priceData['price'],
                    ]);
                }
            }

            DB::commit();

            return response()->json($hospitalService->load(['insuranceCompany', 'costHead', 'prices', 'gstPlan']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update hospital service', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $hospitalService = HospitalService::where('hospital_id', $hospitalId)
            ->where('hospital_service_id', $id)
            ->firstOrFail();

        $hospitalService->delete();

        return response()->json(['message' => 'Hospital service deleted successfully']);
    }

    public function updatePrice(Request $request, $id, $priceId)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $price = HospitalServicePrice::where('price_id', $priceId)
            ->where('hospital_service_id', $id)
            ->firstOrFail();

        $price->update(['price' => $request->price]);

        return response()->json($price);
    }

    public function getRoomsAndBeds(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        // Get rooms through wards (rooms table doesn't have hospital_id)
        $rooms = Room::whereHas('ward', function($q) use ($hospitalId) {
                $q->where('hospital_id', $hospitalId)
                  ->where('is_active', true);
            })
            ->with([
                'ward:ward_id,ward_name,hospital_id,is_active',
                'beds' => function($q) {
                    $q->whereNotNull('room_id')
                      ->orderByRaw('CAST(bed_number AS UNSIGNED)');
                }
            ])
            ->orderBy('room_name')
            ->get();

        return response()->json($rooms);
    }

    public function bulkUpdateCashless(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'updates' => 'required|array',
            'updates.*.hospital_service_id' => 'required|exists:hospital_services,hospital_service_id',
            'updates.*.cashless_pricelist' => 'nullable', // Can be string or integer
            'updates.*.cl_rate' => 'nullable|numeric|min:0',
            'updates.*.cl_day_emergency_rate' => 'nullable|numeric|min:0',
            'updates.*.cl_night_emergency_rate' => 'nullable|numeric|min:0',
            'updates.*.from_date' => 'nullable|date',
            'updates.*.to_date' => 'nullable|date|after_or_equal:updates.*.from_date',
            'updates.*.ward_prices' => 'nullable|array',
            'updates.*.ward_prices.*.ward_id' => 'required|exists:wards,ward_id',
            'updates.*.ward_prices.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hospitalId = Auth::user()->hospital_id;

        DB::beginTransaction();
        try {
            $updatedCount = 0;

            foreach ($request->updates as $update) {
                $service = HospitalService::where('hospital_id', $hospitalId)
                    ->where('hospital_service_id', $update['hospital_service_id'])
                    ->first();

                if ($service) {
                    // Get insurance company name
                    $insuranceCompanyName = null;
                    if (isset($update['cashless_pricelist'])) {
                        if ($update['cashless_pricelist'] === 'private') {
                            $insuranceCompanyName = 'General';
                        } else {
                            $insurance = \App\Models\InsuranceCompany::find($update['cashless_pricelist']);
                            $insuranceCompanyName = $insurance ? $insurance->company_name : null;
                        }
                    }

                    // Save price history before updating
                    CashlessPriceHistory::create([
                        'hospital_service_id' => $service->hospital_service_id,
                        'hospital_id' => $service->hospital_id,
                        'service_name' => $service->service_name,
                        'insurance_company_name' => $insuranceCompanyName,
                        'old_cl_rate' => $service->cl_rate ?? 0,
                        'old_cl_day_emergency_rate' => $service->cl_day_emergency_rate ?? 0,
                        'old_cl_night_emergency_rate' => $service->cl_night_emergency_rate ?? 0,
                        'old_from_date' => $service->from_date,
                        'old_to_date' => $service->to_date,
                        'new_cl_rate' => $update['cl_rate'] ?? 0,
                        'new_cl_day_emergency_rate' => $update['cl_day_emergency_rate'] ?? 0,
                        'new_cl_night_emergency_rate' => $update['cl_night_emergency_rate'] ?? 0,
                        'new_from_date' => $update['from_date'] ?? null,
                        'new_to_date' => $update['to_date'] ?? null,
                        'updated_by' => Auth::id(),
                        'updated_by_name' => Auth::user()->full_name ?? 'Unknown',
                        'updated_at' => now(),
                    ]);

                    // Update cashless pricing
                    $service->update([
                        'cashless_pricelist' => isset($update['cashless_pricelist']) ? (string)$update['cashless_pricelist'] : null,
                        'cl_rate' => $update['cl_rate'] ?? 0,
                        'cl_day_emergency_rate' => $update['cl_day_emergency_rate'] ?? 0,
                        'cl_night_emergency_rate' => $update['cl_night_emergency_rate'] ?? 0,
                        'from_date' => $update['from_date'] ?? null,
                        'to_date' => $update['to_date'] ?? null,
                    ]);

                    // Update ward prices if provided
                    if (isset($update['ward_prices']) && is_array($update['ward_prices'])) {
                        foreach ($update['ward_prices'] as $wardPrice) {
                            $wardId = $wardPrice['ward_id'];
                            $price = $wardPrice['price'];

                            // Get all rooms in this ward
                            $rooms = Room::where('ward_id', $wardId)->get();

                            foreach ($rooms as $room) {
                                // Check if price already exists for this room
                                $existingPrice = HospitalServicePrice::where('hospital_service_id', $service->hospital_service_id)
                                    ->where('room_id', $room->room_id)
                                    ->whereNull('bed_id')
                                    ->first();

                                if ($existingPrice) {
                                    // Update existing price
                                    $existingPrice->update(['price' => $price]);
                                } else {
                                    // Create new price
                                    HospitalServicePrice::create([
                                        'hospital_service_id' => $service->hospital_service_id,
                                        'room_id' => $room->room_id,
                                        'bed_id' => null,
                                        'price' => $price,
                                    ]);
                                }
                            }
                        }
                    }

                    $updatedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cashless prices and ward prices updated successfully',
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update cashless prices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCashlessPriceHistory(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = CashlessPriceHistory::where('hospital_id', $hospitalId);

        // Filter by service name if provided
        if ($request->has('service_name') && $request->service_name) {
            $query->where('service_name', 'like', '%' . $request->service_name . '%');
        }

        // Filter by service ID if provided
        if ($request->has('hospital_service_id') && $request->hospital_service_id) {
            $query->where('hospital_service_id', $request->hospital_service_id);
        }

        // Filter by insurance company name if provided
        if ($request->has('insurance_company_name') && $request->insurance_company_name) {
            $query->where('insurance_company_name', 'like', '%' . $request->insurance_company_name . '%');
        }

        // Filter by date range if provided
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('updated_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('updated_at', '<=', $request->to_date);
        }

        $history = $query->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($history);
    }
}
