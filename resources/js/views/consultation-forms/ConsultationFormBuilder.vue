<template>
  <div class="consultation-form-builder">
    <div class="container-fluid py-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="mb-1">
            <i class="bi bi-file-earmark-medical me-2"></i>
            {{ formId ? 'Edit Consultation Form' : 'New Consultation Form' }}
          </h2>
          <p class="text-muted mb-0" v-if="form.form_name">{{ form.form_name }}</p>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary" @click="goBack">
            <i class="bi bi-arrow-left me-1"></i> Back
          </button>
          <button class="btn btn-success" @click="saveForm" :disabled="saving">
            <i class="bi bi-save me-1"></i> {{ saving ? 'Saving...' : 'Save Form' }}
          </button>
        </div>
      </div>

      <div class="row">
        <!-- Left Panel - Form Settings -->
        <div class="col-md-4">
          <div class="card mb-3">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">
                <i class="bi bi-gear me-2"></i>Form Settings
              </h5>
            </div>
            <div class="card-body">
              <!-- Form Name -->
              <div class="mb-3">
                <label class="form-label">Form Name *</label>
                <input
                  type="text"
                  v-model="form.form_name"
                  class="form-control"
                  placeholder="e.g., General OPD Consultation"
                  required
                />
              </div>

              <!-- Description -->
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea
                  v-model="form.description"
                  class="form-control"
                  rows="3"
                  placeholder="Optional description"
                ></textarea>
              </div>

              <!-- Form Type -->
              <div class="mb-3">
                <label class="form-label">Form Type *</label>
                <select v-model="form.form_type" class="form-select" required>
                  <option value="general">General</option>
                  <option value="opd">OPD</option>
                  <option value="ipd">IPD</option>
                  <option value="specialty">Specialty</option>
                </select>
              </div>

              <!-- Department -->
              <div class="mb-3">
                <label class="form-label">Department (Optional)</label>
                <select v-model="form.department_id" class="form-select">
                  <option :value="null">All Departments</option>
                  <option
                    v-for="dept in departments"
                    :key="dept.department_id"
                    :value="dept.department_id"
                  >
                    {{ dept.department_name }}
                  </option>
                </select>
              </div>

              <!-- Active Status -->
              <div class="mb-3 form-check form-switch">
                <input
                  type="checkbox"
                  v-model="form.is_active"
                  class="form-check-input"
                  id="isActive"
                />
                <label class="form-check-label" for="isActive">Active</label>
              </div>

              <!-- Default Form -->
              <div class="mb-3 form-check form-switch">
                <input
                  type="checkbox"
                  v-model="form.is_default"
                  class="form-check-input"
                  id="isDefault"
                />
                <label class="form-check-label" for="isDefault">Set as Default</label>
              </div>
            </div>
          </div>

          <!-- Add Field Panel -->
          <div class="card">
            <div class="card-header bg-success text-white">
              <h5 class="mb-0">
                <i class="bi bi-plus-circle me-2"></i>Add New Field
              </h5>
            </div>
            <div class="card-body">
              <!-- Warning for unsaved form -->
              <div v-if="!formId" class="alert alert-warning alert-sm mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <small>Save the form first before adding fields</small>
              </div>

              <button
                v-for="fieldType in availableFieldTypes"
                :key="fieldType.type"
                @click="addNewField(fieldType.type)"
                class="btn btn-outline-primary btn-sm w-100 mb-2 text-start"
                :disabled="!formId"
              >
                <i :class="fieldType.icon + ' me-2'"></i>
                {{ fieldType.label }}
              </button>
            </div>
          </div>
        </div>

        <!-- Right Panel - Form Fields Builder -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                <i class="bi bi-list-ul me-2"></i>
                Form Fields ({{ fields.length }})
              </h5>
              <span class="text-muted small">Drag to reorder</span>
            </div>
            <div class="card-body">
              <div v-if="fields.length === 0" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1"></i>
                <p class="mt-3">No fields added yet. Add your first field from the left panel.</p>
              </div>

              <!-- Draggable Fields List -->
              <draggable
                v-model="fields"
                item-key="field_id"
                handle=".drag-handle"
                @end="onFieldReorder"
                class="fields-list"
              >
                <template #item="{ element: field, index }">
                  <div
                    class="field-item card mb-3"
                    :class="{ 'field-hidden': !field.is_visible }"
                  >
                    <div class="card-body">
                      <div class="d-flex align-items-start">
                        <!-- Drag Handle -->
                        <div class="drag-handle me-3">
                          <i class="bi bi-grip-vertical fs-4 text-muted"></i>
                        </div>

                        <!-- Field Content -->
                        <div class="flex-grow-1">
                          <!-- Editing Mode -->
                          <div v-if="editingFieldId === field.field_id">
                            <div class="mb-3">
                              <label class="form-label">Field Label *</label>
                              <input
                                type="text"
                                v-model="field.field_label"
                                class="form-control"
                                placeholder="Enter field label"
                              />
                            </div>

                            <div class="row">
                              <div class="col-md-6 mb-3">
                                <label class="form-label">Section (Category) *</label>
                                <select v-model="field.section" class="form-select">
                                  <option value="">-- Select Section --</option>
                                  <option v-for="section in uniqueSections" :key="section" :value="section">
                                    {{ section }}
                                  </option>
                                  <option value="__new__">+ Add New Section</option>
                                </select>
                                <!-- Add new section input -->
                                <input
                                  v-if="field.section === '__new__'"
                                  type="text"
                                  v-model="newSectionName"
                                  @blur="addNewSection(field)"
                                  @keyup.enter="addNewSection(field)"
                                  class="form-control mt-2"
                                  placeholder="Enter new section name"
                                  autofocus
                                />
                              </div>

                              <div class="col-md-6 mb-3">
                                <label class="form-label">Column Width *</label>
                                <select v-model="field.column_width" class="form-select">
                                  <option value="col-12">Full Width (1 per row)</option>
                                  <option value="col-md-6">Half Width (2 per row)</option>
                                  <option value="col-md-4">One-Third (3 per row)</option>
                                  <option value="col-md-3">One-Quarter (4 per row)</option>
                                </select>
                              </div>
                            </div>

                            <div class="mb-3">
                              <label class="form-label">Help Text</label>
                              <input
                                type="text"
                                v-model="field.help_text"
                                class="form-control"
                                placeholder="Helper text for this field"
                              />
                            </div>

                            <!-- Options for dropdown, radio, checkbox -->
                            <div v-if="needsOptions(field.field_type)" class="mb-3">
                              <label class="form-label">Options (one per line)</label>
                              <textarea
                                v-model="optionsText[field.field_id]"
                                class="form-control"
                                rows="4"
                                placeholder="Option 1&#10;Option 2&#10;Option 3"
                              ></textarea>
                            </div>

                            <div class="mb-3 form-check">
                              <input
                                type="checkbox"
                                v-model="field.is_required"
                                class="form-check-input"
                                :id="'required-' + field.field_id"
                              />
                              <label class="form-check-label" :for="'required-' + field.field_id">
                                Required Field
                              </label>
                            </div>

                            <div class="d-flex gap-2">
                              <button
                                @click="saveFieldEdit(field)"
                                class="btn btn-sm btn-success"
                              >
                                <i class="bi bi-check"></i> Save
                              </button>
                              <button
                                @click="cancelFieldEdit"
                                class="btn btn-sm btn-secondary"
                              >
                                <i class="bi bi-x"></i> Cancel
                              </button>
                            </div>
                          </div>

                          <!-- Display Mode -->
                          <div v-else>
                            <div class="d-flex justify-content-between align-items-start">
                              <div class="flex-grow-1">
                                <h6 class="mb-1">
                                  <i :class="getFieldTypeIcon(field.field_type)" class="me-2" :style="{ color: getFieldTypeColor(field.field_type) }"></i>
                                  {{ field.field_label }}
                                  <span v-if="field.is_required" class="text-danger ms-1">*</span>
                                  <span
                                    v-if="!field.is_visible"
                                    class="badge bg-secondary ms-2"
                                  >
                                    <i class="bi bi-eye-slash me-1"></i>Hidden
                                  </span>
                                </h6>
                                <div class="mb-2">
                                  <span
                                    class="badge me-2"
                                    :style="{ backgroundColor: getFieldTypeColor(field.field_type), color: 'white' }"
                                  >
                                    <i :class="getFieldTypeIcon(field.field_type)" class="me-1"></i>
                                    {{ field.field_type }}
                                  </span>
                                  <span v-if="field.section" class="badge bg-light text-dark me-2">
                                    <i class="bi bi-folder2-open me-1"></i>
                                    {{ field.section }}
                                  </span>
                                  <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="bi bi-layout-three-columns me-1"></i>
                                    {{ getColumnWidthLabel(field.column_width) }}
                                  </span>
                                </div>
                                <p v-if="field.help_text" class="text-muted small mb-0">
                                  <i class="bi bi-info-circle me-1"></i>
                                  {{ field.help_text }}
                                </p>

                                <!-- Show options for dropdown/radio/checkbox -->
                                <div
                                  v-if="needsOptions(field.field_type) && field.field_options"
                                  class="mt-2"
                                >
                                  <small class="text-muted">Options:</small>
                                  <div class="d-flex flex-wrap gap-1 mt-1">
                                    <span
                                      v-for="(opt, idx) in field.field_options"
                                      :key="idx"
                                      class="badge bg-light text-dark"
                                    >
                                      {{ opt }}
                                    </span>
                                  </div>
                                </div>
                              </div>

                              <!-- Actions -->
                              <div class="d-flex gap-1">
                                <button
                                  @click="toggleFieldVisibility(field)"
                                  class="btn btn-sm btn-outline-secondary"
                                  :title="field.is_visible ? 'Hide Field' : 'Show Field'"
                                >
                                  <i
                                    :class="
                                      field.is_visible ? 'bi bi-eye-slash' : 'bi bi-eye'
                                    "
                                  ></i>
                                </button>
                                <button
                                  @click="editField(field)"
                                  class="btn btn-sm btn-outline-primary"
                                  title="Edit Field"
                                >
                                  <i class="bi bi-pencil"></i>
                                </button>
                                <button
                                  @click="duplicateField(field)"
                                  class="btn btn-sm btn-outline-info"
                                  title="Duplicate Field"
                                >
                                  <i class="bi bi-files"></i>
                                </button>
                                <button
                                  @click="deleteField(field, index)"
                                  class="btn btn-sm btn-outline-danger"
                                  title="Delete Field"
                                >
                                  <i class="bi bi-trash"></i>
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </draggable>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import draggable from 'vuedraggable';

