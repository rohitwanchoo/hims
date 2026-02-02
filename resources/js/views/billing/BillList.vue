<template>
    <div>
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold">Billing Dashboard</h2>
                <p class="text-muted mb-0 small">Manage and track all billing transactions</p>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="fetchBills(pagination.current_page)">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh</span>
                </button>
                <router-link to="/billing/create" class="modern-btn modern-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Bill</span>
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-primary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total Bills</div>
                        <div class="stat-value-large">{{ summary.total_count || 0 }}</div>
                        <div class="stat-description">All billing records</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-info">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total Amount</div>
                        <div class="stat-value-large">{{ formatCurrency(summary.total_amount || 0) }}</div>
                        <div class="stat-description">Total billing value</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-success">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Paid Amount</div>
                        <div class="stat-value-large">{{ formatCurrency(summary.paid_amount || 0) }}</div>
                        <div class="stat-description">Total payments received</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-danger">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Pending Amount</div>
                        <div class="stat-value-large">{{ formatCurrency(summary.pending_amount || 0) }}</div>
                        <div class="stat-description">Outstanding balance</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="modern-card mb-4">
            <div class="modern-card-header clickable" @click="showFilters = !showFilters">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>Filters
                        <span v-if="hasActiveFilters" class="badge bg-primary ms-2" style="font-size: 0.7rem;">Active</span>
                    </h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0">
                        <i class="bi" :class="showFilters ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                </div>
            </div>
            <transition name="filter-collapse">
                <div v-show="showFilters" class="modern-card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="modern-label">Search</label>
                            <input
                                type="text"
                                class="modern-input"
                                placeholder="Bill number, patient name..."
                                v-model="search"
                                @input="debouncedSearch"
                            >
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Payment Status</label>
                            <select class="modern-select" v-model="statusFilter" @change="fetchBills">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Bill Type</label>
                            <select class="modern-select" v-model="billTypeFilter" @change="fetchBills">
                                <option value="">All Types</option>
                                <option value="general">General</option>
                                <option value="opd">OPD</option>
                                <option value="ipd">IPD</option>
                                <option value="pharmacy">Pharmacy</option>
                                <option value="lab">Laboratory</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">From Date</label>
                            <input type="date" class="modern-input" v-model="fromDate" @change="fetchBills">
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">To Date</label>
                            <input type="date" class="modern-input" v-model="toDate" @change="fetchBills">
                        </div>
                        <div class="col-md-1">
                            <label class="modern-label">&nbsp;</label>
                            <button class="modern-btn-reset w-100" @click="clearFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
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
                                <span class="badge bg-success text-white">
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
/* Modern Dashboard Styles */
.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Modern Buttons */
.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    color: white;
}

.modern-btn-outline {
    background: white;
    color: #6c757d;
    border: 1px solid #e0e0e0;
}

.modern-btn-outline:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

/* Modern Stat Cards with Gradients */
.stat-card {
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    min-height: 130px;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

/* Gradient Backgrounds */
.stat-card-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-card-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card-gradient-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stat-card-gradient-danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.stat-card-gradient-secondary {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #2c3e50;
}

.stat-content-full {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-label-top {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.stat-value-large {
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1;
    margin: 0.25rem 0;
}

.stat-description {
    font-size: 0.75rem;
    opacity: 0.85;
    line-height: 1.4;
}

/* Modern Cards */
.modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.modern-card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}

.modern-card-header h6 {
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.modern-card-body {
    padding: 1.5rem;
}

/* Modern Filter Styles */
.modern-card-header.clickable {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
}

.modern-card-header.clickable:hover {
    background: #f5f5f5;
}

.modern-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-select,
.modern-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
    color: #2c3e50;
}

.modern-select:focus,
.modern-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-select:hover,
.modern-input:hover {
    border-color: #b0b0b0;
}

.modern-btn-reset {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #6c757d;
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.modern-btn-reset:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

/* Filter Collapse Animation */
.filter-collapse-enter-active,
.filter-collapse-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.filter-collapse-enter-from,
.filter-collapse-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const bills = ref([]);
const search = ref('');
const statusFilter = ref('');
const billTypeFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const loading = ref(false);
const showFilters = ref(false);
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

// Computed
const hasActiveFilters = computed(() => {
    return search.value !== '' ||
           statusFilter.value !== '' ||
           billTypeFilter.value !== '' ||
           fromDate.value !== '' ||
           toDate.value !== '';
});

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
        'pending': 'bg-danger text-white',
        'partial': 'bg-warning text-dark',
        'paid': 'bg-success text-white',
        'cancelled': 'bg-secondary text-white',
        'refunded': 'bg-info text-white'
    };
    return classes[status] || 'bg-secondary text-white';
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
    window.open(`/print/bill/${bill.bill_id}`, '_blank');
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
