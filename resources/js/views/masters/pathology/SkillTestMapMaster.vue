<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Skill Patho Test Mapping</h4>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Skill Selection -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Select Skill/Department</label>
                        <select class="form-select" v-model="selectedDepartmentId" @change="loadMappings">
                            <option value="">-- Select Department --</option>
                            <option v-for="department in departments" :key="department.department_id" :value="department.department_id">
                                {{ department.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="alert alert-info mb-0 w-100" v-if="selectedDepartmentId">
                            <i class="bi bi-info-circle"></i>
                            Click on tests to map/unmap them for the selected department
                        </div>
                    </div>
                </div>

                <!-- Two Panel Mapping Interface -->
                <div v-if="selectedDepartmentId" class="row">
                    <!-- Available Tests Panel -->
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-list-ul"></i> Available Tests
                                </h6>
                                <span class="badge bg-light text-primary">{{ filteredAvailableTests.length }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="p-2 bg-light border-bottom">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        v-model="searchAvailable"
                                        placeholder="Search available tests...">
                                </div>
                                <div style="height: 500px; overflow-y: auto;">
                                    <div v-if="loadingAvailable" class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-2">Loading tests...</div>
                                    </div>
                                    <div v-else-if="filteredAvailableTests.length === 0" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <div class="mt-2">No available tests found</div>
                                    </div>
                                    <div v-else class="list-group list-group-flush">
                                        <div
                                            v-for="test in filteredAvailableTests"
                                            :key="test.test_id"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3"
                                            style="cursor: pointer;"
                                            @click="addMapping(test)">
                                            <div class="flex-grow-1">
                                                <div class="fw-medium">{{ test.test_name }}</div>
                                                <small class="text-muted">
                                                    Code: {{ test.test_code || 'N/A' }} |
                                                    Type: {{ test.value_type || 'N/A' }}
                                                </small>
                                            </div>
                                            <div class="text-end ms-2">
                                                <button class="btn btn-sm btn-success" type="button">
                                                    <i class="bi bi-arrow-right"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mapped Tests Panel -->
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="bi bi-check2-square"></i> Mapped Tests
                                </h6>
                                <span class="badge bg-light text-success">{{ filteredMappedTests.length }}</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="p-2 bg-light border-bottom">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        v-model="searchMapped"
                                        placeholder="Search mapped tests...">
                                </div>
                                <div style="height: 500px; overflow-y: auto;">
                                    <div v-if="loadingMapped" class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-2">Loading mapped tests...</div>
                                    </div>
                                    <div v-else-if="filteredMappedTests.length === 0" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1"></i>
                                        <div class="mt-2">No tests mapped yet</div>
                                        <small>Select tests from left panel to map them</small>
                                    </div>
                                    <div v-else class="list-group list-group-flush">
                                        <div
                                            v-for="test in filteredMappedTests"
                                            :key="test.test_id"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start py-3"
                                            style="cursor: pointer;"
                                            @click="removeMapping(test)">
                                            <div class="flex-grow-1">
                                                <div class="fw-medium">{{ test.test_name }}</div>
                                                <small class="text-muted">
                                                    Code: {{ test.test_code || 'N/A' }} |
                                                    Type: {{ test.value_type || 'N/A' }}
                                                </small>
                                            </div>
                                            <div class="text-end ms-2">
                                                <button class="btn btn-sm btn-danger" type="button">
                                                    <i class="bi bi-arrow-left"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Department Selected -->
                <div v-else class="text-center py-5">
                    <i class="bi bi-arrow-up-circle fs-1 text-muted"></i>
                    <div class="mt-3 text-muted">
                        <h5>Please select a department to manage test mappings</h5>
                        <p>Choose a department from the dropdown above to view and manage its pathology test mappings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loadingAvailable = ref(false);
const loadingMapped = ref(false);
const selectedDepartmentId = ref('');
const departments = ref([]);
const availableTests = ref([]);
const mappedTests = ref([]);
const searchAvailable = ref('');
const searchMapped = ref('');

const filteredAvailableTests = computed(() => {
    if (!searchAvailable.value) return availableTests.value;
    const search = searchAvailable.value.toLowerCase();
    return availableTests.value.filter(test =>
        test.test_name.toLowerCase().includes(search) ||
        (test.test_code && test.test_code.toLowerCase().includes(search))
    );
});

const filteredMappedTests = computed(() => {
    if (!searchMapped.value) return mappedTests.value;
    const search = searchMapped.value.toLowerCase();
    return mappedTests.value.filter(test =>
        test.test_name.toLowerCase().includes(search) ||
        (test.test_code && test.test_code.toLowerCase().includes(search))
    );
});

onMounted(() => {
    loadDepartments();
});

const loadDepartments = async () => {
    try {
        const response = await axios.get('/api/departments', { params: { is_active: 1, per_page: 100 } });

        // Handle paginated response
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            departments.value = paginatedData.data || paginatedData;
        } else {
            departments.value = response.data.data || response.data;
        }

        if (!Array.isArray(departments.value)) {
            departments.value = [];
        }
    } catch (err) {
        console.error('Error loading departments:', err);
        alert('Failed to load departments');
    }
};

const loadMappings = async () => {
    if (!selectedDepartmentId.value) {
        availableTests.value = [];
        mappedTests.value = [];
        return;
    }

    loadingAvailable.value = true;
    loadingMapped.value = true;
    searchAvailable.value = '';
    searchMapped.value = '';

    try {
        // Load all tests and mapped tests in parallel
        const [allTestsResponse, mappedResponse] = await Promise.all([
            axios.get('/api/pathology/tests', { params: { is_active: 1, per_page: 100 } }),
            axios.get(`/api/pathology/skill-test-maps/by-department/${selectedDepartmentId.value}`)
        ]);

        // Handle paginated response for all tests
        let allTests = [];
        if (allTestsResponse.data.success && allTestsResponse.data.data) {
            const paginatedData = allTestsResponse.data.data;
            allTests = paginatedData.data || paginatedData;
        } else {
            allTests = allTestsResponse.data.data || allTestsResponse.data;
        }

        mappedTests.value = mappedResponse.data.data || mappedResponse.data;

        if (!Array.isArray(allTests)) {
            allTests = [];
        }
        if (!Array.isArray(mappedTests.value)) {
            mappedTests.value = [];
        }

        // Filter out already mapped tests from available
        const mappedIds = mappedTests.value.map(t => t.test_id);
        availableTests.value = allTests.filter(t => !mappedIds.includes(t.test_id));
    } catch (err) {
        console.error('Error loading mappings:', err);
        alert('Failed to load test mappings');
    } finally {
        loadingAvailable.value = false;
        loadingMapped.value = false;
    }
};

const addMapping = async (test) => {
    if (!selectedDepartmentId.value) return;

    try {
        await axios.post('/api/pathology/skill-test-maps', {
            department_id: selectedDepartmentId.value,
            test_id: test.test_id
        });

        // Move test from available to mapped
        availableTests.value = availableTests.value.filter(t => t.test_id !== test.test_id);
        mappedTests.value.push(test);

        // Sort mapped tests by name
        mappedTests.value.sort((a, b) => a.test_name.localeCompare(b.test_name));
    } catch (err) {
        console.error('Error adding mapping:', err);
        alert(err.response?.data?.message || 'Failed to add test mapping');
    }
};

const removeMapping = async (test) => {
    if (!selectedDepartmentId.value) return;

    if (!confirm(`Remove "${test.test_name}" from this department?`)) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/skill-test-maps/${selectedDepartmentId.value}/${test.test_id}`);

        // Move test from mapped to available
        mappedTests.value = mappedTests.value.filter(t => t.test_id !== test.test_id);
        availableTests.value.push(test);

        // Sort available tests by name
        availableTests.value.sort((a, b) => a.test_name.localeCompare(b.test_name));
    } catch (err) {
        console.error('Error removing mapping:', err);
        alert(err.response?.data?.message || 'Failed to remove test mapping');
    }
};
</script>

<style scoped>
.list-group-item-action:hover {
    background-color: #f8f9fa;
}
</style>
