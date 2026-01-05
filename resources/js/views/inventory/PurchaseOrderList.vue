<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-cart-check me-2"></i>Purchase Orders</h4>
            <router-link to="/inventory/purchase-orders/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </router-link>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select v-model="filters.status" class="form-select" @change="loadOrders">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="submitted">Submitted</option>
                            <option value="approved">Approved</option>
                            <option value="ordered">Ordered</option>
                            <option value="partially_received">Partially Received</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select v-model="filters.vendor_id" class="form-select" @change="loadOrders">
                            <option value="">All Vendors</option>
                            <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                                {{ vendor.vendor_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" v-model="filters.from_date" class="form-control" @change="loadOrders">
                    </div>
                    <div class="col-md-3">
                        <input type="date" v-model="filters.to_date" class="form-control" @change="loadOrders">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>PO Number</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Delivery Date</th>
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
                        <tr v-else-if="orders.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">No purchase orders found</td>
                        </tr>
                        <tr v-for="order in orders" :key="order.po_id">
                            <td><strong>{{ order.po_number }}</strong></td>
                            <td>{{ order.vendor?.vendor_name }}</td>
                            <td>{{ order.items_count || order.po_items?.length || 0 }} items</td>
                            <td>{{ formatCurrency(order.total_amount) }}</td>
                            <td>{{ formatDate(order.po_date) }}</td>
                            <td>{{ order.expected_delivery_date ? formatDate(order.expected_delivery_date) : '-' }}</td>
                            <td>
                                <span class="badge" :class="getStatusClass(order.status)">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td>
                                <router-link :to="`/inventory/purchase-orders/${order.po_id}`" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <button v-if="order.status === 'approved' || order.status === 'ordered'" @click="receiveOrder(order)" class="btn btn-sm btn-success">
                                    Receive
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
const vendors = ref([]);
const loading = ref(false);

const filters = ref({
    status: '',
    vendor_id: '',
    from_date: '',
    to_date: ''
});

const loadOrders = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.vendor_id) params.append('vendor_id', filters.value.vendor_id);
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);

        const response = await axios.get(`/api/inventory/purchase-orders?${params}`);
        orders.value = response.data.purchase_orders || response.data.data || [];
    } catch (error) {
        console.error('Failed to load purchase orders:', error);
    } finally {
        loading.value = false;
    }
};

const loadVendors = async () => {
    try {
        const response = await axios.get('/api/inventory/vendors');
        vendors.value = response.data.vendors || response.data.data || [];
    } catch (error) {
        console.error('Failed to load vendors:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();
const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);

const getStatusClass = (status) => {
    const classes = {
        'draft': 'bg-secondary',
        'submitted': 'bg-info',
        'approved': 'bg-primary',
        'ordered': 'bg-warning',
        'partially_received': 'bg-info',
        'received': 'bg-success',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const receiveOrder = async (order) => {
    // Navigate to receive form
};

onMounted(() => {
    loadOrders();
    loadVendors();
});
</script>
