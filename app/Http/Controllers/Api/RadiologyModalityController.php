<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RadiologyModality;
use Illuminate\Http\Request;

class RadiologyModalityController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyModality::withCount('tests');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $modalities = $query->orderBy('modality_name')->get();

        return response()->json(['modalities' => $modalities]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'modality_code' => 'required|string|max:20',
            'modality_name' => 'required|string|max:100',
            'modality_type' => 'nullable|in:xray,ct,mri,ultrasound,mammography,fluoroscopy,nuclear,pet,other',
            'room_number' => 'nullable|string|max:20',
            'is_contrast_available' => 'nullable|boolean',
            'default_tat_hours' => 'nullable|integer|min:1',
            'charges_per_hour' => 'nullable|numeric|min:0',
        ]);

        $modality = RadiologyModality::create($validated);

        return response()->json([
            'message' => 'Modality created successfully',
            'modality' => $modality,
        ], 201);
    }

    public function show(RadiologyModality $modality)
    {
        return response()->json([
            'modality' => $modality->load('tests'),
        ]);
    }

    public function update(Request $request, RadiologyModality $modality)
    {
        $validated = $request->validate([
            'modality_name' => 'sometimes|string|max:100',
            'modality_type' => 'nullable|in:xray,ct,mri,ultrasound,mammography,fluoroscopy,nuclear,pet,other',
            'room_number' => 'nullable|string|max:20',
            'is_contrast_available' => 'nullable|boolean',
            'default_tat_hours' => 'nullable|integer|min:1',
            'charges_per_hour' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $modality->update($validated);

        return response()->json([
            'message' => 'Modality updated successfully',
            'modality' => $modality,
        ]);
    }

    public function destroy(RadiologyModality $modality)
    {
        if ($modality->tests()->exists()) {
            return response()->json([
                'message' => 'Cannot delete modality with associated tests',
            ], 422);
        }

        $modality->delete();

        return response()->json([
            'message' => 'Modality deleted successfully',
        ]);
    }
}
