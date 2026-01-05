<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-box-seam me-2"></i>Inventory Management</h4>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3>{{ stats.totalItems }}</h3>
                                <small>Total Items</small>
                            </div>
                            <i class="bi bi-box display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3>{{ stats.lowStock }}</h3>
                                <small>Low Stock Items</small>
                            </div>
                            <i class="bi bi-exclamation-triangle display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3>{{ stats.expiringItems }}</h3>
                                <small>Expiring Soon</small>
                            </div>
                            <i class="bi bi-calendar-x display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h3>{{ stats.pendingIndents }}</h3>
                                <small>Pending Indents</small>
                            </div>
                            <i class="bi bi-file-earmark-text display-6"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-3">
                <router-link to="/inventory/items" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam display-4 text-primary"></i>
                        <h6 class="mt-2">Items Master</h6>
                    </div>
                </router-link>
            </div>
            <div class="col-md-3">
                <router-link to="/inventory/stores" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-building display-4 text-success"></i>
                        <h6 class="mt-2">Stores</h6>
                    </div>
                </router-link>
            </div>
            <div class="col-md-3">
                <router-link to="/inventory/indents" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-arrow-up display-4 text-info"></i>
                        <h6 class="mt-2">Indents</h6>
                    </div>
                </router-link>
            </div>
            <div class="col-md-3">
                <router-link to="/inventory/purchase-orders" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-cart-check display-4 text-warning"></i>
                        <h6 class="mt-2">Purchase Orders</h6>
                    </div>
                </router-link>
            </div>
        </div>

        <div class="row">
            <!-- Low Stock Alert -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <i class="bi bi-exclamation-triangle me-2"></i>Low Stock Items
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div v-for="item in lowStockItems" :key="item.item_id" class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ item.item_name }}</strong>
                                    <br><small class="text-muted">{{ item.item_code }}</small>
                                </div>
                                <span class="badge bg-danger">{{ item.current_stock }} left</span>
                            </div>
                            <div v-if="lowStockItems.length === 0" class="list-group-item text-center text-muted">
                                No low stock items
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiring Items -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning">
                        <i class="bi bi-calendar-x me-2"></i>Expiring Items (Next 30 Days)
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div v-for="item in expiringItems" :key="item.stock_id" class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ item.item?.item_name }}</strong>
                                    <br><small class="text-muted">Batch: {{ item.batch_number }}</small>
                                </div>
                                <span class="badge bg-warning text-dark">{{ formatDate(item.expiry_date) }}</span>
                            </div>
                            <div v-if="expiringItems.length === 0" class="list-group-item text-center text-muted">
                                No items expiring soon
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stats = ref({
    totalItems: 0,
    lowStock: 0,
    expiringItems: 0,
    pendingIndents: 0
});
const lowStockItems = ref([]);
const expiringItems = ref([]);

const loadData = async () => {
    try {
        const [lowStock, expiring] = await Promise.all([
            axios.get('/api/inventory/items/low-stock'),
            axios.get('/api/inventory/items/expiring?days=30')
        ]);

        lowStockItems.value = lowStock.data.items || [];
        expiringItems.value = expiring.data.expiring_stock || [];

        stats.value.lowStock = lowStockItems.value.length;
        stats.value.expiringItems = expiringItems.value.length;
    } catch (error) {
        console.error('Failed to load inventory data:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();

onMounted(loadData);
</script>
