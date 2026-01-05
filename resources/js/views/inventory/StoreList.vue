<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-building me-2"></i>Inventory Stores</h4>
            <button @click="showModal = true" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Store
            </button>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Store Code</th>
                            <th>Store Name</th>
                            <th>Location</th>
                            <th>Store Type</th>
                            <th>In-Charge</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                        <tr v-else-if="stores.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">No stores found</td>
                        </tr>
                        <tr v-for="store in stores" :key="store.store_id">
                            <td><strong>{{ store.store_code }}</strong></td>
                            <td>{{ store.store_name }}</td>
                            <td>{{ store.location || '-' }}</td>
                            <td>
                                <span class="badge bg-info">{{ store.store_type }}</span>
                            </td>
                            <td>{{ store.in_charge?.full_name || '-' }}</td>
                            <td>
                                <span class="badge" :class="store.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ store.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button @click="editStore(store)" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button @click="viewStock(store)" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-box-seam"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingStore ? 'Edit Store' : 'Add Store' }}</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="saveStore">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Store Code *</label>
                                <input type="text" v-model="form.store_code" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Store Name *</label>
                                <input type="text" v-model="form.store_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Store Type *</label>
                                <select v-model="form.store_type" class="form-select" required>
                                    <option value="main">Main Store</option>
                                    <option value="sub">Sub Store</option>
                                    <option value="pharmacy">Pharmacy</option>
                                    <option value="ward">Ward Store</option>
                                    <option value="ot">OT Store</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" v-model="form.location" class="form-control">
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" v-model="form.is_active" class="form-check-input" id="isActive">
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stores = ref([]);
const loading = ref(false);
const showModal = ref(false);
const saving = ref(false);
const editingStore = ref(null);

const form = ref({
    store_code: '',
    store_name: '',
    store_type: 'main',
    location: '',
    is_active: true
});

const loadStores = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/inventory/stores');
        stores.value = response.data.stores || response.data.data || [];
    } catch (error) {
        console.error('Failed to load stores:', error);
    } finally {
        loading.value = false;
    }
};

const editStore = (store) => {
    editingStore.value = store;
    form.value = {
        store_code: store.store_code,
        store_name: store.store_name,
        store_type: store.store_type,
        location: store.location || '',
        is_active: store.is_active
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingStore.value = null;
    form.value = {
        store_code: '',
        store_name: '',
        store_type: 'main',
        location: '',
        is_active: true
    };
};

const saveStore = async () => {
    saving.value = true;
    try {
        if (editingStore.value) {
            await axios.put(`/api/inventory/stores/${editingStore.value.store_id}`, form.value);
        } else {
            await axios.post('/api/inventory/stores', form.value);
        }
        closeModal();
        loadStores();
    } catch (error) {
        console.error('Failed to save store:', error);
        alert(error.response?.data?.message || 'Failed to save store');
    } finally {
        saving.value = false;
    }
};

const viewStock = (store) => {
    // Navigate to store stock view
};

onMounted(loadStores);
</script>