const router = useRouter();
const route = useRoute();

const formId = ref(route.params.formId || null);
const saving = ref(false);
const editingFieldId = ref(null);
const newSectionName = ref('');

const form = reactive({
  form_name: '',
  description: '',
  form_type: 'general',
  department_id: null,
  is_active: true,
  is_default: false,
});

const fields = ref([]);
const departments = ref([]);
const optionsText = ref({});

// Get unique sections from existing fields
const uniqueSections = computed(() => {
  const sections = fields.value
    .map(f => f.section)
    .filter(s => s && s !== '__new__');
  return [...new Set(sections)].sort();
});

const availableFieldTypes = [
  { type: 'text', label: 'Text Input', icon: 'bi bi-input-cursor-text' },
  { type: 'textarea', label: 'Text Area', icon: 'bi bi-textarea-t' },
  { type: 'number', label: 'Number', icon: 'bi bi-123' },
  { type: 'dropdown', label: 'Dropdown', icon: 'bi bi-menu-button-wide' },
  { type: 'radio', label: 'Radio Buttons', icon: 'bi bi-ui-radios' },
  { type: 'checkbox', label: 'Checkboxes', icon: 'bi bi-ui-checks' },
  { type: 'date', label: 'Date Picker', icon: 'bi bi-calendar-date' },
  { type: 'time', label: 'Time Picker', icon: 'bi bi-clock' },
  { type: 'datetime', label: 'Date & Time', icon: 'bi bi-calendar-event' },
  { type: 'email', label: 'Email', icon: 'bi bi-envelope' },
  { type: 'phone', label: 'Phone', icon: 'bi bi-telephone' },
];

