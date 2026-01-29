<template>
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">
        <i class="bi bi-sliders me-2"></i>
        Discharge Summary Custom Fields
      </h4>
      <button class="btn btn-primary" @click="openCreateModal">
        <i class="bi bi-plus-circle me-1"></i>
        Add Custom Field
      </button>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Section</label>
            <select v-model="filters.section" class="form-select">
              <option value="">All Sections</option>
              <option value="custom">Custom Fields (Separate Section)</option>
              <option value="history">Medical History & Examination</option>
              <option value="diagnosis">Diagnosis & ICD Codes</option>
              <option value="treatment">Treatment & Procedures</option>
              <option value="medications">Medications</option>
              <option value="discharge">Discharge Instructions</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Field Type</label>
            <select v-model="filters.type" class="form-select">
              <option value="">All Types</option>
              <option value="text">Text (Single Line)</option>
              <option value="textarea">Textarea (Multi-line)</option>
              <option value="select">Dropdown/Select</option>
              <option value="date">Date</option>
              <option value="number">Number</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status</label>
            <select v-model="filters.status" class="form-select">
              <option value="">All</option>
              <option value="active">Active Only</option>
              <option value="inactive">Inactive Only</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Fields List -->
    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Field Label</th>
                <th>Type</th>
                <th>Section</th>
                <th>Required</th>
                <th>Status</th>
                <th>Order</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="7" class="text-center py-4">
                  <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                  Loading custom fields...
                </td>
              </tr>
              <tr v-else-if="filteredFields.length === 0">
                <td colspan="7" class="text-center py-4 text-muted">
                  No custom fields found
                </td>
              </tr>
              <tr v-else v-for="field in filteredFields" :key="field.field_id">
                <td>
                  <strong>{{ field.field_label }}</strong>
                  <div class="small text-muted">{{ field.field_name }}</div>
                </td>
                <td>
                  <span class="badge bg-secondary">{{ getFieldTypeLabel(field.field_type) }}</span>
                </td>
                <td>{{ getSectionLabel(field.section) }}</td>
                <td>
                  <span v-if="field.is_required" class="badge bg-warning">Required</span>
                  <span v-else class="text-muted">Optional</span>
                </td>
                <td>
                  <span v-if="field.is_active" class="badge bg-success">Active</span>
                  <span v-else class="badge bg-secondary">Inactive</span>
                </td>
                <td>{{ field.display_order }}</td>
                <td>
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" @click="editField(field)" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger" @click="deleteField(field)" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" ref="fieldModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editMode ? 'Edit Custom Field' : 'Create Custom Field' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveField">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Field Label <span class="text-danger">*</span></label>
                  <input type="text" v-model="formData.field_label" class="form-control" required>
                  <small class="text-muted">This will be shown to users</small>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Field Type <span class="text-danger">*</span></label>
                  <select v-model="formData.field_type" class="form-select" required>
                    <option value="">Select Type</option>
                    <option value="text">Text (Single Line)</option>
                    <option value="textarea">Textarea (Multi-line)</option>
                    <option value="select">Dropdown/Select</option>
                    <option value="date">Date</option>
                    <option value="number">Number</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Section <span class="text-danger">*</span></label>
                  <select v-model="formData.section" class="form-select" required>
                    <option value="custom">Custom Fields (Separate Section)</option>
                    <option value="history">Medical History & Examination</option>
                    <option value="diagnosis">Diagnosis & ICD Codes</option>
                    <option value="treatment">Treatment & Procedures</option>
                    <option value="medications">Medications</option>
                    <option value="discharge">Discharge Instructions</option>
                  </select>
                </div>

                <div class="col-md-3">
                  <label class="form-label">Display Order</label>
                  <input type="number" v-model.number="formData.display_order" class="form-control" min="0">
                </div>

                <div class="col-md-3">
                  <label class="form-label d-block">&nbsp;</label>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="formData.is_required">
                    <label class="form-check-label">Required</label>
                  </div>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="formData.is_active">
                    <label class="form-check-label">Active</label>
                  </div>
                </div>

                <div class="col-12" v-if="formData.field_type === 'select'">
                  <label class="form-label">Dropdown Options <span class="text-danger">*</span></label>
                  <div v-for="(option, index) in formData.field_options" :key="index" class="input-group mb-2">
                    <input type="text" v-model="formData.field_options[index]" class="form-control" placeholder="Option value" required>
                    <button type="button" class="btn btn-outline-danger" @click="removeOption(index)">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary" @click="addOption">
                    <i class="bi bi-plus"></i> Add Option
                  </button>
                </div>

                <div class="col-12">
                  <label class="form-label">Placeholder Text</label>
                  <input type="text" v-model="formData.placeholder" class="form-control">
                </div>

                <div class="col-12">
                  <label class="form-label">Help Text</label>
                  <textarea v-model="formData.help_text" class="form-control" rows="2"></textarea>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveField" :disabled="saving">
              <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
              {{ saving ? 'Saving...' : 'Save Field' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Modal } from 'bootstrap';
import axios from 'axios';

const fields = ref([]);
const loading = ref(false);
const saving = ref(false);
const editMode = ref(false);
const fieldModal = ref(null);
let modalInstance = null;

const filters = ref({
  section: '',
  type: '',
  status: ''
});

const formData = ref({
  field_label: '',
  field_type: '',
  field_options: [],
  section: 'custom',
  display_order: 0,
  is_required: false,
  is_active: true,
  placeholder: '',
  help_text: ''
});

const filteredFields = computed(() => {
  return fields.value.filter(field => {
    if (filters.value.section && field.section !== filters.value.section) return false;
    if (filters.value.type && field.field_type !== filters.value.type) return false;
    if (filters.value.status === 'active' && !field.is_active) return false;
    if (filters.value.status === 'inactive' && field.is_active) return false;
    return true;
  });
});

const loadFields = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/discharge-summary-custom-fields');
    fields.value = response.data;
  } catch (error) {
    console.error('Error loading custom fields:', error);
    alert('Error loading custom fields');
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  editMode.value = false;
  resetForm();
  modalInstance.show();
};

