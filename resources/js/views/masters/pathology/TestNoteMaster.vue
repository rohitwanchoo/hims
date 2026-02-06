<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Test Note Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Test Note
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
                            @input="loadNotes"
                            placeholder="Search by note title...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.note_for" @change="loadNotes">
                            <option value="">All Types</option>
                            <option value="test_master">Test Master</option>
                            <option value="test_report">Test Report</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadNotes">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadNotes">
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
                                <th style="min-width: 120px;">Note For</th>
                                <th style="min-width: 200px;">Linked To</th>
                                <th style="min-width: 350px;">Note Content</th>
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
                            <tr v-else-if="notes.length === 0">
                                <td colspan="6" class="text-center text-muted py-3">
                                    No test notes found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in notes" :key="item.note_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>
                                    <span :class="item.note_for === 'test_master' ? 'badge bg-primary' : 'badge bg-info'">
                                        {{ formatNoteFor(item.note_for) }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.note_for === 'test_master'">
                                        {{ item.patho_test?.test_name || '-' }}
                                    </span>
                                    <span v-else>
                                        {{ item.patho_test_report?.report_name || '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 350px;" :title="item.note_text">
                                        {{ item.note_text || '-' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editNote(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteNote(item.note_id)" title="Delete">
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
        <div class="modal fade" ref="noteModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Test Note</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveNote">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Note For *</label>
                                <select class="form-select" v-model="form.note_for" @change="handleNoteForChange" required>
                                    <option value="">Select Type</option>
                                    <option value="test_master">Test Master</option>
                                    <option value="test_report">Test Report</option>
                                </select>
                                <small class="text-muted">
                                    Test Master: applies to individual test | Test Report: applies to entire report
                                </small>
                            </div>

                            <!-- Conditional Field: Test Master -->
                            <div v-if="form.note_for === 'test_master'" class="mb-3">
                                <label class="form-label">Select Test *</label>
                                <select class="form-select" v-model="form.test_master_id" required>
                                    <option value="">Select Test</option>
                                    <option v-for="test in testMasters" :key="test.test_id" :value="test.test_id">
                                        {{ test.test_name }} {{ test.test_code ? `(${test.test_code})` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Conditional Field: Test Report -->
                            <div v-if="form.note_for === 'test_report'" class="mb-3">
                                <label class="form-label">Select Report *</label>
                                <select class="form-select" v-model="form.test_report_id" required>
                                    <option value="">Select Report</option>
                                    <option v-for="report in testReports" :key="report.report_id" :value="report.report_id">
                                        {{ report.report_name }} {{ report.report_code ? `(${report.report_code})` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Note Content -->
                            <div class="mb-3">
                                <label class="form-label">Note Content *</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.note_content"
                                    rows="4"
                                    placeholder="Enter the note content that will appear in reports..."
                                    required></textarea>
                                <small class="text-muted">This content will be displayed in the test report</small>
                            </div>

                            <!-- Additional Options -->
                            <div class="form-check mb-3">
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
const notes = ref([]);
const testMasters = ref([]);
const testReports = ref([]);

const filters = ref({
    search: '',
    note_for: '',
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
    note_for: '',
    test_master_id: '',
    test_report_id: '',
    note_content: '',
    is_active: true,
});

let noteModal = null;
const noteModalRef = ref(null);

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
    if (noteModalRef.value) {
        noteModal = new Modal(noteModalRef.value);
    }
    loadNotes();
});

const formatNoteFor = (type) => {
    if (!type) return '-';
    return type === 'test_master' ? 'Test Master' : 'Test Report';
};

const loadNotes = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-notes', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            notes.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            notes.value = response.data.data || response.data;
        }

        if (!Array.isArray(notes.value)) {
            notes.value = [];
        }
    } catch (err) {
        console.error('Error loading notes:', err);
        error.value = 'Failed to load test notes';
    } finally {
        loading.value = false;
    }
};

const loadTestMasters = async () => {
    try {
        const response = await axios.get('/api/pathology/tests', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            testMasters.value = paginatedData.data || paginatedData;
        } else {
            testMasters.value = response.data.data || response.data;
        }
        if (!Array.isArray(testMasters.value)) {
            testMasters.value = [];
        }
    } catch (err) {
        console.error('Error loading test masters:', err);
    }
};

const loadTestReports = async () => {
    try {
        const response = await axios.get('/api/pathology/test-reports', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            testReports.value = paginatedData.data || paginatedData;
        } else {
            testReports.value = response.data.data || response.data;
        }
        if (!Array.isArray(testReports.value)) {
            testReports.value = [];
        }
    } catch (err) {
        console.error('Error loading test reports:', err);
    }
};

const handleNoteForChange = () => {
    // Clear previous selections when note_for changes
    form.value.test_master_id = '';
    form.value.test_report_id = '';

    // Load appropriate data based on selection
    if (form.value.note_for === 'test_master') {
        loadTestMasters();
    } else if (form.value.note_for === 'test_report') {
        loadTestReports();
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadNotes();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        note_for: '',
        test_master_id: '',
        test_report_id: '',
        note_content: '',
        is_active: true,
    };
    if (noteModal) {
        noteModal.show();
    }
};

const editNote = async (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        note_id: item.note_id,
        note_for: item.note_for,
        test_master_id: item.test_id || '',
        test_report_id: item.report_id || '',
        note_content: item.note_text || '',
        is_active: item.is_active,
    };

    // Load appropriate data based on note_for
    if (item.note_for === 'test_master') {
        await loadTestMasters();
    } else if (item.note_for === 'test_report') {
        await loadTestReports();
    }

    if (noteModal) {
        noteModal.show();
    }
};

const saveNote = async () => {
    saving.value = true;
    error.value = null;
    try {
        // Prepare data based on note_for
        const payload = { ...form.value };
        if (form.value.note_for === 'test_master') {
            payload.test_report_id = null;
        } else if (form.value.note_for === 'test_report') {
            payload.test_master_id = null;
        }

        if (editMode.value) {
            await axios.put(`/api/pathology/test-notes/${form.value.note_id}`, payload);
        } else {
            await axios.post('/api/pathology/test-notes', payload);
        }
        closeModal();
        loadNotes();
    } catch (err) {
        console.error('Error saving note:', err);
        error.value = err.response?.data?.message || 'Failed to save test note';
    } finally {
        saving.value = false;
    }
};

const deleteNote = async (id) => {
    if (!confirm('Are you sure you want to delete this test note?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/test-notes/${id}`);
        loadNotes();
    } catch (err) {
        console.error('Error deleting note:', err);
        alert(err.response?.data?.message || 'Failed to delete test note');
    }
};

const closeModal = () => {
    if (noteModal) {
        noteModal.hide();
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
