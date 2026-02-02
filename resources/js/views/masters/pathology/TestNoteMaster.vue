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
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadNotes">
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
                                <th style="min-width: 200px;">Note Title</th>
                                <th style="min-width: 120px;">Note For</th>
                                <th style="min-width: 200px;">Linked To</th>
                                <th style="min-width: 250px;">Note Content</th>
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
                            <tr v-else-if="notes.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    No test notes found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in notes" :key="item.id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.note_title }}</td>
                                <td>
                                    <span :class="item.note_for === 'test_master' ? 'badge bg-primary' : 'badge bg-info'">
                                        {{ formatNoteFor(item.note_for) }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="item.note_for === 'test_master'">
                                        {{ item.test_master?.test_name || '-' }}
                                    </span>
                                    <span v-else>
                                        {{ item.test_report?.report_name || '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;" :title="item.note_content">
                                        {{ item.note_content }}
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
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteNote(item.id)" title="Delete">
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Note Title *</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.note_title"
                                        placeholder="e.g., Fasting Required"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
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
                            </div>

                            <!-- Conditional Field: Test Master -->
                            <div v-if="form.note_for === 'test_master'" class="mb-3">
                                <label class="form-label">Select Test *</label>
                                <select class="form-select" v-model="form.test_master_id" required>
                                    <option value="">Select Test</option>
                                    <option v-for="test in testMasters" :key="test.id" :value="test.id">
                                        {{ test.test_name }} {{ test.test_code ? `(${test.test_code})` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Conditional Field: Test Report -->
                            <div v-if="form.note_for === 'test_report'" class="mb-3">
                                <label class="form-label">Select Report *</label>
                                <select class="form-select" v-model="form.test_report_id" required>
                                    <option value="">Select Report</option>
                                    <option v-for="report in testReports" :key="report.id" :value="report.id">
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Display Order</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.sort_order"
                                        placeholder="Sort order">
                                </div>
                                <div class="col-md-6 mb-3 d-flex align-items-end">
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
    note_title: '',
    note_for: '',
    test_master_id: '',
    test_report_id: '',
    note_content: '',
    sort_order: 0,
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
        if (response.data.data) {
            notes.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        } else {
            notes.value = response.data;
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
        const response = await axios.get('/api/pathology/test-masters', { params: { status: 1 } });
        testMasters.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading test masters:', err);
    }
};

const loadTestReports = async () => {
    try {
        const response = await axios.get('/api/pathology/test-reports', { params: { status: 1 } });
        testReports.value = response.data.data || response.data;
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
        note_title: '',
        note_for: '',
        test_master_id: '',
        test_report_id: '',
        note_content: '',
        sort_order: 0,
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
        id: item.id,
        note_title: item.note_title,
        note_for: item.note_for,
        test_master_id: item.test_master_id || '',
        test_report_id: item.test_report_id || '',
        note_content: item.note_content,
        sort_order: item.sort_order,
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
            await axios.put(`/api/pathology/test-notes/${form.value.id}`, payload);
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
