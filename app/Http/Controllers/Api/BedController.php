<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use Illuminate\Http\Request;

class BedController extends Controller
{
    public function index(Request $request)
    {
        $query = Bed::with('ward');

        if ($request->ward_id) {
            $query->where('ward_id', $request->ward_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bed_number' => 'required',
            'ward_id' => 'required|exists:wards,ward_id',
            'bed_type' => 'nullable|string',
            'charges_per_day' => 'nullable|numeric|min:0',
        ]);

        $exists = Bed::where('ward_id', $validated['ward_id'])
                     ->where('bed_number', $validated['bed_number'])
                     ->exists();
        if ($exists) {
            return response()->json(['message' => 'Bed number already exists in this ward'], 422);
        }

        $bed = Bed::create($validated);
        return response()->json($bed, 201);
    }

    public function show(string $id)
    {
        $bed = Bed::with('ward')->findOrFail($id);
        return response()->json($bed);
    }

    public function update(Request $request, string $id)
    {
        $bed = Bed::findOrFail($id);

        $validated = $request->validate([
            'bed_number' => 'required',
            'bed_type' => 'nullable|string',
            'charges_per_day' => 'nullable|numeric|min:0',
            'status' => 'in:available,occupied,maintenance',
            'is_active' => 'boolean',
        ]);

        $bed->update($validated);
        return response()->json($bed);
    }

    public function destroy(string $id)
    {
        $bed = Bed::findOrFail($id);

        if ($bed->status === 'occupied') {
            return response()->json(['message' => 'Cannot delete occupied bed'], 400);
        }

        $bed->update(['is_active' => false]);
        return response()->json(['message' => 'Bed deactivated']);
    }
}
