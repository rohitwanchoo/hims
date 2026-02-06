<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoSensitivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoSensitivityController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoSensitivity::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('sensitivity_name', 'like', "%{$search}%");
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'sensitivity_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $sensitivities = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $sensitivities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sensitivities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sensitivity_name' => 'required|string|max:100',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $sensitivity = PathoSensitivity::create([
                'hospital_id' => Auth::user()->hospital_id,
                'sensitivity_name' => $request->sensitivity_name,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sensitivity created successfully',
                'data' => $sensitivity
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sensitivity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $sensitivity = PathoSensitivity::where('hospital_id', $hospitalId)
                ->where('sensitivity_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $sensitivity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sensitivity not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $sensitivity = PathoSensitivity::where('hospital_id', $hospitalId)
                ->where('sensitivity_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'sensitivity_name' => 'required|string|max:100',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $sensitivity->update([
                'sensitivity_name' => $request->sensitivity_name,
                'is_active' => $request->is_active ?? $sensitivity->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sensitivity updated successfully',
                'data' => $sensitivity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sensitivity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $sensitivity = PathoSensitivity::where('hospital_id', $hospitalId)
                ->where('sensitivity_id', $id)
                ->firstOrFail();

            $sensitivity->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sensitivity deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sensitivity',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
