<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\SkillPathoTestMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SkillPathoTestMapController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->with(['department', 'test']);

            // Department filter
            if ($request->filled('department_id')) {
                $query->where('department_id', $request->department_id);
            }

            // Test filter
            if ($request->filled('test_id')) {
                $query->where('test_id', $request->test_id);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $mappings = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $mappings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch skill test mappings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'department_id' => 'required|exists:departments,department_id',
                'test_id' => 'required|exists:patho_tests,test_id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if mapping already exists
            $existingMapping = SkillPathoTestMap::where('hospital_id', Auth::user()->hospital_id)
                ->where('department_id', $request->department_id)
                ->where('test_id', $request->test_id)
                ->first();

            if ($existingMapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapping already exists'
                ], 422);
            }

            $mapping = SkillPathoTestMap::create([
                'hospital_id' => Auth::user()->hospital_id,
                'department_id' => $request->department_id,
                'test_id' => $request->test_id,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Department test mapping created successfully',
                'data' => $mapping->load(['department', 'test'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create skill test mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->with(['department', 'test'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $mapping
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Skill test mapping not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'department_id' => 'required|exists:departments,department_id',
                'test_id' => 'required|exists:patho_tests,test_id',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if mapping already exists (excluding current mapping)
            $existingMapping = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('department_id', $request->department_id)
                ->where('test_id', $request->test_id)
                ->where('map_id', '!=', $id)
                ->first();

            if ($existingMapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mapping already exists'
                ], 422);
            }

            $mapping->update([
                'department_id' => $request->department_id,
                'test_id' => $request->test_id,
                'is_active' => $request->is_active ?? $mapping->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Department test mapping updated successfully',
                'data' => $mapping->load(['department', 'test'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update skill test mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->firstOrFail();

            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Skill test mapping deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete skill test mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTestsByDepartment($departmentId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            // Get tests mapped to this department
            $mappings = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('department_id', $departmentId)
                ->where('is_active', true)
                ->with(['test'])
                ->get();

            // Extract just the tests
            $tests = $mappings->map(function ($mapping) {
                return $mapping->test;
            });

            return response()->json([
                'success' => true,
                'data' => $tests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tests for department',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDepartmentsByTest($testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mappings = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('test_id', $testId)
                ->where('is_active', true)
                ->with(['department'])
                ->get();

            // Extract just the departments
            $departments = $mappings->map(function ($mapping) {
                return $mapping->department;
            });

            return response()->json([
                'success' => true,
                'data' => $departments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch departments for test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyByDepartmentAndTest($departmentId, $testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('department_id', $departmentId)
                ->where('test_id', $testId)
                ->firstOrFail();

            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Department test mapping deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete department test mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
