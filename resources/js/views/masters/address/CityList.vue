<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">City/Taluka Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add City/Taluka
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search..." v-model="search" @input="fetchData">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterState" @change="onStateChange">
                            <option value="">All States</option>
                            <option v-for="s in states" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterDistrict" @change="fetchData">
                            <option value="">All Districts</option>
                            <option v-for="d in filteredDistricts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>City/Taluka Name</th>
                                <th>Code</th>
                                <th>District</th>
                                <th>State</th>
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
                            <tr v-for="(item, index) in items" :key="item.city_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.city_name }}</td>
                                <td>{{ item.city_code || '-' }}</td>
                                <td>{{ item.district?.district_name || '-' }}</td>
                                <td>{{ item.district?.state?.state_name || '-' }}</td>
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
                                    <span v-else class="badge bg-info" title="Has areas defined">
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
                        <h5 class="modal-title">{{ editingItem ? 'Edit City/Taluka' : 'Add City/Taluka' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">State *</label>
                                <select class="form-select" v-model="formState" @change="onFormStateChange" required>
                                    <option value="">Select State</option>
                                    <option v-for="s in states" :key="s.state_id" :value="s.state_id">{{ s.state_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">District *</label>
                                <select class="form-select" v-model="form.district_id" required>
                                    <option value="">Select District</option>
                                    <option v-for="d in formDistricts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City/Taluka Name *</label>
                                <input type="text" class="form-control" v-model="form.city_name" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City Code</label>
                                <input type="text" class="form-control" v-model="form.city_code" maxlength="10">
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
const states = ref([]);
const districts = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const filterState = ref('');
const filterDistrict = ref('');
const formState = ref('');
const editingItem = ref(null);
const modalRef = ref(null);
let modal = null;

const form = reactive({
    district_id: '',
    city_name: '',
    city_code: '',
    is_active: true
});

const filteredDistricts = computed(() => {
    if (!filterState.value) return districts.value;
    return districts.value.filter(d => d.state_id == filterState.value);
});

const formDistricts = computed(() => {
    if (!formState.value) return [];
    return districts.value.filter(d => d.state_id == formState.value);
});

const fetchStates = async () => {
    try {
        const response = await axios.get('/api/states-active');
        states.value = response.data;
    } catch (error) {
        console.error('Error fetching states:', error);
    }
};

const fetchDistricts = async () => {
    try {
        const response = await axios.get('/api/districts-active');
        districts.value = response.data;
    } catch (error) {
        console.error('Error fetching districts:', error);
    }
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (filterDistrict.value) params.district_id = filterDistrict.value;
        const response = await axios.get('/api/cities', { params });
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
    loading.value = false;
};

const onStateChange = () => {
    filterDistrict.value = '';
    fetchData();
};

const onFormStateChange = () => {
    form.district_id = '';
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        formState.value = item.district?.state_id || '';
        Object.assign(form, {
            district_id: item.district_id,
            city_name: item.city_name,
            city_code: item.city_code || '',
            is_active: item.is_active
        });
    } else {
        formState.value = '';
        Object.assign(form, {
            district_id: '',
            city_name: '',
            city_code: '',
            is_active: true
        });
    }
    modal.show();
};

const saveItem = async () => {
    saving.value = true;
    try {
        if (editingItem.value) {
            await axios.put(`/api/cities/${editingItem.value.city_id}`, form);
        } else {
            await axios.post('/api/cities', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.city_name}"?`)) return;
    try {
        await axios.delete(`/api/cities/${item.city_id}`);
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting record');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchStates();
    fetchDistricts();
    fetchData();
});
</script>
