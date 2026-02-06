<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoSampleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoSampleTypeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoSampleType::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('sample_type_name', 'like', "%{$search}%");
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'sample_type_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $sampleTypes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $sampleTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sample types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sample_type_name' => 'required|string|max:100',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $sampleType = PathoSampleType::create([
                'hospital_id' => Auth::user()->hospital_id,
                'sample_type_name' => $request->sample_type_name,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sample type created successfully',
                'data' => $sampleType
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sample type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $sampleType = PathoSampleType::where('hospital_id', $hospitalId)
                ->where('sample_type_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $sampleType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sample type not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $sampleType = PathoSampleType::where('hospital_id', $hospitalId)
                ->where('sample_type_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'sample_type_name' => 'required|string|max:100',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $sampleType->update([
                'sample_type_name' => $request->sample_type_name,
                'is_active' => $request->is_active ?? $sampleType->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sample type updated successfully',
                'data' => $sampleType
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sample type',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $sampleType = PathoSampleType::where('hospital_id', $hospitalId)
                ->where('sample_type_id', $id)
                ->firstOrFail();

            $sampleType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sample type deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sample type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
