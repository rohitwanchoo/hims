<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>Pharmacy Sales</h4>
            <router-link to="/pharmacy/sales/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Sale</router-link>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Patient</th>
                            <th>Sale Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in sales" :key="sale.sale_id">
                            <td>{{ sale.invoice_number }}</td>
                            <td>{{ sale.patient ? `${sale.patient.first_name} ${sale.patient.last_name}` : 'Walk-in' }}</td>
                            <td>{{ formatDate(sale.sale_date) }}</td>
                            <td>{{ sale.details?.length || 0 }}</td>
                            <td>{{ formatCurrency(sale.total_amount) }}</td>
                            <td>
                                <span class="badge" :class="sale.payment_status === 'paid' ? 'bg-success' : 'bg-warning'">
                                    {{ sale.payment_status }}
                                </span>
                            </td>
                            <td>
                                <router-link :to="`/pharmacy/sales/${sale.sale_id}`" class="btn btn-sm btn-outline-primary">
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
const sales = ref([]);
onMounted(async () => {
    const response = await axios.get('/api/pharmacy-sales');
    sales.value = response.data.data || response.data;
});
const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};
</script>
