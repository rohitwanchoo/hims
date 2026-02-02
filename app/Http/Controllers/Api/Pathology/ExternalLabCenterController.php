<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\ExternalLabCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExternalLabCenterController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = ExternalLabCenter::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('lab_name', 'like', "%{$search}%")
                      ->orWhere('lab_code', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // City filter
            if ($request->filled('city')) {
                $query->where('city', 'like', "%{$request->city}%");
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'lab_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $labCenters = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $labCenters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch external lab centers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'lab_name' => 'required|string|max:200',
                'lab_code' => 'nullable|string|max:50',
                'contact_person' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:10',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $labCenter = ExternalLabCenter::create([
                'hospital_id' => Auth::user()->hospital_id,
                'lab_name' => $request->lab_name,
                'lab_code' => $request->lab_code,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'External lab center created successfully',
                'data' => $labCenter
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create external lab center',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $labCenter = ExternalLabCenter::where('hospital_id', $hospitalId)
                ->where('lab_center_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $labCenter
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'External lab center not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $labCenter = ExternalLabCenter::where('hospital_id', $hospitalId)
                ->where('lab_center_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'lab_name' => 'required|string|max:200',
                'lab_code' => 'nullable|string|max:50',
                'contact_person' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:10',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $labCenter->update([
                'lab_name' => $request->lab_name,
                'lab_code' => $request->lab_code,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'pincode' => $request->pincode,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $labCenter->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'External lab center updated successfully',
                'data' => $labCenter
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update external lab center',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $labCenter = ExternalLabCenter::where('hospital_id', $hospitalId)
                ->where('lab_center_id', $id)
                ->firstOrFail();

            $labCenter->delete();

            return response()->json([
                'success' => true,
                'message' => 'External lab center deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete external lab center',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
