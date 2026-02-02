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
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadReports">
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
                                <th style="min-width: 120px;">Base Price</th>
                                <th style="min-width: 100px;">TAT</th>
                                <th style="min-width: 150px;">Sample Type</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 120px;" class="text-center">Actions</th>
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
                            <tr v-else v-for="(item, index) in reports" :key="item.id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.report_name }}</td>
                                <td>{{ item.report_code || '-' }}</td>
                                <td>{{ formatCurrency(item.base_price) }}</td>
                                <td>
                                    <span v-if="item.tat_value" class="badge bg-info">
                                        {{ item.tat_value }} {{ item.tat_unit }}
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td>{{ item.sample_type?.sample_name || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editReport(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteReport(item.id)" title="Delete">
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

                            <!-- Tabs -->
                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">
                                        <i class="bi bi-info-circle"></i> Basic Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button" role="tab">
                                        <i class="bi bi-currency-dollar"></i> Pricing
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="tat-tab" data-bs-toggle="tab" data-bs-target="#tat" type="button" role="tab">
                                        <i class="bi bi-clock-history"></i> TAT & Lab
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="sample-tab" data-bs-toggle="tab" data-bs-target="#sample" type="button" role="tab">
                                        <i class="bi bi-droplet"></i> Sample Details
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <!-- Basic Info Tab -->
                                <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Report Name *</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="form.report_name"
                                                placeholder="e.g., Complete Blood Count"
                                                required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Report Code</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="form.report_code"
                                                placeholder="e.g., CBC">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Category</label>
                                            <select class="form-select" v-model="form.category_id">
                                                <option :value="null">Select Category</option>
                                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                                    {{ cat.category_name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sort Order</label>
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model.number="form.sort_order"
                                                placeholder="Display order">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea
                                            class="form-control"
                                            v-model="form.description"
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

                                <!-- Pricing Tab -->
                                <div class="tab-pane fade" id="pricing" role="tabpanel">
                                    <h6 class="border-bottom pb-2 mb-3">Standard Pricing</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Base Price *</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.base_price"
                                                placeholder="0.00"
                                                required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">OPD Price</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.opd_price"
                                                placeholder="0.00">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Emergency Price</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.emergency_price"
                                                placeholder="0.00">
                                        </div>
                                    </div>

                                    <h6 class="border-bottom pb-2 mb-3 mt-4">Ward-wise Pricing</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">General Ward</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.general_ward_price"
                                                placeholder="0.00">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Semi Private</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.semi_private_price"
                                                placeholder="0.00">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Private Ward</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.private_ward_price"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Deluxe Ward</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.deluxe_ward_price"
                                                placeholder="0.00">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">ICU Price</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                v-model.number="form.icu_price"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                </div>

                                <!-- TAT & Lab Tab -->
                                <div class="tab-pane fade" id="tat" role="tabpanel">
                                    <h6 class="border-bottom pb-2 mb-3">Turn Around Time (TAT)</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TAT Value</label>
                                            <input
                                                type="number"
                                                class="form-control"
                                                v-model.number="form.tat_value"
                                                placeholder="e.g., 24">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">TAT Unit</label>
                                            <select class="form-select" v-model="form.tat_unit">
                                                <option value="">Select Unit</option>
                                                <option value="minutes">Minutes</option>
                                                <option value="hours">Hours</option>
                                                <option value="days">Days</option>
                                                <option value="weeks">Weeks</option>
                                            </select>
                                        </div>
                                    </div>

                                    <h6 class="border-bottom pb-2 mb-3 mt-4">Lab Details</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Processing Lab</label>
                                            <select class="form-select" v-model="form.processing_lab">
                                                <option value="">Select Lab</option>
                                                <option value="in_house">In-House</option>
                                                <option value="external">External Lab</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3" v-if="form.processing_lab === 'external'">
                                            <label class="form-label">External Lab</label>
                                            <select class="form-select" v-model="form.external_lab_id">
                                                <option :value="null">Select External Lab</option>
                                                <option v-for="lab in externalLabs" :key="lab.id" :value="lab.id">
                                                    {{ lab.lab_name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sample Details Tab -->
                                <div class="tab-pane fade" id="sample" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sample Type</label>
                                            <select class="form-select" v-model="form.sample_type_id">
                                                <option :value="null">Select Sample Type</option>
                                                <option v-for="sample in sampleTypes" :key="sample.id" :value="sample.id">
                                                    {{ sample.sample_name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Container</label>
                                            <select class="form-select" v-model="form.container_id">
                                                <option :value="null">Select Container</option>
                                                <option v-for="container in containers" :key="container.id" :value="container.id">
                                                    {{ container.container_name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sample Quantity</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                v-model="form.sample_quantity"
                                                placeholder="e.g., 5 ml">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Instruction</label>
                                            <select class="form-select" v-model="form.instruction_id">
                                                <option :value="null">Select Instruction</option>
                                                <option v-for="inst in instructions" :key="inst.id" :value="inst.id">
                                                    {{ inst.instruction_name }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Special Instructions</label>
                                        <textarea
                                            class="form-control"
                                            v-model="form.special_instructions"
                                            rows="3"
                                            placeholder="Any special sample handling instructions"></textarea>
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
const reports = ref([]);
const categories = ref([]);
const sampleTypes = ref([]);
const containers = ref([]);
const instructions = ref([]);
const externalLabs = ref([]);

const filters = ref({
    search: '',
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
    report_name: '',
    report_code: '',
    category_id: null,
    sort_order: 0,
    description: '',
    base_price: 0,
    opd_price: null,
    emergency_price: null,
    general_ward_price: null,
    semi_private_price: null,
    private_ward_price: null,
    deluxe_ward_price: null,
    icu_price: null,
    tat_value: null,
    tat_unit: '',
    processing_lab: '',
    external_lab_id: null,
    sample_type_id: null,
    container_id: null,
    sample_quantity: '',
    instruction_id: null,
    special_instructions: '',
    is_active: true,
});

let reportModal = null;
const reportModalRef = ref(null);

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
    if (!value) return '-';
    return `₹${parseFloat(value).toFixed(2)}`;
};

onMounted(async () => {
    await nextTick();
    if (reportModalRef.value) {
        reportModal = new Modal(reportModalRef.value);
    }
    loadReports();
    loadCategories();
    loadSampleTypes();
    loadContainers();
    loadInstructions();
    loadExternalLabs();
});

const loadReports = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-reports', { params: filters.value });
        if (response.data.data) {
            reports.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        } else {
            reports.value = response.data;
        }
    } catch (err) {
        console.error('Error loading reports:', err);
        error.value = 'Failed to load test reports';
    } finally {
        loading.value = false;
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get('/api/pathology/test-categories', { params: { status: 1 } });
        categories.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading categories:', err);
    }
};

const loadSampleTypes = async () => {
    try {
        const response = await axios.get('/api/pathology/sample-types', { params: { status: 1 } });
        sampleTypes.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading sample types:', err);
    }
};

const loadContainers = async () => {
    try {
        const response = await axios.get('/api/pathology/containers', { params: { status: 1 } });
        containers.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading containers:', err);
    }
};

const loadInstructions = async () => {
    try {
        const response = await axios.get('/api/pathology/instructions', { params: { status: 1 } });
        instructions.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading instructions:', err);
    }
};

const loadExternalLabs = async () => {
    try {
        const response = await axios.get('/api/pathology/external-labs', { params: { status: 1 } });
        externalLabs.value = response.data.data || response.data;
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
        category_id: null,
        sort_order: 0,
        description: '',
        base_price: 0,
        opd_price: null,
        emergency_price: null,
        general_ward_price: null,
        semi_private_price: null,
        private_ward_price: null,
        deluxe_ward_price: null,
        icu_price: null,
        tat_value: null,
        tat_unit: '',
        processing_lab: '',
        external_lab_id: null,
        sample_type_id: null,
        container_id: null,
        sample_quantity: '',
        instruction_id: null,
        special_instructions: '',
        is_active: true,
    };
    if (reportModal) {
        reportModal.show();
    }
};

const editReport = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        id: item.id,
        report_name: item.report_name,
        report_code: item.report_code,
        category_id: item.category_id,
        sort_order: item.sort_order,
        description: item.description,
        base_price: item.base_price,
        opd_price: item.opd_price,
        emergency_price: item.emergency_price,
        general_ward_price: item.general_ward_price,
        semi_private_price: item.semi_private_price,
        private_ward_price: item.private_ward_price,
        deluxe_ward_price: item.deluxe_ward_price,
        icu_price: item.icu_price,
        tat_value: item.tat_value,
        tat_unit: item.tat_unit,
        processing_lab: item.processing_lab,
        external_lab_id: item.external_lab_id,
        sample_type_id: item.sample_type_id,
        container_id: item.container_id,
        sample_quantity: item.sample_quantity,
        instruction_id: item.instruction_id,
        special_instructions: item.special_instructions,
        is_active: item.is_active,
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
            await axios.put(`/api/pathology/test-reports/${form.value.id}`, form.value);
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
</script>

<style scoped>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.nav-tabs .nav-link {
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    font-weight: 500;
}
</style>
