<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RaceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = Race::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('race_name', 'like', "%{$search}%")
                      ->orWhere('race_code', 'like', "%{$search}%");
                });
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'race_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $races = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $races
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch races',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'race_name' => 'required|string|max:100',
                'race_code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $race = Race::create([
                'hospital_id' => Auth::user()->hospital_id,
                'race_name' => $request->race_name,
                'race_code' => $request->race_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Race created successfully',
                'data' => $race
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create race',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $race = Race::where('hospital_id', $hospitalId)
                ->where('race_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $race
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Race not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $race = Race::where('hospital_id', $hospitalId)
                ->where('race_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'race_name' => 'required|string|max:100',
                'race_code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $race->update([
                'race_name' => $request->race_name,
                'race_code' => $request->race_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $race->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Race updated successfully',
                'data' => $race
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update race',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $race = Race::where('hospital_id', $hospitalId)
                ->where('race_id', $id)
                ->firstOrFail();

            $race->delete();

            return response()->json([
                'success' => true,
                'message' => 'Race deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete race',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
