<template>
  <div class="consultation-forms-list">
    <div class="container-fluid py-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
          <i class="bi bi-file-earmark-medical me-2"></i>
          Consultation Forms
        </h2>
        <router-link to="/consultation-forms/create" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Create New Form
        </router-link>
      </div>

      <!-- Filters -->
      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Form Type</label>
              <select v-model="filters.form_type" @change="fetchForms" class="form-select">
                <option value="">All Types</option>
                <option value="general">General</option>
                <option value="opd">OPD</option>
                <option value="ipd">IPD</option>
                <option value="specialty">Specialty</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select v-model="filters.is_active" @change="fetchForms" class="form-select">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button @click="resetFilters" class="btn btn-outline-secondary w-100">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Forms List -->
      <div v-else-if="forms.length > 0" class="row g-3">
        <div v-for="form in forms" :key="form.form_id" class="col-md-6 col-lg-4">
          <div class="card h-100 form-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <h5 class="card-title mb-0">{{ form.form_name }}</h5>
                <div class="d-flex gap-1">
                  <span
                    v-if="form.is_default"
                    class="badge bg-primary"
                    title="Default Form"
                  >
                    <i class="bi bi-star-fill"></i>
                  </span>
                  <span
                    :class="form.is_active ? 'badge bg-success' : 'badge bg-secondary'"
                  >
                    {{ form.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>

              <p v-if="form.description" class="card-text text-muted small">
                {{ form.description }}
              </p>

              <div class="mb-3">
                <span class="badge bg-info me-2">{{ formatType(form.form_type) }}</span>
                <span v-if="form.department" class="badge bg-light text-dark">
                  {{ form.department.department_name }}
                </span>
              </div>

              <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                <span>
                  <i class="bi bi-list-ul me-1"></i>
                  {{ form.fields?.length || 0 }} fields
                </span>
                <span>
                  <i class="bi bi-calendar me-1"></i>
                  {{ formatDate(form.updated_at) }}
                </span>
              </div>

              <div class="d-flex gap-2">
                <router-link
                  :to="`/consultation-forms/${form.form_id}/edit`"
                  class="btn btn-sm btn-outline-primary flex-grow-1"
                >
                  <i class="bi bi-pencil me-1"></i> Edit
                </router-link>
                <button
                  @click="toggleFormStatus(form)"
                  class="btn btn-sm btn-outline-secondary"
                  :title="form.is_active ? 'Deactivate' : 'Activate'"
                >
                  <i :class="form.is_active ? 'bi bi-toggle-on' : 'bi bi-toggle-off'"></i>
                </button>
                <button
                  @click="deleteForm(form)"
                  class="btn btn-sm btn-outline-danger"
                  title="Delete"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-5">
        <i class="bi bi-inbox fs-1 text-muted"></i>
        <p class="mt-3 text-muted">No consultation forms found</p>
        <router-link to="/consultation-forms/create" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i> Create Your First Form
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const forms = ref([]);

const filters = reactive({
  form_type: '',
  is_active: '',
});

const fetchForms = async () => {
  loading.value = true;

  try {
    const params = {};
    if (filters.form_type) params.form_type = filters.form_type;
    if (filters.is_active !== '') params.is_active = filters.is_active;

    const response = await axios.get('/api/consultation-forms', { params });

    if (response.data.success) {
      forms.value = response.data.data;
    }
  } catch (error) {
    console.error('Error fetching forms:', error);
    alert('Failed to load forms');
  } finally {
    loading.value = false;
  }
};

const toggleFormStatus = async (form) => {
  try {
    const response = await axios.put(`/api/consultation-forms/${form.form_id}`, {
      is_active: !form.is_active,
    });

    if (response.data.success) {
      form.is_active = response.data.data.is_active;
    }
  } catch (error) {
    console.error('Error toggling form status:', error);
    alert('Failed to update form status');
  }
};

const deleteForm = async (form) => {
  if (!confirm(`Are you sure you want to delete "${form.form_name}"?\n\nThis action cannot be undone.`)) {
    return;
  }

  try {
    await axios.delete(`/api/consultation-forms/${form.form_id}`);
    forms.value = forms.value.filter(f => f.form_id !== form.form_id);
    alert('Form deleted successfully');
  } catch (error) {
    console.error('Error deleting form:', error);
    alert(error.response?.data?.message || 'Failed to delete form');
  }
};

const resetFilters = () => {
  filters.form_type = '';
  filters.is_active = '';
  fetchForms();
};

const formatType = (type) => {
  const types = {
    general: 'General',
    opd: 'OPD',
    ipd: 'IPD',
    specialty: 'Specialty',
  };
  return types[type] || type;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
};

onMounted(() => {
  fetchForms();
});
</script>

<style scoped>
.form-card {
  transition: transform 0.2s, box-shadow 0.2s;
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.form-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>
