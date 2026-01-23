<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GenderController extends Controller
{
    public function index(Request $request)
    {
        $query = Gender::withCount('patients as usage_count');

        if ($request->has('search')) {
            $query->where('gender_name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('gender_name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'gender_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $gender = Gender::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Gender created successfully',
            'data' => $gender
        ], 201);
    }

    public function show(Gender $gender)
    {
        return response()->json($gender);
    }

    public function update(Request $request, Gender $gender)
    {
        $validated = $request->validate([
            'gender_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $gender->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Gender updated successfully',
            'data' => $gender
        ]);
    }

    public function destroy(Gender $gender)
    {
        if ($gender->patients()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete gender. It is being used by patients.'
            ], 422);
        }

        $gender->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gender deleted successfully'
        ]);
    }

    public function active()
    {
        return response()->json(
            Gender::where('is_active', true)->orderBy('gender_name')->get(['gender_id', 'gender_name'])
        );
    }
}
