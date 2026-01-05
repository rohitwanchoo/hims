<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>Lab Orders</h4>
            <router-link to="/laboratory/orders/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Order</router-link>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Patient</th>
                            <th>Order Date</th>
                            <th>Tests</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in orders" :key="order.order_id">
                            <td>{{ order.order_number }}</td>
                            <td>{{ order.patient?.first_name }} {{ order.patient?.last_name }}</td>
                            <td>{{ formatDate(order.order_date) }}</td>
                            <td>{{ order.details?.length || 0 }}</td>
                            <td>{{ formatCurrency(order.total_amount) }}</td>
                            <td>
                                <span class="badge" :class="getStatusClass(order.status)">{{ order.status }}</span>
                            </td>
                            <td>
                                <router-link :to="`/laboratory/orders/${order.order_id}`" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </router-link>
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
onMounted(async () => {
    const response = await axios.get('/api/lab-orders');
    orders.value = response.data.data || response.data;
});
const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};
const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-warning',
        'in_progress': 'bg-info',
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};
</script>
