<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Test Category Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Category
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
                            @input="loadCategories"
                            placeholder="Search by category name...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.status" @change="loadCategories">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadCategories">
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
                                <th style="min-width: 250px;">Category Name</th>
                                <th style="min-width: 200px;">Parent Category</th>
                                <th style="width: 100px;" class="text-center">Fit 100</th>
                                <th style="min-width: 150px;">Description</th>
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
                            <tr v-else-if="categories.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    No categories found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in categories" :key="item.category_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>
                                    {{ item.parent_category_id ? '- ' : '' }}{{ item.category_name }}
                                </td>
                                <td>{{ item.parent?.category_name || '-' }}</td>
                                <td class="text-center">
                                    <span v-if="item.fit_100" class="badge bg-info">
                                        <i class="bi bi-check"></i>
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td>{{ item.remarks || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editCategory(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info me-1" @click="manageTests(item)" title="Manage Tests">
                                        <i class="bi bi-list-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteCategory(item.category_id)" title="Delete">
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
        <div class="modal fade" ref="categoryModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Test Category</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveCategory">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category Name *</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        v-model="form.category_name"
                                        placeholder="e.g., Hematology, CBC"
                                        required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Parent Category</label>
                                    <select class="form-select" v-model="form.parent_category_id">
                                        <option :value="null">None (Main Category)</option>
                                        <option v-for="cat in parentCategories" :key="cat.category_id" :value="cat.category_id">
                                            {{ cat.category_name }}
                                        </option>
                                    </select>
                                    <small class="text-muted">Select parent for sub-category</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.remarks"
                                    rows="3"
                                    placeholder="Optional description"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="fit100"
                                            v-model="form.fit_100">
                                        <label class="form-check-label" for="fit100">
                                            Fit 100
                                        </label>
                                        <small class="d-block text-muted">Enable for special formatting</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
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

        <!-- Test Mapping Modal -->
        <div class="modal fade" ref="testMappingModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Tests - {{ selectedCategory?.category_name }}</h5>
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
const categories = ref([]);
const parentCategories = ref([]);
const availableTests = ref([]);
const mappedTests = ref([]);
const selectedCategory = ref(null);
const testSearchAvailable = ref('');
const testSearchMapped = ref('');
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
    category_name: '',
    category_code: '',
    parent_category_id: null,
    fit_100: false,
    has_sub_category: false,
    remarks: '',
    is_active: true,
});

let categoryModal = null;
let testMappingModal = null;
const categoryModalRef = ref(null);
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
    if (categoryModalRef.value) {
        categoryModal = new Modal(categoryModalRef.value);
    }
    if (testMappingModalRef.value) {
        testMappingModal = new Modal(testMappingModalRef.value);
    }
    loadCategories();
});

const loadCategories = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-categories', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            categories.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            categories.value = response.data.data || response.data;
        }

        if (!Array.isArray(categories.value)) {
            categories.value = [];
        }
    } catch (err) {
        console.error('Error loading categories:', err);
        error.value = 'Failed to load categories';
    } finally {
        loading.value = false;
    }
};

const loadParentCategories = async () => {
    try {
        const response = await axios.get('/api/pathology/test-categories', {
            params: { status: 1, all: true }
        });
        const allCategories = response.data.data || response.data;
        // Filter out only parent categories (those without parent_category_id)
        parentCategories.value = allCategories.filter(cat => !cat.parent_category_id && (!editMode.value || cat.category_id !== form.value.category_id));
    } catch (err) {
        console.error('Error loading parent categories:', err);
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadCategories();
    }
};

const openAddModal = async () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        category_name: '',
        category_code: '',
        parent_category_id: null,
        fit_100: false,
        has_sub_category: false,
        remarks: '',
        is_active: true,
    };
    await loadParentCategories();
    if (categoryModal) {
        categoryModal.show();
    }
};

const editCategory = async (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        category_id: item.category_id,
        category_name: item.category_name,
        category_code: item.category_code,
        parent_category_id: item.parent_category_id,
        fit_100: item.fit_100,
        has_sub_category: item.has_sub_category,
        remarks: item.remarks,
        is_active: item.is_active,
    };
    await loadParentCategories();
    if (categoryModal) {
        categoryModal.show();
    }
};

const saveCategory = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/test-categories/${form.value.category_id}`, form.value);
        } else {
            await axios.post('/api/pathology/test-categories', form.value);
        }
        closeModal();
        loadCategories();
    } catch (err) {
        console.error('Error saving category:', err);
        error.value = err.response?.data?.message || 'Failed to save category';
    } finally {
        saving.value = false;
    }
};

const deleteCategory = async (id) => {
    if (!confirm('Are you sure you want to delete this category? This may affect sub-categories.')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/test-categories/${id}`);
        loadCategories();
    } catch (err) {
        console.error('Error deleting category:', err);
        alert(err.response?.data?.message || 'Failed to delete category');
    }
};

const closeModal = () => {
    if (categoryModal) {
        categoryModal.hide();
    }
};

const manageTests = async (category) => {
    selectedCategory.value = category;
    testSearchAvailable.value = '';
    testSearchMapped.value = '';

    try {
        const [availableResponse, mappedResponse] = await Promise.all([
            axios.get('/api/pathology/tests', { params: { is_active: 1, per_page: 1000 } }),
            axios.get(`/api/pathology/test-categories/${category.category_id}`)
        ]);

        // Handle paginated response for available tests
        if (availableResponse.data.success && availableResponse.data.data) {
            const paginatedData = availableResponse.data.data;
            availableTests.value = paginatedData.data || paginatedData;
        } else {
            availableTests.value = availableResponse.data.data || availableResponse.data;
        }

        // Get mapped tests from category response
        const categoryData = mappedResponse.data.data || mappedResponse.data;
        mappedTests.value = categoryData.patho_tests || [];

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
        await axios.post(`/api/pathology/test-categories/${selectedCategory.value.category_id}/tests`, {
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
        await axios.delete(`/api/pathology/test-categories/${selectedCategory.value.category_id}/tests/${test.test_id}`);

        // Move test from mapped to available
        mappedTests.value = mappedTests.value.filter(t => t.test_id !== test.test_id);
        availableTests.value.push(test);
    } catch (err) {
        console.error('Error removing test mapping:', err);
        alert(err.response?.data?.message || 'Failed to remove test mapping');
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
