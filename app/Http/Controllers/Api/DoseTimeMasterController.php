<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoseTimeMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoseTimeMasterController extends Controller
{
    /**
     * Display a listing of the dose time masters.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DoseTimeMaster::where('hospital_id', $hospitalId);

        // Filter by language
        if ($request->has('language') && $request->language) {
            $query->where('language', $request->language);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('dose_time_text', 'like', '%' . $request->search . '%');
        }

        // Only active records
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $doseTimes = $query->orderBy('dose_time_text', 'asc')->get();

        return response()->json($doseTimes);
    }

    /**
     * Store a newly created dose time master.
     */
    public function store(Request $request)
    {
        $request->validate([
            'dose_time_text' => 'required|string|max:1000',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Check for duplicate
        $exists = DoseTimeMaster::where('hospital_id', $hospitalId)
            ->where('dose_time_text', $request->dose_time_text)
            ->where('language', $request->language)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This dose time instruction already exists.'
            ], 422);
        }

        $doseTime = DoseTimeMaster::create([
            'hospital_id' => $hospitalId,
            'dose_time_text' => $request->dose_time_text,
            'language' => $request->language,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dose time created successfully',
            'data' => $doseTime
        ], 201);
    }

    /**
     * Display the specified dose time master.
     */
    public function show(DoseTimeMaster $doseTimeMaster)
    {
        // Check if belongs to user's hospital
        if ($doseTimeMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($doseTimeMaster);
    }

    /**
     * Update the specified dose time master.
     */
    public function update(Request $request, DoseTimeMaster $doseTimeMaster)
    {
        // Check if belongs to user's hospital
        if ($doseTimeMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'dose_time_text' => 'required|string|max:1000',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        // Check for duplicate (excluding current record)
        $exists = DoseTimeMaster::where('hospital_id', $doseTimeMaster->hospital_id)
            ->where('dose_time_text', $request->dose_time_text)
            ->where('language', $request->language)
            ->where('dose_time_id', '!=', $doseTimeMaster->dose_time_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This dose time instruction already exists.'
            ], 422);
        }

        $doseTimeMaster->update([
            'dose_time_text' => $request->dose_time_text,
            'language' => $request->language,
            'is_active' => $request->is_active ?? $doseTimeMaster->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dose time updated successfully',
            'data' => $doseTimeMaster
        ]);
    }

    /**
     * Remove the specified dose time master.
     */
    public function destroy(DoseTimeMaster $doseTimeMaster)
    {
        // Check if belongs to user's hospital
        if ($doseTimeMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $doseTimeMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dose time deleted successfully'
        ]);
    }
}
