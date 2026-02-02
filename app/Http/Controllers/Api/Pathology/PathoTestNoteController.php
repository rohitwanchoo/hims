<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoTestNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoTestNoteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoTestNote::where('hospital_id', $hospitalId)
                ->with(['test']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('note_title', 'like', "%{$search}%")
                      ->orWhere('note_text', 'like', "%{$search}%");
                });
            }

            // Test filter
            if ($request->filled('test_id')) {
                $query->where('test_id', $request->test_id);
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'note_title');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $notes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $notes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_id' => 'nullable|exists:patho_tests,test_id',
                'note_title' => 'required|string|max:200',
                'note_text' => 'required|string',
                'display_order' => 'nullable|integer',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $note = PathoTestNote::create([
                'hospital_id' => Auth::user()->hospital_id,
                'test_id' => $request->test_id,
                'note_title' => $request->note_title,
                'note_text' => $request->note_text,
                'display_order' => $request->display_order ?? 0,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test note created successfully',
                'data' => $note->load('test')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $note = PathoTestNote::where('hospital_id', $hospitalId)
                ->where('note_id', $id)
                ->with(['test'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $note
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test note not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $note = PathoTestNote::where('hospital_id', $hospitalId)
                ->where('note_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'test_id' => 'nullable|exists:patho_tests,test_id',
                'note_title' => 'required|string|max:200',
                'note_text' => 'required|string',
                'display_order' => 'nullable|integer',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $note->update([
                'test_id' => $request->test_id,
                'note_title' => $request->note_title,
                'note_text' => $request->note_text,
                'display_order' => $request->display_order ?? $note->display_order,
                'is_active' => $request->is_active ?? $note->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test note updated successfully',
                'data' => $note->load('test')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $note = PathoTestNote::where('hospital_id', $hospitalId)
                ->where('note_id', $id)
                ->firstOrFail();

            $note->delete();

            return response()->json([
                'success' => true,
                'message' => 'Test note deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test note',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByTest($testId)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $notes = PathoTestNote::where('hospital_id', $hospitalId)
                ->where('test_id', $testId)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('note_title')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $notes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch test notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
