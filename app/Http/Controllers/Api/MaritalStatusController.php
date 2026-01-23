<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaritalStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaritalStatusController extends Controller
{
    public function index(Request $request)
    {
        $query = MaritalStatus::withCount('patients as usage_count');

        if ($request->has('search')) {
            $query->where('marital_status_name', 'like', "%{$request->search}%");
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('marital_status_name')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'marital_status_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $maritalStatus = MaritalStatus::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Marital status created successfully',
            'data' => $maritalStatus
        ], 201);
    }

    public function show(MaritalStatus $maritalStatus)
    {
        return response()->json($maritalStatus);
    }

    public function update(Request $request, MaritalStatus $maritalStatus)
    {
        $validated = $request->validate([
            'marital_status_name' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $maritalStatus->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Marital status updated successfully',
            'data' => $maritalStatus
        ]);
    }

    public function destroy(MaritalStatus $maritalStatus)
    {
        if ($maritalStatus->patients()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete marital status. It is being used by patients.'
            ], 422);
        }

        $maritalStatus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Marital status deleted successfully'
        ]);
    }

    public function active()
    {
        return response()->json(
            MaritalStatus::where('is_active', true)->orderBy('marital_status_name')->get(['marital_status_id', 'marital_status_name'])
        );
    }
}
