<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Country Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Country
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" placeholder="Search..." v-model="search" @input="fetchData">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Country Name</th>
                                <th>Country Code</th>
                                <th>Phone Code</th>
                                <th>Default</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2"></div>Loading...
                                </td>
                            </tr>
                            <tr v-else-if="items.length === 0">
                                <td colspan="7" class="text-center py-4 text-muted">No records found</td>
                            </tr>
                            <tr v-for="(item, index) in items" :key="item.country_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.country_name }}</td>
                                <td>{{ item.country_code || '-' }}</td>
                                <td>{{ item.phone_code || '-' }}</td>
                                <td>
                                    <span v-if="item.is_default" class="badge bg-primary">Default</span>
                                    <span v-else>-</span>
                                </td>
                                <td>
                                    <span :class="item.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ item.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary me-1" @click="openModal(item)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button
                                        v-if="!item.usage_count"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="deleteItem(item)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <span v-else class="badge bg-info" title="Has states defined">
                                        <i class="bi bi-link-45deg"></i> In Use
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="itemModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingItem ? 'Edit Country' : 'Add Country' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Country Name *</label>
                                <input type="text" class="form-control" v-model="form.country_name" required maxlength="100">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Country Code</label>
                                    <input type="text" class="form-control" v-model="form.country_code" maxlength="10" placeholder="e.g., IN">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Code</label>
                                    <input type="text" class="form-control" v-model="form.phone_code" maxlength="10" placeholder="e.g., +91">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" v-model="form.is_active">
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_default" v-model="form.is_default">
                                        <label class="form-check-label" for="is_default">Set as Default</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editingItem ? 'Update' : 'Save' }}
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

const items = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const editingItem = ref(null);
const modalRef = ref(null);
let modal = null;

const form = reactive({
    country_name: '',
    country_code: '',
    phone_code: '',
    is_active: true,
    is_default: false
});

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        const response = await axios.get('/api/countries', { params });
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
    loading.value = false;
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        Object.assign(form, {
            country_name: item.country_name,
            country_code: item.country_code || '',
            phone_code: item.phone_code || '',
            is_active: item.is_active,
            is_default: item.is_default || false
        });
    } else {
        Object.assign(form, {
            country_name: '',
            country_code: '',
            phone_code: '',
            is_active: true,
            is_default: false
        });
    }
    modal.show();
};

const saveItem = async () => {
    saving.value = true;
    try {
        if (editingItem.value) {
            await axios.put(`/api/countries/${editingItem.value.country_id}`, form);
        } else {
            await axios.post('/api/countries', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.country_name}"?`)) return;
    try {
        await axios.delete(`/api/countries/${item.country_id}`);
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting record');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchData();
});
</script>
