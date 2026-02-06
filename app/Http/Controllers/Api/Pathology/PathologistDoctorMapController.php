<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathologistDoctorMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PathologistDoctorMapController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathologistDoctorMap::where('hospital_id', $hospitalId)
                ->with(['doctor', 'faculty']);

            // Faculty filter
            if ($request->filled('faculty_id')) {
                $query->where('faculty_id', $request->faculty_id);
            }

            // Doctor filter
            if ($request->filled('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('doctor', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
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
                'faculty_id' => 'required|exists:patho_faculties,faculty_id',
                'doctor_id' => 'required|exists:doctors,doctor_id',
                'skill_set_id' => 'nullable|integer',
                'signature' => 'nullable|image|max:2048',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $signaturePath = null;
            if ($request->hasFile('signature')) {
                $file = $request->file('signature');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $signaturePath = $file->storeAs('pathologist_signatures', $filename, 'public');
            }

            $mapping = PathologistDoctorMap::create([
                'hospital_id' => Auth::user()->hospital_id,
                'faculty_id' => $request->faculty_id,
                'doctor_id' => $request->doctor_id,
                'skill_set_id' => $request->skill_set_id,
                'signature_path' => $signaturePath,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pathologist mapping created successfully',
                'data' => $mapping->load(['doctor', 'faculty'])
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create pathologist mapping: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
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
                ->with(['doctor', 'faculty'])
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
                'faculty_id' => 'required|exists:patho_faculties,faculty_id',
                'doctor_id' => 'required|exists:doctors,doctor_id',
                'skill_set_id' => 'nullable|integer',
                'signature' => 'nullable|image|max:2048',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [
                'faculty_id' => $request->faculty_id,
                'doctor_id' => $request->doctor_id,
                'skill_set_id' => $request->skill_set_id,
                'is_active' => $request->is_active ?? $mapping->is_active,
            ];

            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($mapping->signature_path && Storage::disk('public')->exists($mapping->signature_path)) {
                    Storage::disk('public')->delete($mapping->signature_path);
                }

                $file = $request->file('signature');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $updateData['signature_path'] = $file->storeAs('pathologist_signatures', $filename, 'public');
            }

            $mapping->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Pathologist mapping updated successfully',
                'data' => $mapping->load(['doctor', 'faculty'])
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

}
