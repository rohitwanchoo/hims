<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DrugMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DrugMasterController extends Controller
{
    /**
     * Display a listing of drug masters.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = DrugMaster::with('drugType')->where('hospital_id', $hospitalId);

        // Filter by drug type
        if ($request->has('drug_type_id') && $request->drug_type_id) {
            $query->where('drug_type_id', $request->drug_type_id);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('drug_name', 'like', '%' . $request->search . '%');
        }

        // Only active records
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $drugs = $query->orderBy('drug_name', 'asc')->get();

        return response()->json($drugs);
    }

    /**
     * Store a newly created drug master.
     */
    public function store(Request $request)
    {
        $request->validate([
            'drug_name' => 'required|string|max:255',
            'drug_type_id' => 'nullable|exists:drug_types,drug_type_id',
            'language' => 'required|in:english,marathi,hindi',
            'dose_time' => 'nullable|string|max:100',
            'days' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        $drug = DrugMaster::create([
            'hospital_id' => $hospitalId,
            'drug_type_id' => $request->drug_type_id,
            'drug_name' => $request->drug_name,
            'language' => $request->language,
            'dose_time' => $request->dose_time,
            'days' => $request->days,
            'quantity' => $request->quantity,
            'is_active' => $request->is_active ?? true,
        ]);

        // Load relationship
        $drug->load('drugType');

        return response()->json([
            'success' => true,
            'message' => 'Drug created successfully',
            'data' => $drug
        ], 201);
    }

    /**
     * Display the specified drug master.
     */
    public function show(DrugMaster $drugMaster)
    {
        if ($drugMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $drugMaster->load('drugType');
        return response()->json($drugMaster);
    }

    /**
     * Update the specified drug master.
     */
    public function update(Request $request, DrugMaster $drugMaster)
    {
        if ($drugMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'drug_name' => 'required|string|max:255',
            'drug_type_id' => 'nullable|exists:drug_types,drug_type_id',
            'language' => 'required|in:english,marathi,hindi',
            'dose_time' => 'nullable|string|max:100',
            'days' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $drugMaster->update([
            'drug_type_id' => $request->drug_type_id,
            'drug_name' => $request->drug_name,
            'language' => $request->language,
            'dose_time' => $request->dose_time,
            'days' => $request->days,
            'quantity' => $request->quantity,
            'is_active' => $request->is_active ?? $drugMaster->is_active,
        ]);

        // Load relationship
        $drugMaster->load('drugType');

        return response()->json([
            'success' => true,
            'message' => 'Drug updated successfully',
            'data' => $drugMaster
        ]);
    }

    /**
     * Remove the specified drug master.
     */
    public function destroy(DrugMaster $drugMaster)
    {
        if ($drugMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $drugMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Drug deleted successfully'
        ]);
    }

    /**
     * Download Excel template for bulk drug import.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="drug_import_template.csv"',
        ];

        $columns = ['drug_name', 'drug_type', 'language', 'dose_time', 'days', 'quantity'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Add sample data
            fputcsv($file, ['Paracetamol 500mg', 'Tablet', 'english', '1-1-1', '5', '15']);
            fputcsv($file, ['Amoxicillin 250mg', 'Capsule', 'english', '1-0-1', '7', '14']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import drugs from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:2048',
        ]);

        $hospitalId = Auth::user()->hospital_id;
        $file = $request->file('file');
        $importedCount = 0;
        $errors = [];

        try {
            // Read CSV file
            if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                $header = fgetcsv($handle); // Skip header row

                while (($row = fgetcsv($handle)) !== false) {
                    try {
                        // Find or create drug type
                        $drugTypeId = null;
                        if (!empty($row[1])) {
                            $drugType = \App\Models\DrugType::firstOrCreate([
                                'hospital_id' => $hospitalId,
                                'type_name' => trim($row[1])
                            ]);
                            $drugTypeId = $drugType->drug_type_id;
                        }

                        DrugMaster::create([
                            'hospital_id' => $hospitalId,
                            'drug_name' => trim($row[0]),
                            'drug_type_id' => $drugTypeId,
                            'language' => !empty($row[2]) ? trim($row[2]) : 'english',
                            'dose_time' => !empty($row[3]) ? trim($row[3]) : null,
                            'days' => !empty($row[4]) ? (int)$row[4] : null,
                            'quantity' => !empty($row[5]) ? (int)$row[5] : null,
                            'is_active' => true,
                        ]);

                        $importedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Row error: " . $e->getMessage();
                    }
                }

                fclose($handle);
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$importedCount} drugs",
                'count' => $importedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing file: ' . $e->getMessage()
            ], 500);
        }
    }
}
