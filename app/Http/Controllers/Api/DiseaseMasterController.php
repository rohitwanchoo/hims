<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiseaseMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiseaseMasterController extends Controller
{
    /**
     * Display a listing of the disease masters.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DiseaseMaster::where('hospital_id', $hospitalId);

        // Filter by language
        if ($request->has('language') && $request->language) {
            $query->where('language', $request->language);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('disease_name', 'like', '%' . $request->search . '%');
        }

        // Only active records
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $diseases = $query->orderBy('disease_name', 'asc')->get();

        return response()->json($diseases);
    }

    /**
     * Store a newly created disease master.
     */
    public function store(Request $request)
    {
        $request->validate([
            'disease_name' => 'required|string|max:255',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Check for duplicate
        $exists = DiseaseMaster::where('hospital_id', $hospitalId)
            ->where('disease_name', $request->disease_name)
            ->where('language', $request->language)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This disease already exists.'
            ], 422);
        }

        $disease = DiseaseMaster::create([
            'hospital_id' => $hospitalId,
            'disease_name' => $request->disease_name,
            'language' => $request->language,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disease created successfully',
            'data' => $disease
        ], 201);
    }

    /**
     * Display the specified disease master.
     */
    public function show(DiseaseMaster $diseaseMaster)
    {
        // Check if belongs to user's hospital
        if ($diseaseMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($diseaseMaster);
    }

    /**
     * Update the specified disease master.
     */
    public function update(Request $request, DiseaseMaster $diseaseMaster)
    {
        // Check if belongs to user's hospital
        if ($diseaseMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'disease_name' => 'required|string|max:255',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        // Check for duplicate (excluding current record)
        $exists = DiseaseMaster::where('hospital_id', $diseaseMaster->hospital_id)
            ->where('disease_name', $request->disease_name)
            ->where('language', $request->language)
            ->where('disease_id', '!=', $diseaseMaster->disease_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This disease already exists.'
            ], 422);
        }

        $diseaseMaster->update([
            'disease_name' => $request->disease_name,
            'language' => $request->language,
            'is_active' => $request->is_active ?? $diseaseMaster->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Disease updated successfully',
            'data' => $diseaseMaster
        ]);
    }

    /**
     * Remove the specified disease master.
     */
    public function destroy(DiseaseMaster $diseaseMaster)
    {
        // Check if belongs to user's hospital
        if ($diseaseMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $diseaseMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Disease deleted successfully'
        ]);
    }
}
