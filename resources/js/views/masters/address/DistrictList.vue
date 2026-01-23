<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">District Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add District
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search..." v-model="search" @input="fetchData">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterCountry" @change="onCountryChange">
                            <option value="">All Countries</option>
                            <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterState" @change="fetchData">
                            <option value="">All States</option>
                            <option v-for="s in filteredStates" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>District Name</th>
                                <th>District Code</th>
                                <th>State</th>
                                <th>Country</th>
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
                            <tr v-for="(item, index) in items" :key="item.district_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.district_name }}</td>
                                <td>{{ item.district_code || '-' }}</td>
                                <td>{{ item.state?.state_name || '-' }}</td>
                                <td>{{ item.state?.country?.country_name || '-' }}</td>
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
                                    <span v-else class="badge bg-info" title="Has cities defined">
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
                        <h5 class="modal-title">{{ editingItem ? 'Edit District' : 'Add District' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Country *</label>
                                <select class="form-select" v-model="formCountry" @change="onFormCountryChange" required>
                                    <option value="">Select Country</option>
                                    <option v-for="c in countries" :key="c.country_id" :value="c.country_id">{{ c.country_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">State *</label>
                                <select class="form-select" v-model="form.state_id" required>
                                    <option value="">Select State</option>
                                    <option v-for="s in formStates" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">District Name *</label>
                                <input type="text" class="form-control" v-model="form.district_name" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">District Code</label>
                                <input type="text" class="form-control" v-model="form.district_code" maxlength="10">
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" v-model="form.is_active">
                                    <label class="form-check-label" for="is_active">Active</label>
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
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const items = ref([]);
const countries = ref([]);
const states = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const filterCountry = ref('');
const filterState = ref('');
const formCountry = ref('');
const editingItem = ref(null);
const modalRef = ref(null);
let modal = null;

const form = reactive({
    state_id: '',
    district_name: '',
    district_code: '',
    is_active: true
});

const filteredStates = computed(() => {
    if (!filterCountry.value) return states.value;
    return states.value.filter(s => s.country_id == filterCountry.value);
});

const formStates = computed(() => {
    if (!formCountry.value) return [];
    return states.value.filter(s => s.country_id == formCountry.value);
});

const fetchCountries = async () => {
    try {
        const response = await axios.get('/api/countries-active');
        countries.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
    }
};

const fetchStates = async () => {
    try {
        const response = await axios.get('/api/states-active');
        states.value = response.data;
    } catch (error) {
        console.error('Error fetching states:', error);
    }
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (filterState.value) params.state_id = filterState.value;
        const response = await axios.get('/api/districts', { params });
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
    loading.value = false;
};

const onCountryChange = () => {
    filterState.value = '';
    fetchData();
};

const onFormCountryChange = () => {
    form.state_id = '';
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        formCountry.value = item.state?.country_id || '';
        Object.assign(form, {
            state_id: item.state_id,
            district_name: item.district_name,
            district_code: item.district_code || '',
            is_active: item.is_active
        });
    } else {
        formCountry.value = '';
        Object.assign(form, {
            state_id: '',
            district_name: '',
            district_code: '',
            is_active: true
        });
    }
    modal.show();
};

const saveItem = async () => {
    saving.value = true;
    try {
        if (editingItem.value) {
            await axios.put(`/api/districts/${editingItem.value.district_id}`, form);
        } else {
            await axios.post('/api/districts', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.district_name}"?`)) return;
    try {
        await axios.delete(`/api/districts/${item.district_id}`);
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting record');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchCountries();
    fetchStates();
    fetchData();
});
</script>
