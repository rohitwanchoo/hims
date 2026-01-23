<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BloodGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BloodGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = BloodGroup::withCount('patients as usage_count');

        if ($request->has('search')) {
            $query->where('blood_group_name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('blood_group_name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blood_group_name' => 'required|string|max:10',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $bloodGroup = BloodGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Blood group created successfully',
            'data' => $bloodGroup
        ], 201);
    }

    public function show(BloodGroup $bloodGroup)
    {
        return response()->json($bloodGroup);
    }

    public function update(Request $request, BloodGroup $bloodGroup)
    {
        $validated = $request->validate([
            'blood_group_name' => 'required|string|max:10',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $bloodGroup->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Blood group updated successfully',
            'data' => $bloodGroup
        ]);
    }

    public function destroy(BloodGroup $bloodGroup)
    {
        if ($bloodGroup->patients()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete blood group. It is being used by patients.'
            ], 422);
        }

        $bloodGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blood group deleted successfully'
        ]);
    }

    public function active()
    {
        return response()->json(
            BloodGroup::where('is_active', true)->orderBy('blood_group_name')->get(['blood_group_id', 'blood_group_name'])
        );
    }
}
