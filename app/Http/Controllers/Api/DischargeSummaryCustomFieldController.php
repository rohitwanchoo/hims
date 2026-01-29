<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DischargeSummaryCustomField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DischargeSummaryCustomFieldController extends Controller
{
    /**
     * Display a listing of custom fields
     */
    public function index()
    {
        $hospitalId = Auth::user()->hospital_id;

        $fields = DischargeSummaryCustomField::where('hospital_id', $hospitalId)
            ->orderBy('section')
            ->orderBy('display_order')
            ->orderBy('field_label')
            ->get();

        return response()->json($fields);
    }

    /**
     * Store a newly created custom field
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'field_label' => 'required|string|max:200',
            'field_type' => 'required|in:text,textarea,select,date,number',
            'field_options' => 'nullable|array',
            'field_options.*' => 'string|max:200',
            'section' => 'required|in:custom,history,diagnosis,treatment,medications,discharge',
            'display_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'placeholder' => 'nullable|string|max:200',
            'help_text' => 'nullable|string',
        ]);

        $hospitalId = Auth::user()->hospital_id;

        // Generate field_name from field_label
        $fieldName = Str::snake(Str::lower($validated['field_label']));
        $fieldName = preg_replace('/[^a-z0-9_]/', '', $fieldName);

        // Ensure unique field name
        $baseFieldName = $fieldName;
        $counter = 1;
        while (DischargeSummaryCustomField::where('hospital_id', $hospitalId)
            ->where('field_name', $fieldName)
            ->exists()) {
            $fieldName = $baseFieldName . '_' . $counter;
            $counter++;
        }

        $validated['hospital_id'] = $hospitalId;
        $validated['field_name'] = $fieldName;
        $validated['is_required'] = $validated['is_required'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['display_order'] = $validated['display_order'] ?? 0;

        $field = DischargeSummaryCustomField::create($validated);

        return response()->json($field, 201);
    }

    /**
     * Display the specified custom field
     */
    public function show(string $id)
    {
        $hospitalId = Auth::user()->hospital_id;

        $field = DischargeSummaryCustomField::where('hospital_id', $hospitalId)
            ->findOrFail($id);

        return response()->json($field);
    }

    /**
     * Update the specified custom field
     */
    public function update(Request $request, string $id)
    {
        $field = DischargeSummaryCustomField::findOrFail($id);

        $validated = $request->validate([
            'field_label' => 'sometimes|string|max:200',
            'field_type' => 'sometimes|in:text,textarea,select,date,number',
            'field_options' => 'nullable|array',
            'field_options.*' => 'string|max:200',
            'section' => 'sometimes|in:custom,history,diagnosis,treatment,medications,discharge',
            'display_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'placeholder' => 'nullable|string|max:200',
            'help_text' => 'nullable|string',
        ]);

        $field->update($validated);

        return response()->json($field);
    }

    /**
     * Remove the specified custom field
     */
    public function destroy(string $id)
    {
        $field = DischargeSummaryCustomField::findOrFail($id);
        $field->delete();

        return response()->json([
            'message' => 'Custom field deleted successfully'
        ]);
    }

    /**
     * Get active custom fields for discharge summary form
     */
    public function getActiveFields()
    {
        $hospitalId = Auth::user()->hospital_id;

        $fields = DischargeSummaryCustomField::where('hospital_id', $hospitalId)
            ->where('is_active', true)
            ->orderBy('section')
            ->orderBy('display_order')
            ->orderBy('field_label')
            ->get();

        return response()->json($fields);
    }
}
