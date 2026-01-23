<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrefixController extends Controller
{
    public function index(Request $request)
    {
        $query = Prefix::withCount('patients as usage_count');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('prefix_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $prefixes = $query->orderBy('prefix_name')->get();

        return response()->json($prefixes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;

        $prefix = Prefix::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Prefix created successfully',
            'data' => $prefix
        ], 201);
    }

    public function show(Prefix $prefix)
    {
        return response()->json($prefix);
    }

    public function update(Request $request, Prefix $prefix)
    {
        $validated = $request->validate([
            'prefix_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $prefix->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Prefix updated successfully',
            'data' => $prefix
        ]);
    }

    public function destroy(Prefix $prefix)
    {
        // Check if prefix is being used by any patient
        if ($prefix->patients()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete prefix. It is being used by patients.'
            ], 422);
        }

        $prefix->delete();

        return response()->json([
            'success' => true,
            'message' => 'Prefix deleted successfully'
        ]);
    }

    public function active()
    {
        $prefixes = Prefix::where('is_active', true)
            ->orderBy('prefix_name')
            ->get(['prefix_id', 'prefix_name']);

        return response()->json($prefixes);
    }
}
