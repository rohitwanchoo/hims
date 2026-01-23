<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $query = City::with(['district', 'district.state', 'district.state.country'])
            ->withCount('areas as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('city_name', 'like', "%{$search}%")
                  ->orWhere('city_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('district_id') && $request->district_id) {
            $query->where('district_id', $request->district_id);
        }

        return $query->orderBy('city_name')->get();
    }

    public function active(Request $request)
    {
        $query = City::where('is_active', true);

        if ($request->has('district_id') && $request->district_id) {
            $query->where('district_id', $request->district_id);
        }

        return $query->orderBy('city_name')
            ->get(['city_id', 'district_id', 'city_name', 'city_code']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:districts,district_id',
            'city_name' => 'required|string|max:100',
            'city_code' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = City::create($validated);

        return response()->json($item->load(['district', 'district.state', 'district.state.country']), 201);
    }

    public function show(City $city)
    {
        return $city->load(['district', 'district.state', 'district.state.country']);
    }

    public function update(Request $request, City $city)
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:districts,district_id',
            'city_name' => 'required|string|max:100',
            'city_code' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $city->update($validated);

        return response()->json($city->load(['district', 'district.state', 'district.state.country']));
    }

    public function destroy(City $city)
    {
        $city->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
