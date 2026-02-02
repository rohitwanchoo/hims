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
                            <tr v-else-if="categories.length === 0">
                                <td colspan="7" class="text-center text-muted py-3">
                                    No categories found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in categories" :key="item.id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>
                                    {{ item.parent_id ? '- ' : '' }}{{ item.category_name }}
                                </td>
                                <td>{{ item.parent?.category_name || '-' }}</td>
                                <td class="text-center">
                                    <span v-if="item.fit_100" class="badge bg-info">
                                        <i class="bi bi-check"></i>
                                    </span>
                                    <span v-else>-</span>
                                </td>
                                <td>{{ item.description || '-' }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editCategory(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteCategory(item.id)" title="Delete">
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
                                    <select class="form-select" v-model="form.parent_id">
                                        <option :value="null">None (Main Category)</option>
                                        <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                                            {{ cat.category_name }}
                                        </option>
                                    </select>
                                    <small class="text-muted">Select parent for sub-category</small>
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
    parent_id: null,
    fit_100: false,
    description: '',
    is_active: true,
});

let categoryModal = null;
const categoryModalRef = ref(null);

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
    if (categoryModalRef.value) {
        categoryModal = new Modal(categoryModalRef.value);
    }
    loadCategories();
});

const loadCategories = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/test-categories', { params: filters.value });
        if (response.data.data) {
            categories.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
                per_page: response.data.per_page,
                total: response.data.total,
                from: response.data.from,
                to: response.data.to,
            };
        } else {
            categories.value = response.data;
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
        // Filter out only parent categories (those without parent_id)
        parentCategories.value = allCategories.filter(cat => !cat.parent_id && (!editMode.value || cat.id !== form.value.id));
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
        parent_id: null,
        fit_100: false,
        description: '',
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
        id: item.id,
        category_name: item.category_name,
        parent_id: item.parent_id,
        fit_100: item.fit_100,
        description: item.description,
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
            await axios.put(`/api/pathology/test-categories/${form.value.id}`, form.value);
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
</script>

<style scoped>
.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>
