<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabCategory;
use Illuminate\Http\Request;

class LabCategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            LabCategory::withCount('tests')->where('is_active', true)->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|unique:lab_categories',
            'description' => 'nullable|string',
        ]);

        $category = LabCategory::create($validated);
        return response()->json($category, 201);
    }

    public function show(string $id)
    {
        $category = LabCategory::with('tests')->findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = LabCategory::findOrFail($id);

        $validated = $request->validate([
            'category_name' => 'required|unique:lab_categories,category_name,' . $id . ',category_id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    public function destroy(string $id)
    {
        $category = LabCategory::findOrFail($id);
        $category->update(['is_active' => false]);
        return response()->json(['message' => 'Category deactivated']);
    }
}
