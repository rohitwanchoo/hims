<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Test Report Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Test Report
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
                            @input="loadReports"
                            placeholder="Search by report name, code...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadReports">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadReports">
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
                                <th style="min-width: 200px;">Report Name</th>
                                <th style="min-width: 120px;">Report Code</th>
                                <th style="min-width: 150px;">Faculty</th>
                                <th style="min-width: 100px;">Type</th>
                                <th style="min-width: 100px;">Price</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 180px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="8" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="reports.length === 0">
                                <td colspan="8" class="text-center text-muted py-3">
                                    No test reports found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in reports" :key="item.report_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.report_name }}</td>
                                <td>{{ item.report_code || '-' }}</td>
                                <td>{{ item.faculty?.faculty_name || '-' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ formatReportType(item.report_type) }}</span>
                                </td>
                                <td>{{ formatCurrency(item.base_price) }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info me-1" @click="manageTests(item)" title="Manage Tests">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editReport(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteReport(item.report_id)" title="Delete">
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
        <div class="modal fade" ref="reportModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Test Report</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveReport">
                        <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>

                            <!-- Basic Info -->
                            <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Report Name *</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.report_name"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Report Code</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.report_code">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Test Report Date</label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        v-model="form.report_date">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Faculty *</label>
                                    <select class="form-select" v-model="form.faculty_id" required>
                                        <option :value="null">Select Faculty</option>
                                        <option v-for="faculty in faculties" :key="faculty.faculty_id" :value="faculty.faculty_id">
                                            {{ faculty.faculty_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Report Type *</label>
                                    <select class="form-select" v-model="form.report_type" required>
                                        <option value="">Select Type</option>
                                        <option value="normal">Normal</option>
                                        <option value="culture">Culture</option>
                                        <option value="histo_patho">Histo Patho</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Pricing -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Pricing</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Base Price *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        class="form-control"
                                        v-model.number="form.base_price"
                                        required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Day Emergency Rate</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        class="form-control"
                                        v-model.number="form.day_emergency_rate">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Night Emergency Rate</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        class="form-control"
                                        v-model.number="form.night_emergency_rate">
                                </div>
                            </div>

                            <!-- TAT -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Turn Around Time</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TAT Hours</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.tat_hours"
                                        placeholder="Hours">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">TAT Days</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.tat_days"
                                        placeholder="Days">
                                </div>
                            </div>

                            <!-- Lab Details -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Lab Details</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Lab Type *</label>
                                    <select class="form-select" v-model="form.lab_type" required>
                                        <option value="internal">Internal</option>
                                        <option value="external">External</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" v-if="form.lab_type === 'external'">
                                    <label class="form-label">External Lab</label>
                                    <select class="form-select" v-model="form.external_lab_id">
                                        <option :value="null">Select External Lab</option>
                                        <option v-for="lab in externalLabs" :key="lab.lab_id" :value="lab.lab_id">
                                            {{ lab.lab_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Notes & Remarks -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Additional Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.notes"
                                    rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Interpretation</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.interpretation"
                                    rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.remarks"
                                    rows="2"></textarea>
                            </div>

                            <!-- Options -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Options</h6>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isActive" v-model="form.is_active">
                                        <label class="form-check-label" for="isActive">Active</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isMultiValue" v-model="form.is_multi_value">
                                        <label class="form-check-label" for="isMultiValue">Multi Value</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="reportInNewPage" v-model="form.report_in_new_page">
                                        <label class="form-check-label" for="reportInNewPage">Report in New Page</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isNonRoutine" v-model="form.is_non_routine">
                                        <label class="form-check-label" for="isNonRoutine">Non Routine</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isConfidential" v-model="form.is_confidential">
                                        <label class="form-check-label" for="isConfidential">Confidential</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isPremium" v-model="form.is_premium">
                                        <label class="form-check-label" for="isPremium">Premium</label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="showPreviousResult" v-model="form.show_previous_result">
                                        <label class="form-check-label" for="showPreviousResult">Show Previous Result</label>
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

        <!-- Manage Tests Modal -->
        <div class="modal fade" ref="manageTestsModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Tests - {{ selectedReport?.report_name }}</h5>
                        <button type="button" class="btn-close" @click="closeManageTestsModal"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Category Filter -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Filter by Category (Optional)</label>
                                <select class="form-select" v-model="selectedCategoryId" @change="loadTestsByCategory">
                                    <option value="">-- All Categories --</option>
                                    <option v-for="category in categories" :key="category.category_id" :value="category.category_id">
                                        {{ category.category_name }}
                                    </option>
                                </select>
                                <small class="text-muted">Select a category to filter tests in the report, or leave empty to show all</small>
                            </div>
                        </div>

                        <div v-if="loadingTests" class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading tests...</p>
                        </div>

                        <div v-else class="row">
                            <!-- Left Panel: All Tests in Report -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">
                                            Tests in Report ({{ categoryTests.length }})
                                        </h6>
                                        <small v-if="selectedCategoryId">
                                            Category "{{ getCategoryName(selectedCategoryId) }}" tests auto-added
                                        </small>
                                    </div>
                                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                        <div v-if="categoryTests.length === 0" class="text-center text-muted py-3">
                                            No tests in this report
                                        </div>
                                        <div v-else class="list-group">
                                            <div v-for="test in categoryTests" :key="test.test_id"
                                                 class="list-group-item d-flex justify-content-between align-items-center"
                                                 :class="{ 'bg-light': isTestInReport(test) }">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <strong>{{ test.test_name }}</strong>
                                                        <span v-if="isTestInReport(test)" class="badge bg-secondary">In Report</span>
                                                    </div>
                                                    <small class="text-muted">
                                                        Code: {{ test.test_code || 'N/A' }} |
                                                        Category: {{ test.category?.category_name || 'N/A' }}
                                                    </small>
                                                </div>
                                                <button v-if="isTestInReport(test)"
                                                        class="btn btn-sm btn-danger ms-2"
                                                        @click="removeTestMapping(test)"
                                                        :disabled="removingTestId === test.test_id">
                                                    <span v-if="removingTestId === test.test_id"
                                                          class="spinner-border spinner-border-sm"></span>
                                                    <i v-else class="bi bi-x-circle"></i>
                                                </button>
                                                <button v-else
                                                        class="btn btn-sm btn-success ms-2"
                                                        @click="addTestMapping(test)"
                                                        :disabled="addingTestId === test.test_id">
                                                    <span v-if="addingTestId === test.test_id"
                                                          class="spinner-border spinner-border-sm"></span>
                                                    <i v-else class="bi bi-plus-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Panel: Other Tests -->
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0">
                                            {{ selectedCategoryId ? 'Other Available Tests' : 'Available Tests' }}
                                            ({{ otherTests.length }})
                                        </h6>
                                    </div>
                                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                        <div v-if="otherTests.length === 0" class="text-center text-muted py-3">
                                            {{ selectedCategoryId ? 'No other tests available' : 'All tests are in the report' }}
                                        </div>
                                        <div v-else class="list-group">
                                            <div v-for="test in otherTests" :key="test.test_id"
                                                 class="list-group-item d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <strong>{{ test.test_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Code: {{ test.test_code || 'N/A' }} |
                                                        Category: {{ test.category?.category_name || 'N/A' }}
                                                    </small>
                                                </div>
                                                <button class="btn btn-sm btn-success ms-2"
                                                        @click="addTestMapping(test)"
                                                        :disabled="addingTestId === test.test_id">
                                                    <span v-if="addingTestId === test.test_id"
                                                          class="spinner-border spinner-border-sm"></span>
                                                    <i v-else class="bi bi-plus-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeManageTestsModal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Modal } from 'bootstrap';
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const reports = ref([]);
const faculties = ref([]);
const externalLabs = ref([]);

// Manage Tests Modal
const selectedReport = ref(null);
const selectedCategoryId = ref('');
const categories = ref([]);
const mappedTests = ref([]);
const availableTests = ref([]);
const loadingTests = ref(false);
const addingTestId = ref(null);
const removingTestId = ref(null);

// Store last selected category for each report (report_id -> category_id)
const reportCategoryMap = ref({});

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
    report_name: '',
    report_code: '',
    report_date: null,
    faculty_id: null,
    report_type: '',
    base_price: 0,
    day_emergency_rate: null,
    night_emergency_rate: null,
    tat_hours: null,
    tat_days: null,
    lab_type: 'internal',
    external_lab_id: null,
    notes: '',
    interpretation: '',
    remarks: '',
    is_active: true,
    is_multi_value: false,
    report_in_new_page: false,
    is_non_routine: false,
    is_confidential: false,
    is_premium: false,
    show_previous_result: false,
});

let reportModal = null;
const reportModalRef = ref(null);

let manageTestsModal = null;
const manageTestsModalRef = ref(null);

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

const formatCurrency = (value) => {
    if (!value && value !== 0) return '-';
    return `₹${parseFloat(value).toFixed(2)}`;
};

const formatReportType = (type) => {
    const types = {
        'normal': 'Normal',
        'culture': 'Culture',
        'histo_patho': 'Histo Patho'
    };
    return types[type] || type;
};

// Check if a test is already in the report
const isTestInReport = (test) => {
    return mappedTests.value.some(t => t.test_id === test.test_id);
};

// Get category name by ID
const getCategoryName = (categoryId) => {
    const category = categories.value.find(c => c.category_id == categoryId);
    return category ? category.category_name : '';
};

// Left panel: All tests in report (when category selected or not)
const categoryTests = computed(() => {
    // Always show ALL tests that are in the report
    return mappedTests.value.sort((a, b) => a.test_name.localeCompare(b.test_name));
});

// Right panel: Other tests when category selected, or available tests when no category
const otherTests = computed(() => {
    if (selectedCategoryId.value) {
        // Show tests NOT from selected category (only available, not in report)
        return availableTests.value
            .filter(t => t.category_id != selectedCategoryId.value)
            .sort((a, b) => a.test_name.localeCompare(b.test_name));
    } else {
        // Show all available tests
        return availableTests.value.sort((a, b) => a.test_name.localeCompare(b.test_name));
    }
});

onMounted(async () => {
    await nextTick();
    if (reportModalRef.value) {
        reportModal = new Modal(reportModalRef.value);
    }
    if (manageTestsModalRef.value) {
        manageTestsModal = new Modal(manageTestsModalRef.value);
    }
    loadReports();
    loadFaculties();
    loadExternalLabs();
    loadCategories();
});

const loadReports = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-reports', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            reports.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            reports.value = response.data.data || response.data;
        }

        if (!Array.isArray(reports.value)) {
            reports.value = [];
        }
    } catch (err) {
        console.error('Error loading reports:', err);
        error.value = 'Failed to load test reports';
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

const loadExternalLabs = async () => {
    try {
        const response = await axios.get('/api/pathology/lab-centers', { params: { is_active: 1, per_page: 100 } });

        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            externalLabs.value = paginatedData.data || paginatedData;
        } else {
            externalLabs.value = response.data.data || response.data;
        }

        if (!Array.isArray(externalLabs.value)) {
            externalLabs.value = [];
        }
    } catch (err) {
        console.error('Error loading external labs:', err);
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadReports();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        report_name: '',
        report_code: '',
        report_date: null,
        faculty_id: null,
        report_type: '',
        base_price: 0,
        day_emergency_rate: null,
        night_emergency_rate: null,
        tat_hours: null,
        tat_days: null,
        lab_type: 'internal',
        external_lab_id: null,
        notes: '',
        interpretation: '',
        remarks: '',
        is_active: true,
        is_multi_value: false,
        report_in_new_page: false,
        is_non_routine: false,
        is_confidential: false,
        is_premium: false,
        show_previous_result: false,
    };
    if (reportModal) {
        reportModal.show();
    }
};

const editReport = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        report_id: item.report_id,
        report_name: item.report_name,
        report_code: item.report_code,
        report_date: item.report_date,
        faculty_id: item.faculty_id,
        report_type: item.report_type,
        base_price: item.base_price,
        day_emergency_rate: item.day_emergency_rate,
        night_emergency_rate: item.night_emergency_rate,
        tat_hours: item.tat_hours,
        tat_days: item.tat_days,
        lab_type: item.lab_type,
        external_lab_id: item.external_lab_id,
        notes: item.notes,
        interpretation: item.interpretation,
        remarks: item.remarks,
        is_active: item.is_active,
        is_multi_value: item.is_multi_value,
        report_in_new_page: item.report_in_new_page,
        is_non_routine: item.is_non_routine,
        is_confidential: item.is_confidential,
        is_premium: item.is_premium,
        show_previous_result: item.show_previous_result,
    };
    if (reportModal) {
        reportModal.show();
    }
};

