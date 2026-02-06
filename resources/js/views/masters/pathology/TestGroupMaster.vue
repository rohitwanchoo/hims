<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Test Group Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Test Group
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
                            @input="loadGroups"
                            placeholder="Search by group name...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadGroups">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadGroups">
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
                                <th style="min-width: 250px;">Group Name</th>
                                <th style="min-width: 150px;">Group Code</th>
                                <th style="width: 120px;" class="text-center">Tests Count</th>
                                <th style="min-width: 200px;">Description</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 150px;" class="text-center">Actions</th>
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
                            <tr v-else-if="groups.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    No test groups found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in groups" :key="item.group_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.group_name }}</td>
                                <td>{{ item.group_code || '-' }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ item.patho_tests_count || 0 }}</span>
                                </td>
                                <td>{{ item.remarks || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editGroup(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-1" @click="manageTests(item)" title="Manage Tests">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteGroup(item.group_id)" title="Delete">
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
        <div class="modal fade" ref="groupModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Test Group</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveGroup">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Group Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.group_name"
                                    placeholder="e.g., Liver Function Test, Renal Panel"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Group Code</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.group_code"
                                    placeholder="e.g., LFT, RFT">
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

        <!-- Test Mapping Modal -->
        <div class="modal fade" ref="testMappingModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Tests - {{ selectedGroup?.group_name }}</h5>
                        <button type="button" class="btn-close" @click="closeTestMappingModal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Available Tests</h6>
                                <input
                                    type="text"
                                    class="form-control form-control-sm mb-2"
                                    v-model="testSearchAvailable"
                                    placeholder="Search tests...">
                                <div class="border rounded" style="height: 450px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <div
                                            v-for="test in filteredAvailableTests"
                                            :key="test.test_id"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                            style="cursor: pointer;"
                                            @click="addTestMapping(test)">
                                            <div>
                                                <div class="fw-medium">{{ test.test_name }}</div>
                                                <small class="text-muted">{{ test.test_code || 'No code' }}</small>
                                            </div>
                                            <i class="bi bi-plus-circle text-success fs-5"></i>
                                        </div>
                                        <div v-if="filteredAvailableTests.length === 0" class="p-3 text-center text-muted">
                                            No tests available
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Mapped Tests ({{ mappedTests.length }})</h6>
                                <input
                                    type="text"
                                    class="form-control form-control-sm mb-2"
                                    v-model="testSearchMapped"
                                    placeholder="Search mapped tests...">
                                <div class="border rounded" style="height: 450px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <div
                                            v-for="test in filteredMappedTests"
                                            :key="test.test_id"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                            style="cursor: pointer;"
                                            @click="removeTestMapping(test)">
                                            <div>
                                                <div class="fw-medium">{{ test.test_name }}</div>
                                                <small class="text-muted">{{ test.test_code || 'No code' }}</small>
                                            </div>
                                            <i class="bi bi-dash-circle text-danger fs-5"></i>
                                        </div>
                                        <div v-if="filteredMappedTests.length === 0" class="p-3 text-center text-muted">
                                            No tests mapped yet
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeTestMappingModal">Close</button>
                    </div>
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
const groups = ref([]);
const availableTests = ref([]);
const mappedTests = ref([]);
const selectedGroup = ref(null);
const testSearchAvailable = ref('');
const testSearchMapped = ref('');

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
    group_name: '',
    group_code: '',
    remarks: '',
    is_active: true,
});

let groupModal = null;
let testMappingModal = null;
const groupModalRef = ref(null);
const testMappingModalRef = ref(null);

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

const filteredAvailableTests = computed(() => {
    if (!testSearchAvailable.value) return availableTests.value;
    const search = testSearchAvailable.value.toLowerCase();
    return availableTests.value.filter(test =>
        test.test_name.toLowerCase().includes(search) ||
        (test.test_code && test.test_code.toLowerCase().includes(search))
    );
});

const filteredMappedTests = computed(() => {
    if (!testSearchMapped.value) return mappedTests.value;
    const search = testSearchMapped.value.toLowerCase();
    return mappedTests.value.filter(test =>
        test.test_name.toLowerCase().includes(search) ||
        (test.test_code && test.test_code.toLowerCase().includes(search))
    );
});

onMounted(async () => {
    await nextTick();
    if (groupModalRef.value) {
        groupModal = new Modal(groupModalRef.value);
    }
    if (testMappingModalRef.value) {
        testMappingModal = new Modal(testMappingModalRef.value);
    }
    loadGroups();
});

const loadGroups = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-groups', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            groups.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            groups.value = response.data.data || response.data;
        }

        if (!Array.isArray(groups.value)) {
            groups.value = [];
        }
    } catch (err) {
        console.error('Error loading groups:', err);
        error.value = 'Failed to load test groups';
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadGroups();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        group_name: '',
        group_code: '',
        remarks: '',
        is_active: true,
    };
    if (groupModal) {
        groupModal.show();
    }
};

