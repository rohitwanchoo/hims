<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CancelReason;
use Illuminate\Http\Request;

class CancelReasonController extends Controller
{
    public function index(Request $request)
    {
        $query = CancelReason::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reason_name', 'like', "%{$request->search}%")
                  ->orWhere('reason_code', 'like', "%{$request->search}%");
            });
        }

        if ($request->applicable_for) {
            $query->where(function ($q) use ($request) {
                $q->where('applicable_for', $request->applicable_for)
                  ->orWhere('applicable_for', 'both');
            });
        }

        if ($request->is_auto_process !== null) {
            $query->where('is_auto_process', $request->is_auto_process);
        }

        if ($request->active_only) {
            $query->where('is_active', true);
        }

        $cancelReasons = $query->orderBy('reason_name')->get();

        return response()->json($cancelReasons);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reason_code' => 'required|string|max:20|unique:cancel_reasons',
            'reason_name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'is_auto_process' => 'boolean',
            'applicable_for' => 'nullable|in:opd,ipd,both',
        ]);

        $cancelReason = CancelReason::create($validated);

        return response()->json($cancelReason, 201);
    }

    public function show(CancelReason $cancelReason)
    {
        return response()->json($cancelReason);
    }

    public function update(Request $request, CancelReason $cancelReason)
    {
        $validated = $request->validate([
            'reason_name' => 'sometimes|required|string|max:150',
            'description' => 'nullable|string',
            'is_auto_process' => 'boolean',
            'applicable_for' => 'nullable|in:opd,ipd,both',
            'is_active' => 'boolean',
        ]);

        $cancelReason->update($validated);

        return response()->json($cancelReason);
    }

    public function destroy(CancelReason $cancelReason)
    {
        $cancelReason->delete();
        return response()->json(['message' => 'Cancel reason deleted successfully']);
    }
}
