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
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        $columns = ['Drug Type', 'Dose Time', 'Drug Name', 'Language', 'Days', 'Qty'];

        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            // Add sample data
            fputcsv($file, ['Tablet', '1-1-1', 'Paracetamol 500mg', 'english', '5', '15']);
            fputcsv($file, ['Capsule', '1-0-1', 'Amoxicillin 250mg', 'english', '7', '14']);
            fputcsv($file, ['Syrup', '1-1-1', 'Cough Syrup 100ml', 'marathi', '3', '1']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import drugs from Excel/CSV file.
     * CSV Format: Drug Type, Dose Time, Drug Name, Language, Days, Qty
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
                    // Skip empty rows
                    if (empty($row[2])) continue;

                    try {
                        // CSV Format: Drug Type (0), Dose Time (1), Drug Name (2), Language (3), Days (4), Qty (5)

                        // Validate and normalize language
                        $language = !empty($row[3]) ? strtolower(trim($row[3])) : 'english';
                        if (!in_array($language, ['english', 'marathi', 'hindi'])) {
                            $language = 'english'; // Default to english if invalid
                        }

                        // Find or create drug type
                        $drugTypeId = null;
                        if (!empty($row[0])) {
                            $drugType = \App\Models\DrugType::firstOrCreate(
                                [
                                    'hospital_id' => $hospitalId,
                                    'type_name' => trim($row[0])
                                ],
                                [
                                    'is_active' => true
                                ]
                            );
                            $drugTypeId = $drugType->drug_type_id;
                        }

                        // Find or create dose time master
                        if (!empty($row[1])) {
                            $doseTimeText = trim($row[1]);
                            \App\Models\DoseTimeMaster::firstOrCreate(
                                [
                                    'hospital_id' => $hospitalId,
                                    'dose_time_text' => $doseTimeText,
                                    'language' => $language
                                ],
                                [
                                    'is_active' => true
                                ]
                            );
                        }

                        // Update or create drug master (prevent duplicates)
                        $drugName = trim($row[2]);

                        DrugMaster::updateOrCreate(
                            [
                                'hospital_id' => $hospitalId,
                                'drug_name' => $drugName,
                                'language' => $language
                            ],
                            [
                                'drug_type_id' => $drugTypeId,
                                'dose_time' => !empty($row[1]) ? trim($row[1]) : null,
                                'days' => !empty($row[4]) ? (int)$row[4] : null,
                                'quantity' => !empty($row[5]) ? (int)$row[5] : null,
                                'is_active' => true,
                            ]
                        );

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

    /**
     * Export all drugs to CSV
     */
    public function export()
    {
        $hospitalId = Auth::user()->hospital_id;

        $drugs = DrugMaster::with('drugType')
            ->where('hospital_id', $hospitalId)
            ->orderBy('drug_name')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="drug_masters_export.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ];

        $columns = ['Drug Type', 'Dose Time', 'Drug Name', 'Language', 'Days', 'Qty'];

        $callback = function() use ($drugs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($drugs as $drug) {
                fputcsv($file, [
                    $drug->drugType->type_name ?? '',
                    $drug->dose_time ?? '',
                    $drug->drug_name,
                    $drug->language,
                    $drug->days ?? '',
                    $drug->quantity ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
