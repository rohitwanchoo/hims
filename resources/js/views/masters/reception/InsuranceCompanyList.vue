<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Insurance Company Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Insurance Company
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
                                <th>Company Name</th>
                                <th>Company Code</th>
                                <th>Contact Person</th>
                                <th>Mobile</th>
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
                            <tr v-for="(item, index) in items" :key="item.insurance_company_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.company_name }}</td>
                                <td>{{ item.company_code || '-' }}</td>
                                <td>{{ item.contact_person || '-' }}</td>
                                <td>{{ item.mobile || '-' }}</td>
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
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingItem ? 'Edit Insurance Company' : 'Add Insurance Company' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" v-model="form.company_name" required maxlength="150">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Company Code</label>
                                    <input type="text" class="form-control" v-model="form.company_code" maxlength="50">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" v-model="form.contact_person" maxlength="100">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mobile</label>
                                    <input type="text" class="form-control" v-model="form.mobile" maxlength="15">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" v-model="form.email" maxlength="100">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" v-model="form.website" maxlength="200">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" v-model="form.address" rows="2"></textarea>
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
    company_name: '',
    company_code: '',
    address: '',
    contact_person: '',
    mobile: '',
    email: '',
    website: '',
    is_active: true
});

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        const response = await axios.get('/api/insurance-companies', { params });
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
            company_name: item.company_name,
            company_code: item.company_code || '',
            address: item.address || '',
            contact_person: item.contact_person || '',
            mobile: item.mobile || '',
            email: item.email || '',
            website: item.website || '',
            is_active: item.is_active
        });
    } else {
        Object.assign(form, {
            company_name: '',
            company_code: '',
            address: '',
            contact_person: '',
            mobile: '',
            email: '',
            website: '',
            is_active: true
        });
    }
    modal.show();
};

const saveItem = async () => {
    saving.value = true;
    try {
        if (editingItem.value) {
            await axios.put(`/api/insurance-companies/${editingItem.value.insurance_company_id}`, form);
        } else {
            await axios.post('/api/insurance-companies', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.company_name}"?`)) return;
    try {
        await axios.delete(`/api/insurance-companies/${item.insurance_company_id}`);
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
