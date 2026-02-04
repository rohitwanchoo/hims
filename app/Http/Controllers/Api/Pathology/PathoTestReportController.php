<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoTestReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoTestReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTestReport::where('hospital_id', $hospitalId)
                ->with(['faculty', 'externalLab']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('report_name', 'like', "%{$search}%")
                      ->orWhere('report_code', 'like', "%{$search}%");
                });
            }

            // Faculty filter
            if ($request->filled('faculty_id')) {
                $query->where('faculty_id', $request->faculty_id);
            }

            // Report type filter
            if ($request->filled('report_type')) {
                $query->where('report_type', $request->report_type);
            }

            // Lab type filter
            if ($request->filled('lab_type')) {
                $query->where('lab_type', $request->lab_type);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'report_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $reports = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'report_name' => 'required|string|max:200',
                'report_code' => 'nullable|string|max:100',
                'service_id' => 'nullable|integer',
                'faculty_id' => 'required|exists:patho_faculties,faculty_id',
                'report_type' => 'required|in:normal,culture,histo_patho',
                'is_multi_value' => 'boolean',
                'is_active' => 'boolean',
                'report_in_new_page' => 'boolean',
                'is_non_routine' => 'boolean',
                'is_confidential' => 'boolean',
                'is_premium' => 'boolean',
                'notes' => 'nullable|string',
                'interpretation' => 'nullable|string',
                'remarks' => 'nullable|string',
                'tat_hours' => 'nullable|integer',
                'tat_days' => 'nullable|integer',
                'show_previous_result' => 'boolean',
                'base_price' => 'nullable|numeric|min:0',
                'day_emergency_rate' => 'nullable|numeric|min:0',
                'night_emergency_rate' => 'nullable|numeric|min:0',
                'price_from_date' => 'nullable|date',
                'price_to_date' => 'nullable|date',
                'lab_type' => 'required|in:internal,external',
                'external_lab_id' => 'nullable|exists:external_lab_centers,lab_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $report = PathoTestReport::create([
                'hospital_id' => Auth::user()->hospital_id,
                'report_name' => $request->report_name,
                'report_code' => $request->report_code,
                'service_id' => $request->service_id,
                'faculty_id' => $request->faculty_id,
                'report_type' => $request->report_type,
                'is_multi_value' => $request->is_multi_value ?? false,
                'is_active' => $request->is_active ?? true,
                'report_in_new_page' => $request->report_in_new_page ?? false,
                'is_non_routine' => $request->is_non_routine ?? false,
                'is_confidential' => $request->is_confidential ?? false,
                'is_premium' => $request->is_premium ?? false,
                'notes' => $request->notes,
                'interpretation' => $request->interpretation,
                'remarks' => $request->remarks,
                'tat_hours' => $request->tat_hours,
                'tat_days' => $request->tat_days,
                'show_previous_result' => $request->show_previous_result ?? false,
                'base_price' => $request->base_price ?? 0,
                'day_emergency_rate' => $request->day_emergency_rate,
                'night_emergency_rate' => $request->night_emergency_rate,
                'price_from_date' => $request->price_from_date,
                'price_to_date' => $request->price_to_date,
                'lab_type' => $request->lab_type,
                'external_lab_id' => $request->external_lab_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test report created successfully',
                'data' => $report->load(['faculty', 'externalLab'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $report = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_id', $id)
                ->with(['faculty', 'externalLab'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test report not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $report = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'report_name' => 'required|string|max:200',
                'report_code' => 'nullable|string|max:100',
                'service_id' => 'nullable|integer',
                'faculty_id' => 'required|exists:patho_faculties,faculty_id',
                'report_type' => 'required|in:normal,culture,histo_patho',
                'is_multi_value' => 'boolean',
                'is_active' => 'boolean',
                'report_in_new_page' => 'boolean',
                'is_non_routine' => 'boolean',
                'is_confidential' => 'boolean',
                'is_premium' => 'boolean',
                'notes' => 'nullable|string',
                'interpretation' => 'nullable|string',
                'remarks' => 'nullable|string',
                'tat_hours' => 'nullable|integer',
                'tat_days' => 'nullable|integer',
                'show_previous_result' => 'boolean',
                'base_price' => 'nullable|numeric|min:0',
                'day_emergency_rate' => 'nullable|numeric|min:0',
                'night_emergency_rate' => 'nullable|numeric|min:0',
                'price_from_date' => 'nullable|date',
                'price_to_date' => 'nullable|date',
                'lab_type' => 'required|in:internal,external',
                'external_lab_id' => 'nullable|exists:external_lab_centers,lab_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $report->update([
                'report_name' => $request->report_name,
                'report_code' => $request->report_code,
                'service_id' => $request->service_id,
                'faculty_id' => $request->faculty_id,
                'report_type' => $request->report_type,
                'is_multi_value' => $request->is_multi_value ?? $report->is_multi_value,
                'is_active' => $request->is_active ?? $report->is_active,
                'report_in_new_page' => $request->report_in_new_page ?? $report->report_in_new_page,
                'is_non_routine' => $request->is_non_routine ?? $report->is_non_routine,
                'is_confidential' => $request->is_confidential ?? $report->is_confidential,
                'is_premium' => $request->is_premium ?? $report->is_premium,
                'notes' => $request->notes,
                'interpretation' => $request->interpretation,
                'remarks' => $request->remarks,
                'tat_hours' => $request->tat_hours,
                'tat_days' => $request->tat_days,
                'show_previous_result' => $request->show_previous_result ?? $report->show_previous_result,
                'base_price' => $request->base_price ?? $report->base_price,
                'day_emergency_rate' => $request->day_emergency_rate,
                'night_emergency_rate' => $request->night_emergency_rate,
                'price_from_date' => $request->price_from_date,
                'price_to_date' => $request->price_to_date,
                'lab_type' => $request->lab_type,
                'external_lab_id' => $request->external_lab_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test report updated successfully',
                'data' => $report->load(['faculty', 'externalLab'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $report = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_id', $id)
                ->firstOrFail();

            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test report deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByFaculty($facultyId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $reports = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('faculty_id', $facultyId)
                ->where('is_active', true)
                ->orderBy('report_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByType($type)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $reports = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_type', $type)
                ->where('is_active', true)
                ->orderBy('report_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tests for a report, optionally filtered by category
     */
    public function getTests(Request $request, $reportId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            // Verify report belongs to hospital
            $report = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_id', $reportId)
                ->firstOrFail();

            // Get all mapped test IDs
            $mappedTestIds = \DB::table('patho_test_report_test_maps')
                ->where('report_id', $reportId)
                ->pluck('test_id')
                ->toArray();

            // Get ALL mapped tests with their details (no category filter)
            // We always return all tests in the report, regardless of category selection
            $mappedTests = \App\Models\Pathology\PathoTest::where('hospital_id', $hospitalId)
                ->whereIn('test_id', $mappedTestIds)
                ->with(['category', 'method', 'unit'])
                ->orderBy('test_name')
                ->get();

            // Get ALL available tests (not in report) regardless of category
            $availableTests = \App\Models\Pathology\PathoTest::where('hospital_id', $hospitalId)
                ->where('is_active', true)
                ->whereNotIn('test_id', $mappedTestIds)
                ->with(['category', 'method', 'unit'])
                ->orderBy('test_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'mapped_tests' => $mappedTests,
                    'available_tests' => $availableTests
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getTests: ' . $e->getMessage(), [
                'report_id' => $reportId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a test to a report
     */
    public function addTest(Request $request, $reportId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            \Log::info('Adding test to report', [
                'report_id' => $reportId,
                'test_id' => $request->test_id,
                'hospital_id' => $hospitalId
            ]);

            // Verify report belongs to hospital
            $report = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_id', $reportId)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'test_id' => 'required|exists:patho_tests,test_id',
            ]);

            if ($validator->fails()) {
                \Log::warning('Validation failed for addTest', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify test belongs to hospital
            $test = \App\Models\Pathology\PathoTest::where('test_id', $request->test_id)
                ->where('hospital_id', $hospitalId)
                ->firstOrFail();

            // Check if already mapped
            $exists = \DB::table('patho_test_report_test_maps')
                ->where('report_id', $reportId)
                ->where('test_id', $request->test_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test is already mapped to this report'
                ], 422);
            }

            // Add the mapping
            \DB::table('patho_test_report_test_maps')->insert([
                'report_id' => $reportId,
                'test_id' => $request->test_id,
                'test_sequence' => $request->test_sequence ?? null,
                'is_mandatory' => $request->is_mandatory ?? false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            \Log::info('Test added to report successfully', [
                'report_id' => $reportId,
                'test_id' => $request->test_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test added to report successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error adding test to report: ' . $e->getMessage(), [
                'report_id' => $reportId,
                'test_id' => $request->test_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add test to report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a test from a report
     */
    public function removeTest($reportId, $testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            \Log::info('Removing test from report', [
                'report_id' => $reportId,
                'test_id' => $testId,
                'hospital_id' => $hospitalId
            ]);

            // Verify report belongs to hospital
            $report = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('report_id', $reportId)
                ->firstOrFail();

            // Verify test belongs to hospital
            \App\Models\Pathology\PathoTest::where('test_id', $testId)
                ->where('hospital_id', $hospitalId)
                ->firstOrFail();

            // Remove the mapping
            $deleted = \DB::table('patho_test_report_test_maps')
                ->where('report_id', $reportId)
                ->where('test_id', $testId)
                ->delete();

            \Log::info('Test removed from report', [
                'report_id' => $reportId,
                'test_id' => $testId,
                'rows_deleted' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test removed from report successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error removing test from report: ' . $e->getMessage(), [
                'report_id' => $reportId,
                'test_id' => $testId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove test from report',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
