<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drug;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function index(Request $request)
    {
        $query = Drug::with('category');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('drug_name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('drug_code', 'like', "%{$search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->where('is_active', true)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'drug_code' => 'required|unique:drugs',
            'drug_name' => 'required',
            'generic_name' => 'nullable|string',
            'category_id' => 'nullable|exists:drug_categories,category_id',
            'manufacturer' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|integer',
            'requires_prescription' => 'boolean',
            'schedule_id' => 'nullable|exists:drug_schedules,schedule_id',
        ]);

        $drug = Drug::create($validated);
        return response()->json($drug, 201);
    }

    public function show(string $id)
    {
        $drug = Drug::with(['category', 'batches'])->findOrFail($id);
        return response()->json($drug);
    }

    public function update(Request $request, string $id)
    {
        $drug = Drug::findOrFail($id);

        $validated = $request->validate([
            'drug_code' => 'required|unique:drugs,drug_code,' . $id . ',drug_id',
            'drug_name' => 'required',
            'generic_name' => 'nullable|string',
            'category_id' => 'nullable|exists:drug_categories,category_id',
            'manufacturer' => 'nullable|string',
            'unit_of_measure' => 'nullable|string',
            'unit_price' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|integer',
            'requires_prescription' => 'boolean',
            'schedule_id' => 'nullable|exists:drug_schedules,schedule_id',
            'is_active' => 'boolean',
        ]);

        $drug->update($validated);
        return response()->json($drug);
    }

    public function destroy(string $id)
    {
        $drug = Drug::findOrFail($id);
        $drug->update(['is_active' => false]);
        return response()->json(['message' => 'Drug deactivated']);
    }
}
