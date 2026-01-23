<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemStock;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->item_type) {
            $query->where('item_type', $request->item_type);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('item_code', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('low_stock')) {
            $query->whereHas('stock', function ($q) {
                $q->whereRaw('quantity <= reorder_level');
            });
        }

        $items = $query->orderBy('item_name')
            ->paginate($request->per_page ?? 50);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:30',
            'item_name' => 'required|string|max:200',
            'generic_name' => 'nullable|string|max:200',
            'category_id' => 'required|exists:item_categories,category_id',
            'item_type' => 'required|in:consumable,equipment,implant,reagent,drug,general',
            'unit_of_measure' => 'required|string|max:20',
            'hsn_code' => 'nullable|string|max:20',
            'gst_percent' => 'nullable|numeric|min:0|max:100',
            'reorder_level' => 'nullable|integer|min:0',
            'is_batch_tracked' => 'nullable|boolean',
            'is_expiry_tracked' => 'nullable|boolean',
            'manufacturer' => 'nullable|string|max:100',
            'storage_conditions' => 'nullable|string',
        ]);

        $item = Item::create($validated);

        return response()->json([
            'message' => 'Item created successfully',
            'item' => $item->load('category'),
        ], 201);
    }

    public function show(Item $item)
    {
        return response()->json([
            'item' => $item->load('category'),
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'item_name' => 'sometimes|string|max:200',
            'generic_name' => 'nullable|string|max:200',
            'category_id' => 'sometimes|exists:item_categories,category_id',
            'item_type' => 'sometimes|in:consumable,equipment,implant,reagent,drug,general',
            'unit_of_measure' => 'sometimes|string|max:20',
            'hsn_code' => 'nullable|string|max:20',
            'gst_percent' => 'nullable|numeric|min:0|max:100',
            'reorder_level' => 'nullable|integer|min:0',
            'is_batch_tracked' => 'nullable|boolean',
            'is_expiry_tracked' => 'nullable|boolean',
            'manufacturer' => 'nullable|string|max:100',
            'storage_conditions' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully',
            'item' => $item->load('category'),
        ]);
    }

    public function stock(Request $request, Item $item)
    {
        $query = ItemStock::where('item_id', $item->item_id)
            ->with('store');

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $stock = $query->get();

        $summary = [
            'total_quantity' => $stock->sum('quantity'),
            'total_reserved' => $stock->sum('reserved_quantity'),
            'available' => $stock->sum('quantity') - $stock->sum('reserved_quantity'),
            'stores' => $stock->count(),
        ];

        return response()->json([
            'item' => $item,
            'stock' => $stock,
            'summary' => $summary,
        ]);
    }

    public function lowStock(Request $request)
    {
        $query = Item::with(['category', 'stocks' => function ($q) {
            $q->select('item_id')
                ->selectRaw('SUM(quantity) as total_quantity')
                ->groupBy('item_id');
        }])
            ->where('is_active', true)
            ->whereHas('stocks', function ($q) {
                $q->havingRaw('SUM(quantity) <= ?', [0]);
            });

        $items = $query->get()->filter(function ($item) {
            $totalStock = $item->stocks->sum('total_quantity');
            return $totalStock <= $item->reorder_level;
        });

        return response()->json(['items' => $items->values()]);
    }

    public function expiringItems(Request $request)
    {
        $days = $request->days ?? 30;
        $expiryDate = now()->addDays($days);

        $stock = ItemStock::with(['item', 'store'])
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $expiryDate)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get();

        return response()->json(['expiring_stock' => $stock]);
    }
}
