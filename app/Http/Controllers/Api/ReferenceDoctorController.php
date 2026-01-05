<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferenceDoctor;
use Illuminate\Http\Request;

class ReferenceDoctorController extends Controller
{
    public function index(Request $request)
    {
        $query = ReferenceDoctor::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'like', "%{$request->search}%")
                  ->orWhere('doctor_code', 'like', "%{$request->search}%")
                  ->orWhere('hospital_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->group_name) {
            $query->where('group_name', $request->group_name);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $referenceDoctors = $query->orderBy('full_name')->get();

        return response()->json($referenceDoctors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_code' => 'required|string|max:20|unique:reference_doctors',
            'full_name' => 'required|string|max:100',
            'qualification' => 'nullable|string|max:200',
            'skill_set' => 'nullable|string|max:200',
            'registration_no' => 'nullable|string|max:50',
            'hospital_name' => 'nullable|string|max:200',
            'group_name' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $referenceDoctor = ReferenceDoctor::create($validated);

        return response()->json($referenceDoctor, 201);
    }

    public function show(ReferenceDoctor $referenceDoctor)
    {
        return response()->json($referenceDoctor);
    }

    public function update(Request $request, ReferenceDoctor $referenceDoctor)
    {
        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:100',
            'qualification' => 'nullable|string|max:200',
            'skill_set' => 'nullable|string|max:200',
            'registration_no' => 'nullable|string|max:50',
            'hospital_name' => 'nullable|string|max:200',
            'group_name' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:50',
            'state' => 'nullable|string|max:50',
            'pincode' => 'nullable|string|max:10',
            'mobile' => 'nullable|string|max:15',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:100',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $referenceDoctor->update($validated);

        return response()->json($referenceDoctor);
    }

    public function destroy(ReferenceDoctor $referenceDoctor)
    {
        $referenceDoctor->delete();
        return response()->json(['message' => 'Reference doctor deleted successfully']);
    }
}
