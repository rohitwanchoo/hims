<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DistrictController extends Controller
{
    public function index(Request $request)
    {
        $query = District::with(['state', 'state.country'])->withCount('cities as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('district_name', 'like', "%{$search}%")
                  ->orWhere('district_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        return $query->orderBy('district_name')->get();
    }

    public function active(Request $request)
    {
        $query = District::where('is_active', true);

        if ($request->has('state_id') && $request->state_id) {
            $query->where('state_id', $request->state_id);
        }

        return $query->orderBy('district_name')
            ->get(['district_id', 'state_id', 'district_name', 'district_code']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,state_id',
            'district_name' => 'required|string|max:100',
            'district_code' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = District::create($validated);

        return response()->json($item->load(['state', 'state.country']), 201);
    }

    public function show(District $district)
    {
        return $district->load(['state', 'state.country']);
    }

    public function update(Request $request, District $district)
    {
        $validated = $request->validate([
            'state_id' => 'required|exists:states,state_id',
            'district_name' => 'required|string|max:100',
            'district_code' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $district->update($validated);

        return response()->json($district->load(['state', 'state.country']));
    }

    public function destroy(District $district)
    {
        $district->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
