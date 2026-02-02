<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoTestGroup;
use App\Models\PathoTestGroupMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PathoTestGroupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTestGroup::where('hospital_id', $hospitalId)
                ->with(['category', 'tests']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('group_name', 'like', "%{$search}%")
                      ->orWhere('group_code', 'like', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'group_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $groups = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'group_name' => 'required|string|max:200',
                'group_code' => 'nullable|string|max:50',
                'category_id' => 'nullable|exists:patho_test_categories,category_id',
                'description' => 'nullable|string',
                'display_order' => 'nullable|integer',
                'price' => 'nullable|numeric|min:0',
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

            $group = PathoTestGroup::create([
                'hospital_id' => Auth::user()->hospital_id,
                'group_name' => $request->group_name,
                'group_code' => $request->group_code,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'display_order' => $request->display_order ?? 0,
                'price' => $request->price ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);

            // Map tests to group
            if ($request->filled('tests')) {
                foreach ($request->tests as $testId) {
                    PathoTestGroupMap::create([
                        'group_id' => $group->group_id,
                        'test_id' => $testId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test group created successfully',
                'data' => $group->load(['category', 'tests'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $id)
                ->with(['category', 'tests'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $group
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test group not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'group_name' => 'required|string|max:200',
                'group_code' => 'nullable|string|max:50',
                'category_id' => 'nullable|exists:patho_test_categories,category_id',
                'description' => 'nullable|string',
                'display_order' => 'nullable|integer',
                'price' => 'nullable|numeric|min:0',
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

            $group->update([
                'group_name' => $request->group_name,
                'group_code' => $request->group_code,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'display_order' => $request->display_order ?? $group->display_order,
                'price' => $request->price ?? $group->price,
                'is_active' => $request->is_active ?? $group->is_active,
            ]);

            // Update test mappings
            if ($request->has('tests')) {
                // Delete existing mappings
                PathoTestGroupMap::where('group_id', $group->group_id)->delete();

                // Create new mappings
                if (is_array($request->tests)) {
                    foreach ($request->tests as $testId) {
                        PathoTestGroupMap::create([
                            'group_id' => $group->group_id,
                            'test_id' => $testId,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test group updated successfully',
                'data' => $group->load(['category', 'tests'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $id)
                ->firstOrFail();

            DB::beginTransaction();

            // Delete test mappings
            PathoTestGroupMap::where('group_id', $group->group_id)->delete();

            // Delete group
            $group->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test group deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mapTests(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $id)
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
            PathoTestGroupMap::where('group_id', $group->group_id)->delete();

            // Create new mappings
            foreach ($request->tests as $testId) {
                PathoTestGroupMap::create([
                    'group_id' => $group->group_id,
                    'test_id' => $testId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tests mapped successfully',
                'data' => $group->load('tests')
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
