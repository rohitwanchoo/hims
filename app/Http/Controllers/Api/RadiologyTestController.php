<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RadiologyTest;
use Illuminate\Http\Request;

class RadiologyTestController extends Controller
{
    public function index(Request $request)
    {
        $query = RadiologyTest::with('modality');

        if ($request->modality_id) {
            $query->where('modality_id', $request->modality_id);
        }

        if ($request->body_part) {
            $query->where('body_part', $request->body_part);
        }

        if ($request->has('contrast_required')) {
            $query->where('contrast_required', $request->boolean('contrast_required'));
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('test_name', 'like', "%{$search}%")
                    ->orWhere('test_code', 'like', "%{$search}%");
            });
        }

        $tests = $query->orderBy('test_name')
            ->paginate($request->per_page ?? 50);

        return response()->json($tests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'modality_id' => 'required|exists:radiology_modalities,modality_id',
            'test_code' => 'required|string|max:20',
            'test_name' => 'required|string|max:200',
            'body_part' => 'nullable|string|max:100',
            'laterality' => 'nullable|in:left,right,bilateral,na',
            'cpt_code' => 'nullable|string|max:20',
            'rate' => 'nullable|numeric|min:0',
            'contrast_required' => 'nullable|boolean',
            'consent_required' => 'nullable|boolean',
            'preparation_instructions' => 'nullable|string',
            'estimated_duration_mins' => 'nullable|integer|min:1',
        ]);

        $test = RadiologyTest::create($validated);

        return response()->json([
            'message' => 'Test created successfully',
            'test' => $test->load('modality'),
        ], 201);
    }

    public function show(RadiologyTest $test)
    {
        return response()->json([
            'test' => $test->load('modality'),
        ]);
    }

    public function update(Request $request, RadiologyTest $test)
    {
        $validated = $request->validate([
            'modality_id' => 'sometimes|exists:radiology_modalities,modality_id',
            'test_name' => 'sometimes|string|max:200',
            'body_part' => 'nullable|string|max:100',
            'laterality' => 'nullable|in:left,right,bilateral,na',
            'cpt_code' => 'nullable|string|max:20',
            'rate' => 'nullable|numeric|min:0',
            'contrast_required' => 'nullable|boolean',
            'consent_required' => 'nullable|boolean',
            'preparation_instructions' => 'nullable|string',
            'estimated_duration_mins' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        $test->update($validated);

        return response()->json([
            'message' => 'Test updated successfully',
            'test' => $test->load('modality'),
        ]);
    }

    public function destroy(RadiologyTest $test)
    {
        if ($test->orderDetails()->exists()) {
            return response()->json([
                'message' => 'Cannot delete test with existing orders',
            ], 422);
        }

        $test->delete();

        return response()->json([
            'message' => 'Test deleted successfully',
        ]);
    }
}
