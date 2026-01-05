<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkillSet;
use App\Models\SkillSetVisitValidity;
use Illuminate\Http\Request;

class SkillSetController extends Controller
{
    /**
     * Display a listing of skill sets
     */
    public function index(Request $request)
    {
        $query = SkillSet::with('visitValidity');

        if ($request->search) {
            $query->where('skill_name', 'like', "%{$request->search}%");
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $skillSets = $query->orderBy('skill_name')->get();

        return response()->json($skillSets);
    }

    /**
     * Store a newly created skill set
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'skill_code' => 'nullable|string|max:20',
            'skill_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            // Visit validity
            'followup_validity_days' => 'nullable|integer|min:1',
            'free_followup_validity_days' => 'nullable|integer|min:0',
        ]);

        $skillSet = SkillSet::create([
            'skill_code' => $validated['skill_code'] ?? null,
            'skill_name' => $validated['skill_name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Create validity if provided
        if (!empty($validated['followup_validity_days'])) {
            SkillSetVisitValidity::create([
                'skill_set_id' => $skillSet->skill_set_id,
                'followup_validity_days' => $validated['followup_validity_days'],
                'free_followup_validity_days' => $validated['free_followup_validity_days'] ?? 0,
            ]);
        }

        return response()->json($skillSet->load('visitValidity'), 201);
    }

    /**
     * Display the specified skill set
     */
    public function show(string $id)
    {
        $skillSet = SkillSet::with(['visitValidity', 'doctors'])->findOrFail($id);

        return response()->json($skillSet);
    }

    /**
     * Update the specified skill set
     */
    public function update(Request $request, string $id)
    {
        $skillSet = SkillSet::findOrFail($id);

        $validated = $request->validate([
            'skill_code' => 'nullable|string|max:20',
            'skill_name' => 'string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $skillSet->update($validated);

        return response()->json($skillSet->load('visitValidity'));
    }

    /**
     * Remove the specified skill set
     */
    public function destroy(string $id)
    {
        $skillSet = SkillSet::findOrFail($id);

        // Check if skill set is in use
        if ($skillSet->doctors()->exists()) {
            return response()->json([
                'message' => 'Cannot delete skill set that is assigned to doctors',
            ], 422);
        }

        $skillSet->visitValidity()->delete();
        $skillSet->delete();

        return response()->json(['message' => 'Skill set deleted successfully']);
    }

    /**
     * Get validity settings for a skill set
     */
    public function getValidity(string $id)
    {
        $skillSet = SkillSet::findOrFail($id);
        $validity = $skillSet->visitValidity;

        return response()->json([
            'skill_set_id' => $skillSet->skill_set_id,
            'skill_name' => $skillSet->skill_name,
            'validity' => $validity,
        ]);
    }

    /**
     * Set validity settings for a skill set
     */
    public function setValidity(Request $request, string $id)
    {
        $skillSet = SkillSet::findOrFail($id);

        $validated = $request->validate([
            'followup_validity_days' => 'required|integer|min:1',
            'free_followup_validity_days' => 'nullable|integer|min:0',
        ]);

        $validity = SkillSetVisitValidity::updateOrCreate(
            ['skill_set_id' => $skillSet->skill_set_id],
            [
                'followup_validity_days' => $validated['followup_validity_days'],
                'free_followup_validity_days' => $validated['free_followup_validity_days'] ?? 0,
            ]
        );

        return response()->json([
            'message' => 'Validity settings updated',
            'validity' => $validity,
        ]);
    }
}