// Helper methods for enhanced display
const getFieldTypeIcon = (fieldType) => {
  const iconMap = {
    text: 'bi bi-input-cursor-text',
    textarea: 'bi bi-textarea-t',
    number: 'bi bi-123',
    dropdown: 'bi bi-menu-button-wide',
    radio: 'bi bi-ui-radios',
    checkbox: 'bi bi-ui-checks',
    date: 'bi bi-calendar-date',
    time: 'bi bi-clock',
    datetime: 'bi bi-calendar-event',
    email: 'bi bi-envelope',
    phone: 'bi bi-telephone',
  };
  return iconMap[fieldType] || 'bi bi-question-circle';
};

const getFieldTypeColor = (fieldType) => {
  const colorMap = {
    text: '#0d6efd',
    textarea: '#6610f2',
    number: '#d63384',
    dropdown: '#fd7e14',
    radio: '#20c997',
    checkbox: '#198754',
    date: '#0dcaf0',
    time: '#ffc107',
    datetime: '#dc3545',
    email: '#6f42c1',
    phone: '#0a58ca',
  };
  return colorMap[fieldType] || '#6c757d';
};

const getColumnWidthLabel = (columnWidth) => {
  const labelMap = {
    'col-12': 'Full Width',
    'col-md-6': 'Half Width',
    'col-md-4': '1/3 Width',
    'col-md-3': '1/4 Width',
  };
  return labelMap[columnWidth] || 'Half Width';
};

