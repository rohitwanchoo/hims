<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrugType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DrugTypeController extends Controller
{
    /**
     * Display a listing of drug types.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DrugType::where('hospital_id', $hospitalId);

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('type_name', 'like', '%' . $request->search . '%');
        }

        // Only active records
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $drugTypes = $query->orderBy('type_name', 'asc')->get();

        return response()->json($drugTypes);
    }

    /**
     * Store a newly created drug type.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string|max:100',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Check for duplicate
        $exists = DrugType::where('hospital_id', $hospitalId)
            ->where('type_name', $request->type_name)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This drug type already exists.'
            ], 422);
        }

        $drugType = DrugType::create([
            'hospital_id' => $hospitalId,
            'type_name' => $request->type_name,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Drug type created successfully',
            'data' => $drugType
        ], 201);
    }

    /**
     * Display the specified drug type.
     */
    public function show(DrugType $drugType)
    {
        if ($drugType->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($drugType);
    }

    /**
     * Update the specified drug type.
     */
    public function update(Request $request, DrugType $drugType)
    {
        if ($drugType->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'type_name' => 'required|string|max:100',
        ]);

        // Check for duplicate
        $exists = DrugType::where('hospital_id', $drugType->hospital_id)
            ->where('type_name', $request->type_name)
            ->where('drug_type_id', '!=', $drugType->drug_type_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This drug type already exists.'
            ], 422);
        }

        $drugType->update([
            'type_name' => $request->type_name,
            'is_active' => $request->is_active ?? $drugType->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Drug type updated successfully',
            'data' => $drugType
        ]);
    }

    /**
     * Remove the specified drug type.
     */
    public function destroy(DrugType $drugType)
    {
        if ($drugType->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if drug type is being used
        if ($drugType->drugs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete drug type. It is being used by drugs.'
            ], 422);
        }

        $drugType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Drug type deleted successfully'
        ]);
    }
}