const saveReport = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/test-reports/${form.value.report_id}`, form.value);
        } else {
            await axios.post('/api/pathology/test-reports', form.value);
        }
        closeModal();
        loadReports();
    } catch (err) {
        console.error('Error saving report:', err);
        error.value = err.response?.data?.message || 'Failed to save test report';
    } finally {
        saving.value = false;
    }
};

const deleteReport = async (id) => {
    if (!confirm('Are you sure you want to delete this test report?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/test-reports/${id}`);
        loadReports();
    } catch (err) {
        console.error('Error deleting report:', err);
        alert(err.response?.data?.message || 'Failed to delete test report');
    }
};

const closeModal = () => {
    if (reportModal) {
        reportModal.hide();
    }
};

// Manage Tests Functions
const loadCategories = async () => {
    try {
        const response = await axios.get('/api/pathology/test-categories', {
            params: { is_active: 1, per_page: 100 }
        });

        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            categories.value = paginatedData.data || paginatedData;
        } else {
            categories.value = response.data.data || response.data;
        }

        if (!Array.isArray(categories.value)) {
            categories.value = [];
        }
    } catch (err) {
        console.error('Error loading categories:', err);
    }
};

const manageTests = (report) => {
    selectedReport.value = report;

    // Restore last selected category for this report (if any)
    selectedCategoryId.value = reportCategoryMap.value[report.report_id] || '';

    mappedTests.value = [];
    availableTests.value = [];
    if (manageTestsModal) {
        manageTestsModal.show();
    }
    // Load all tests immediately when modal opens
    loadTestsByCategory();
};

