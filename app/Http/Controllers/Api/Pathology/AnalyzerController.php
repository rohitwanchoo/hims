<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\Analyzer;
use App\Models\Pathology\AnalyzerTestMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AnalyzerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = Analyzer::where('hospital_id', $hospitalId)
                ->with(['tests']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('analyzer_name', 'like', "%{$search}%")
                      ->orWhere('analyzer_code', 'like', "%{$search}%")
                      ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            // Analyzer type filter
            if ($request->filled('analyzer_type')) {
                $query->where('analyzer_type', $request->analyzer_type);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'analyzer_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $analyzers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $analyzers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analyzers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'analyzer_name' => 'required|string|max:255',
                'analyzer_code' => 'nullable|string|max:255',
                'analyzer_type' => 'required|in:on_demand,pre_database',
                'is_bidirectional' => 'boolean',
                'analyzer_count' => 'integer|min:0',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
                'tests' => 'nullable|array',
                'tests.*' => 'exists:patho_tests,test_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $analyzer = Analyzer::create([
                'hospital_id' => Auth::user()->hospital_id,
                'analyzer_name' => $request->analyzer_name,
                'analyzer_code' => $request->analyzer_code,
                'analyzer_type' => $request->analyzer_type,
                'is_bidirectional' => $request->is_bidirectional ?? false,
                'analyzer_count' => $request->analyzer_count ?? 1,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? true,
            ]);

            // Map tests to analyzer
            if ($request->filled('tests')) {
                foreach ($request->tests as $testId) {
                    AnalyzerTestMap::create([
                        'analyzer_id' => $analyzer->analyzer_id,
                        'test_id' => $testId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Analyzer created successfully',
                'data' => $analyzer->load('tests')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create analyzer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->with(['tests'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $analyzer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analyzer not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'analyzer_name' => 'required|string|max:255',
                'analyzer_code' => 'nullable|string|max:255',
                'analyzer_type' => 'required|in:on_demand,pre_database',
                'is_bidirectional' => 'boolean',
                'analyzer_count' => 'integer|min:0',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
                'tests' => 'nullable|array',
                'tests.*' => 'exists:patho_tests,test_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $analyzer->update([
                'analyzer_name' => $request->analyzer_name,
                'analyzer_code' => $request->analyzer_code,
                'analyzer_type' => $request->analyzer_type,
                'is_bidirectional' => $request->is_bidirectional ?? $analyzer->is_bidirectional,
                'analyzer_count' => $request->analyzer_count ?? $analyzer->analyzer_count,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? $analyzer->is_active,
            ]);

            // Update test mappings
            if ($request->has('tests')) {
                // Delete existing mappings
                AnalyzerTestMap::where('analyzer_id', $analyzer->analyzer_id)->delete();

                // Create new mappings
                if (is_array($request->tests)) {
                    foreach ($request->tests as $testId) {
                        AnalyzerTestMap::create([
                            'analyzer_id' => $analyzer->analyzer_id,
                            'test_id' => $testId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Analyzer updated successfully',
                'data' => $analyzer->load('tests')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update analyzer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->firstOrFail();

            DB::beginTransaction();

            // Delete test mappings
            AnalyzerTestMap::where('analyzer_id', $analyzer->analyzer_id)->delete();

            // Delete analyzer
            $analyzer->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Analyzer deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete analyzer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mapTests(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'tests' => 'required|array',
                'tests.*' => 'exists:patho_tests,test_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Delete existing mappings
            AnalyzerTestMap::where('analyzer_id', $analyzer->analyzer_id)->delete();

            // Create new mappings
            foreach ($request->tests as $testId) {
                AnalyzerTestMap::create([
                    'analyzer_id' => $analyzer->analyzer_id,
                    'test_id' => $testId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tests mapped successfully',
                'data' => $analyzer->load('tests')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to map tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTests($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->firstOrFail();

            $tests = $analyzer->tests;

            return response()->json([
                'success' => true,
                'data' => $tests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addTest(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'test_id' => 'required|exists:patho_tests,test_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if mapping already exists
            $existingMapping = AnalyzerTestMap::where('analyzer_id', $analyzer->analyzer_id)
                ->where('test_id', $request->test_id)
                ->first();

            if ($existingMapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test already mapped to this analyzer'
                ], 422);
            }

            AnalyzerTestMap::create([
                'analyzer_id' => $analyzer->analyzer_id,
                'test_id' => $request->test_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test added successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeTest($id, $testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $analyzer = Analyzer::where('hospital_id', $hospitalId)
                ->where('analyzer_id', $id)
                ->firstOrFail();

            $mapping = AnalyzerTestMap::where('analyzer_id', $analyzer->analyzer_id)
                ->where('test_id', $testId)
                ->firstOrFail();

            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test removed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove test',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
