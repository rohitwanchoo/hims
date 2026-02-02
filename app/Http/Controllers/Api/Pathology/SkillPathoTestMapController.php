<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\SkillPathoTestMap;
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
                ->with(['user', 'test']);

            // User filter
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
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
                'user_id' => 'required|exists:users,user_id',
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
                ->where('user_id', $request->user_id)
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
                'user_id' => $request->user_id,
                'test_id' => $request->test_id,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Skill test mapping created successfully',
                'data' => $mapping->load(['user', 'test'])
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
                ->with(['user', 'test'])
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
                'user_id' => 'required|exists:users,user_id',
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
                ->where('user_id', $request->user_id)
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
                'user_id' => $request->user_id,
                'test_id' => $request->test_id,
                'is_active' => $request->is_active ?? $mapping->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Skill test mapping updated successfully',
                'data' => $mapping->load(['user', 'test'])
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

    public function getTestsByUser($userId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mappings = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->with(['test'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $mappings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tests for user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getUsersByTest($testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mappings = SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('test_id', $testId)
                ->where('is_active', true)
                ->with(['user'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $mappings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users for test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkMapTests(Request $request, $userId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $validator = Validator::make($request->all(), [
                'test_ids' => 'required|array',
                'test_ids.*' => 'exists:patho_tests,test_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Delete existing mappings for this user
            SkillPathoTestMap::where('hospital_id', $hospitalId)
                ->where('user_id', $userId)
                ->delete();

            // Create new mappings
            foreach ($request->test_ids as $testId) {
                SkillPathoTestMap::create([
                    'hospital_id' => $hospitalId,
                    'user_id' => $userId,
                    'test_id' => $testId,
                    'is_active' => true,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tests mapped successfully'
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
