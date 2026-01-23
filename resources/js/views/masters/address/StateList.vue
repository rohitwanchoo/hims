<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">State Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add State
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search..." v-model="search" @input="fetchData">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterCountry" @change="fetchData">
                            <option value="">All Countries</option>
                            <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>State Name</th>
                                <th>State Code</th>
                                <th>Country</th>
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
                            <tr v-for="(item, index) in items" :key="item.state_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.state_name }}</td>
                                <td>{{ item.state_code || '-' }}</td>
                                <td>{{ item.country?.country_name || '-' }}</td>
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
                                    <span v-else class="badge bg-info" title="Has districts defined">
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
                        <h5 class="modal-title">{{ editingItem ? 'Edit State' : 'Add State' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Country *</label>
                                <select class="form-select" v-model="form.country_id" required>
                                    <option value="">Select Country</option>
                                    <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">State Name *</label>
                                <input type="text" class="form-control" v-model="form.state_name" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">State Code</label>
                                <input type="text" class="form-control" v-model="form.state_code" maxlength="10" placeholder="e.g., MH">
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
const countries = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const filterCountry = ref('');
const editingItem = ref(null);
const modalRef = ref(null);
let modal = null;

const form = reactive({
    country_id: '',
    state_name: '',
    state_code: '',
    is_active: true,
    is_default: false
});

const fetchCountries = async () => {
    try {
        const response = await axios.get('/api/countries-active');
        countries.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
    }
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (filterCountry.value) params.country_id = filterCountry.value;
        const response = await axios.get('/api/states', { params });
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
            country_id: item.country_id,
            state_name: item.state_name,
            state_code: item.state_code || '',
            is_active: item.is_active,
            is_default: item.is_default || false
        });
    } else {
        Object.assign(form, {
            country_id: '',
            state_name: '',
            state_code: '',
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
            await axios.put(`/api/states/${editingItem.value.state_id}`, form);
        } else {
            await axios.post('/api/states', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.state_name}"?`)) return;
    try {
        await axios.delete(`/api/states/${item.state_id}`);
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting record');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchCountries();
    fetchData();
});
</script>
