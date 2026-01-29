<template>
    <div>
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Bills Management</h4>
                <p class="text-muted mb-0 small">Manage and track all billing transactions</p>
            </div>
            <router-link to="/billing/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Bill
            </router-link>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4" v-if="!loading && summary">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Bills</p>
                                <h5 class="mb-0">{{ summary.total_count || 0 }}</h5>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="bi bi-receipt text-primary fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Amount</p>
                                <h5 class="mb-0">{{ formatCurrency(summary.total_amount || 0) }}</h5>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-currency-rupee text-success fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Paid Amount</p>
                                <h5 class="mb-0 text-success">{{ formatCurrency(summary.paid_amount || 0) }}</h5>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="bi bi-check-circle text-success fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Pending Amount</p>
                                <h5 class="mb-0 text-danger">{{ formatCurrency(summary.pending_amount || 0) }}</h5>
                            </div>
                            <div class="bg-danger bg-opacity-10 rounded p-3">
                                <i class="bi bi-exclamation-circle text-danger fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control border-start-0"
                                placeholder="Bill number, patient name..."
                                v-model="search"
                                @input="debouncedSearch"
                            >
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Payment Status</label>
                        <select class="form-select" v-model="statusFilter" @change="fetchBills">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Bill Type</label>
                        <select class="form-select" v-model="billTypeFilter" @change="fetchBills">
                            <option value="">All Types</option>
                            <option value="general">General</option>
                            <option value="opd">OPD</option>
                            <option value="ipd">IPD</option>
                            <option value="pharmacy">Pharmacy</option>
                            <option value="lab">Laboratory</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">From Date</label>
                        <input type="date" class="form-control" v-model="fromDate" @change="fetchBills">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">To Date</label>
                        <input type="date" class="form-control" v-model="toDate" @change="fetchBills">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted mb-1">&nbsp;</label>
                        <button class="btn btn-outline-secondary w-100" @click="clearFilters" title="Clear Filters">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bills Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Bills List</h6>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" @click="exportBills" :disabled="loading">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="fetchBills" :disabled="loading">
                            <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading bills...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="bills.length === 0" class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h5 class="text-muted mt-3">No Bills Found</h5>
                    <p class="text-muted">
                        {{ search || statusFilter || billTypeFilter ? 'Try adjusting your filters' : 'Create your first bill to get started' }}
                    </p>
                    <router-link to="/billing/create" class="btn btn-primary mt-2" v-if="!search && !statusFilter && !billTypeFilter">
                        <i class="bi bi-plus-lg"></i> Create Bill
                    </router-link>
                </div>

                <!-- Table Content -->
                <table class="table table-hover mb-0 align-middle" v-else>
                    <thead class="table-light">
                        <tr>
                            <th>Bill #</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Bill Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="bill in bills" :key="bill.bill_id">
                            <td>
                                <router-link :to="`/billing/${bill.bill_id}`" class="text-primary text-decoration-none fw-bold">
                                    {{ bill.bill_number }}
                                </router-link>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-medium">{{ bill.patient?.first_name }} {{ bill.patient?.last_name }}</div>
                                    <small class="text-muted">{{ bill.patient?.patient_code }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                    {{ formatBillType(bill.bill_type) }}
                                </span>
                            </td>
                            <td>
                                <small>{{ formatDate(bill.bill_date) }}</small>
                            </td>
                            <td class="text-end fw-medium">{{ formatCurrency(bill.total_amount) }}</td>
                            <td class="text-end text-success">{{ formatCurrency(bill.paid_amount) }}</td>
                            <td class="text-end fw-bold" :class="bill.due_amount > 0 ? 'text-danger' : 'text-success'">
                                {{ formatCurrency(bill.due_amount) }}
                            </td>
                            <td>
                                <span class="badge" :class="getStatusClass(bill.payment_status)">
                                    <i :class="getStatusIcon(bill.payment_status)" class="me-1"></i>
                                    {{ formatStatus(bill.payment_status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <router-link
                                        :to="`/billing/${bill.bill_id}`"
                                        class="btn btn-outline-primary"
                                        title="View Bill"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <router-link
                                        v-if="bill.payment_status === 'pending'"
                                        :to="`/billing/${bill.bill_id}?mode=edit`"
                                        class="btn btn-outline-warning"
                                        title="Edit Bill"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </router-link>
                                    <button
                                        v-if="bill.due_amount > 0"
                                        class="btn btn-outline-success"
                                        @click="addPayment(bill)"
                                        title="Add Payment"
                                    >
                                        <i class="bi bi-cash"></i>
                                    </button>
                                    <button
                                        class="btn btn-outline-secondary"
                                        @click="printBill(bill)"
                                        title="Print Bill"
                                    >
                                        <i class="bi bi-printer"></i>
                                    </button>
                                    <button
                                        v-if="bill.payment_status === 'pending'"
                                        class="btn btn-outline-danger"
                                        @click="deleteBill(bill)"
                                        title="Delete Bill"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer bg-white border-top" v-if="pagination.total > 0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} bills
                    </div>
                    <nav v-if="pagination.last_page > 1">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                <button class="page-link" @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            </li>
                            <li
                                v-for="page in getPageNumbers()"
                                :key="page"
                                class="page-item"
                                :class="{ active: page === pagination.current_page }"
                            >
                                <button class="page-link" @click="changePage(page)">{{ page }}</button>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                <button class="page-link" @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const bills = ref([]);
const search = ref('');
const statusFilter = ref('');
const billTypeFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const loading = ref(false);
const summary = ref({
    total_count: 0,
    total_amount: 0,
    paid_amount: 0,
    pending_amount: 0
});
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
});

let searchTimeout = null;

const fetchBills = async (page = 1) => {
    try {
        loading.value = true;
        const params = {
            page,
            per_page: pagination.value.per_page
        };
        if (search.value) params.search = search.value;
        if (statusFilter.value) params.status = statusFilter.value;
        if (billTypeFilter.value) params.bill_type = billTypeFilter.value;
        if (fromDate.value) params.from_date = fromDate.value;
        if (toDate.value) params.to_date = toDate.value;

        const response = await axios.get('/api/bills', { params });

        if (response.data.data) {
            bills.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page || 1,
                last_page: response.data.last_page || 1,
                per_page: response.data.per_page || 15,
                total: response.data.total || 0,
                from: response.data.from || 0,
                to: response.data.to || 0
            };
        } else {
            bills.value = response.data;
        }

        // Fetch summary
        await fetchSummary();
    } catch (error) {
        console.error('Error fetching bills:', error);
        alert('Error loading bills');
    } finally {
        loading.value = false;
    }
};

