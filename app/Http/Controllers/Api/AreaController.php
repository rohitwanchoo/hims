<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $query = Area::with(['city', 'city.district', 'city.district.state', 'city.district.state.country'])
            ->withCount('patients as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('area_name', 'like', "%{$search}%")
                  ->orWhere('pincode', 'like', "%{$search}%");
            });
        }

        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        return $query->orderBy('area_name')->get();
    }

    public function active(Request $request)
    {
        $query = Area::where('is_active', true);

        if ($request->has('city_id') && $request->city_id) {
            $query->where('city_id', $request->city_id);
        }

        return $query->orderBy('area_name')
            ->get(['area_id', 'city_id', 'area_name', 'pincode']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:cities,city_id',
            'area_name' => 'required|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = Area::create($validated);

        return response()->json($item->load(['city', 'city.district', 'city.district.state', 'city.district.state.country']), 201);
    }

    public function show(Area $area)
    {
        return $area->load(['city', 'city.district', 'city.district.state', 'city.district.state.country']);
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:cities,city_id',
            'area_name' => 'required|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'is_active' => 'boolean'
        ]);

        $area->update($validated);

        return response()->json($area->load(['city', 'city.district', 'city.district.state', 'city.district.state.country']));
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // Get full hierarchy for an area (for auto-populate feature)
    public function getHierarchy(Area $area)
    {
        return response()->json($area->getFullHierarchy());
    }

    // Search areas with full hierarchy (for typeahead/autocomplete)
    public function searchWithHierarchy(Request $request)
    {
        $search = $request->get('q', '');

        $areas = Area::where('is_active', true)
            ->where(function($q) use ($search) {
                $q->where('area_name', 'like', "%{$search}%")
                  ->orWhere('pincode', 'like', "%{$search}%");
            })
            ->with(['city', 'city.district', 'city.district.state', 'city.district.state.country'])
            ->limit(20)
            ->get();

        return $areas->map(function($area) {
            return $area->getFullHierarchy();
        });
    }

    // Get areas filtered by state (for cascading dropdowns)
    public function getByState(Request $request)
    {
        $stateId = $request->get('state_id');

        if (!$stateId) {
            return response()->json([]);
        }

        $areas = Area::where('is_active', true)
            ->whereHas('city.district', function($q) use ($stateId) {
                $q->where('state_id', $stateId);
            })
            ->with(['city', 'city.district'])
            ->orderBy('area_name')
            ->get(['area_id', 'city_id', 'area_name', 'pincode']);

        return response()->json($areas);
    }

    // Get areas filtered by country (for broader filtering)
    public function getByCountry(Request $request)
    {
        $countryId = $request->get('country_id');

        if (!$countryId) {
            return response()->json([]);
        }

        $areas = Area::where('is_active', true)
            ->whereHas('city.district.state', function($q) use ($countryId) {
                $q->where('country_id', $countryId);
            })
            ->with(['city', 'city.district', 'city.district.state'])
            ->orderBy('area_name')
            ->get(['area_id', 'city_id', 'area_name', 'pincode']);

        return response()->json($areas);
    }
}