const fetchForm = async () => {
  if (!formId.value) return;

  try {
    const response = await axios.get(`/api/consultation-forms/${formId.value}`);
    if (response.data.success) {
      Object.assign(form, response.data.data);
      fields.value = response.data.data.fields || [];

      fields.value.forEach(field => {
        if (field.field_options && Array.isArray(field.field_options)) {
          optionsText.value[field.field_id] = field.field_options.join('\n');
        }
      });
    }
  } catch (error) {
    console.error('Error fetching form:', error);
    alert('Failed to load form');
  }
};

const fetchDepartments = async () => {
  try {
    const response = await axios.get('/api/departments');
    departments.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Error fetching departments:', error);
  }
};

const saveForm = async () => {
  if (!form.form_name) {
    alert('Form name is required');
    return;
  }

  saving.value = true;

  try {
    let response;

    if (formId.value) {
      response = await axios.put(`/api/consultation-forms/${formId.value}`, form);
    } else {
      response = await axios.post('/api/consultation-forms', form);
    }

    if (response.data.success) {
      if (!formId.value) {
        formId.value = response.data.data.form_id;
        router.replace(`/consultation-forms/${formId.value}/edit`);
      }

      await saveAllFields();

      alert('Form saved successfully!');
    }
  } catch (error) {
    console.error('Error saving form:', error);
    alert('Failed to save form: ' + (error.response?.data?.message || error.message));
  } finally {
    saving.value = false;
  }
};

const saveAllFields = async () => {
  if (!formId.value) return;

  const reorderData = {
    fields: fields.value.map((field, index) => ({
      field_id: field.field_id,
      field_order: index,
    })),
  };

  try {
    await axios.post(`/api/consultation-forms/${formId.value}/fields/reorder`, reorderData);
  } catch (error) {
    console.error('Error reordering fields:', error);
  }
};

const addNewField = (fieldType) => {
  // Check if form is saved first
  if (!formId.value) {
    alert('Please save the form first before adding fields.\n\nFill in the form name and other details in the left panel, then click "Save Form".');
    return;
  }

  const newField = {
    field_id: 'temp_' + Date.now(),
    field_label: `New ${fieldType} Field`,
    field_key: '',
    field_type: fieldType,
    field_options: needsOptions(fieldType) ? [] : null,
    field_config: {},
    default_value: '',
    field_order: fields.value.length,
    is_required: false,
    is_visible: true,
    section: '',
    column_width: fieldType === 'textarea' ? 'col-12' : 'col-md-6',
    help_text: '',
    _isNew: true,
  };

  fields.value.push(newField);
  editingFieldId.value = newField.field_id;

  if (needsOptions(fieldType)) {
    optionsText.value[newField.field_id] = '';
  }
};

const editField = (field) => {
  editingFieldId.value = field.field_id;
};

const addNewSection = (field) => {
  if (newSectionName.value.trim()) {
    field.section = newSectionName.value.trim();
    newSectionName.value = '';
  } else {
    field.section = '';
  }
};

const saveFieldEdit = async (field) => {
  if (!field.field_label) {
    alert('Field label is required');
    return;
  }

  if (needsOptions(field.field_type) && optionsText.value[field.field_id]) {
    field.field_options = optionsText.value[field.field_id]
      .split('\n')
      .map(opt => opt.trim())
      .filter(opt => opt.length > 0);
  }

  try {
    if (field._isNew) {
      const response = await axios.post(`/api/consultation-forms/${formId.value}/fields`, field);
      if (response.data.success) {
        const index = fields.value.findIndex(f => f.field_id === field.field_id);
        fields.value[index] = response.data.data;
        delete field._isNew;
      }
    } else {
      const response = await axios.put(
        `/api/consultation-forms/${formId.value}/fields/${field.field_id}`,
        field
      );
      if (response.data.success) {
        Object.assign(field, response.data.data);
      }
    }

    editingFieldId.value = null;
  } catch (error) {
    console.error('Error saving field:', error);
    alert('Failed to save field: ' + (error.response?.data?.message || error.message));
  }
};

