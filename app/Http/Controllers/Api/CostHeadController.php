<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CostHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CostHeadController extends Controller
{
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = CostHead::where('hospital_id', $hospitalId);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('cost_head_name', 'like', "%{$search}%")
                  ->orWhere('cost_head_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('cost_head_type', $request->type);
        }

        if ($request->has('active_only') && $request->active_only) {
            $query->where('is_active', true);
        }

        $costHeads = $query->orderBy('cost_head_name')->get();

        return response()->json($costHeads);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cost_head_code' => 'required|string|max:50|unique:cost_heads,cost_head_code',
            'cost_head_name' => 'required|string|max:200',
            'cost_head_type' => 'required|in:ipd_services,opd_services,lab_services,pharmacy,radiology,procedure,others',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $costHead = CostHead::create([
            'hospital_id' => Auth::user()->hospital_id,
            'cost_head_code' => $request->cost_head_code,
            'cost_head_name' => $request->cost_head_name,
            'cost_head_type' => $request->cost_head_type,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json($costHead, 201);
    }

    public function show($id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $costHead = CostHead::where('hospital_id', $hospitalId)
            ->where('cost_head_id', $id)
            ->firstOrFail();

        return response()->json($costHead);
    }

    public function update(Request $request, $id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $costHead = CostHead::where('hospital_id', $hospitalId)
            ->where('cost_head_id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'cost_head_code' => 'required|string|max:50|unique:cost_heads,cost_head_code,' . $id . ',cost_head_id',
            'cost_head_name' => 'required|string|max:200',
            'cost_head_type' => 'required|in:ipd_services,opd_services,lab_services,pharmacy,radiology,procedure,others',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $costHead->update($request->all());

        return response()->json($costHead);
    }

    public function destroy($id)
    {
        $hospitalId = Auth::user()->hospital_id;
        $costHead = CostHead::where('hospital_id', $hospitalId)
            ->where('cost_head_id', $id)
            ->firstOrFail();

        $costHead->delete();

        return response()->json(['message' => 'Cost head deleted successfully']);
    }
}
