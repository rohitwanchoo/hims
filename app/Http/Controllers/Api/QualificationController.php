<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QualificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Qualification::withCount('referenceDoctors as usage_count');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('qualification_name', 'like', "%{$search}%")
                  ->orWhere('qualification_code', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('qualification_name')->get();
    }

    public function active()
    {
        return Qualification::where('is_active', true)
            ->orderBy('qualification_name')
            ->get(['qualification_id', 'qualification_name', 'qualification_code']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'qualification_name' => 'required|string|max:100',
            'qualification_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $validated['hospital_id'] = Auth::user()->hospital_id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $item = Qualification::create($validated);

        return response()->json($item, 201);
    }

    public function show(Qualification $qualification)
    {
        return $qualification;
    }

    public function update(Request $request, Qualification $qualification)
    {
        $validated = $request->validate([
            'qualification_name' => 'required|string|max:100',
            'qualification_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $qualification->update($validated);

        return response()->json($qualification);
    }

    public function destroy(Qualification $qualification)
    {
        $qualification->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
