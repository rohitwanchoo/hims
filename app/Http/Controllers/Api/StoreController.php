<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::with('inCharge');

        if ($request->store_type) {
            $query->where('store_type', $request->store_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $stores = $query->orderBy('store_name')->get();

        return response()->json(['stores' => $stores]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_code' => 'required|string|max:20',
            'store_name' => 'required|string|max:100',
            'store_type' => 'required|in:main,sub,pharmacy,lab,ot,radiology',
            'in_charge_id' => 'nullable|exists:users,user_id',
            'location' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
        ]);

        $store = Store::create($validated);

        return response()->json([
            'message' => 'Store created successfully',
            'store' => $store,
        ], 201);
    }

    public function show(Store $store)
    {
        return response()->json([
            'store' => $store->load('inCharge'),
        ]);
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'store_name' => 'sometimes|string|max:100',
            'store_type' => 'sometimes|in:main,sub,pharmacy,lab,ot,radiology',
            'in_charge_id' => 'nullable|exists:users,user_id',
            'location' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'is_active' => 'nullable|boolean',
        ]);

        $store->update($validated);

        return response()->json([
            'message' => 'Store updated successfully',
            'store' => $store,
        ]);
    }

    public function destroy(Store $store)
    {
        if ($store->stock()->exists()) {
            return response()->json([
                'message' => 'Cannot delete store with existing stock',
            ], 422);
        }

        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully',
        ]);
    }
}
