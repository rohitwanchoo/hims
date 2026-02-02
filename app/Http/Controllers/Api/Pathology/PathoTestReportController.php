<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoTestReport;
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
                ->with(['test']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('report_name', 'like', "%{$search}%")
                      ->orWhere('report_title', 'like', "%{$search}%");
                });
            }

            // Test filter
            if ($request->filled('test_id')) {
                $query->where('test_id', $request->test_id);
            }

            // Report type filter
            if ($request->filled('report_type')) {
                $query->where('report_type', $request->report_type);
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
                'test_id' => 'nullable|exists:patho_tests,test_id',
                'report_name' => 'required|string|max:200',
                'report_title' => 'nullable|string|max:200',
                'report_type' => 'required|in:standard,custom,template',
                'report_template' => 'nullable|string',
                'header_text' => 'nullable|string',
                'footer_text' => 'nullable|string',
                'display_order' => 'nullable|integer',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $report = PathoTestReport::create([
                'hospital_id' => Auth::user()->hospital_id,
                'test_id' => $request->test_id,
                'report_name' => $request->report_name,
                'report_title' => $request->report_title,
                'report_type' => $request->report_type,
                'report_template' => $request->report_template,
                'header_text' => $request->header_text,
                'footer_text' => $request->footer_text,
                'display_order' => $request->display_order ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test report created successfully',
                'data' => $report->load('test')
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
                ->with(['test'])
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
                'test_id' => 'nullable|exists:patho_tests,test_id',
                'report_name' => 'required|string|max:200',
                'report_title' => 'nullable|string|max:200',
                'report_type' => 'required|in:standard,custom,template',
                'report_template' => 'nullable|string',
                'header_text' => 'nullable|string',
                'footer_text' => 'nullable|string',
                'display_order' => 'nullable|integer',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $report->update([
                'test_id' => $request->test_id,
                'report_name' => $request->report_name,
                'report_title' => $request->report_title,
                'report_type' => $request->report_type,
                'report_template' => $request->report_template,
                'header_text' => $request->header_text,
                'footer_text' => $request->footer_text,
                'display_order' => $request->display_order ?? $report->display_order,
                'is_active' => $request->is_active ?? $report->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test report updated successfully',
                'data' => $report->load('test')
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

    public function getByTest($testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $reports = PathoTestReport::where('hospital_id', $hospitalId)
                ->where('test_id', $testId)
                ->where('is_active', true)
                ->orderBy('display_order')
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
}
