<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsultationForm;
use App\Models\ConsultationFormField;
use App\Models\ConsultationRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ConsultationFormController extends Controller
{
    /**
     * Get all consultation forms
     */
    public function index(Request $request)
    {
        $query = ConsultationForm::with(['department', 'fields']);

        // Filter by type
        if ($request->has('form_type')) {
            $query->where('form_type', $request->form_type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $forms = $query->orderBy('form_name')->get();

        return response()->json([
            'success' => true,
            'data' => $forms
        ]);
    }

    /**
     * Get a specific form with its fields
     */
    public function show($formId)
    {
        $form = ConsultationForm::with(['fields' => function ($query) {
            $query->orderBy('field_order');
        }, 'department'])->findOrFail($formId);

        return response()->json([
            'success' => true,
            'data' => $form
        ]);
    }

    /**
     * Create a new consultation form
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'form_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'form_type' => 'required|in:general,opd,ipd,specialty',
            'department_id' => 'nullable|exists:departments,department_id',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $form = ConsultationForm::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Consultation form created successfully',
            'data' => $form->load('fields')
        ], 201);
    }

    /**
     * Update a consultation form
     */
    public function update(Request $request, $formId)
    {
        $form = ConsultationForm::findOrFail($formId);

        $validator = Validator::make($request->all(), [
            'form_name' => 'string|max:255',
            'description' => 'nullable|string',
            'form_type' => 'in:general,opd,ipd,specialty',
            'department_id' => 'nullable|exists:departments,department_id',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $form->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Consultation form updated successfully',
            'data' => $form->load('fields')
        ]);
    }

    /**
     * Delete a consultation form
     */
    public function destroy($formId)
    {
        $form = ConsultationForm::findOrFail($formId);

        // Check if form has any records
        if ($form->records()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete form that has consultation records'
            ], 400);
        }

        $form->delete();

        return response()->json([
            'success' => true,
            'message' => 'Consultation form deleted successfully'
        ]);
    }

    // ==================== FIELD OPERATIONS ====================

    /**
     * Add a new field to a form
     */
    public function storeField(Request $request, $formId)
    {
        $form = ConsultationForm::findOrFail($formId);

        $validator = Validator::make($request->all(), [
            'field_label' => 'required|string|max:255',
            'field_key' => 'nullable|string|max:255',
            'field_type' => 'required|in:text,textarea,number,dropdown,radio,checkbox,date,time,datetime,file,email,phone',
            'field_options' => 'nullable|array',
            'field_config' => 'nullable|array',
            'default_value' => 'nullable|string',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'validation_rules' => 'nullable|string',
            'section' => 'nullable|string|max:255',
            'help_text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $data['form_id'] = $formId;

        // Generate field_key if not provided
        if (empty($data['field_key'])) {
            $data['field_key'] = Str::slug($data['field_label'], '_');
        }

        // Ensure unique field_key within the form
        $originalKey = $data['field_key'];
        $counter = 1;
        while (ConsultationFormField::where('form_id', $formId)
            ->where('field_key', $data['field_key'])
            ->exists()) {
            $data['field_key'] = $originalKey . '_' . $counter;
            $counter++;
        }

        // Set field_order to be last
        $maxOrder = ConsultationFormField::where('form_id', $formId)->max('field_order') ?? 0;
        $data['field_order'] = $maxOrder + 1;

        $field = ConsultationFormField::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Field added successfully',
            'data' => $field
        ], 201);
    }

    /**
     * Update a field
     */
    public function updateField(Request $request, $formId, $fieldId)
    {
        $field = ConsultationFormField::where('form_id', $formId)
            ->where('field_id', $fieldId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'field_label' => 'string|max:255',
            'field_type' => 'in:text,textarea,number,dropdown,radio,checkbox,date,time,datetime,file,email,phone',
            'field_options' => 'nullable|array',
            'field_config' => 'nullable|array',
            'default_value' => 'nullable|string',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'validation_rules' => 'nullable|string',
            'section' => 'nullable|string|max:255',
            'help_text' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $field->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully',
            'data' => $field
        ]);
    }

    /**
     * Delete a field
     */
    public function deleteField($formId, $fieldId)
    {
        $field = ConsultationFormField::where('form_id', $formId)
            ->where('field_id', $fieldId)
            ->firstOrFail();

        $field->delete();

        // Reorder remaining fields
        $this->reorderFieldsAfterDelete($formId);

        return response()->json([
            'success' => true,
            'message' => 'Field deleted successfully'
        ]);
    }

    /**
     * Toggle field visibility
     */
    public function toggleFieldVisibility($formId, $fieldId)
    {
        $field = ConsultationFormField::where('form_id', $formId)
            ->where('field_id', $fieldId)
            ->firstOrFail();

        $field->update(['is_visible' => !$field->is_visible]);

        return response()->json([
            'success' => true,
            'message' => 'Field visibility toggled successfully',
            'data' => $field
        ]);
    }

    /**
     * Reorder fields (drag and drop)
     */
    public function reorderFields(Request $request, $formId)
    {
        $validator = Validator::make($request->all(), [
            'fields' => 'required|array',
            'fields.*.field_id' => 'required|exists:consultation_form_fields,field_id',
            'fields.*.field_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->fields as $fieldData) {
                ConsultationFormField::where('field_id', $fieldData['field_id'])
                    ->where('form_id', $formId)
                    ->update(['field_order' => $fieldData['field_order']]);
            }

            DB::commit();

            $updatedFields = ConsultationFormField::where('form_id', $formId)
                ->orderBy('field_order')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Fields reordered successfully',
                'data' => $updatedFields
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder fields'
            ], 500);
        }
    }

    /**
     * Duplicate a field
     */
    public function duplicateField($formId, $fieldId)
    {
        $originalField = ConsultationFormField::where('form_id', $formId)
            ->where('field_id', $fieldId)
            ->firstOrFail();

        $newField = $originalField->replicate();
        $newField->field_label = $originalField->field_label . ' (Copy)';

        // Generate unique field_key
        $baseKey = $originalField->field_key . '_copy';
        $counter = 1;
        $newKey = $baseKey;
        while (ConsultationFormField::where('form_id', $formId)
            ->where('field_key', $newKey)
            ->exists()) {
            $newKey = $baseKey . '_' . $counter;
            $counter++;
        }
        $newField->field_key = $newKey;

        // Set as last field
        $maxOrder = ConsultationFormField::where('form_id', $formId)->max('field_order') ?? 0;
        $newField->field_order = $maxOrder + 1;

        $newField->save();

        return response()->json([
            'success' => true,
            'message' => 'Field duplicated successfully',
            'data' => $newField
        ]);
    }

    // ==================== CONSULTATION RECORDS ====================

    /**
     * Get consultation records for a patient
     */
    public function getPatientRecords($patientId)
    {
        $records = ConsultationRecord::with(['form', 'doctor', 'opdVisit'])
            ->where('patient_id', $patientId)
            ->orderBy('consultation_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $records
        ]);
    }

    /**
     * Get last consultation record for a patient (optionally filtered by OPD)
     */
    public function getLastConsultation(Request $request, $patientId)
    {
        $query = ConsultationRecord::with(['form.fields', 'patient', 'doctor'])
            ->where('patient_id', $patientId);

        // Filter by OPD if provided
        if ($request->has('opd_id')) {
            $query->where('opd_id', $request->opd_id);
        }

        $record = $query->orderBy('consultation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $record
        ]);
    }

    /**
     * Get a specific consultation record
     */
    public function getRecord($recordId)
    {
        $record = ConsultationRecord::with(['form.fields', 'patient', 'doctor'])
            ->findOrFail($recordId);

        return response()->json([
            'success' => true,
            'data' => $record
        ]);
    }

    /**
     * Save consultation record
     */
    public function storeRecord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'opd_id' => 'nullable|exists:opd_visits,opd_id',
            'ipd_id' => 'nullable|exists:ipd_admissions,ipd_id',
            'patient_id' => 'required|exists:patients,patient_id',
            'doctor_id' => 'nullable|exists:doctors,doctor_id',
            'form_id' => 'required|exists:consultation_forms,form_id',
            'consultation_date' => 'required|date',
            'form_data' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate form_data against form fields
        $form = ConsultationForm::with('fields')->findOrFail($request->form_id);
        $validationErrors = $this->validateFormData($request->form_data, $form->fields);

        if (!empty($validationErrors)) {
            return response()->json([
                'success' => false,
                'errors' => ['form_data' => $validationErrors]
            ], 422);
        }

        $data = $request->all();
        $data['created_by'] = Auth::id();

        $record = ConsultationRecord::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Consultation record saved successfully',
            'data' => $record->load(['form', 'patient', 'doctor'])
        ], 201);
    }

    /**
     * Update consultation record
     */
    public function updateRecord(Request $request, $recordId)
    {
        $record = ConsultationRecord::findOrFail($recordId);

        $validator = Validator::make($request->all(), [
            'form_data' => 'array',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('form_data')) {
            // Validate form_data
            $form = $record->form()->with('fields')->first();
            $validationErrors = $this->validateFormData($request->form_data, $form->fields);

            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'errors' => ['form_data' => $validationErrors]
                ], 422);
            }
        }

        $data = $request->only(['form_data', 'notes']);
        $data['updated_by'] = Auth::id();

        $record->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Consultation record updated successfully',
            'data' => $record->load(['form', 'patient', 'doctor'])
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Reorder fields after deletion
     */
    private function reorderFieldsAfterDelete($formId)
    {
        $fields = ConsultationFormField::where('form_id', $formId)
            ->orderBy('field_order')
            ->get();

        $order = 0;
        foreach ($fields as $field) {
            $field->update(['field_order' => $order]);
            $order++;
        }
    }

    /**
     * Validate form data against field definitions
     */
    private function validateFormData($formData, $fields)
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!$field->is_visible) {
                continue;
            }

            $fieldKey = $field->field_key;
            $value = $formData[$fieldKey] ?? null;

            // Check required fields
            if ($field->is_required && empty($value)) {
                $errors[$fieldKey] = "{$field->field_label} is required";
                continue;
            }

            // Skip validation if field is empty and not required
            if (empty($value)) {
                continue;
            }

            // Type-specific validation
            switch ($field->field_type) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$fieldKey] = "{$field->field_label} must be a valid email";
                    }
                    break;
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[$fieldKey] = "{$field->field_label} must be a number";
                    }
                    break;
                case 'date':
                case 'datetime':
                    if (!strtotime($value)) {
                        $errors[$fieldKey] = "{$field->field_label} must be a valid date";
                    }
                    break;
            }
        }

        return $errors;
    }

    /**
     * Get default form for OPD/IPD
     */
    public function getDefaultForm(Request $request)
    {
        $formType = $request->input('form_type', 'general');

        $form = ConsultationForm::with(['fields' => function ($query) {
                $query->where('is_visible', true)->orderBy('field_order');
            }])
            ->where('is_active', true)
            ->where('is_default', true)
            ->where('form_type', $formType)
            ->first();

        if (!$form) {
            // Fallback to any active form of that type
            $form = ConsultationForm::with(['fields' => function ($query) {
                    $query->where('is_visible', true)->orderBy('field_order');
                }])
                ->where('is_active', true)
                ->where('form_type', $formType)
                ->first();
        }

        return response()->json([
            'success' => true,
            'data' => $form
        ]);
    }
}
