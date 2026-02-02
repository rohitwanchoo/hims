<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Pathology Test Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Test
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
                            @input="loadTests"
                            placeholder="Search by test name, code...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.value_type" @change="loadTests">
                            <option value="">All Types</option>
                            <option value="numeric">Numeric</option>
                            <option value="alphanumeric">Alphanumeric</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadTests">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadTests">
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
                                <th style="min-width: 200px;">Test Name</th>
                                <th style="min-width: 120px;">Test Code</th>
                                <th style="min-width: 120px;">Value Type</th>
                                <th style="min-width: 150px;">Unit</th>
                                <th style="min-width: 150px;">Method</th>
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
                            <tr v-else-if="tests.length === 0">
                                <td colspan="8" class="text-center text-muted py-3">
                                    No tests found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in tests" :key="item.id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.test_name }}</td>
                                <td>{{ item.test_code || '-' }}</td>
                                <td>
                                    <span :class="item.value_type === 'numeric' ? 'badge bg-primary' : 'badge bg-info'">
                                        {{ formatValueType(item.value_type) }}
                                    </span>
                                </td>
                                <td>{{ item.unit?.unit_name || '-' }}</td>
                                <td>{{ item.method?.method_name || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editTest(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteTest(item.id)" title="Delete">
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
        <div class="modal fade" ref="testModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Test</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveTest">
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>

                            <!-- Basic Information -->
                            <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Test Name *</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.test_name"
                                        placeholder="e.g., Hemoglobin"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Test Code</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.test_code"
                                        placeholder="e.g., HB">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Value Type *</label>
                                    <select class="form-select" v-model="form.value_type" required>
                                        <option value="">Select Type</option>
                                        <option value="numeric">Numeric</option>
                                        <option value="alphanumeric">Alphanumeric</option>
                                    </select>
                                    <small class="text-muted">Numeric: for measurable values with ranges | Alphanumeric: for text results</small>
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

                            <!-- Test Details -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Test Details</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Method</label>
                                    <select class="form-select" v-model="form.method_id">
                                        <option :value="null">Select Method</option>
                                        <option v-for="method in methods" :key="method.id" :value="method.id">
                                            {{ method.method_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Unit</label>
                                    <select class="form-select" v-model="form.unit_id">
                                        <option :value="null">Select Unit</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.unit_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Container</label>
                                    <select class="form-select" v-model="form.container_id">
                                        <option :value="null">Select Container</option>
                                        <option v-for="container in containers" :key="container.id" :value="container.id">
                                            {{ container.container_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reference Ranges (Only for Numeric) -->
                            <div v-if="form.value_type === 'numeric'">
                                <h6 class="border-bottom pb-2 mb-3 mt-4">Reference Ranges</h6>

                                <!-- Male Range -->
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <label class="fw-bold text-primary">Male Range</label>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Min Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.male_min"
                                            placeholder="e.g., 13.0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Max Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.male_max"
                                            placeholder="e.g., 17.0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference Text</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.male_reference"
                                            placeholder="e.g., 13.0 - 17.0 g/dL">
                                    </div>
                                </div>

                                <!-- Female Range -->
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <label class="fw-bold text-danger">Female Range</label>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Min Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.female_min"
                                            placeholder="e.g., 12.0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Max Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.female_max"
                                            placeholder="e.g., 15.0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference Text</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.female_reference"
                                            placeholder="e.g., 12.0 - 15.0 g/dL">
                                    </div>
                                </div>

                                <!-- Child Range -->
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <label class="fw-bold text-success">Child Range</label>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Min Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.child_min"
                                            placeholder="e.g., 11.0">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Max Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.child_max"
                                            placeholder="e.g., 14.0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Reference Text</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            v-model="form.child_reference"
                                            placeholder="e.g., 11.0 - 14.0 g/dL">
                                    </div>
                                </div>

                                <!-- Critical Values -->
                                <div class="row">
                                    <div class="col-md-12 mb-2">
                                        <label class="fw-bold text-warning">Critical Values (Alerts)</label>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Critical Low</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.critical_low"
                                            placeholder="Trigger alert below this value">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Critical High</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.critical_high"
                                            placeholder="Trigger alert above this value">
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <h6 class="border-bottom pb-2 mb-3 mt-4">Additional Information</h6>
                            <div class="mb-3">
                                <label class="form-label">Description/Notes</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Optional notes about the test"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="isBold"
                                            v-model="form.is_bold">
                                        <label class="form-check-label" for="isBold">
                                            Bold in Report
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="isHeading"
                                            v-model="form.is_heading">
                                        <label class="form-check-label" for="isHeading">
                                            Show as Heading
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
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
const tests = ref([]);
const methods = ref([]);
const units = ref([]);
const containers = ref([]);

const filters = ref({
    search: '',
    value_type: '',
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
    test_name: '',
    test_code: '',
    value_type: '',
    method_id: null,
    unit_id: null,
    container_id: null,
    sort_order: 0,
    male_min: null,
    male_max: null,
    male_reference: '',
    female_min: null,
    female_max: null,
    female_reference: '',
    child_min: null,
    child_max: null,
    child_reference: '',
    critical_low: null,
    critical_high: null,
    description: '',
    is_bold: false,
    is_heading: false,
    is_active: true,
});

let testModal = null;
const testModalRef = ref(null);

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
    if (testModalRef.value) {
        testModal = new Modal(testModalRef.value);
    }
    loadTests();
    loadMethods();
    loadUnits();
    loadContainers();
});

const formatValueType = (type) => {
    if (!type) return '-';
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const loadTests = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-masters', { params: filters.value });
        if (response.data.data) {
            tests.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        } else {
            tests.value = response.data;
        }
    } catch (err) {
        console.error('Error loading tests:', err);
        error.value = 'Failed to load tests';
    } finally {
        loading.value = false;
    }
};

const loadMethods = async () => {
    try {
        const response = await axios.get('/api/pathology/test-methods', { params: { status: 1 } });
        methods.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading methods:', err);
    }
};

const loadUnits = async () => {
    try {
        const response = await axios.get('/api/pathology/test-units', { params: { status: 1 } });
        units.value = response.data.data || response.data;
    } catch (err) {
        console.error('Error loading units:', err);
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

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadTests();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        test_name: '',
        test_code: '',
        value_type: '',
        method_id: null,
        unit_id: null,
        container_id: null,
        sort_order: 0,
        male_min: null,
        male_max: null,
        male_reference: '',
        female_min: null,
        female_max: null,
        female_reference: '',
        child_min: null,
        child_max: null,
        child_reference: '',
        critical_low: null,
        critical_high: null,
        description: '',
        is_bold: false,
        is_heading: false,
        is_active: true,
    };
    if (testModal) {
        testModal.show();
    }
};

const editTest = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        id: item.id,
        test_name: item.test_name,
        test_code: item.test_code,
        value_type: item.value_type,
        method_id: item.method_id,
        unit_id: item.unit_id,
        container_id: item.container_id,
        sort_order: item.sort_order,
        male_min: item.male_min,
        male_max: item.male_max,
        male_reference: item.male_reference,
        female_min: item.female_min,
        female_max: item.female_max,
        female_reference: item.female_reference,
        child_min: item.child_min,
        child_max: item.child_max,
        child_reference: item.child_reference,
        critical_low: item.critical_low,
        critical_high: item.critical_high,
        description: item.description,
        is_bold: item.is_bold,
        is_heading: item.is_heading,
        is_active: item.is_active,
    };
    if (testModal) {
        testModal.show();
    }
};

const saveTest = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/test-masters/${form.value.id}`, form.value);
        } else {
            await axios.post('/api/pathology/test-masters', form.value);
        }
        closeModal();
        loadTests();
    } catch (err) {
        console.error('Error saving test:', err);
        error.value = err.response?.data?.message || 'Failed to save test';
    } finally {
        saving.value = false;
    }
};

const deleteTest = async (id) => {
    if (!confirm('Are you sure you want to delete this test?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/test-masters/${id}`);
        loadTests();
    } catch (err) {
        console.error('Error deleting test:', err);
        alert(err.response?.data?.message || 'Failed to delete test');
    }
};

const closeModal = () => {
    if (testModal) {
        testModal.hide();
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