const fetchSummary = async () => {
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (statusFilter.value) params.status = statusFilter.value;
        if (billTypeFilter.value) params.bill_type = billTypeFilter.value;
        if (fromDate.value) params.from_date = fromDate.value;
        if (toDate.value) params.to_date = toDate.value;

        const response = await axios.get('/api/bills/summary', { params });
        summary.value = response.data || {
            total_count: 0,
            total_amount: 0,
            paid_amount: 0,
            pending_amount: 0
        };
    } catch (error) {
        console.error('Error fetching summary:', error);
        // Calculate summary from current bills if API fails
        summary.value = {
            total_count: bills.value.length,
            total_amount: bills.value.reduce((sum, b) => sum + (Number(b.total_amount) || 0), 0),
            paid_amount: bills.value.reduce((sum, b) => sum + (Number(b.paid_amount) || 0), 0),
            pending_amount: bills.value.reduce((sum, b) => sum + (Number(b.due_amount) || 0), 0)
        };
    }
};

const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchBills(1);
    }, 500);
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchBills(page);
    }
};

const getPageNumbers = () => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            pages.push(i);
        }
    } else {
        if (current <= 4) {
            for (let i = 1; i <= 5; i++) pages.push(i);
            pages.push('...');
            pages.push(last);
        } else if (current >= last - 3) {
            pages.push(1);
            pages.push('...');
            for (let i = last - 4; i <= last; i++) pages.push(i);
        } else {
            pages.push(1);
            pages.push('...');
            for (let i = current - 1; i <= current + 1; i++) pages.push(i);
            pages.push('...');
            pages.push(last);
        }
    }

    return pages;
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    billTypeFilter.value = '';
    fromDate.value = '';
    toDate.value = '';
    fetchBills(1);
};

const exportBills = async () => {
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (statusFilter.value) params.status = statusFilter.value;
        if (billTypeFilter.value) params.bill_type = billTypeFilter.value;
        if (fromDate.value) params.from_date = fromDate.value;
        if (toDate.value) params.to_date = toDate.value;

        const queryString = new URLSearchParams(params).toString();
        window.open(`/api/bills/export?${queryString}`, '_blank');
    } catch (error) {
        console.error('Error exporting bills:', error);
        alert('Error exporting bills');
    }
};

onMounted(() => fetchBills(1));

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount || 0);
};

const formatBillType = (type) => {
    const types = {
        'general': 'General',
        'opd': 'OPD',
        'ipd': 'IPD',
        'pharmacy': 'Pharmacy',
        'lab': 'Laboratory'
    };
    return types[type] || type?.toUpperCase() || 'N/A';
};

const formatStatus = (status) => {
    const statuses = {
        'pending': 'Pending',
        'partial': 'Partial',
        'paid': 'Paid',
        'cancelled': 'Cancelled',
        'refunded': 'Refunded'
    };
    return statuses[status] || status?.toUpperCase() || 'N/A';
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-danger',
        'partial': 'bg-warning text-dark',
        'paid': 'bg-success',
        'cancelled': 'bg-secondary',
        'refunded': 'bg-info'
    };
    return classes[status] || 'bg-secondary';
};

const getStatusIcon = (status) => {
    const icons = {
        'pending': 'bi bi-clock',
        'partial': 'bi bi-hourglass-split',
        'paid': 'bi bi-check-circle',
        'cancelled': 'bi bi-x-circle',
        'refunded': 'bi bi-arrow-counterclockwise'
    };
    return icons[status] || 'bi bi-question-circle';
};

const addPayment = (bill) => {
    window.location.href = `/payments?bill_id=${bill.bill_id}`;
};

const printBill = (bill) => {
    window.open(`/api/bills/${bill.bill_id}/print`, '_blank');
};

const deleteBill = async (bill) => {
    if (!confirm(`Are you sure you want to delete bill ${bill.bill_number}?\n\nThis action cannot be undone.`)) {
        return;
    }

    try {
        await axios.delete(`/api/bills/${bill.bill_id}`);
        alert('Bill deleted successfully!');
        fetchBills(pagination.value.current_page);
    } catch (error) {
        console.error('Error deleting bill:', error);
        alert(error.response?.data?.message || 'Error deleting bill');
    }
};
</script>
