<?php

namespace App\Http\Controllers\Api\Pathology;

use App\Http\Controllers\Controller;
use App\Models\PathoInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PathoInstructionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;

            $query = PathoInstruction::where('hospital_id', $hospitalId);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('instruction_name', 'like', "%{$search}%")
                      ->orWhere('instruction_text', 'like', "%{$search}%");
                });
            }

            // Active filter
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'instruction_name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 20);
            if (!in_array($perPage, [20, 50, 100])) {
                $perPage = 20;
            }

            $instructions = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $instructions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch instructions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'instruction_name' => 'required|string|max:100',
                'instruction_text' => 'required|string',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $instruction = PathoInstruction::create([
                'hospital_id' => Auth::user()->hospital_id,
                'instruction_name' => $request->instruction_name,
                'instruction_text' => $request->instruction_text,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Instruction created successfully',
                'data' => $instruction
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create instruction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $instruction = PathoInstruction::where('hospital_id', $hospitalId)
                ->where('instruction_id', $id)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $instruction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instruction not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $instruction = PathoInstruction::where('hospital_id', $hospitalId)
                ->where('instruction_id', $id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'instruction_name' => 'required|string|max:100',
                'instruction_text' => 'required|string',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $instruction->update([
                'instruction_name' => $request->instruction_name,
                'instruction_text' => $request->instruction_text,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $instruction->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Instruction updated successfully',
                'data' => $instruction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update instruction',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $hospitalId = Auth::user()->hospital_id;
            $instruction = PathoInstruction::where('hospital_id', $hospitalId)
                ->where('instruction_id', $id)
                ->firstOrFail();

            $instruction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Instruction deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete instruction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
