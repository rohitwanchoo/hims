<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoFaculty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoFacultyController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoFaculty::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('faculty_name', 'like', "%{$search}%")
                      ->orWhere('faculty_code', 'like', "%{$search}%");
                });
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'faculty_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $faculties = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $faculties
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch faculties',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'faculty_name' => 'required|string|max:100',
                'faculty_code' => 'nullable|string|max:50',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $faculty = PathoFaculty::create([
                'hospital_id' => Auth::user()->hospital_id,
                'faculty_name' => $request->faculty_name,
                'faculty_code' => $request->faculty_code,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Faculty created successfully',
                'data' => $faculty
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create faculty',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $faculty = PathoFaculty::where('hospital_id', $hospitalId)
                ->where('faculty_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $faculty
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $faculty = PathoFaculty::where('hospital_id', $hospitalId)
                ->where('faculty_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'faculty_name' => 'required|string|max:100',
                'faculty_code' => 'nullable|string|max:50',
                'remarks' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $faculty->update([
                'faculty_name' => $request->faculty_name,
                'faculty_code' => $request->faculty_code,
                'remarks' => $request->remarks,
                'is_active' => $request->is_active ?? $faculty->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Faculty updated successfully',
                'data' => $faculty
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update faculty',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $faculty = PathoFaculty::where('hospital_id', $hospitalId)
                ->where('faculty_id', $id)
                ->firstOrFail();

            $faculty->delete();

            return response()->json([
                'success' => true,
                'message' => 'Faculty deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete faculty',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
