<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoContainer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoContainerController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoContainer::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('container_name', 'like', "%{$search}%")
                      ->orWhere('container_code', 'like', "%{$search}%");
                });
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'container_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $containers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $containers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch containers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'container_name' => 'required|string|max:100',
                'container_code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $container = PathoContainer::create([
                'hospital_id' => Auth::user()->hospital_id,
                'container_name' => $request->container_name,
                'container_code' => $request->container_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Container created successfully',
                'data' => $container
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create container',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $container = PathoContainer::where('hospital_id', $hospitalId)
                ->where('container_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $container
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Container not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $container = PathoContainer::where('hospital_id', $hospitalId)
                ->where('container_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'container_name' => 'required|string|max:100',
                'container_code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $container->update([
                'container_name' => $request->container_name,
                'container_code' => $request->container_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $container->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Container updated successfully',
                'data' => $container
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update container',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $container = PathoContainer::where('hospital_id', $hospitalId)
                ->where('container_id', $id)
                ->firstOrFail();

            $container->delete();

            return response()->json([
                'success' => true,
                'message' => 'Container deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete container',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
