<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoTest;
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
                ->with(['method', 'unit', 'container', 'referenceRanges.gender', 'referenceRanges.ageGroup', 'referenceRanges.race']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('test_name', 'like', "%{$search}%")
                      ->orWhere('test_code', 'like', "%{$search}%")
                      ->orWhere('short_name', 'like', "%{$search}%");
                });
            }

            // Value type filter
            if ($request->filled('value_type')) {
                $query->where('value_type', $request->value_type);
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
                'value_type' => 'required|in:numeric,alphanumeric',
                'method_id' => 'nullable|exists:patho_test_methods,method_id',
                'unit_id' => 'nullable|exists:patho_test_units,unit_id',
                'container_id' => 'nullable|exists:patho_containers,container_id',
                'test_sequence' => 'nullable|integer',
                'min_value' => 'nullable|numeric',
                'max_value' => 'nullable|numeric',
                'critical_low' => 'nullable|numeric',
                'critical_high' => 'nullable|numeric',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
                'reference_ranges' => 'nullable|array',
                'reference_ranges.*.gender_id' => 'nullable|exists:genders,gender_id',
                'reference_ranges.*.age_group_id' => 'nullable|exists:age_groups,age_group_id',
                'reference_ranges.*.race_id' => 'nullable|exists:races,race_id',
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
                'value_type' => $request->value_type,
                'method_id' => $request->method_id,
                'unit_id' => $request->unit_id,
                'container_id' => $request->container_id,
                'test_sequence' => $request->test_sequence,
                'min_value' => $request->min_value,
                'max_value' => $request->max_value,
                'critical_low' => $request->critical_low,
                'critical_high' => $request->critical_high,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? true,
            ]);

            // Save reference ranges if provided
            if ($request->has('reference_ranges') && is_array($request->reference_ranges)) {
                foreach ($request->reference_ranges as $range) {
                    $test->referenceRanges()->create([
                        'gender_id' => $range['gender_id'] ?? null,
                        'age_group_id' => $range['age_group_id'] ?? null,
                        'race_id' => $range['race_id'] ?? null,
                        'min_value' => null,
                        'max_value' => null,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test created successfully',
                'data' => $test->load(['method', 'unit', 'container', 'referenceRanges.gender', 'referenceRanges.ageGroup', 'referenceRanges.race'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create test: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
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
                ->with(['method', 'unit', 'container', 'referenceRanges.gender', 'referenceRanges.ageGroup', 'referenceRanges.race'])
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
                'value_type' => 'required|in:numeric,alphanumeric',
                'method_id' => 'nullable|exists:patho_test_methods,method_id',
                'unit_id' => 'nullable|exists:patho_test_units,unit_id',
                'container_id' => 'nullable|exists:patho_containers,container_id',
                'test_sequence' => 'nullable|integer',
                'min_value' => 'nullable|numeric',
                'max_value' => 'nullable|numeric',
                'critical_low' => 'nullable|numeric',
                'critical_high' => 'nullable|numeric',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
                'reference_ranges' => 'nullable|array',
                'reference_ranges.*.gender_id' => 'nullable|exists:genders,gender_id',
                'reference_ranges.*.age_group_id' => 'nullable|exists:age_groups,age_group_id',
                'reference_ranges.*.race_id' => 'nullable|exists:races,race_id',
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
                'value_type' => $request->value_type,
                'method_id' => $request->method_id,
                'unit_id' => $request->unit_id,
                'container_id' => $request->container_id,
                'test_sequence' => $request->test_sequence,
                'min_value' => $request->min_value,
                'max_value' => $request->max_value,
                'critical_low' => $request->critical_low,
                'critical_high' => $request->critical_high,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? $test->is_active,
            ]);

            // Update reference ranges if provided
            if ($request->has('reference_ranges')) {
                // Delete existing reference ranges
                $test->referenceRanges()->delete();

                // Create new reference ranges
                if (is_array($request->reference_ranges)) {
                    foreach ($request->reference_ranges as $range) {
                        $test->referenceRanges()->create([
                            'gender_id' => $range['gender_id'] ?? null,
                            'age_group_id' => $range['age_group_id'] ?? null,
                            'min_value' => $range['min_value'] ?? null,
                            'max_value' => $range['max_value'] ?? null,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test updated successfully',
                'data' => $test->load(['method', 'unit', 'container', 'referenceRanges.gender', 'referenceRanges.ageGroup', 'referenceRanges.race'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update test: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
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
                ->orderBy('test_sequence')
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
