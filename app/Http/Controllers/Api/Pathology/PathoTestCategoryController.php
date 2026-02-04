<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoTestCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoTestCategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTestCategory::where('hospital_id', $hospitalId)
                ->with(['parent']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('category_name', 'like', "%{$search}%")
                      ->orWhere('category_code', 'like', "%{$search}%");
                });
            }

            // Parent category filter
            if ($request->filled('parent_category_id')) {
                $query->where('parent_category_id', $request->parent_category_id);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'category_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $categories = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_name' => 'required|string|max:100',
                'category_code' => 'nullable|string|max:50',
                'parent_category_id' => 'nullable|exists:patho_test_categories,category_id',
                'fit_100' => 'boolean',
                'has_sub_category' => 'boolean',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $category = PathoTestCategory::create([
                'hospital_id' => Auth::user()->hospital_id,
                'category_name' => $request->category_name,
                'category_code' => $request->category_code,
                'parent_category_id' => $request->parent_category_id,
                'fit_100' => $request->fit_100 ?? false,
                'has_sub_category' => $request->has_sub_category ?? false,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test category created successfully',
                'data' => $category->load('parent')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $category = PathoTestCategory::where('hospital_id', $hospitalId)
                ->where('category_id', $id)
                ->with(['parent', 'pathoTests'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test category not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $category = PathoTestCategory::where('hospital_id', $hospitalId)
                ->where('category_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'category_name' => 'required|string|max:100',
                'category_code' => 'nullable|string|max:50',
                'parent_category_id' => 'nullable|exists:patho_test_categories,category_id',
                'fit_100' => 'boolean',
                'has_sub_category' => 'boolean',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $category->update([
                'category_name' => $request->category_name,
                'category_code' => $request->category_code,
                'parent_category_id' => $request->parent_category_id,
                'fit_100' => $request->fit_100 ?? $category->fit_100,
                'has_sub_category' => $request->has_sub_category ?? $category->has_sub_category,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? $category->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test category updated successfully',
                'data' => $category->load('parent')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $category = PathoTestCategory::where('hospital_id', $hospitalId)
                ->where('category_id', $id)
                ->firstOrFail();

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a test to this category
     */
    public function addTest(Request $request, $categoryId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            // Verify category belongs to hospital
            $category = PathoTestCategory::where('hospital_id', $hospitalId)
                ->where('category_id', $categoryId)
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

            // Update test's category
            $test = \App\Models\Pathology\PathoTest::where('test_id', $request->test_id)
                ->where('hospital_id', $hospitalId)
                ->firstOrFail();

            $test->update(['category_id' => $categoryId]);

            return response()->json([
                'success' => true,
                'message' => 'Test added to category successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add test to category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a test from this category
     */
    public function removeTest($categoryId, $testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            // Verify category belongs to hospital
            PathoTestCategory::where('hospital_id', $hospitalId)
                ->where('category_id', $categoryId)
                ->firstOrFail();

            // Update test's category to null
            $test = \App\Models\Pathology\PathoTest::where('test_id', $testId)
                ->where('hospital_id', $hospitalId)
                ->where('category_id', $categoryId)
                ->firstOrFail();

            $test->update(['category_id' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Test removed from category successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove test from category',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
