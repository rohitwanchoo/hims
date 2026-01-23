<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StateController extends Controller
{
    public function index(Request $request)
    {
        $query = State::with('country')->withCount('districts as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('state_name', 'like', "%{$search}%")
                  ->orWhere('state_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('country_id') && $request->country_id) {
            $query->where('country_id', $request->country_id);
        }

        return $query->orderBy('state_name')->get();
    }

    public function active(Request $request)
    {
        $query = State::where('is_active', true)->with('country');

        if ($request->has('country_id') && $request->country_id) {
            $query->where('country_id', $request->country_id);
        }

        return $query->orderBy('state_name')
            ->get(['state_id', 'country_id', 'state_name', 'state_code', 'is_default']);
    }

    public function getDefault()
    {
        return State::where('is_default', true)->where('is_active', true)->with('country')->first();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,country_id',
            'state_name' => 'required|string|max:100',
            'state_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        // If setting as default, remove default from others
        if (!empty($validated['is_default']) && $validated['is_default']) {
            State::where('is_default', true)->update(['is_default' => false]);
        }

        $item = State::create($validated);

        return response()->json($item->load('country'), 201);
    }

    public function show(State $state)
    {
        return $state->load('country');
    }

    public function update(Request $request, State $state)
    {
        $validated = $request->validate([
            'country_id' => 'required|exists:countries,country_id',
            'state_name' => 'required|string|max:100',
            'state_code' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'is_default' => 'boolean'
        ]);

        // If setting as default, remove default from others
        if (!empty($validated['is_default']) && $validated['is_default']) {
            State::where('is_default', true)
                ->where('state_id', '!=', $state->state_id)
                ->update(['is_default' => false]);
        }

        $state->update($validated);

        return response()->json($state->load('country'));
    }

    public function destroy(State $state)
    {
        $state->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // Get state with full hierarchy (for auto-populate)
    public function getWithHierarchy(State $state)
    {
        return response()->json([
            'state_id' => $state->state_id,
            'state_name' => $state->state_name,
            'country_id' => $state->country_id,
            'country_name' => $state->country ? $state->country->country_name : null
        ]);
    }
}