const editField = (field) => {
  editMode.value = true;
  formData.value = {
    field_id: field.field_id,
    field_label: field.field_label,
    field_type: field.field_type,
    field_options: field.field_options || [],
    section: field.section,
    display_order: field.display_order,
    is_required: field.is_required,
    is_active: field.is_active,
    placeholder: field.placeholder || '',
    help_text: field.help_text || ''
  };
  modalInstance.show();
};

const saveField = async () => {
  if (!formData.value.field_label || !formData.value.field_type || !formData.value.section) {
    alert('Please fill in all required fields');
    return;
  }

  if (formData.value.field_type === 'select' && formData.value.field_options.length === 0) {
    alert('Please add at least one option for dropdown field');
    return;
  }

  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/discharge-summary-custom-fields/${formData.value.field_id}`, formData.value);
    } else {
      await axios.post('/api/discharge-summary-custom-fields', formData.value);
    }
    modalInstance.hide();
    loadFields();
  } catch (error) {
    console.error('Error saving custom field:', error);
    alert('Error saving custom field');
  } finally {
    saving.value = false;
  }
};

const deleteField = async (field) => {
  if (!confirm(`Are you sure you want to delete "${field.field_label}"?`)) return;

  try {
    await axios.delete(`/api/discharge-summary-custom-fields/${field.field_id}`);
    loadFields();
  } catch (error) {
    console.error('Error deleting custom field:', error);
    alert('Error deleting custom field');
  }
};

const addOption = () => {
  formData.value.field_options.push('');
};

const removeOption = (index) => {
  formData.value.field_options.splice(index, 1);
};

const resetForm = () => {
  formData.value = {
    field_label: '',
    field_type: '',
    field_options: [],
    section: 'custom',
    display_order: 0,
    is_required: false,
    is_active: true,
    placeholder: '',
    help_text: ''
  };
};

const getFieldTypeLabel = (type) => {
  const labels = {
    text: 'Text',
    textarea: 'Textarea',
    select: 'Dropdown',
    date: 'Date',
    number: 'Number'
  };
  return labels[type] || type;
};

const getSectionLabel = (section) => {
  const labels = {
    custom: 'Custom Fields',
    history: 'Medical History',
    diagnosis: 'Diagnosis',
    treatment: 'Treatment',
    medications: 'Medications',
    discharge: 'Discharge Instructions'
  };
  return labels[section] || section;
};

onMounted(() => {
  modalInstance = new Modal(fieldModal.value);
  loadFields();
});
</script>

<style scoped>
.table th {
  font-weight: 600;
  background-color: #f8f9fa;
}
</style>
