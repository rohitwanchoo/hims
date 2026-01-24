<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoseMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoseMasterController extends Controller
{
    /**
     * Display a listing of the dose masters.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DoseMaster::where('hospital_id', $hospitalId);

        // Filter by language
        if ($request->has('language') && $request->language) {
            $query->where('language', $request->language);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('dose_text', 'like', '%' . $request->search . '%');
        }

        // Only active records
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $doses = $query->orderBy('dose_text', 'asc')->get();

        return response()->json($doses);
    }

    /**
     * Store a newly created dose master.
     */
    public function store(Request $request)
    {
        $request->validate([
            'dose_text' => 'required|string|max:1000',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Check for duplicate
        $exists = DoseMaster::where('hospital_id', $hospitalId)
            ->where('dose_text', $request->dose_text)
            ->where('language', $request->language)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This dosage instruction already exists.'
            ], 422);
        }

        $dose = DoseMaster::create([
            'hospital_id' => $hospitalId,
            'dose_text' => $request->dose_text,
            'language' => $request->language,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dosage created successfully',
            'data' => $dose
        ], 201);
    }

    /**
     * Display the specified dose master.
     */
    public function show(DoseMaster $doseMaster)
    {
        // Check if belongs to user's hospital
        if ($doseMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($doseMaster);
    }

    /**
     * Update the specified dose master.
     */
    public function update(Request $request, DoseMaster $doseMaster)
    {
        // Check if belongs to user's hospital
        if ($doseMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'dose_text' => 'required|string|max:1000',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        // Check for duplicate (excluding current record)
        $exists = DoseMaster::where('hospital_id', $doseMaster->hospital_id)
            ->where('dose_text', $request->dose_text)
            ->where('language', $request->language)
            ->where('dose_id', '!=', $doseMaster->dose_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This dosage instruction already exists.'
            ], 422);
        }

        $doseMaster->update([
            'dose_text' => $request->dose_text,
            'language' => $request->language,
            'is_active' => $request->is_active ?? $doseMaster->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dosage updated successfully',
            'data' => $doseMaster
        ]);
    }

    /**
     * Remove the specified dose master.
     */
    public function destroy(DoseMaster $doseMaster)
    {
        // Check if belongs to user's hospital
        if ($doseMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doseMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dosage deleted successfully'
        ]);
    }
}
