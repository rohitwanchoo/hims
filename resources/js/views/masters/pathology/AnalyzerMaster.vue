<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Pathology Analyzer Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Analyzer
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
                            @input="loadAnalyzers"
                            placeholder="Search by analyzer name...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.analyzer_type" @change="loadAnalyzers">
                            <option value="">All Types</option>
                            <option value="hematology">Hematology</option>
                            <option value="biochemistry">Biochemistry</option>
                            <option value="immunology">Immunology</option>
                            <option value="microbiology">Microbiology</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadAnalyzers">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadAnalyzers">
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
                                <th style="min-width: 200px;">Analyzer Name</th>
                                <th style="min-width: 150px;">Type</th>
                                <th style="width: 100px;" class="text-center">Count</th>
                                <th style="width: 120px;" class="text-center">Bidirectional</th>
                                <th style="min-width: 150px;">Description</th>
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
                            <tr v-else-if="analyzers.length === 0">
                                <td colspan="8" class="text-center text-muted py-3">
                                    No analyzers found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in analyzers" :key="item.id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>{{ item.analyzer_name }}</td>
                                <td>
                                    <span class="badge bg-info">{{ formatType(item.analyzer_type) }}</span>
                                </td>
                                <td class="text-center">{{ item.analyzer_count || 0 }}</td>
                                <td class="text-center">
                                    <span :class="item.is_bidirectional ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_bidirectional ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td>{{ item.description || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editAnalyzer(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-1" @click="manageTests(item)" title="Manage Tests">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteAnalyzer(item.id)" title="Delete">
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
        <div class="modal fade" ref="analyzerModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Analyzer</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveAnalyzer">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Analyzer Name *</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.analyzer_name"
                                        placeholder="e.g., Sysmex XN-1000"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Analyzer Type *</label>
                                    <select class="form-select" v-model="form.analyzer_type" required>
                                        <option value="">Select Type</option>
                                        <option value="hematology">Hematology</option>
                                        <option value="biochemistry">Biochemistry</option>
                                        <option value="immunology">Immunology</option>
                                        <option value="microbiology">Microbiology</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Analyzer Count</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.analyzer_count"
                                        min="0"
                                        placeholder="Number of machines">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label d-block">Options</label>
                                    <div class="form-check form-check-inline">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="isBidirectional"
                                            v-model="form.is_bidirectional">
                                        <label class="form-check-label" for="isBidirectional">
                                            Bidirectional Interface
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
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
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Optional description or notes"></textarea>
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
                        <h5 class="modal-title">Manage Tests - {{ selectedAnalyzer?.analyzer_name }}</h5>
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
                                <div class="border rounded" style="height: 400px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <div
                                            v-for="test in filteredAvailableTests"
                                            :key="test.id"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                            style="cursor: pointer;"
                                            @click="addTestMapping(test)">
                                            <span>{{ test.test_name }}</span>
                                            <i class="bi bi-plus-circle text-success"></i>
                                        </div>
                                        <div v-if="filteredAvailableTests.length === 0" class="p-3 text-center text-muted">
                                            No tests available
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Mapped Tests</h6>
                                <input
                                    type="text"
                                    class="form-control form-control-sm mb-2"
                                    v-model="testSearchMapped"
                                    placeholder="Search mapped tests...">
                                <div class="border rounded" style="height: 400px; overflow-y: auto;">
                                    <div class="list-group list-group-flush">
                                        <div
                                            v-for="test in filteredMappedTests"
                                            :key="test.id"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                            style="cursor: pointer;"
                                            @click="removeTestMapping(test)">
                                            <span>{{ test.test_name }}</span>
                                            <i class="bi bi-dash-circle text-danger"></i>
                                        </div>
                                        <div v-if="filteredMappedTests.length === 0" class="p-3 text-center text-muted">
                                            No tests mapped
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
const analyzers = ref([]);
const availableTests = ref([]);
const mappedTests = ref([]);
const selectedAnalyzer = ref(null);
const testSearchAvailable = ref('');
const testSearchMapped = ref('');

const filters = ref({
    search: '',
    analyzer_type: '',
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
    analyzer_name: '',
    analyzer_type: '',
    analyzer_count: 0,
    is_bidirectional: false,
    description: '',
    is_active: true,
});

