<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorOpdRate;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorOpdRateController extends Controller
{
    /**
     * Display all doctor OPD rates
     */
    public function index(Request $request)
    {
        $query = DoctorOpdRate::with(['doctor', 'patientClass']);

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->class_id) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->visit_type) {
            $query->where('visit_type', $request->visit_type);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rates = $query->orderBy('doctor_id')->orderBy('visit_type')->get();

        return response()->json($rates);
    }

    /**
     * Store new doctor OPD rate
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'class_id' => 'nullable|exists:classes,class_id',
            'visit_type' => 'required|in:new,followup,emergency,referral',
            'charge_type' => 'nullable|in:normal,day_emergency,night_emergency',
            'rate' => 'required|numeric|min:0',
            'free_followup_rate' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
        ]);

        // Check for existing rate
        $existing = DoctorOpdRate::where('doctor_id', $validated['doctor_id'])
            ->where('class_id', $validated['class_id'] ?? null)
            ->where('visit_type', $validated['visit_type'])
            ->where('charge_type', $validated['charge_type'] ?? 'normal')
            ->where('is_active', true)
            ->first();

        if ($existing) {
            // Deactivate existing rate
            $existing->update(['is_active' => false, 'effective_to' => now()->toDateString()]);
        }

        $rate = DoctorOpdRate::create($validated);

        return response()->json($rate->load(['doctor', 'patientClass']), 201);
    }

    /**
     * Display specific rate
     */
    public function show(string $id)
    {
        $rate = DoctorOpdRate::with(['doctor', 'patientClass'])->findOrFail($id);

        return response()->json($rate);
    }

    /**
     * Update rate
     */
    public function update(Request $request, string $id)
    {
        $rate = DoctorOpdRate::findOrFail($id);

        $validated = $request->validate([
            'rate' => 'numeric|min:0',
            'free_followup_rate' => 'nullable|numeric|min:0',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $rate->update($validated);

        return response()->json($rate->load(['doctor', 'patientClass']));
    }

    /**
     * Delete rate
     */
    public function destroy(string $id)
    {
        $rate = DoctorOpdRate::findOrFail($id);
        $rate->delete();

        return response()->json(['message' => 'Rate deleted successfully']);
    }

    /**
     * Get rates for a specific doctor
     */
    public function doctorRates(string $doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        $rates = DoctorOpdRate::with('patientClass')
            ->where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->orderBy('visit_type')
            ->orderBy('class_id')
            ->get();

        return response()->json([
            'doctor' => $doctor,
            'rates' => $rates,
        ]);
    }

    /**
     * Get applicable rate for consultation
     */
    public function getRate(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,doctor_id',
            'visit_type' => 'nullable|in:new,followup,emergency,referral',
            'charge_type' => 'nullable|in:normal,day_emergency,night_emergency',
            'class_id' => 'nullable|exists:classes,class_id',
        ]);

        $rate = DoctorOpdRate::getRate(
            $request->doctor_id,
            $request->visit_type ?? 'new',
            $request->charge_type ?? 'normal',
            $request->class_id
        );

        if ($rate === null) {
            // Fall back to doctor's default consultation fee
            $doctor = Doctor::find($request->doctor_id);
            $rate = $doctor->consultation_fee ?? 0;
        }

        return response()->json([
            'doctor_id' => $request->doctor_id,
            'visit_type' => $request->visit_type ?? 'new',
            'charge_type' => $request->charge_type ?? 'normal',
            'class_id' => $request->class_id,
            'rate' => $rate,
        ]);
    }

    /**
     * Bulk set rates for a doctor
     */
    public function bulkSetRates(Request $request, string $doctorId)
    {
        $doctor = Doctor::findOrFail($doctorId);

        $validated = $request->validate([
            'rates' => 'required|array',
            'rates.*.visit_type' => 'required|in:new,followup,emergency,referral',
            'rates.*.charge_type' => 'nullable|in:normal,day_emergency,night_emergency',
            'rates.*.class_id' => 'nullable|exists:classes,class_id',
            'rates.*.rate' => 'required|numeric|min:0',
            'rates.*.free_followup_rate' => 'nullable|numeric|min:0',
        ]);

        $createdRates = [];
        foreach ($validated['rates'] as $rateData) {
            // Deactivate existing
            DoctorOpdRate::where('doctor_id', $doctorId)
                ->where('visit_type', $rateData['visit_type'])
                ->where('charge_type', $rateData['charge_type'] ?? 'normal')
                ->where('class_id', $rateData['class_id'] ?? null)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // Create new rate
            $createdRates[] = DoctorOpdRate::create([
                'doctor_id' => $doctorId,
                'visit_type' => $rateData['visit_type'],
                'charge_type' => $rateData['charge_type'] ?? 'normal',
                'class_id' => $rateData['class_id'] ?? null,
                'rate' => $rateData['rate'],
                'free_followup_rate' => $rateData['free_followup_rate'] ?? 0,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        return response()->json([
            'message' => 'Rates updated successfully',
            'rates' => $createdRates,
        ]);
    }
}
