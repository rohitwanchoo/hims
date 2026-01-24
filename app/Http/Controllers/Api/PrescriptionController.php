<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionDrug;
use App\Models\StandardRx;
use App\Models\StandardRxDrug;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * Store a newly created prescription.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'nullable|exists:patients,patient_id',
            'drugs' => 'required|array|min:1',
            'drugs.*.drug_name' => 'required|string',
            'advice' => 'nullable|string',
            'investigations' => 'nullable|string',
            'qty_display_on_print' => 'boolean',
            'save_as_standard_rx' => 'boolean',
            'disease_name' => 'required_if:save_as_standard_rx,true|string|max:255',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        DB::beginTransaction();
        try {
            // Create prescription
            $prescription = Prescription::create([
                'hospital_id' => $hospitalId,
                'patient_id' => $request->patient_id,
                'appointment_id' => $request->appointment_id,
                'advice' => $request->advice,
                'investigations' => $request->investigations,
                'qty_display_on_print' => $request->qty_display_on_print ?? true,
                'prescription_date' => now(),
                'created_by' => Auth::id(),
            ]);

            // Add drugs
            foreach ($request->drugs as $index => $drug) {
                PrescriptionDrug::create([
                    'prescription_id' => $prescription->prescription_id,
                    'drug_master_id' => $drug['drug_master_id'] ?? null,
                    'drug_name' => $drug['drug_name'],
                    'drug_type' => $drug['drug_type'] ?? null,
                    'language' => $drug['language'] ?? 'english',
                    'dose_advice' => $drug['dose_advice'] ?? null,
                    'days' => $drug['days'] ?? null,
                    'qty' => $drug['qty'] ?? null,
                    'display_order' => $index,
                ]);
            }

            // Save as standard Rx if requested
            if ($request->save_as_standard_rx && $request->disease_name) {
                $standardRx = StandardRx::create([
                    'hospital_id' => $hospitalId,
                    'disease_name' => $request->disease_name,
                    'advice' => $request->advice,
                ]);

                foreach ($request->drugs as $index => $drug) {
                    StandardRxDrug::create([
                        'standard_rx_id' => $standardRx->standard_rx_id,
                        'drug_master_id' => $drug['drug_master_id'] ?? null,
                        'drug_name' => $drug['drug_name'],
                        'drug_type' => $drug['drug_type'] ?? null,
                        'language' => $drug['language'] ?? 'english',
                        'dose_advice' => $drug['dose_advice'] ?? null,
                        'days' => $drug['days'] ?? null,
                        'qty' => $drug['qty'] ?? null,
                        'display_order' => $index,
                    ]);
                }
            }

            DB::commit();

            $prescription->load('drugs');

            return response()->json([
                'success' => true,
                'message' => 'Prescription saved successfully',
                'data' => $prescription
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error saving prescription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get last prescription for a patient.
     */
    public function getLastPrescription($patientId)
    {
        $hospitalId = Auth::user()->hospital_id;

        $prescription = Prescription::with('drugs')
            ->where('hospital_id', $hospitalId)
            ->where('patient_id', $patientId)
            ->latest('prescription_date')
            ->first();

        if (!$prescription) {
            return response()->json([
                'success' => false,
                'message' => 'No previous prescription found'
            ], 404);
        }

        return response()->json([
            'drugs' => $prescription->drugs->map(function ($drug) {
                return [
                    'drug_master_id' => $drug->drug_master_id,
                    'drug_name' => $drug->drug_name,
                    'drug_type' => $drug->drug_type,
                    'language' => $drug->language,
                    'dose_advice' => $drug->dose_advice,
                    'days' => $drug->days,
                    'qty' => $drug->qty,
                ];
            }),
            'advice' => $prescription->advice,
            'investigations' => $prescription->investigations,
        ]);
    }

    /**
     * Display the specified prescription.
     */
    public function show(Prescription $prescription)
    {
        if ($prescription->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $prescription->load('drugs', 'patient');

        return response()->json($prescription);
    }
}
