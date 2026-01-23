<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $query = Country::withCount('states as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('country_name', 'like', "%{$search}%")
                  ->orWhere('country_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('country_name')->get();
    }

    public function active()
    {
        return Country::where('is_active', true)
            ->orderBy('country_name')
            ->get(['country_id', 'country_name', 'country_code', 'phone_code', 'is_default']);
    }

    public function getDefault()
    {
        return Country::where('is_default', true)->where('is_active', true)->first();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_name' => 'required|string|max:100',
            'country_code' => 'nullable|string|max:10',
            'phone_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // If setting as default, remove default from others
        if (!empty($validated['is_default']) && $validated['is_default']) {
            Country::where('is_default', true)->update(['is_default' => false]);
        }

        $item = Country::create($validated);

        return response()->json($item, 201);
    }

    public function show(Country $country)
    {
        return $country;
    }

    public function update(Request $request, Country $country)
    {
        $validated = $request->validate([
            'country_name' => 'required|string|max:100',
            'country_code' => 'nullable|string|max:10',
            'phone_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        // If setting as default, remove default from others
        if (!empty($validated['is_default']) && $validated['is_default']) {
            Country::where('is_default', true)
                ->where('country_id', '!=', $country->country_id)
                ->update(['is_default' => false]);
        }

        $country->update($validated);

        return response()->json($country);
    }

    public function destroy(Country $country)
    {
        $country->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
