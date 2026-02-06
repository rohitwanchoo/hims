<template>
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Pathology Instruction Master</h4>
            <button class="btn btn-primary btn-sm" @click="openAddModal">
                <i class="bi bi-plus-circle"></i> Add Instruction
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
                            @input="loadInstructions"
                            placeholder="Search instructions...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.instruction_type" @change="loadInstructions">
                            <option value="">All Types</option>
                            <option value="pathology">Pathology</option>
                            <option value="procedure">Procedure</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.is_active" @change="loadInstructions">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" v-model="filters.per_page" @change="loadInstructions">
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
                                <th style="width: 120px;">Type</th>
                                <th style="min-width: 300px;">Instruction Text</th>
                                <th style="width: 100px;" class="text-center">Status</th>
                                <th style="width: 120px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="5" class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="instructions.length === 0">
                                <td colspan="5" class="text-center text-muted py-3">
                                    No instructions found
                                </td>
                            </tr>
                            <tr v-else v-for="(item, index) in instructions" :key="item.instruction_id">
                                <td>{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                                <td>
                                    <span class="badge" :class="item.instruction_type === 'pathology' ? 'bg-info' : 'bg-warning'">
                                        {{ item.instruction_type }}
                                    </span>
                                </td>
                                <td>{{ item.instruction_text }}</td>
                                <td class="text-center">
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="editInstruction(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteInstruction(item.instruction_id)" title="Delete">
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
        <div class="modal fade" ref="instructionModalRef" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editMode ? 'Edit' : 'Add' }} Instruction</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveInstruction">
                        <div class="modal-body">
                            <div class="alert alert-danger" v-if="error">
                                {{ error }}
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Instruction Type *</label>
                                <select class="form-select" v-model="form.instruction_type" required>
                                    <option value="">Select Type</option>
                                    <option value="pathology">Pathology</option>
                                    <option value="procedure">Procedure</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Instruction Text *</label>
                                <textarea
                                    class="form-control"
                                    v-model="form.instruction_text"
                                    rows="4"
                                    placeholder="Enter instruction details..."
                                    required></textarea>
                                <small class="text-muted">
                                    E.g., "Fasting required for 8-12 hours before test" or "Collect morning sample"
                                </small>
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
const instructions = ref([]);
const filters = ref({
    search: '',
    instruction_type: '',
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
    instruction_type: '',
    instruction_text: '',
    is_active: true,
});

let instructionModal = null;
const instructionModalRef = ref(null);

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
    if (instructionModalRef.value) {
        instructionModal = new Modal(instructionModalRef.value);
    }
    loadInstructions();
});

const loadInstructions = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/pathology/instructions', { params: filters.value });
        if (response.data.success && response.data.data) {
            const paginatedData = response.data.data;
            instructions.value = paginatedData.data || paginatedData;
            pagination.value = {
                current_page: paginatedData.current_page || 1,
                last_page: paginatedData.last_page || 1,
                per_page: paginatedData.per_page || 20,
                total: paginatedData.total || 0,
                from: paginatedData.from || 0,
                to: paginatedData.to || 0,
            };
        } else {
            instructions.value = response.data.data || response.data;
        }

        if (!Array.isArray(instructions.value)) {
            instructions.value = [];
        }
    } catch (err) {
        console.error('Error loading instructions:', err);
        error.value = 'Failed to load instructions';
    } finally {
        loading.value = false;
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page;
        loadInstructions();
    }
};

const openAddModal = () => {
    editMode.value = false;
    error.value = null;
    form.value = {
        instruction_type: '',
        instruction_text: '',
        is_active: true,
    };
    if (instructionModal) {
        instructionModal.show();
    }
};

const editInstruction = (item) => {
    editMode.value = true;
    error.value = null;
    form.value = {
        instruction_id: item.instruction_id,
        instruction_type: item.instruction_type,
        instruction_text: item.instruction_text,
        is_active: item.is_active,
    };
    if (instructionModal) {
        instructionModal.show();
    }
};

const saveInstruction = async () => {
    saving.value = true;
    error.value = null;
    try {
        if (editMode.value) {
            await axios.put(`/api/pathology/instructions/${form.value.instruction_id}`, form.value);
        } else {
            await axios.post('/api/pathology/instructions', form.value);
        }
        closeModal();
        loadInstructions();
    } catch (err) {
        console.error('Error saving instruction:', err);
        error.value = err.response?.data?.message || 'Failed to save instruction';
    } finally {
        saving.value = false;
    }
};

const deleteInstruction = async (id) => {
    if (!confirm('Are you sure you want to delete this instruction?')) {
        return;
    }

    try {
        await axios.delete(`/api/pathology/instructions/${id}`);
        loadInstructions();
    } catch (err) {
        console.error('Error deleting instruction:', err);
        alert(err.response?.data?.message || 'Failed to delete instruction');
    }
};

const closeModal = () => {
    if (instructionModal) {
        instructionModal.hide();
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
