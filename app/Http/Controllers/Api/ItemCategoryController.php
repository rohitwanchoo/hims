<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('item_categories')
            ->where('hospital_id', $request->user()->hospital_id);

        if ($request->has('search')) {
            $query->where('category_name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('category_name')->get();

        return response()->json([
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer'
        ]);

        $id = DB::table('item_categories')->insertGetId([
            'hospital_id' => $request->user()->hospital_id,
            'category_name' => $request->category_name,
            'category_code' => $request->category_code,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category_id' => $id
        ], 201);
    }

    public function show($id)
    {
        $category = DB::table('item_categories')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json(['category' => $category]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        DB::table('item_categories')
            ->where('category_id', $id)
            ->update([
                'category_name' => $request->category_name,
                'category_code' => $request->category_code,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'updated_at' => now()
            ]);

        return response()->json(['message' => 'Category updated successfully']);
    }

    public function destroy($id)
    {
        // Check if category has items
        $hasItems = DB::table('inventory_items')
            ->where('category_id', $id)
            ->exists();

        if ($hasItems) {
            return response()->json([
                'message' => 'Cannot delete category with items'
            ], 422);
        }

        DB::table('item_categories')->where('category_id', $id)->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
