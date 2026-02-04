<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Age Group Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Age Group
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
                                <th>Age Group Caption</th>
                                <th>From Age</th>
                                <th>To Age</th>
                                <th>Age Unit</th>
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
                            <tr v-for="(item, index) in items" :key="item.age_group_id">
                                <td>{{ index + 1 }}</td>
                                <td>{{ item.age_group_caption }}</td>
                                <td>{{ item.from_age }}</td>
                                <td>{{ item.to_age }}</td>
                                <td>
                                    <span class="badge bg-info">{{ formatAgeUnit(item.age_unit) }}</span>
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
                                        class="btn btn-sm btn-outline-danger"
                                        @click="deleteItem(item)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="itemModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingItem ? 'Edit Age Group' : 'Add Age Group' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveItem">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Age Group Caption *</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    v-model="form.age_group_caption"
                                    required
                                    maxlength="100"
                                    placeholder="e.g., Newborn, Infant, Child, Adult">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">From Age *</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.from_age"
                                        required
                                        min="0"
                                        placeholder="0">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">To Age *</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.to_age"
                                        required
                                        min="0"
                                        placeholder="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Age Unit *</label>
                                <select class="form-select" v-model="form.age_unit" required>
                                    <option value="">Select Unit</option>
                                    <option value="days">Days</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
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
    age_group_caption: '',
    from_age: 0,
    to_age: 0,
    age_unit: '',
    is_active: true
});

const formatAgeUnit = (unit) => {
    if (!unit) return '-';
    return unit.charAt(0).toUpperCase() + unit.slice(1);
};

const fetchData = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        const response = await axios.get('/api/age-groups', { params });
        items.value = response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    }
    loading.value = false;
};

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.age_group_caption = item.age_group_caption;
        form.from_age = item.from_age;
        form.to_age = item.to_age;
        form.age_unit = item.age_unit;
        form.is_active = item.is_active;
    } else {
        form.age_group_caption = '';
        form.from_age = 0;
        form.to_age = 0;
        form.age_unit = '';
        form.is_active = true;
    }
    modal.show();
};

const saveItem = async () => {
    // Validate to_age >= from_age
    if (form.to_age < form.from_age) {
        alert('To Age must be greater than or equal to From Age');
        return;
    }

    saving.value = true;
    try {
        if (editingItem.value) {
            await axios.put(`/api/age-groups/${editingItem.value.age_group_id}`, form);
        } else {
            await axios.post('/api/age-groups', form);
        }
        modal.hide();
        fetchData();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving record');
    }
    saving.value = false;
};

const deleteItem = async (item) => {
    if (!confirm(`Are you sure you want to delete "${item.age_group_caption}"?`)) return;
    try {
        await axios.delete(`/api/age-groups/${item.age_group_id}`);
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
