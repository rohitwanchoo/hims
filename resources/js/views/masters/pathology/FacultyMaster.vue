<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Pathology Faculty Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Faculty
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            v-model="filters.search"
                            @input="loadFaculties"
                            placeholder="Search by faculty name...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadFaculties">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadFaculties">
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
                                <th style="min-width: 200px;">Faculty Name</th>
                                <th style="min-width: 150px;">Faculty Code</th>
                                <th style="min-width: 200px;">Remarks</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="6" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="faculties.length === 0">
                                <td colspan="6" class="text-center text-muted py-3">
                                    No faculties found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in faculties" :key="item.faculty_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.faculty_name }}</td>
                                <td>{{ item.faculty_code || '-' }}</td>
                                <td>{{ item.remarks || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editFaculty(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteFaculty(item.faculty_id)" title="Delete">
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
        <div class="modal fade" ref="facultyModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Faculty</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveFaculty">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Faculty Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.faculty_name"
                                    placeholder="e.g., Hematology, Biochemistry"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Faculty Code</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.faculty_code"
                                    placeholder="e.g., HEM, BIO">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.remarks"
                                    rows="3"
                                    placeholder="Optional description"></textarea>
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
const faculties = ref([]);
const filters = ref({
    search: '',
    is_active: '',
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
    faculty_name: '',
    faculty_code: '',
    remarks: '',
    is_active: true,
});

let facultyModal = null;
const facultyModalRef = ref(null);

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
    if (facultyModalRef.value) {
        facultyModal = new Modal(facultyModalRef.value);
    }
    loadFaculties();
});

const loadFaculties = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/faculties', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            faculties.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            faculties.value = response.data.data || response.data;
        }

        // Ensure faculties is always an array
        if (!Array.isArray(faculties.value)) {
            faculties.value = [];
        }
    } catch (err) {
        console.error('Error loading faculties:', err);
        error.value = 'Failed to load faculties';
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadFaculties();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        faculty_name: '',
        faculty_code: '',
        remarks: '',
        is_active: true,
    };
    if (facultyModal) {
        facultyModal.show();
    }
};

const editFaculty = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        faculty_id: item.faculty_id,
        faculty_name: item.faculty_name,
        faculty_code: item.faculty_code,
        remarks: item.remarks,
        is_active: item.is_active,
    };
    if (facultyModal) {
        facultyModal.show();
    }
};

const saveFaculty = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/faculties/${form.value.faculty_id}`, form.value);
        } else {
            await axios.post('/api/pathology/faculties', form.value);
        }
        closeModal();
        loadFaculties();
    } catch (err) {
        console.error('Error saving faculty:', err);
        error.value = err.response?.data?.message || 'Failed to save faculty';
    } finally {
        saving.value = false;
    }
};

const deleteFaculty = async (id) => {
    if (!confirm('Are you sure you want to delete this faculty?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/faculties/${id}`);
        loadFaculties();
    } catch (err) {
        console.error('Error deleting faculty:', err);
        alert(err.response?.data?.message || 'Failed to delete faculty');
    }
};

const closeModal = () => {
    if (facultyModal) {
        facultyModal.hide();
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
