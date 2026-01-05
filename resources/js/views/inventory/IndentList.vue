<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-file-earmark-arrow-up me-2"></i>Inventory Indents</h4>
            <router-link to="/inventory/indents/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Indent
            </router-link>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select v-model="filters.status" class="form-select" @change="loadIndents">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="partially_issued">Partially Issued</option>
                            <option value="issued">Issued</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select v-model="filters.store_id" class="form-select" @change="loadIndents">
                            <option value="">All Stores</option>
                            <option v-for="store in stores" :key="store.store_id" :value="store.store_id">
                                {{ store.store_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" v-model="filters.from_date" class="form-control" @change="loadIndents">
                    </div>
                    <div class="col-md-3">
                        <input type="date" v-model="filters.to_date" class="form-control" @change="loadIndents">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Indent #</th>
                            <th>From Store</th>
                            <th>To Store</th>
                            <th>Items</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                        <tr v-else-if="indents.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">No indents found</td>
                        </tr>
                        <tr v-for="indent in indents" :key="indent.indent_id">
                            <td><strong>{{ indent.indent_number }}</strong></td>
                            <td>{{ indent.from_store?.store_name }}</td>
                            <td>{{ indent.to_store?.store_name }}</td>
                            <td>{{ indent.items_count || indent.indent_items?.length || 0 }} items</td>
                            <td>{{ indent.requested_by?.full_name }}</td>
                            <td>{{ formatDate(indent.indent_date) }}</td>
                            <td>
                                <span class="badge" :class="getStatusClass(indent.status)">
                                    {{ indent.status }}
                                </span>
                            </td>
                            <td>
                                <router-link :to="`/inventory/indents/${indent.indent_id}`" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <button v-if="indent.status === 'pending'" @click="approveIndent(indent)" class="btn btn-sm btn-outline-success me-1">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <button v-if="indent.status === 'approved'" @click="issueIndent(indent)" class="btn btn-sm btn-success">
                                    Issue
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const indents = ref([]);
const stores = ref([]);
const loading = ref(false);

const filters = ref({
    status: '',
    store_id: '',
    from_date: '',
    to_date: ''
});

const loadIndents = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.store_id) params.append('store_id', filters.value.store_id);
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);

        const response = await axios.get(`/api/inventory/indents?${params}`);
        indents.value = response.data.indents || response.data.data || [];
    } catch (error) {
        console.error('Failed to load indents:', error);
    } finally {
        loading.value = false;
    }
};

const loadStores = async () => {
    try {
        const response = await axios.get('/api/inventory/stores');
        stores.value = response.data.stores || response.data.data || [];
    } catch (error) {
        console.error('Failed to load stores:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-warning',
        'approved': 'bg-info',
        'partially_issued': 'bg-primary',
        'issued': 'bg-success',
        'rejected': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const approveIndent = async (indent) => {
    if (!confirm('Approve this indent?')) return;
    try {
        await axios.post(`/api/inventory/indents/${indent.indent_id}/approve`);
        loadIndents();
    } catch (error) {
        console.error('Failed to approve indent:', error);
        alert(error.response?.data?.message || 'Failed to approve indent');
    }
};

const issueIndent = async (indent) => {
    // Navigate to issue form
};

onMounted(() => {
    loadIndents();
    loadStores();
});
</script>
