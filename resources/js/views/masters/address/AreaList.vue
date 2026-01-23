<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Area/Village Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Area/Village
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search..." v-model="search" @input="fetchData">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterDistrict" @change="onDistrictChange">
                            <option value="">All Districts</option>
                            <option v-for="d in districts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterCity" @change="fetchData">
                            <option value="">All Cities</option>
                            <option v-for="c in filteredCities" :key="c.city_id" :value="c.city_id">{{ c.city_name }}</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Area/Village Name</th>
                                <th>Pincode</th>
                                <th>City/Taluka</th>
                                <th>District</th>
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
                            <tr v-for="(item, index) in items" :key="item.area_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.area_name }}</td>
                                <td>{{ item.pincode || '-' }}</td>
                                <td>{{ item.city?.city_name || '-' }}</td>
                                <td>{{ item.city?.district?.district_name || '-' }}</td>
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

        <div class="modal fade" id="itemModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingItem ? 'Edit Area/Village' : 'Add Area/Village' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">District *</label>
                                <select class="form-select" v-model="formDistrict" @change="onFormDistrictChange" required>
                                    <option value="">Select District</option>
                                    <option v-for="d in districts" :key="d.district_id" :value="d.district_id">{{ d.district_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">City/Taluka *</label>
                                <select class="form-select" v-model="form.city_id" required>
                                    <option value="">Select City/Taluka</option>
                                    <option v-for="c in formCities" :key="c.city_id" :value="c.city_id">{{ c.city_name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Area/Village Name *</label>
                                <input type="text" class="form-control" v-model="form.area_name" required maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pincode</label>
                                <input type="text" class="form-control" v-model="form.pincode" maxlength="10">
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
const districts = ref([]);
const cities = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const filterDistrict = ref('');
const filterCity = ref('');
const formDistrict = ref('');
const editingItem = ref(null);
const modalRef = ref(null);
let modal = null;

const form = reactive({
    city_id: '',
    area_name: '',
    pincode: '',
    is_active: true
});

const filteredCities = computed(() => {
    if (!filterDistrict.value) return cities.value;
    return cities.value.filter(c => c.district_id == filterDistrict.value);
});

const formCities = computed(() => {
    if (!formDistrict.value) return [];
    return cities.value.filter(c => c.district_id == formDistrict.value);
});

const fetchDistricts = async () => {
    try {
        const response = await axios.get('/api/districts-active');
        districts.value = response.data;
    } catch (error) {
        console.error('Error fetching districts:', error);
    }
};

const fetchCities = async () => {
    try {
        const response = await axios.get('/api/cities-active');
        cities.value = response.data;
    } catch (error) {
        console.error('Error fetching cities:', error);
    }
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (filterCity.value) params.city_id = filterCity.value;
        const response = await axios.get('/api/areas', { params });
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
    loading.value = false;
};

const onDistrictChange = () => {
    filterCity.value = '';
    fetchData();
};

const onFormDistrictChange = () => {
    form.city_id = '';
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        formDistrict.value = item.city?.district_id || '';
        Object.assign(form, {
            city_id: item.city_id,
            area_name: item.area_name,
            pincode: item.pincode || '',
            is_active: item.is_active
        });
    } else {
        formDistrict.value = '';
        Object.assign(form, {
            city_id: '',
            area_name: '',
            pincode: '',
            is_active: true
        });
    }
    modal.show();
};

const saveItem = async () => {
    saving.value = true;
    try {
        if (editingItem.value) {
            await axios.put(`/api/areas/${editingItem.value.area_id}`, form);
        } else {
            await axios.post('/api/areas', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.area_name}"?`)) return;
    try {
        await axios.delete(`/api/areas/${item.area_id}`);
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting record');
    }
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    fetchDistricts();
    fetchCities();
    fetchData();
});
</script>
