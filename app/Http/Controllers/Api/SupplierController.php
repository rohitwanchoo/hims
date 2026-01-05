<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('suppliers')
            ->where('hospital_id', $request->user()->hospital_id);

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('vendor_name', 'like', '%' . $request->search . '%')
                  ->orWhere('vendor_code', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $suppliers = $query->orderBy('vendor_name')->get();

        return response()->json([
            'vendors' => $suppliers,
            'suppliers' => $suppliers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_name' => 'required|string|max:255',
            'vendor_code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|string|max:100',
            'credit_days' => 'nullable|integer'
        ]);

        $id = DB::table('suppliers')->insertGetId([
            'hospital_id' => $request->user()->hospital_id,
            'vendor_name' => $request->vendor_name,
            'vendor_code' => $request->vendor_code ?? $this->generateVendorCode(),
            'contact_person' => $request->contact_person,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'gst_number' => $request->gst_number,
            'pan_number' => $request->pan_number,
            'payment_terms' => $request->payment_terms,
            'credit_days' => $request->credit_days ?? 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Supplier created successfully',
            'vendor_id' => $id
        ], 201);
    }

    public function show($id)
    {
        $supplier = DB::table('suppliers')->find($id);

        if (!$supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        return response()->json(['vendor' => $supplier, 'supplier' => $supplier]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'vendor_name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:20',
            'payment_terms' => 'nullable|string|max:100',
            'credit_days' => 'nullable|integer',
            'is_active' => 'boolean'
        ]);

        DB::table('suppliers')
            ->where('vendor_id', $id)
            ->update([
                'vendor_name' => $request->vendor_name,
                'contact_person' => $request->contact_person,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'gst_number' => $request->gst_number,
                'pan_number' => $request->pan_number,
                'payment_terms' => $request->payment_terms,
                'credit_days' => $request->credit_days,
                'is_active' => $request->is_active ?? true,
                'updated_at' => now()
            ]);

        return response()->json(['message' => 'Supplier updated successfully']);
    }

    public function destroy($id)
    {
        // Check if supplier has purchase orders
        $hasOrders = DB::table('purchase_orders')
            ->where('vendor_id', $id)
            ->exists();

        if ($hasOrders) {
            return response()->json([
                'message' => 'Cannot delete supplier with existing purchase orders'
            ], 422);
        }

        DB::table('suppliers')->where('vendor_id', $id)->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    private function generateVendorCode()
    {
        $lastVendor = DB::table('suppliers')
            ->orderBy('vendor_id', 'desc')
            ->first();

        $nextNumber = $lastVendor ? ($lastVendor->vendor_id + 1) : 1;
        return 'VND' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