const cancelFieldEdit = () => {
  const field = fields.value.find(f => f.field_id === editingFieldId.value);
  if (field && field._isNew) {
    const index = fields.value.indexOf(field);
    fields.value.splice(index, 1);
  }

  editingFieldId.value = null;
};

const toggleFieldVisibility = async (field) => {
  if (field._isNew) {
    field.is_visible = !field.is_visible;
    return;
  }

  try {
    const response = await axios.post(
      `/api/consultation-forms/${formId.value}/fields/${field.field_id}/toggle-visibility`
    );
    if (response.data.success) {
      field.is_visible = response.data.data.is_visible;
    }
  } catch (error) {
    console.error('Error toggling visibility:', error);
    alert('Failed to toggle field visibility');
  }
};

const duplicateField = async (field) => {
  try {
    const response = await axios.post(
      `/api/consultation-forms/${formId.value}/fields/${field.field_id}/duplicate`
    );
    if (response.data.success) {
      fields.value.push(response.data.data);
    }
  } catch (error) {
    console.error('Error duplicating field:', error);
    alert('Failed to duplicate field');
  }
};

const deleteField = async (field, index) => {
  if (!confirm(`Are you sure you want to delete "${field.field_label}"?`)) {
    return;
  }

  if (field._isNew) {
    fields.value.splice(index, 1);
    return;
  }

  try {
    await axios.delete(`/api/consultation-forms/${formId.value}/fields/${field.field_id}`);
    fields.value.splice(index, 1);
  } catch (error) {
    console.error('Error deleting field:', error);
    alert('Failed to delete field');
  }
};

const onFieldReorder = () => {
  fields.value.forEach((field, index) => {
    field.field_order = index;
  });
};

const needsOptions = (fieldType) => {
  return ['dropdown', 'radio', 'checkbox'].includes(fieldType);
};

const goBack = () => {
  router.push('/consultation-forms');
};

onMounted(() => {
  fetchDepartments();
  if (formId.value) {
    fetchForm();
  }
});
</script>

<style scoped>
/* Card styling */
.card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.card-header {
  border-radius: 12px 12px 0 0 !important;
  padding: 1rem 1.25rem;
}

.card-header.bg-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  border: none;
}

.card-header.bg-success {
  background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%) !important;
  border: none;
}

/* Field items */
.field-item {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 4px solid #667eea;
  border-radius: 8px;
  background: white;
}

.field-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(102, 126, 234, 0.2);
  border-left-color: #764ba2;
}

.field-item.field-hidden {
  opacity: 0.5;
  border-left-color: #6c757d;
  background: #f8f9fa;
}

/* Drag handle */
.drag-handle {
  cursor: grab;
  user-select: none;
  transition: color 0.2s ease;
}

.drag-handle:hover {
  color: #667eea !important;
}

.drag-handle:active {
  cursor: grabbing;
}

/* Fields list */
.fields-list {
  min-height: 100px;
}

/* Add field buttons */
.btn-outline-primary.w-100 {
  transition: all 0.3s ease;
  border-radius: 8px;
}

.btn-outline-primary.w-100:hover:not(:disabled) {
  transform: translateX(5px);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
  color: white;
}

.btn-outline-primary.w-100:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Badges */
.badge {
  padding: 0.35em 0.65em;
  font-weight: 500;
  border-radius: 6px;
}

.bg-secondary-subtle {
  background-color: #e9ecef !important;
  color: #495057 !important;
}

/* Action buttons */
.btn-sm {
  transition: all 0.2s ease;
  border-radius: 6px;
}

.btn-sm:hover {
  transform: scale(1.05);
}

/* Alert styling */
.alert-warning {
  border-left: 4px solid #ffc107;
  border-radius: 8px;
}

/* Form controls */
.form-control, .form-select {
  border-radius: 8px;
  border: 1px solid #dee2e6;
  transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}

/* Form check */
.form-check-input:checked {
  background-color: #667eea;
  border-color: #667eea;
}

/* Empty state */
.text-center.py-5 i {
  color: #667eea;
}
</style>
