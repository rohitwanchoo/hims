<template>
  <div class="consultation-form">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h5 class="mb-1">
          <i class="bi bi-clipboard2-pulse me-2"></i>
          {{ form.form_name || 'Consultation' }}
        </h5>
        <p class="text-muted mb-0" v-if="patient">
          {{ patient.patient_name }} | {{ patient.pcd }} |
          {{ patient.gender }} | {{ patient.age }} {{ patient.age_unit || 'years' }}
        </p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-light" @click="goBack">
          <i class="bi bi-arrow-left me-1"></i> Back
        </button>
        <button class="btn btn-success" @click="saveConsultation" :disabled="saving">
          <i class="bi bi-save me-1"></i> {{ saving ? 'Saving...' : 'Save' }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <!-- Form Content -->
    <div v-else class="row">
      <div class="col-lg-12">
        <!-- Sections -->
        <div v-for="(section, idx) in sections" :key="idx" class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="bi bi-folder2-open me-2"></i>
              {{ section.name }}
            </h6>
            <button class="btn btn-sm btn-outline-secondary" @click="toggleSection(section.name)">
              {{ expandedSections[section.name] ? 'Hide' : 'Show' }}
            </button>
          </div>
          <div class="card-body" v-show="expandedSections[section.name]">
            <div class="row g-3">
              <div
                v-for="field in section.fields"
                :key="field.field_id"
                :class="getFieldColClass(field)"
              >
                <DynamicField
                  :field="field"
                  v-model="formData[field.field_key]"
                  @update:modelValue="handleFieldChange(field.field_key, $event)"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Additional Notes -->
        <div class="card mb-3">
          <div class="card-header">
            <h6 class="mb-0">
              <i class="bi bi-journal-text me-2"></i>
              Additional Notes
            </h6>
          </div>
          <div class="card-body">
            <textarea
              v-model="notes"
              class="form-control"
              rows="3"
              placeholder="Enter any additional notes or observations..."
            ></textarea>
          </div>
        </div>

        <!-- Validation Errors -->
        <div v-if="Object.keys(validationErrors).length > 0" class="alert alert-danger">
          <h6 class="alert-heading">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Please fix the following errors:
          </h6>
          <ul class="mb-0">
            <li v-for="(error, key) in validationErrors" :key="key">{{ error }}</li>
          </ul>
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2 mb-4">
          <button class="btn btn-outline-secondary" @click="goBack">
            Cancel
          </button>
          <button class="btn btn-success" @click="saveConsultation" :disabled="saving">
            <i class="bi bi-save me-2"></i> {{ saving ? 'Saving...' : 'Save Consultation' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import DynamicField from './components/DynamicField.vue';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const saving = ref(false);

const opdId = ref(route.params.opdId || null);
const patientId = ref(route.query.patient_id || null);
const formType = ref(route.query.form_type || 'opd');
const recordId = ref(route.params.recordId || null);

const form = ref({});
const patient = ref(null);
const doctor = ref(null);
const formData = reactive({});
const notes = ref('');
const validationErrors = ref({});
const expandedSections = ref({});

// Group fields by section
const sections = computed(() => {
  if (!form.value.fields) return [];

  const grouped = {};

  form.value.fields.forEach(field => {
    const sectionName = field.section || 'General';
    if (!grouped[sectionName]) {
      grouped[sectionName] = [];
    }
    grouped[sectionName].push(field);
  });

  return Object.keys(grouped).map(name => ({
    name,
    fields: grouped[name],
  }));
});

const fetchForm = async () => {
  loading.value = true;

  try {
    // Get default form for the specified type
    console.log('Fetching form with type:', formType.value);
    const response = await axios.get('/api/consultation-forms/default', {
      params: { form_type: formType.value },
    });

    console.log('Form API response:', response.data);

    if (response.data.success && response.data.data) {
      form.value = response.data.data;
      console.log('Form loaded:', form.value.form_name);
      console.log('Fields count:', form.value.fields?.length || 0);

      // Initialize formData with default values
      form.value.fields?.forEach(field => {
        if (field.default_value) {
          formData[field.field_key] = field.default_value;
        } else {
          formData[field.field_key] = getDefaultValueForType(field.field_type);
        }
      });

      // Initialize all sections as expanded
      sections.value.forEach(section => {
        expandedSections.value[section.name] = true;
      });
    } else {
      console.error('Form not found in response');
      alert('No default consultation form found. Please contact administrator.');
    }
  } catch (error) {
    console.error('Error fetching form:', error);
    alert('Failed to load consultation form: ' + (error.response?.data?.message || error.message));
  } finally {
    loading.value = false;
  }
};

const fetchPatient = async () => {
  if (!patientId.value) return;

  try {
    const response = await axios.get(`/api/patients/${patientId.value}`);
    patient.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching patient:', error);
  }
};

const fetchRecord = async () => {
  if (!recordId.value) return;

  loading.value = true;

  try {
    const response = await axios.get(`/api/consultation-records/${recordId.value}`);

    if (response.data.success) {
      const record = response.data.data;
      form.value = record.form;
      patient.value = record.patient;
      doctor.value = record.doctor;
      notes.value = record.notes || '';

      // Populate form data
      Object.assign(formData, record.form_data);

      // Initialize all sections as expanded
      sections.value.forEach(section => {
        expandedSections.value[section.name] = true;
      });
    }
  } catch (error) {
    console.error('Error fetching record:', error);
    alert('Failed to load consultation record');
  } finally {
    loading.value = false;
  }
};

const saveConsultation = async () => {
  // Validate form
  validationErrors.value = validateForm();

  if (Object.keys(validationErrors.value).length > 0) {
    alert('Please fix validation errors before saving');
    return;
  }

  saving.value = true;

  try {
    const payload = {
      opd_id: opdId.value,
      patient_id: patientId.value,
      doctor_id: doctor.value?.doctor_id,
      form_id: form.value.form_id,
      consultation_date: new Date().toISOString(),
      form_data: formData,
      notes: notes.value,
    };

    let response;

    if (recordId.value) {
      // Update existing record
      response = await axios.put(`/api/consultation-records/${recordId.value}`, {
        form_data: formData,
        notes: notes.value,
      });
    } else {
      // Create new record
      response = await axios.post('/api/consultation-records', payload);
    }

    if (response.data.success) {
      alert('Consultation saved successfully!');
      goBack();
    }
  } catch (error) {
    console.error('Error saving consultation:', error);

    if (error.response?.data?.errors?.form_data) {
      validationErrors.value = error.response.data.errors.form_data;
    }

    alert('Failed to save consultation: ' + (error.response?.data?.message || error.message));
  } finally {
    saving.value = false;
  }
};

const validateForm = () => {
  const errors = {};

  form.value.fields?.forEach(field => {
    if (!field.is_visible) return;

    const value = formData[field.field_key];

    // Check required fields
    if (field.is_required && !value) {
      errors[field.field_key] = `${field.field_label} is required`;
    }

    // Type-specific validation
    if (value) {
      switch (field.field_type) {
        case 'email':
          if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors[field.field_key] = `${field.field_label} must be a valid email`;
          }
          break;
        case 'number':
          if (isNaN(value)) {
            errors[field.field_key] = `${field.field_label} must be a number`;
          }
          break;
      }
    }
  });

  return errors;
};

const handleFieldChange = (fieldKey, value) => {
  formData[fieldKey] = value;

  // Remove validation error for this field if it exists
  if (validationErrors.value[fieldKey]) {
    delete validationErrors.value[fieldKey];
  }
};

const toggleSection = (sectionName) => {
  expandedSections.value[sectionName] = !expandedSections.value[sectionName];
};

const getDefaultValueForType = (fieldType) => {
  switch (fieldType) {
    case 'checkbox':
      return [];
    case 'number':
      return '';
    default:
      return '';
  }
};

const getFieldColClass = (field) => {
  // Use column_width from field if available, otherwise default based on type
  if (field.column_width) {
    return field.column_width;
  }
  // Fallback for old fields without column_width
  if (['textarea'].includes(field.field_type)) {
    return 'col-12';
  }
  return 'col-md-6';
};

const goBack = () => {
  if (opdId.value) {
    router.push(`/opd/${opdId.value}`);
  } else {
    router.push('/opd');
  }
};

onMounted(() => {
  if (recordId.value) {
    fetchRecord();
  } else {
    fetchForm();
    fetchPatient();
  }
});
</script>

<style scoped>
.consultation-form {
  padding: 1rem 0;
}

.card-header h6 {
  font-weight: 600;
}
</style>