const loadTestsByCategory = async () => {
    if (!selectedReport.value) {
        return;
    }

    // Save the selected category for this report
    if (selectedReport.value.report_id) {
        reportCategoryMap.value[selectedReport.value.report_id] = selectedCategoryId.value;
    }

    loadingTests.value = true;
    try {
        const params = {};
        if (selectedCategoryId.value) {
            params.category_id = selectedCategoryId.value;
        }

        const response = await axios.get(
            `/api/pathology/test-reports/${selectedReport.value.report_id}/tests`,
            { params }
        );

        if (response.data.success) {
            mappedTests.value = response.data.data.mapped_tests || [];
            availableTests.value = response.data.data.available_tests || [];

            // Auto-add all tests from selected category to report
            if (selectedCategoryId.value) {
                await autoAddCategoryTests();
            }
        }
    } catch (err) {
        console.error('Error loading tests:', err);
        alert(err.response?.data?.message || 'Failed to load tests');
    } finally {
        loadingTests.value = false;
    }
};

// Automatically add all tests from the selected category to the report
const autoAddCategoryTests = async () => {
    if (!selectedCategoryId.value || !selectedReport.value) {
        return;
    }

    // Find tests from selected category that are not yet in the report
    const testsToAdd = availableTests.value.filter(test =>
        test.category_id == selectedCategoryId.value
    );

    if (testsToAdd.length === 0) {
        return; // All category tests are already in the report
    }

    // Add all tests in parallel
    const addPromises = testsToAdd.map(test =>
        axios.post(`/api/pathology/test-reports/${selectedReport.value.report_id}/tests`, {
            test_id: test.test_id
        }).then(() => test)
          .catch(err => {
              console.error(`Failed to add test ${test.test_name}:`, err);
              return null;
          })
    );

    try {
        const results = await Promise.all(addPromises);

        // Update the lists
        results.forEach(test => {
            if (test) {
                // Add to mapped tests
                if (!mappedTests.value.some(t => t.test_id === test.test_id)) {
                    mappedTests.value.push(test);
                }

                // Remove from available tests
                availableTests.value = availableTests.value.filter(t => t.test_id !== test.test_id);
            }
        });
    } catch (err) {
        console.error('Error auto-adding category tests:', err);
    }
};

