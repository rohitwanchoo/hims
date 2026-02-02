<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoTestUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoTestUnitController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTestUnit::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('unit_name', 'like', "%{$search}%")
                      ->orWhere('unit_code', 'like', "%{$search}%");
                });
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'unit_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $units = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test units',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'unit_name' => 'required|string|max:100',
                'unit_code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $unit = PathoTestUnit::create([
                'hospital_id' => Auth::user()->hospital_id,
                'unit_name' => $request->unit_name,
                'unit_code' => $request->unit_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test unit created successfully',
                'data' => $unit
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $unit = PathoTestUnit::where('hospital_id', $hospitalId)
                ->where('unit_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test unit not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $unit = PathoTestUnit::where('hospital_id', $hospitalId)
                ->where('unit_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'unit_name' => 'required|string|max:100',
                'unit_code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $unit->update([
                'unit_name' => $request->unit_name,
                'unit_code' => $request->unit_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $unit->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test unit updated successfully',
                'data' => $unit
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $unit = PathoTestUnit::where('hospital_id', $hospitalId)
                ->where('unit_id', $id)
                ->firstOrFail();

            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test unit deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test unit',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
