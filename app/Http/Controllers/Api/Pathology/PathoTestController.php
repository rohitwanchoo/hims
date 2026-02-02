<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoTest;
use App\Models\PathoTestParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PathoTestController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTest::where('hospital_id', $hospitalId)
                ->with(['category', 'sampleType', 'testMethod', 'testUnit', 'container', 'externalLabCenter', 'parameters']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('test_name', 'like', "%{$search}%")
                      ->orWhere('test_code', 'like', "%{$search}%")
                      ->orWhere('short_name', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Sample type filter
            if ($request->filled('sample_type_id')) {
                $query->where('sample_type_id', $request->sample_type_id);
            }

            // Test type filter (internal/external)
            if ($request->filled('is_external')) {
                $query->where('is_external', $request->is_external);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'test_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $tests = $query->paginate($perPage);

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

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_name' => 'required|string|max:200',
                'test_code' => 'nullable|string|max:50',
                'short_name' => 'nullable|string|max:100',
                'category_id' => 'nullable|exists:patho_test_categories,category_id',
                'sample_type_id' => 'nullable|exists:patho_sample_types,sample_type_id',
                'method_id' => 'nullable|exists:patho_test_methods,method_id',
                'unit_id' => 'nullable|exists:patho_test_units,unit_id',
                'container_id' => 'nullable|exists:patho_containers,container_id',
                'external_lab_center_id' => 'nullable|exists:external_lab_centers,lab_center_id',
                'price' => 'nullable|numeric|min:0',
                'cost' => 'nullable|numeric|min:0',
                'tat_hours' => 'nullable|integer|min:0',
                'normal_range' => 'nullable|string',
                'description' => 'nullable|string',
                'interpretation' => 'nullable|string',
                'is_external' => 'boolean',
                'is_active' => 'boolean',
                'display_order' => 'nullable|integer',
                'parameters' => 'nullable|array',
                'parameters.*.parameter_name' => 'required|string|max:200',
                'parameters.*.unit_id' => 'nullable|exists:patho_test_units,unit_id',
                'parameters.*.normal_range' => 'nullable|string',
                'parameters.*.display_order' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $test = PathoTest::create([
                'hospital_id' => Auth::user()->hospital_id,
                'test_name' => $request->test_name,
                'test_code' => $request->test_code,
                'short_name' => $request->short_name,
                'category_id' => $request->category_id,
                'sample_type_id' => $request->sample_type_id,
                'method_id' => $request->method_id,
                'unit_id' => $request->unit_id,
                'container_id' => $request->container_id,
                'external_lab_center_id' => $request->external_lab_center_id,
                'price' => $request->price ?? 0,
                'cost' => $request->cost ?? 0,
                'tat_hours' => $request->tat_hours ?? 24,
                'normal_range' => $request->normal_range,
                'description' => $request->description,
                'interpretation' => $request->interpretation,
                'is_external' => $request->is_external ?? false,
                'is_active' => $request->is_active ?? true,
                'display_order' => $request->display_order ?? 0,
            ]);

            // Create parameters
            if ($request->filled('parameters')) {
                foreach ($request->parameters as $paramData) {
                    PathoTestParameter::create([
                        'test_id' => $test->test_id,
                        'parameter_name' => $paramData['parameter_name'],
                        'unit_id' => $paramData['unit_id'] ?? null,
                        'normal_range' => $paramData['normal_range'] ?? null,
                        'display_order' => $paramData['display_order'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test created successfully',
                'data' => $test->load(['category', 'sampleType', 'testMethod', 'testUnit', 'container', 'externalLabCenter', 'parameters'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $test = PathoTest::where('hospital_id', $hospitalId)
                ->where('test_id', $id)
                ->with(['category', 'sampleType', 'testMethod', 'testUnit', 'container', 'externalLabCenter', 'parameters'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $test
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $test = PathoTest::where('hospital_id', $hospitalId)
                ->where('test_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'test_name' => 'required|string|max:200',
                'test_code' => 'nullable|string|max:50',
                'short_name' => 'nullable|string|max:100',
                'category_id' => 'nullable|exists:patho_test_categories,category_id',
                'sample_type_id' => 'nullable|exists:patho_sample_types,sample_type_id',
                'method_id' => 'nullable|exists:patho_test_methods,method_id',
                'unit_id' => 'nullable|exists:patho_test_units,unit_id',
                'container_id' => 'nullable|exists:patho_containers,container_id',
                'external_lab_center_id' => 'nullable|exists:external_lab_centers,lab_center_id',
                'price' => 'nullable|numeric|min:0',
                'cost' => 'nullable|numeric|min:0',
                'tat_hours' => 'nullable|integer|min:0',
                'normal_range' => 'nullable|string',
                'description' => 'nullable|string',
                'interpretation' => 'nullable|string',
                'is_external' => 'boolean',
                'is_active' => 'boolean',
                'display_order' => 'nullable|integer',
                'parameters' => 'nullable|array',
                'parameters.*.parameter_name' => 'required|string|max:200',
                'parameters.*.unit_id' => 'nullable|exists:patho_test_units,unit_id',
                'parameters.*.normal_range' => 'nullable|string',
                'parameters.*.display_order' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $test->update([
                'test_name' => $request->test_name,
                'test_code' => $request->test_code,
                'short_name' => $request->short_name,
                'category_id' => $request->category_id,
                'sample_type_id' => $request->sample_type_id,
                'method_id' => $request->method_id,
                'unit_id' => $request->unit_id,
                'container_id' => $request->container_id,
                'external_lab_center_id' => $request->external_lab_center_id,
                'price' => $request->price ?? $test->price,
                'cost' => $request->cost ?? $test->cost,
                'tat_hours' => $request->tat_hours ?? $test->tat_hours,
                'normal_range' => $request->normal_range,
                'description' => $request->description,
                'interpretation' => $request->interpretation,
                'is_external' => $request->is_external ?? $test->is_external,
                'is_active' => $request->is_active ?? $test->is_active,
                'display_order' => $request->display_order ?? $test->display_order,
            ]);

            // Update parameters
            if ($request->has('parameters')) {
                // Delete existing parameters
                PathoTestParameter::where('test_id', $test->test_id)->delete();

                // Create new parameters
                if (is_array($request->parameters)) {
                    foreach ($request->parameters as $paramData) {
                        PathoTestParameter::create([
                            'test_id' => $test->test_id,
                            'parameter_name' => $paramData['parameter_name'],
                            'unit_id' => $paramData['unit_id'] ?? null,
                            'normal_range' => $paramData['normal_range'] ?? null,
                            'display_order' => $paramData['display_order'] ?? 0,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test updated successfully',
                'data' => $test->load(['category', 'sampleType', 'testMethod', 'testUnit', 'container', 'externalLabCenter', 'parameters'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $test = PathoTest::where('hospital_id', $hospitalId)
                ->where('test_id', $id)
                ->firstOrFail();

            DB::beginTransaction();

            // Delete parameters
            PathoTestParameter::where('test_id', $test->test_id)->delete();

            // Delete test
            $test->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByCategory($categoryId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $tests = PathoTest::where('hospital_id', $hospitalId)
                ->where('category_id', $categoryId)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('test_name')
                ->get();

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
}
