<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-radioactive me-2"></i>Radiology Orders</h4>
            <router-link to="/radiology/orders/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Order
            </router-link>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="date" v-model="filters.date" class="form-control" @change="loadOrders">
                    </div>
                    <div class="col-md-3">
                        <select v-model="filters.status" class="form-select" @change="loadOrders">
                            <option value="">All Status</option>
                            <option value="ordered">Ordered</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" v-model="filters.search" class="form-control" placeholder="Search patient or order #" @input="loadOrders">
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Patient</th>
                            <th>Referring Doctor</th>
                            <th>Tests</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status"></div>
                            </td>
                        </tr>
                        <tr v-else-if="orders.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">No radiology orders found</td>
                        </tr>
                        <tr v-for="order in orders" :key="order.radiology_order_id">
                            <td><strong>{{ order.order_number }}</strong></td>
                            <td>{{ order.patient?.patient_name }}</td>
                            <td>{{ order.referring_doctor?.full_name }}</td>
                            <td>{{ order.order_details?.length || 0 }} tests</td>
                            <td>
                                <span class="badge" :class="getPriorityClass(order.priority)">
                                    {{ order.priority }}
                                </span>
                            </td>
                            <td>
                                <span class="badge" :class="getStatusClass(order.status)">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td>{{ formatDate(order.created_at) }}</td>
                            <td>
                                <router-link :to="`/radiology/orders/${order.radiology_order_id}`" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <button v-if="order.status === 'completed'" @click="viewReport(order)" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-file-earmark-text"></i>
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

const orders = ref([]);
const loading = ref(false);
const filters = ref({
    date: new Date().toISOString().split('T')[0],
    status: '',
    search: ''
});

const loadOrders = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.date) params.append('date', filters.value.date);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.search) params.append('search', filters.value.search);

        const response = await axios.get(`/api/radiology/orders?${params}`);
        orders.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to load orders:', error);
    } finally {
        loading.value = false;
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const getStatusClass = (status) => {
    const classes = {
        'ordered': 'bg-secondary',
        'scheduled': 'bg-info',
        'in_progress': 'bg-warning',
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const getPriorityClass = (priority) => {
    const classes = {
        'routine': 'bg-secondary',
        'urgent': 'bg-warning',
        'stat': 'bg-danger'
    };
    return classes[priority] || 'bg-secondary';
};

const viewReport = (order) => {
    // Navigate to report view
};

onMounted(loadOrders);
</script>
