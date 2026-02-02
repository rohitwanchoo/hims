<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Analyzer;
use App\Models\AnalyzerTestMap;
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
                      ->orWhere('manufacturer', 'like', "%{$search}%");
                });
            }

            // Manufacturer filter
            if ($request->filled('manufacturer')) {
                $query->where('manufacturer', 'like', "%{$request->manufacturer}%");
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
                'analyzer_name' => 'required|string|max:100',
                'analyzer_code' => 'nullable|string|max:50',
                'manufacturer' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100',
                'description' => 'nullable|string',
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
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'serial_number' => $request->serial_number,
                'description' => $request->description,
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
                'analyzer_name' => 'required|string|max:100',
                'analyzer_code' => 'nullable|string|max:50',
                'manufacturer' => 'nullable|string|max:100',
                'model' => 'nullable|string|max:100',
                'serial_number' => 'nullable|string|max:100',
                'description' => 'nullable|string',
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
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'serial_number' => $request->serial_number,
                'description' => $request->description,
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
}