const editGroup = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        group_id: item.group_id,
        group_name: item.group_name,
        group_code: item.group_code,
        remarks: item.remarks,
        is_active: item.is_active,
    };
    if (groupModal) {
        groupModal.show();
    }
};

const saveGroup = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/test-groups/${form.value.group_id}`, form.value);
        } else {
            await axios.post('/api/pathology/test-groups', form.value);
        }
        closeModal();
        loadGroups();
    } catch (err) {
        console.error('Error saving group:', err);
        error.value = err.response?.data?.message || 'Failed to save test group';
    } finally {
        saving.value = false;
    }
};

const deleteGroup = async (id) => {
    if (!confirm('Are you sure you want to delete this test group?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/test-groups/${id}`);
        loadGroups();
    } catch (err) {
        console.error('Error deleting group:', err);
        alert(err.response?.data?.message || 'Failed to delete test group');
    }
};

const manageTests = async (group) => {
    selectedGroup.value = group;
    testSearchAvailable.value = '';
    testSearchMapped.value = '';

    try {
        const [availableResponse, mappedResponse] = await Promise.all([
            axios.get('/api/pathology/tests', { params: { is_active: 1, per_page: 100 } }),
            axios.get(`/api/pathology/test-groups/${group.group_id}/tests`)
        ]);

        // Handle paginated response for available tests
        if (availableResponse.data.success && availableResponse.data.data) {
            const paginatedData = availableResponse.data.data;
            availableTests.value = paginatedData.data || paginatedData;
        } else {
            availableTests.value = availableResponse.data.data || availableResponse.data;
        }

        mappedTests.value = mappedResponse.data.data || mappedResponse.data;

        if (!Array.isArray(availableTests.value)) {
            availableTests.value = [];
        }
        if (!Array.isArray(mappedTests.value)) {
            mappedTests.value = [];
        }

        // Filter out already mapped tests from available
        const mappedIds = mappedTests.value.map(t => t.test_id);
        availableTests.value = availableTests.value.filter(t => !mappedIds.includes(t.test_id));

        if (testMappingModal) {
            testMappingModal.show();
        }
    } catch (err) {
        console.error('Error loading tests:', err);
        alert('Failed to load test data');
    }
};

const addTestMapping = async (test) => {
    try {
        await axios.post(`/api/pathology/test-groups/${selectedGroup.value.group_id}/tests`, {
            test_id: test.test_id
        });

        // Move test from available to mapped
        availableTests.value = availableTests.value.filter(t => t.test_id !== test.test_id);
        mappedTests.value.push(test);
    } catch (err) {
        console.error('Error adding test mapping:', err);
        alert(err.response?.data?.message || 'Failed to add test mapping');
    }
};

const removeTestMapping = async (test) => {
    try {
        await axios.delete(`/api/pathology/test-groups/${selectedGroup.value.group_id}/tests/${test.test_id}`);

        // Move test from mapped to available
        mappedTests.value = mappedTests.value.filter(t => t.test_id !== test.test_id);
        availableTests.value.push(test);
    } catch (err) {
        console.error('Error removing test mapping:', err);
        alert(err.response?.data?.message || 'Failed to remove test mapping');
    }
};

const closeModal = () => {
    if (groupModal) {
        groupModal.hide();
    }
};

const closeTestMappingModal = () => {
    if (testMappingModal) {
        testMappingModal.hide();
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
