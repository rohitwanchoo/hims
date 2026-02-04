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
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadTests">
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
                            <tr v-else v-for="(item, index) in tests" :key="item.test_id">
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
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteTest(item.test_id)" title="Delete">
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
                                    <label class="form-label">Test Sequence</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.test_sequence"
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
                                        <option v-for="method in methods" :key="method.method_id" :value="method.method_id">
                                            {{ method.method_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Unit</label>
                                    <select class="form-select" v-model="form.unit_id">
                                        <option :value="null">Select Unit</option>
                                        <option v-for="unit in units" :key="unit.unit_id" :value="unit.unit_id">
                                            {{ unit.unit_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Container</label>
                                    <select class="form-select" v-model="form.container_id">
                                        <option :value="null">Select Container</option>
                                        <option v-for="container in containers" :key="container.container_id" :value="container.container_id">
                                            {{ container.container_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Reference Ranges (Only for Numeric) -->
                            <div v-if="form.value_type === 'numeric'">
                                <h6 class="border-bottom pb-2 mb-3 mt-4">Reference Ranges</h6>

                                <!-- Normal Range -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Min Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.min_value"
                                            placeholder="e.g., 13.0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Max Value</label>
                                        <input
                                            type="number"
                                            step="any"
                                            class="form-control"
                                            v-model.number="form.max_value"
                                            placeholder="e.g., 17.0">
                                    </div>
                                </div>

                                <!-- Gender, Age Group & Race Specific Ranges -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="fw-bold">Gender, Age Group & Race Specific Ranges</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="addReferenceRange">
                                            <i class="bi bi-plus-circle"></i> Add Range
                                        </button>
                                    </div>

                                    <div v-if="form.reference_ranges.length === 0" class="text-muted small">
                                        No specific ranges defined. Click "Add Range" to specify gender, age group, and race combinations this test applies to.
                                    </div>

                                    <div v-else class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 25%;">Gender</th>
                                                    <th style="width: 40%;">Age Group</th>
                                                    <th style="width: 25%;">Race</th>
                                                    <th style="width: 10%;" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(range, index) in form.reference_ranges" :key="index">
                                                    <td>
                                                        <select class="form-select form-select-sm" v-model="range.gender_id">
                                                            <option :value="null">All Genders</option>
                                                            <option v-for="gender in genders" :key="gender.gender_id" :value="gender.gender_id">
                                                                {{ gender.gender_name }}
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" v-model="range.age_group_id">
                                                            <option :value="null">All Ages</option>
                                                            <option v-for="ageGroup in ageGroups" :key="ageGroup.age_group_id" :value="ageGroup.age_group_id">
                                                                {{ ageGroup.age_group_caption }} ({{ ageGroup.from_age }}-{{ ageGroup.to_age }} {{ formatAgeUnit(ageGroup.age_unit) }})
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select class="form-select form-select-sm" v-model="range.race_id">
                                                            <option :value="null">All Races</option>
                                                            <option v-for="race in races" :key="race.race_id" :value="race.race_id">
                                                                {{ race.race_description }}
                                                            </option>
                                                        </select>
                                                    </td>
                                                    <td class="text-center">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            @click="removeReferenceRange(index)"
                                                            title="Remove">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
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
                                <label class="form-label">Remarks</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.remarks"
                                    rows="2"
                                    placeholder="Optional notes about the test"></textarea>
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
const tests = ref([]);
const methods = ref([]);
const units = ref([]);
const containers = ref([]);
const genders = ref([]);
const ageGroups = ref([]);
const races = ref([]);

const filters = ref({
    search: '',
    value_type: '',
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
    test_name: '',
    test_code: '',
    value_type: '',
    method_id: null,
    unit_id: null,
    container_id: null,
    test_sequence: 0,
    min_value: null,
    max_value: null,
    critical_low: null,
    critical_high: null,
    remarks: '',
    is_active: true,
    reference_ranges: [],
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
    loadGenders();
    loadAgeGroups();
    loadRaces();
});

const formatValueType = (type) => {
    if (!type) return '-';
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const loadTests = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/tests', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            tests.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            tests.value = response.data.data || response.data;
        }

        // Ensure array
        if (!Array.isArray(tests.value)) {
            tests.value = [];
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
        const response = await axios.get('/api/pathology/test-methods', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            methods.value = paginatedData.data || paginatedData;
        } else {
            methods.value = response.data.data || response.data;
        }
        if (!Array.isArray(methods.value)) {
            methods.value = [];
        }
    } catch (err) {
        console.error('Error loading methods:', err);
    }
};

const loadUnits = async () => {
    try {
        const response = await axios.get('/api/pathology/test-units', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            units.value = paginatedData.data || paginatedData;
        } else {
            units.value = response.data.data || response.data;
        }
        if (!Array.isArray(units.value)) {
            units.value = [];
        }
    } catch (err) {
        console.error('Error loading units:', err);
    }
};

const loadContainers = async () => {
    try {
        const response = await axios.get('/api/pathology/containers', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            containers.value = paginatedData.data || paginatedData;
        } else {
            containers.value = response.data.data || response.data;
        }
        if (!Array.isArray(containers.value)) {
            containers.value = [];
        }
    } catch (err) {
        console.error('Error loading containers:', err);
    }
};

const loadGenders = async () => {
    try {
        const response = await axios.get('/api/genders-active');
        genders.value = response.data || [];
    } catch (err) {
        console.error('Error loading genders:', err);
    }
};

const loadAgeGroups = async () => {
    try {
        const response = await axios.get('/api/age-groups-active');
        ageGroups.value = response.data || [];
    } catch (err) {
        console.error('Error loading age groups:', err);
    }
};

const loadRaces = async () => {
    try {
        const response = await axios.get('/api/pathology/races', { params: { is_active: 1, per_page: 100 } });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            races.value = paginatedData.data || paginatedData;
        } else {
            races.value = response.data.data || response.data;
        }
        if (!Array.isArray(races.value)) {
            races.value = [];
        }
    } catch (err) {
        console.error('Error loading races:', err);
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadTests();
    }
};

const addReferenceRange = () => {
    form.value.reference_ranges.push({
        gender_id: null,
        age_group_id: null,
        race_id: null,
    });
};

const formatAgeUnit = (unit) => {
    if (!unit) return '';
    return unit.charAt(0).toUpperCase() + unit.slice(1);
};

const removeReferenceRange = (index) => {
    form.value.reference_ranges.splice(index, 1);
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
        test_sequence: 0,
        min_value: null,
        max_value: null,
        critical_low: null,
        critical_high: null,
        remarks: '',
        is_active: true,
        reference_ranges: [],
    };
    if (testModal) {
        testModal.show();
    }
};

const editTest = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        test_id: item.test_id,
        test_name: item.test_name,
        test_code: item.test_code,
        value_type: item.value_type,
        method_id: item.method_id,
        unit_id: item.unit_id,
        container_id: item.container_id,
        test_sequence: item.test_sequence,
        min_value: item.min_value,
        max_value: item.max_value,
        critical_low: item.critical_low,
        critical_high: item.critical_high,
        remarks: item.remarks,
        is_active: item.is_active,
        reference_ranges: item.reference_ranges ? item.reference_ranges.map(r => ({
            gender_id: r.gender_id,
            age_group_id: r.age_group_id,
            race_id: r.race_id,
        })) : [],
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
            await axios.put(`/api/pathology/tests/${form.value.test_id}`, form.value);
        } else {
            await axios.post('/api/pathology/tests', form.value);
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
        await axios.delete(`/api/pathology/tests/${id}`);
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
