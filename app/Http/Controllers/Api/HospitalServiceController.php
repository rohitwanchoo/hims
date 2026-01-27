<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HospitalService;
use App\Models\HospitalServicePrice;
use App\Models\Room;
use App\Models\Bed;
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

        if ($request->has('insurance_id') && $request->insurance_id) {
            if ($request->insurance_id === 'private') {
                $query->whereNull('insurance_id');
            } else {
                $query->where('insurance_id', $request->insurance_id);
            }
        }

        if ($request->has('cost_head_id') && $request->cost_head_id) {
            $query->where('cost_head_id', $request->cost_head_id);
        }

        if ($request->has('active_only') && $request->active_only) {
            $query->where('is_active', true);
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
                'insurance_id' => $request->insurance_id,
                'cost_head_id' => $request->cost_head_id,
                'service_name' => $request->service_name,
                'description' => $request->description,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'base_price' => $request->base_price,
                'day_emergency_rate' => $request->day_emergency_rate ?? 0,
                'night_emergency_rate' => $request->night_emergency_rate ?? 0,
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
                'insurance_id' => $request->insurance_id,
                'cost_head_id' => $request->cost_head_id,
                'service_name' => $request->service_name,
                'description' => $request->description,
                'from_date' => $request->from_date,
                'to_date' => $request->to_date,
                'base_price' => $request->base_price,
                'day_emergency_rate' => $request->day_emergency_rate ?? 0,
                'night_emergency_rate' => $request->night_emergency_rate ?? 0,
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
}
