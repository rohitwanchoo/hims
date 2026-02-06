<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoTestGroup;
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
                ->withCount('pathoTests');

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('group_name', 'like', "%{$search}%")
                      ->orWhere('group_code', 'like', "%{$search}%");
                });
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
                'is_default_group' => 'boolean',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $group = PathoTestGroup::create([
                'hospital_id' => Auth::user()->hospital_id,
                'group_name' => $request->group_name,
                'group_code' => $request->group_code,
                'is_default_group' => $request->is_default_group ?? false,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test group created successfully',
                'data' => $group
            ], 201);
        } catch (\Exception $e) {
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
                ->withCount('pathoTests')
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
                'is_default_group' => 'boolean',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $group->update([
                'group_name' => $request->group_name,
                'group_code' => $request->group_code,
                'is_default_group' => $request->is_default_group ?? $group->is_default_group,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? $group->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test group updated successfully',
                'data' => $group
            ]);
        } catch (\Exception $e) {
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

            // Delete test mappings if relationship exists
            if (method_exists($group, 'pathoTests')) {
                $group->pathoTests()->delete();
            }

            $group->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test group deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getGroupTests($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $id)
                ->firstOrFail();

            // Load tests if relationship exists
            $tests = method_exists($group, 'pathoTests') ? $group->pathoTests : [];

            return response()->json([
                'success' => true,
                'data' => $tests
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch group tests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function addTestToGroup(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $id)
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

            if (method_exists($group, 'pathoTests')) {
                // Since pathoTests is hasMany, we need to update the test's group_id
                \App\Models\Pathology\PathoTest::where('test_id', $request->test_id)->update(['group_id' => $group->group_id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test added to group successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add test to group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function removeTestFromGroup($groupId, $testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $group = PathoTestGroup::where('hospital_id', $hospitalId)
                ->where('group_id', $groupId)
                ->firstOrFail();

            if (method_exists($group, 'pathoTests')) {
                // Since pathoTests is hasMany, we need to set the test's group_id to null
                \App\Models\Pathology\PathoTest::where('test_id', $testId)->where('group_id', $group->group_id)->update(['group_id' => null]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test removed from group successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove test from group',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
