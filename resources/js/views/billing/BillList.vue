<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>Bills</h4>
            <router-link to="/billing/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Bill</router-link>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search bills..." v-model="search">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" v-model="statusFilter">
                            <option value="">All Status</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary" @click="fetchBills">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bill #</th>
                            <th>Patient</th>
                            <th>Bill Date</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bill in bills" :key="bill.bill_id">
                            <td>{{ bill.bill_number }}</td>
                            <td>{{ bill.patient?.first_name }} {{ bill.patient?.last_name }}</td>
                            <td>{{ formatDate(bill.bill_date) }}</td>
                            <td>{{ formatCurrency(bill.total_amount) }}</td>
                            <td>{{ formatCurrency(bill.paid_amount) }}</td>
                            <td class="fw-bold" :class="bill.due_amount > 0 ? 'text-danger' : 'text-success'">
                                {{ formatCurrency(bill.due_amount) }}
                            </td>
                            <td>
                                <span class="badge" :class="getStatusClass(bill.payment_status)">
                                    {{ bill.payment_status }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <router-link :to="`/billing/${bill.bill_id}`" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <router-link :to="`/billing/${bill.bill_id}`" class="btn btn-outline-warning" title="Edit" v-if="bill.payment_status === 'pending'">
                                        <i class="bi bi-pencil"></i>
                                    </router-link>
                                    <button class="btn btn-outline-success" @click="addPayment(bill)" v-if="bill.due_amount > 0" title="Add Payment">
                                        <i class="bi bi-cash"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" @click="printBill(bill)" title="Print">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" @click="deleteBill(bill)" v-if="bill.payment_status === 'pending'" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
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

const bills = ref([]);
const search = ref('');
const statusFilter = ref('');

const fetchBills = async () => {
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (statusFilter.value) params.status = statusFilter.value;
        const response = await axios.get('/api/bills', { params });
        bills.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching bills:', error);
        alert('Error loading bills');
    }
};

onMounted(fetchBills);

const formatDate = (date) => new Date(date).toLocaleDateString('en-IN');
const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-danger',
        'partial': 'bg-warning',
        'paid': 'bg-success',
        'cancelled': 'bg-secondary',
        'refunded': 'bg-info'
    };
    return classes[status] || 'bg-secondary';
};

const addPayment = (bill) => {
    // Redirect to payments page with bill selected
    window.location.href = `/payments?bill_id=${bill.bill_id}`;
};

const printBill = (bill) => {
    // TODO: Implement print bill
    window.open(`/api/bills/${bill.bill_id}/print`, '_blank');
};

const deleteBill = async (bill) => {
    if (!confirm(`Are you sure you want to delete bill ${bill.bill_number}? This action cannot be undone.`)) {
        return;
    }

    try {
        await axios.delete(`/api/bills/${bill.bill_id}`);
        alert('Bill deleted successfully!');
        fetchBills(); // Refresh the list
    } catch (error) {
        console.error('Error deleting bill:', error);
        alert(error.response?.data?.message || 'Error deleting bill');
    }
};
</script>
