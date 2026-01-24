<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrugMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DrugMasterController extends Controller
{
    /**
     * Display a listing of drug masters.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DrugMaster::with('drugType')->where('hospital_id', $hospitalId);

        // Filter by drug type
        if ($request->has('drug_type_id') && $request->drug_type_id) {
            $query->where('drug_type_id', $request->drug_type_id);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('drug_name', 'like', '%' . $request->search . '%');
        }

        // Only active records
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $drugs = $query->orderBy('drug_name', 'asc')->get();

        return response()->json($drugs);
    }

    /**
     * Store a newly created drug master.
     */
    public function store(Request $request)
    {
        $request->validate([
            'drug_name' => 'required|string|max:255',
            'drug_type_id' => 'nullable|exists:drug_types,drug_type_id',
            'language' => 'required|in:english,marathi,hindi',
            'dose_time' => 'nullable|string|max:100',
            'days' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $drug = DrugMaster::create([
            'hospital_id' => $hospitalId,
            'drug_type_id' => $request->drug_type_id,
            'drug_name' => $request->drug_name,
            'language' => $request->language,
            'dose_time' => $request->dose_time,
            'days' => $request->days,
            'quantity' => $request->quantity,
            'is_active' => $request->is_active ?? true,
        ]);

        // Load relationship
        $drug->load('drugType');

        return response()->json([
            'success' => true,
            'message' => 'Drug created successfully',
            'data' => $drug
        ], 201);
    }

    /**
     * Display the specified drug master.
     */
    public function show(DrugMaster $drugMaster)
    {
        if ($drugMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $drugMaster->load('drugType');
        return response()->json($drugMaster);
    }

    /**
     * Update the specified drug master.
     */
    public function update(Request $request, DrugMaster $drugMaster)
    {
        if ($drugMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'drug_name' => 'required|string|max:255',
            'drug_type_id' => 'nullable|exists:drug_types,drug_type_id',
            'language' => 'required|in:english,marathi,hindi',
            'dose_time' => 'nullable|string|max:100',
            'days' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $drugMaster->update([
            'drug_type_id' => $request->drug_type_id,
            'drug_name' => $request->drug_name,
            'language' => $request->language,
            'dose_time' => $request->dose_time,
            'days' => $request->days,
            'quantity' => $request->quantity,
            'is_active' => $request->is_active ?? $drugMaster->is_active,
        ]);

        // Load relationship
        $drugMaster->load('drugType');

        return response()->json([
            'success' => true,
            'message' => 'Drug updated successfully',
            'data' => $drugMaster
        ]);
    }

    /**
     * Remove the specified drug master.
     */
    public function destroy(DrugMaster $drugMaster)
    {
        if ($drugMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $drugMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Drug deleted successfully'
        ]);
    }
}
