<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vaccination;
use App\Models\PatientVaccination;
use Illuminate\Http\Request;

class VaccinationController extends Controller
{
    public function index(Request $request)
    {
        $query = Vaccination::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('vaccine_name', 'like', "%{$request->search}%")
                  ->orWhere('vaccine_code', 'like', "%{$request->search}%")
                  ->orWhere('manufacturer', 'like', "%{$request->search}%");
            });
        }

        if ($request->schedule_type) {
            $query->where('schedule_type', $request->schedule_type);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $vaccinations = $query->orderBy('schedule_value')->orderBy('vaccine_name')->get();

        return response()->json($vaccinations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vaccine_code' => 'required|string|max:20|unique:vaccinations',
            'vaccine_name' => 'required|string|max:150',
            'manufacturer' => 'nullable|string|max:100',
            'schedule_value' => 'nullable|integer|min:0',
            'schedule_type' => 'nullable|in:days,months,years',
            'schedule_text' => 'nullable|string|max:100',
            'dose_number' => 'nullable|integer|min:1',
            'total_doses' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'rate' => 'nullable|numeric|min:0',
        ]);

        $vaccination = Vaccination::create($validated);

        return response()->json($vaccination, 201);
    }

    public function show(Vaccination $vaccination)
    {
        return response()->json($vaccination);
    }

    public function update(Request $request, Vaccination $vaccination)
    {
        $validated = $request->validate([
            'vaccine_name' => 'sometimes|required|string|max:150',
            'manufacturer' => 'nullable|string|max:100',
            'schedule_value' => 'nullable|integer|min:0',
            'schedule_type' => 'nullable|in:days,months,years',
            'schedule_text' => 'nullable|string|max:100',
            'dose_number' => 'nullable|integer|min:1',
            'total_doses' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $vaccination->update($validated);

        return response()->json($vaccination);
    }

    public function destroy(Vaccination $vaccination)
    {
        $vaccination->delete();
        return response()->json(['message' => 'Vaccination deleted successfully']);
    }

    /**
     * Get patient vaccination records
     */
    public function patientRecords(Request $request, $patientId)
    {
        $records = PatientVaccination::with('vaccination')
            ->where('patient_id', $patientId)
            ->orderBy('scheduled_date')
            ->get();

        return response()->json($records);
    }

    /**
     * Schedule vaccination for a patient
     */
    public function scheduleVaccination(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'vaccination_id' => 'required|exists:vaccinations,vaccination_id',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $record = PatientVaccination::create($validated);

        return response()->json($record->load('vaccination'), 201);
    }

    /**
     * Administer vaccination
     */
    public function administerVaccination(Request $request, PatientVaccination $patientVaccination)
    {
        $validated = $request->validate([
            'administered_date' => 'required|date',
            'batch_number' => 'nullable|string|max:50',
            'administered_by' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $patientVaccination->update([
            'administered_date' => $validated['administered_date'],
            'batch_number' => $validated['batch_number'] ?? null,
            'administered_by' => $validated['administered_by'] ?? null,
            'notes' => $validated['notes'] ?? $patientVaccination->notes,
            'status' => 'administered',
        ]);

        return response()->json($patientVaccination->load('vaccination'));
    }
}
