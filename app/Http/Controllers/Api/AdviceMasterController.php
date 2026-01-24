<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdviceMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdviceMasterController extends Controller
{
    /**
     * Display a listing of advice masters.
     */
    public function index(Request $request)
    {
        $hospitalId = Auth::user()->hospital_id;

        $query = AdviceMaster::where('hospital_id', $hospitalId);

        // Filter by language
        if ($request->has('language') && $request->language) {
            $query->where('language', $request->language);
        }

        // Search filter
        if ($request->has('search') && $request->search) {
            $query->where('advice_text', 'like', '%' . $request->search . '%');
        }

        $advice = $query->where('is_active', true)
            ->orderBy('advice_text', 'asc')
            ->get();

        return response()->json($advice);
    }

    /**
     * Store a newly created advice master.
     */
    public function store(Request $request)
    {
        $request->validate([
            'advice_text' => 'required|string',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Check for duplicates
        $exists = AdviceMaster::where('hospital_id', $hospitalId)
            ->where('advice_text', $request->advice_text)
            ->where('language', $request->language)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This advice already exists'
            ], 422);
        }

        $advice = AdviceMaster::create([
            'hospital_id' => $hospitalId,
            'advice_text' => $request->advice_text,
            'language' => $request->language,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advice created successfully',
            'data' => $advice
        ], 201);
    }

    /**
     * Display the specified advice master.
     */
    public function show(AdviceMaster $adviceMaster)
    {
        if ($adviceMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($adviceMaster);
    }

    /**
     * Update the specified advice master.
     */
    public function update(Request $request, AdviceMaster $adviceMaster)
    {
        if ($adviceMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'advice_text' => 'required|string',
            'language' => 'required|in:english,marathi,hindi',
        ]);

        $adviceMaster->update([
            'advice_text' => $request->advice_text,
            'language' => $request->language,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advice updated successfully',
            'data' => $adviceMaster
        ]);
    }

    /**
     * Remove the specified advice master.
     */
    public function destroy(AdviceMaster $adviceMaster)
    {
        if ($adviceMaster->hospital_id !== Auth::user()->hospital_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $adviceMaster->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advice deleted successfully'
        ]);
    }
}
