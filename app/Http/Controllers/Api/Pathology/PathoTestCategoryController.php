<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoTestCategory;
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
                ->with(['faculty']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('category_name', 'like', "%{$search}%")
                      ->orWhere('category_code', 'like', "%{$search}%");
                });
            }

            // Faculty filter
            if ($request->filled('faculty_id')) {
                $query->where('faculty_id', $request->faculty_id);
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
                'faculty_id' => 'nullable|exists:patho_faculties,faculty_id',
                'description' => 'nullable|string',
                'display_order' => 'nullable|integer',
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
                'faculty_id' => $request->faculty_id,
                'description' => $request->description,
                'display_order' => $request->display_order ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test category created successfully',
                'data' => $category->load('faculty')
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
                ->with(['faculty'])
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
                'faculty_id' => 'nullable|exists:patho_faculties,faculty_id',
                'description' => 'nullable|string',
                'display_order' => 'nullable|integer',
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
                'faculty_id' => $request->faculty_id,
                'description' => $request->description,
                'display_order' => $request->display_order ?? $category->display_order,
                'is_active' => $request->is_active ?? $category->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test category updated successfully',
                'data' => $category->load('faculty')
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
}