let analyzerModal = null;
let testMappingModal = null;
const analyzerModalRef = ref(null);
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
        test.test_name.toLowerCase().includes(search)
    );
});

const filteredMappedTests = computed(() => {
    if (!testSearchMapped.value) return mappedTests.value;
    const search = testSearchMapped.value.toLowerCase();
    return mappedTests.value.filter(test =>
        test.test_name.toLowerCase().includes(search)
    );
});

onMounted(async () => {
    await nextTick();
    if (analyzerModalRef.value) {
        analyzerModal = new Modal(analyzerModalRef.value);
    }
    if (testMappingModalRef.value) {
        testMappingModal = new Modal(testMappingModalRef.value);
    }
    loadAnalyzers();
});

const formatType = (type) => {
    if (!type) return '-';
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const loadAnalyzers = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/analyzers', { params: filters.value });
        if (response.data.data) {
            analyzers.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        } else {
            analyzers.value = response.data;
        }
    } catch (err) {
        console.error('Error loading analyzers:', err);
        error.value = 'Failed to load analyzers';
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadAnalyzers();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        analyzer_name: '',
        analyzer_type: '',
        analyzer_count: 0,
        is_bidirectional: false,
        description: '',
        is_active: true,
    };
    if (analyzerModal) {
        analyzerModal.show();
    }
};

const editAnalyzer = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        id: item.id,
        analyzer_name: item.analyzer_name,
        analyzer_type: item.analyzer_type,
        analyzer_count: item.analyzer_count,
        is_bidirectional: item.is_bidirectional,
        description: item.description,
        is_active: item.is_active,
    };
    if (analyzerModal) {
        analyzerModal.show();
    }
};

const saveAnalyzer = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/analyzers/${form.value.id}`, form.value);
        } else {
            await axios.post('/api/pathology/analyzers', form.value);
        }
        closeModal();
        loadAnalyzers();
    } catch (err) {
        console.error('Error saving analyzer:', err);
        error.value = err.response?.data?.message || 'Failed to save analyzer';
    } finally {
        saving.value = false;
    }
};

const deleteAnalyzer = async (id) => {
    if (!confirm('Are you sure you want to delete this analyzer?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/analyzers/${id}`);
        loadAnalyzers();
    } catch (err) {
        console.error('Error deleting analyzer:', err);
        alert(err.response?.data?.message || 'Failed to delete analyzer');
    }
};

const manageTests = async (analyzer) => {
    selectedAnalyzer.value = analyzer;
    testSearchAvailable.value = '';
    testSearchMapped.value = '';

    try {
        const [availableResponse, mappedResponse] = await Promise.all([
            axios.get('/api/pathology/test-masters'),
            axios.get(`/api/pathology/analyzers/${analyzer.id}/tests`)
        ]);

        availableTests.value = availableResponse.data.data || availableResponse.data;
        mappedTests.value = mappedResponse.data.data || mappedResponse.data;

        // Filter out already mapped tests from available
        const mappedIds = mappedTests.value.map(t => t.id);
        availableTests.value = availableTests.value.filter(t => !mappedIds.includes(t.id));

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
        await axios.post(`/api/pathology/analyzers/${selectedAnalyzer.value.id}/tests`, {
            test_id: test.id
        });

        // Move test from available to mapped
        availableTests.value = availableTests.value.filter(t => t.id !== test.id);
        mappedTests.value.push(test);
    } catch (err) {
        console.error('Error adding test mapping:', err);
        alert(err.response?.data?.message || 'Failed to add test mapping');
    }
};

const removeTestMapping = async (test) => {
    try {
        await axios.delete(`/api/pathology/analyzers/${selectedAnalyzer.value.id}/tests/${test.id}`);

        // Move test from mapped to available
        mappedTests.value = mappedTests.value.filter(t => t.id !== test.id);
        availableTests.value.push(test);
    } catch (err) {
        console.error('Error removing test mapping:', err);
        alert(err.response?.data?.message || 'Failed to remove test mapping');
    }
};

const closeModal = () => {
    if (analyzerModal) {
        analyzerModal.hide();
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
