<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\Pathology\PathoTestNote;
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
                ->with(['pathoTest', 'pathoTestReport']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('note_text', 'like', "%{$search}%");
            }

            // Note for filter
            if ($request->filled('note_for')) {
                $query->where('note_for', $request->note_for);
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
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
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
                'note_for' => 'required|in:test_master,test_report',
                'test_master_id' => 'required_if:note_for,test_master|nullable|exists:patho_tests,test_id',
                'test_report_id' => 'required_if:note_for,test_report|nullable|exists:patho_test_reports,report_id',
                'note_content' => 'required|string',
                'age_group' => 'nullable|string|max:50',
                'test_remark' => 'nullable|string',
                'is_default' => 'boolean',
                'is_abnormal' => 'boolean',
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
                'note_for' => $request->note_for,
                'test_id' => $request->note_for === 'test_master' ? $request->test_master_id : null,
                'report_id' => $request->note_for === 'test_report' ? $request->test_report_id : null,
                'note_text' => $request->note_content,
                'age_group' => $request->age_group,
                'test_remark' => $request->test_remark,
                'is_default' => $request->is_default ?? false,
                'is_abnormal' => $request->is_abnormal ?? false,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test note created successfully',
                'data' => $note->load(['pathoTest', 'pathoTestReport'])
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
                ->with(['testMaster', 'testReport'])
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
                'note_for' => 'required|in:test_master,test_report',
                'test_master_id' => 'required_if:note_for,test_master|nullable|exists:patho_tests,test_id',
                'test_report_id' => 'required_if:note_for,test_report|nullable|exists:patho_test_reports,report_id',
                'note_content' => 'required|string',
                'age_group' => 'nullable|string|max:50',
                'test_remark' => 'nullable|string',
                'is_default' => 'boolean',
                'is_abnormal' => 'boolean',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $note->update([
                'note_for' => $request->note_for,
                'test_id' => $request->note_for === 'test_master' ? $request->test_master_id : null,
                'report_id' => $request->note_for === 'test_report' ? $request->test_report_id : null,
                'note_text' => $request->note_content,
                'age_group' => $request->age_group,
                'test_remark' => $request->test_remark,
                'is_default' => $request->is_default ?? $note->is_default,
                'is_abnormal' => $request->is_abnormal ?? $note->is_abnormal,
                'is_active' => $request->is_active ?? $note->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test note updated successfully',
                'data' => $note->load(['pathoTest', 'pathoTestReport'])
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
                ->orderBy('created_at', 'desc')
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
