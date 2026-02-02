<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathologistDoctorMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathologistDoctorMapController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->with(['doctor']);

            // Doctor filter
            if ($request->filled('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
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
                'message' => 'Failed to fetch pathologist mappings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:doctors,doctor_id',
                'signature_path' => 'nullable|string|max:255',
                'is_default' => 'boolean',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // If this is default, unset other defaults
            if ($request->is_default) {
                PathologistDoctorMap::where('hospital_id', Auth::user()->hospital_id)
                    ->update(['is_default' => false]);
            }

            $mapping = PathologistDoctorMap::create([
                'hospital_id' => Auth::user()->hospital_id,
                'doctor_id' => $request->doctor_id,
                'signature_path' => $request->signature_path,
                'is_default' => $request->is_default ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pathologist mapping created successfully',
                'data' => $mapping->load('doctor')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create pathologist mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->with(['doctor'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $mapping
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pathologist mapping not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'doctor_id' => 'required|exists:doctors,doctor_id',
                'signature_path' => 'nullable|string|max:255',
                'is_default' => 'boolean',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // If this is being set as default, unset other defaults
            if ($request->is_default && !$mapping->is_default) {
                PathologistDoctorMap::where('hospital_id', $hospitalId)
                    ->where('map_id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $mapping->update([
                'doctor_id' => $request->doctor_id,
                'signature_path' => $request->signature_path,
                'is_default' => $request->is_default ?? $mapping->is_default,
                'is_active' => $request->is_active ?? $mapping->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pathologist mapping updated successfully',
                'data' => $mapping->load('doctor')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pathologist mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->firstOrFail();

            $mapping->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pathologist mapping deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pathologist mapping',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDefaultPathologist()
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->where('is_default', true)
                ->where('is_active', true)
                ->with(['doctor'])
                ->first();

            if (!$mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'No default pathologist found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $mapping
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch default pathologist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function setDefault($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $mapping = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->where('map_id', $id)
                ->firstOrFail();

            // Unset all defaults
            PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->update(['is_default' => false]);

            // Set this as default
            $mapping->update(['is_default' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Default pathologist set successfully',
                'data' => $mapping->load('doctor')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to set default pathologist',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
