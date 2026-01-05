<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurgeryType;
use Illuminate\Http\Request;

class SurgeryTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = SurgeryType::with('department');

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->complexity) {
            $query->where('complexity', $request->complexity);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('surgery_name', 'like', "%{$search}%")
                    ->orWhere('surgery_code', 'like', "%{$search}%");
            });
        }

        $surgeries = $query->orderBy('surgery_name')
            ->paginate($request->per_page ?? 50);

        return response()->json($surgeries);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'surgery_code' => 'required|string|max:20',
            'surgery_name' => 'required|string|max:200',
            'department_id' => 'required|exists:departments,department_id',
            'complexity' => 'required|in:minor,moderate,major,super_specialty',
            'estimated_duration_mins' => 'nullable|integer|min:15',
            'base_charges' => 'nullable|numeric|min:0',
            'anesthesia_type' => 'nullable|in:local,regional,general,sedation',
            'requires_icu' => 'nullable|boolean',
            'blood_requirement' => 'nullable|in:none,possible,mandatory',
            'consent_form_type' => 'nullable|string|max:50',
        ]);

        $surgery = SurgeryType::create($validated);

        return response()->json([
            'message' => 'Surgery type created successfully',
            'surgery' => $surgery->load('department'),
        ], 201);
    }

    public function show(SurgeryType $surgery)
    {
        return response()->json([
            'surgery' => $surgery->load('department'),
        ]);
    }

    public function update(Request $request, SurgeryType $surgery)
    {
        $validated = $request->validate([
            'surgery_name' => 'sometimes|string|max:200',
            'department_id' => 'sometimes|exists:departments,department_id',
            'complexity' => 'sometimes|in:minor,moderate,major,super_specialty',
            'estimated_duration_mins' => 'nullable|integer|min:15',
            'base_charges' => 'nullable|numeric|min:0',
            'anesthesia_type' => 'nullable|in:local,regional,general,sedation',
            'requires_icu' => 'nullable|boolean',
            'blood_requirement' => 'nullable|in:none,possible,mandatory',
            'consent_form_type' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $surgery->update($validated);

        return response()->json([
            'message' => 'Surgery type updated successfully',
            'surgery' => $surgery->load('department'),
        ]);
    }

    public function destroy(SurgeryType $surgery)
    {
        if ($surgery->schedules()->exists()) {
            return response()->json([
                'message' => 'Cannot delete surgery type with existing schedules',
            ], 422);
        }

        $surgery->delete();

        return response()->json([
            'message' => 'Surgery type deleted successfully',
        ]);
    }
}
