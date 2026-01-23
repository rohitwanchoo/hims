<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Prefix Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Prefix
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search prefixes..."
                            v-model="search"
                            @input="fetchPrefixes"
                        >
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Prefix Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2"></div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="prefixes.length === 0">
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No prefixes found
                                </td>
                            </tr>
                            <tr v-for="(prefix, index) in prefixes" :key="prefix.prefix_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ prefix.prefix_name }}</td>
                                <td>{{ prefix.description || '-' }}</td>
                                <td>
                                    <span :class="prefix.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ prefix.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(prefix)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        v-if="!prefix.usage_count"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="deletePrefix(prefix)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <span v-else class="badge bg-info" title="In use by patients">
                                        <i class="bi bi-link-45deg"></i> In Use
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="prefixModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingPrefix ? 'Edit Prefix' : 'Add Prefix' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="savePrefix">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Prefix Name *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.prefix_name"
                                    required
                                    maxlength="50"
                                >
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.description"
                                    maxlength="255"
                                >
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input
                                        type="checkbox"
                                        class="form-check-input"
                                        id="is_active"
                                        v-model="form.is_active"
                                    >
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editingPrefix ? 'Update' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const prefixes = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const editingPrefix = ref(null);
const modalRef = ref(null);
let modal = null;

const form = reactive({
    prefix_name: '',
    description: '',
    is_active: true
});

const fetchPrefixes = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;

        const response = await axios.get('/api/prefixes', { params });
        prefixes.value = response.data;
    } catch (error) {
        console.error('Error fetching prefixes:', error);
    }
    loading.value = false;
};

const openModal = (prefix = null) => {
    editingPrefix.value = prefix;
    if (prefix) {
        form.prefix_name = prefix.prefix_name;
        form.description = prefix.description || '';
        form.is_active = prefix.is_active;
    } else {
        form.prefix_name = '';
        form.description = '';
        form.is_active = true;
    }
    modal.show();
};

const savePrefix = async () => {
    saving.value = true;
    try {
        if (editingPrefix.value) {
            await axios.put(`/api/prefixes/${editingPrefix.value.prefix_id}`, form);
        } else {
            await axios.post('/api/prefixes', form);
        }
        modal.hide();
        fetchPrefixes();
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            alert('Validation Error:\n' + errors.join('\n'));
        } else {
            alert('Error saving prefix');
        }
    }
    saving.value = false;
};

const deletePrefix = async (prefix) => {
    if (!confirm(`Are you sure you want to delete "${prefix.prefix_name}"?`)) return;

    try {
        await axios.delete(`/api/prefixes/${prefix.prefix_id}`);
        fetchPrefixes();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting prefix');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchPrefixes();
});
</script>
