<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgeGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = AgeGroup::query();

        if ($request->has('search')) {
            $query->where('age_group_caption', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('from_age')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'age_group_caption' => 'required|string|max:100',
            'from_age' => 'required|integer|min:0',
            'to_age' => 'required|integer|min:0|gte:from_age',
            'age_unit' => 'required|in:days,months,years',
            'is_active' => 'boolean',
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $ageGroup = AgeGroup::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Age group created successfully',
            'data' => $ageGroup
        ], 201);
    }

    public function show(AgeGroup $ageGroup)
    {
        return response()->json($ageGroup);
    }

    public function update(Request $request, AgeGroup $ageGroup)
    {
        $validated = $request->validate([
            'age_group_caption' => 'required|string|max:100',
            'from_age' => 'required|integer|min:0',
            'to_age' => 'required|integer|min:0|gte:from_age',
            'age_unit' => 'required|in:days,months,years',
            'is_active' => 'boolean',
        ]);

        $ageGroup->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Age group updated successfully',
            'data' => $ageGroup
        ]);
    }

    public function destroy(AgeGroup $ageGroup)
    {
        $ageGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Age group deleted successfully'
        ]);
    }

    public function active()
    {
        return response()->json(
            AgeGroup::where('is_active', true)
                ->orderBy('from_age')
                ->get(['age_group_id', 'age_group_caption', 'from_age', 'to_age', 'age_unit'])
        );
    }
}