const addTestMapping = async (test) => {
    addingTestId.value = test.test_id;
    try {
        await axios.post(`/api/pathology/test-reports/${selectedReport.value.report_id}/tests`, {
            test_id: test.test_id
        });

        // Add to mapped list
        mappedTests.value.push(test);

        // Remove from available list
        availableTests.value = availableTests.value.filter(t => t.test_id !== test.test_id);
    } catch (err) {
        console.error('Error adding test mapping:', err);
        alert(err.response?.data?.message || 'Failed to add test mapping');
    } finally {
        addingTestId.value = null;
    }
};

const removeTestMapping = async (test) => {
    removingTestId.value = test.test_id;
    try {
        await axios.delete(
            `/api/pathology/test-reports/${selectedReport.value.report_id}/tests/${test.test_id}`
        );

        // Remove from mapped list
        mappedTests.value = mappedTests.value.filter(t => t.test_id !== test.test_id);

        // Add back to available list
        availableTests.value.push(test);
        availableTests.value.sort((a, b) => a.test_name.localeCompare(b.test_name));
    } catch (err) {
        console.error('Error removing test mapping:', err);
        alert(err.response?.data?.message || 'Failed to remove test mapping');
    } finally {
        removingTestId.value = null;
    }
};

const closeManageTestsModal = () => {
    // Save current category selection before closing
    if (selectedReport.value && selectedReport.value.report_id) {
        reportCategoryMap.value[selectedReport.value.report_id] = selectedCategoryId.value;
    }

    if (manageTestsModal) {
        manageTestsModal.hide();
    }

    // Clear modal state
    selectedReport.value = null;
    mappedTests.value = [];
    availableTests.value = [];
    // Note: We don't clear selectedCategoryId here - it will be restored when reopening
};
</script>

<style scoped>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.bg-light-success {
    background-color: #d1f5e3 !important;
}

.border-success {
    border-color: #28a745 !important;
}

.border-2 {
    border-width: 2px !important;
}

.list-group-item.border-success {
    transition: all 0.2s ease;
}

.list-group-item.border-success:hover {
    background-color: #c3f0d9 !important;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
}
</style>
