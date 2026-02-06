<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Pathologist Doctor Mapping</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Pathologist Mapping
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filters.search"
                            @input="loadMappings"
                            placeholder="Search by doctor name...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.faculty_id" @change="loadMappings">
                            <option value="">All Faculties</option>
                            <option v-for="faculty in faculties" :key="faculty.faculty_id" :value="faculty.faculty_id">
                                {{ faculty.faculty_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadMappings">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadMappings">
                            <option value="20">20 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive" style="max-height: calc(100vh - 280px); overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th style="min-width: 200px;">Doctor Name</th>
                                <th style="min-width: 150px;">Faculty</th>
                                <th style="min-width: 150px;">Qualification</th>
                                <th style="width: 120px;" class="text-center">Signature</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="mappings.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    No pathologist mappings found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in mappings" :key="item.map_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.doctor?.name || '-' }}</td>
                                <td>{{ item.faculty?.faculty_name || '-' }}</td>
                                <td>{{ item.doctor?.specialization || '-' }}</td>
                                <td class="text-center">
                                    <span v-if="item.signature_path" class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Yes
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editMapping(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteMapping(item.map_id)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > 0">
                    <div class="text-muted small">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">Previous</a>
                            </li>
                            <li class="page-item" v-for="page in visiblePages" :key="page" :class="{ active: page === pagination.current_page }">
                                <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" ref="mappingModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Pathologist Mapping</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveMapping">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Faculty *</label>
                                    <select class="form-select" v-model="form.faculty_id" required>
                                        <option value="">Select Faculty</option>
                                        <option v-for="faculty in faculties" :key="faculty.faculty_id" :value="faculty.faculty_id">
                                            {{ faculty.faculty_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Doctor *</label>
                                    <select class="form-select" v-model="form.doctor_id" required>
                                        <option value="">Select Doctor</option>
                                        <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                            {{ doctor.name }} - {{ doctor.specialization || 'N/A' }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Signature Upload</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    ref="signatureFileInput"
                                    @change="handleFileChange"
                                    accept="image/*">
                                <small class="text-muted">Recommended: PNG/JPG, max 2MB, transparent background preferred</small>

                                <!-- Current Signature Preview -->
                                <div v-if="currentSignaturePath && !signaturePreview" class="mt-2">
                                    <label class="d-block text-muted small mb-1">Current Signature:</label>
                                    <img :src="currentSignaturePath" alt="Current Signature" class="border rounded p-2" style="max-height: 100px; background: #f8f9fa;">
                                </div>

                                <!-- New Signature Preview -->
                                <div v-if="signaturePreview" class="mt-2">
                                    <label class="d-block text-muted small mb-1">New Signature Preview:</label>
                                    <img :src="signaturePreview" alt="Signature Preview" class="border rounded p-2" style="max-height: 100px; background: #f8f9fa;">
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" @click="clearSignature">
                                        <i class="bi bi-x"></i> Remove
                                    </button>
                                </div>
                            </div>
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    id="isActive"
                                    v-model="form.is_active">
                                <label class="form-check-label" for="isActive">
                                    Active
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                {{ saving ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Modal } from 'bootstrap';
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const mappings = ref([]);
const faculties = ref([]);
const doctors = ref([]);
const signatureFile = ref(null);
const signaturePreview = ref(null);
const currentSignaturePath = ref(null);
const signatureFileInput = ref(null);

const filters = ref({
    search: '',
    faculty_id: '',
    status: '',
    per_page: 20,
    page: 1,
});

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0,
});

const editMode = ref(false);
const form = ref({
    faculty_id: '',
    doctor_id: '',
    is_active: true,
});

let mappingModal = null;
const mappingModalRef = ref(null);

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    let start = Math.max(1, current - 2);
    let end = Math.min(last, current + 2);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

onMounted(async () => {
    await nextTick();
    if (mappingModalRef.value) {
        mappingModal = new Modal(mappingModalRef.value);
    }
    loadMappings();
    loadFaculties();
    loadDoctors();
});

const loadMappings = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/pathologist-mappings', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            mappings.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            mappings.value = response.data.data || response.data;
        }

        // Ensure array
        if (!Array.isArray(mappings.value)) {
            mappings.value = [];
        }
    } catch (err) {
        console.error('Error loading mappings:', err);
        error.value = 'Failed to load pathologist mappings';
    } finally {
        loading.value = false;
    }
};

const loadFaculties = async () => {
    try {
        const response = await axios.get('/api/pathology/faculties', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            faculties.value = paginatedData.data || paginatedData;
        } else {
            faculties.value = response.data.data || response.data;
        }
        if (!Array.isArray(faculties.value)) {
            faculties.value = [];
        }
    } catch (err) {
        console.error('Error loading faculties:', err);
    }
};

const loadDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            doctors.value = paginatedData.data || paginatedData;
        } else {
            doctors.value = response.data.data || response.data;
        }
        if (!Array.isArray(doctors.value)) {
            doctors.value = [];
        }
    } catch (err) {
        console.error('Error loading doctors:', err);
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadMappings();
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
            event.target.value = '';
            return;
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            event.target.value = '';
            return;
        }

        signatureFile.value = file;

        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            signaturePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const clearSignature = () => {
    signatureFile.value = null;
    signaturePreview.value = null;
    if (signatureFileInput.value) {
        signatureFileInput.value.value = '';
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        faculty_id: '',
        doctor_id: '',
        is_active: true,
    };
    clearSignature();
    currentSignaturePath.value = null;
    if (mappingModal) {
        mappingModal.show();
    }
};

const editMapping = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        map_id: item.map_id,
        faculty_id: item.faculty_id,
        doctor_id: item.doctor_id,
        is_active: item.is_active,
    };
    clearSignature();
    currentSignaturePath.value = item.signature_path ? `/storage/${item.signature_path}` : null;
    if (mappingModal) {
        mappingModal.show();
    }
};

const saveMapping = async () => {
    saving.value = true;
    error.value = null;
    try {
        const formData = new FormData();
        formData.append('faculty_id', form.value.faculty_id);
        formData.append('doctor_id', form.value.doctor_id);
        formData.append('is_active', form.value.is_active ? 1 : 0);

        if (signatureFile.value) {
            formData.append('signature', signatureFile.value);
        }

        if (editMode.value) {
            formData.append('_method', 'PUT');
            await axios.post(`/api/pathology/pathologist-mappings/${form.value.map_id}`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
        } else {
            await axios.post('/api/pathology/pathologist-mappings', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
        }
        closeModal();
        loadMappings();
    } catch (err) {
        console.error('Error saving mapping:', err);
        error.value = err.response?.data?.message || 'Failed to save pathologist mapping';
    } finally {
        saving.value = false;
    }
};

const deleteMapping = async (id) => {
    if (!confirm('Are you sure you want to delete this pathologist mapping?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/pathologist-mappings/${id}`);
        loadMappings();
    } catch (err) {
        console.error('Error deleting mapping:', err);
        alert(err.response?.data?.message || 'Failed to delete pathologist mapping');
    }
};

const closeModal = () => {
    if (mappingModal) {
        mappingModal.hide();
    }
};
</script>

<style scoped>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
