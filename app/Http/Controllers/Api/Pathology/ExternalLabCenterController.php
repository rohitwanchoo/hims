<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\ExternalLabCenter;
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
                      ->orWhere('contact_person', 'like', "%{$search}%")
                      ->orWhere('telephone', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%");
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
                'lab_name' => 'required|string|max:255',
                'has_patho_test' => 'boolean',
                'has_radio_test' => 'boolean',
                'has_procedure_test' => 'boolean',
                'contact_person' => 'nullable|string|max:255',
                'telephone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'pincode' => 'nullable|string|max:10',
                'fax' => 'nullable|string|max:20',
                'website' => 'nullable|string|max:255',
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
                'has_patho_test' => $request->has_patho_test ?? false,
                'has_radio_test' => $request->has_radio_test ?? false,
                'has_procedure_test' => $request->has_procedure_test ?? false,
                'contact_person' => $request->contact_person,
                'telephone' => $request->telephone,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'district' => $request->district,
                'state' => $request->state,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'fax' => $request->fax,
                'website' => $request->website,
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
                ->where('lab_id', $id)
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
                ->where('lab_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'lab_name' => 'required|string|max:255',
                'has_patho_test' => 'boolean',
                'has_radio_test' => 'boolean',
                'has_procedure_test' => 'boolean',
                'contact_person' => 'nullable|string|max:255',
                'telephone' => 'nullable|string|max:20',
                'mobile' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'pincode' => 'nullable|string|max:10',
                'fax' => 'nullable|string|max:20',
                'website' => 'nullable|string|max:255',
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
                'has_patho_test' => $request->has_patho_test ?? $labCenter->has_patho_test,
                'has_radio_test' => $request->has_radio_test ?? $labCenter->has_radio_test,
                'has_procedure_test' => $request->has_procedure_test ?? $labCenter->has_procedure_test,
                'contact_person' => $request->contact_person,
                'telephone' => $request->telephone,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'district' => $request->district,
                'state' => $request->state,
                'country' => $request->country,
                'pincode' => $request->pincode,
                'fax' => $request->fax,
                'website' => $request->website,
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
                ->where('lab_id', $id)
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
