<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GstPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GstPlanController extends Controller
{
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = GstPlan::where('hospital_id', $hospitalId);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('plan_name', 'like', "%{$search}%");
        }

        if ($request->has('active_only') && $request->active_only) {
            $query->where('is_active', true);
        }

        $plans = $query->orderBy('gst_percentage')->get();

        return response()->json($plans);
    }

    public function active()
    {
        $hospitalId = Auth::user()->hospital_id;

        $plans = GstPlan::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->orderBy('gst_percentage')
            ->get(['gst_plan_id', 'plan_name', 'gst_percentage']);

        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_name' => 'required|string|max:100',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gstPlan = GstPlan::create([
            'hospital_id' => Auth::user()->hospital_id,
            'plan_name' => $request->plan_name,
            'gst_percentage' => $request->gst_percentage,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($gstPlan, 201);
    }

    public function show($id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $gstPlan = GstPlan::where('hospital_id', $hospitalId)
            ->where('gst_plan_id', $id)
            ->firstOrFail();

        return response()->json($gstPlan);
    }

    public function update(Request $request, $id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $gstPlan = GstPlan::where('hospital_id', $hospitalId)
            ->where('gst_plan_id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'plan_name' => 'required|string|max:100',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $gstPlan->update([
            'plan_name' => $request->plan_name,
            'gst_percentage' => $request->gst_percentage,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($gstPlan);
    }

    public function destroy($id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $gstPlan = GstPlan::where('hospital_id', $hospitalId)
            ->where('gst_plan_id', $id)
            ->firstOrFail();

        $gstPlan->delete();

        return response()->json(['message' => 'GST Plan deleted successfully']);
    }
}
