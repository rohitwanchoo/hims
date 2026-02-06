<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoTestMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoTestMethodController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTestMethod::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('method_name', 'like', "%{$search}%")
                      ->orWhere('method_code', 'like', "%{$search}%");
                });
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'method_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $methods = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $methods
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test methods',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'method_name' => 'required|string|max:100',
                'method_code' => 'nullable|string|max:50',
                'use_for_blood_bank' => 'boolean',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $method = PathoTestMethod::create([
                'hospital_id' => Auth::user()->hospital_id,
                'method_name' => $request->method_name,
                'method_code' => $request->method_code,
                'use_for_blood_bank' => $request->use_for_blood_bank ?? false,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test method created successfully',
                'data' => $method
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $method = PathoTestMethod::where('hospital_id', $hospitalId)
                ->where('method_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $method
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test method not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $method = PathoTestMethod::where('hospital_id', $hospitalId)
                ->where('method_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'method_name' => 'required|string|max:100',
                'method_code' => 'nullable|string|max:50',
                'use_for_blood_bank' => 'boolean',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $method->update([
                'method_name' => $request->method_name,
                'method_code' => $request->method_code,
                'use_for_blood_bank' => $request->use_for_blood_bank ?? $method->use_for_blood_bank,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? $method->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test method updated successfully',
                'data' => $method
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test method',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $method = PathoTestMethod::where('hospital_id', $hospitalId)
                ->where('method_id', $id)
                ->firstOrFail();

            $method->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test method deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test method',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
