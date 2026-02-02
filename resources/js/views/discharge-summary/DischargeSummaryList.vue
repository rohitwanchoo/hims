<template>
    <div class="discharge-summary-list">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold">Discharge Summary Dashboard</h2>
                <p class="text-muted mb-0 small">ABDM compliant discharge documentation</p>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="fetchSummaries(pagination.current_page)">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh</span>
                </button>
                <router-link to="/discharge-summary/create" class="modern-btn modern-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Discharge Summary</span>
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-primary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total Summaries</div>
                        <div class="stat-value-large">{{ summary.total }}</div>
                        <div class="stat-description">All discharge records</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-secondary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Draft</div>
                        <div class="stat-value-large">{{ summary.draft }}</div>
                        <div class="stat-description">Pending completion</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-warning">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Completed</div>
                        <div class="stat-value-large">{{ summary.completed }}</div>
                        <div class="stat-description">Awaiting signature</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="stat-card stat-card-gradient-success">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Signed</div>
                        <div class="stat-value-large">{{ summary.signed }}</div>
                        <div class="stat-description">Finalized documents</div>
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
                                placeholder="Summary #, patient name..."
                                v-model="search"
                                @input="debouncedSearch"
                            >
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Status</label>
                            <select class="modern-select" v-model="statusFilter" @change="fetchSummaries">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="completed">Completed</option>
                                <option value="signed">Signed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">From Date</label>
                            <input type="date" class="modern-input" v-model="fromDate" @change="fetchSummaries">
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">To Date</label>
                            <input type="date" class="modern-input" v-model="toDate" @change="fetchSummaries">
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

        <!-- Summaries Table -->
        <div class="modern-card">
            <div class="modern-card-header">
                <h6 class="mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Discharge Summaries</h6>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                <!-- Loading State -->
                <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2">Loading discharge summaries...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="summaries.length === 0" class="text-center py-5">
                    <i class="bi bi-file-earmark-medical display-1 text-muted"></i>
                    <h5 class="text-muted mt-3">No Discharge Summaries Found</h5>
                    <p class="text-muted">
                        {{ search || statusFilter ? 'Try adjusting your filters' : 'Create your first discharge summary' }}
                    </p>
                    <router-link to="/discharge-summary/create" class="btn btn-primary mt-2" v-if="!search && !statusFilter">
                        <i class="bi bi-plus-lg"></i> Create Discharge Summary
                    </router-link>
                </div>

                <!-- Table Content -->
                <table class="table table-hover mb-0 modern-table" v-else>
                    <thead>
                        <tr>
                            <th>Summary #</th>
                            <th>Patient</th>
                            <th>IPD #</th>
                            <th>Admission Date</th>
                            <th>Discharge Date</th>
                            <th>Final Diagnosis</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="summary in summaries" :key="summary.discharge_summary_id">
                            <td>
                                <router-link :to="`/discharge-summary/${summary.discharge_summary_id}`" class="text-primary text-decoration-none fw-bold">
                                    {{ summary.discharge_summary_number }}
                                </router-link>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-medium">{{ summary.patient?.first_name }} {{ summary.patient?.last_name }}</div>
                                    <small class="text-muted">{{ summary.patient?.patient_code }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ summary.ipd_admission?.ipd_number }}</span>
                            </td>
                            <td>
                                <small>{{ formatDate(summary.admission_date) }}</small>
                            </td>
                            <td>
                                <small>{{ formatDate(summary.discharge_date) }}</small>
                            </td>
                            <td>
                                <small class="text-truncate d-inline-block" style="max-width: 200px;" :title="summary.final_diagnosis">
                                    {{ summary.final_diagnosis }}
                                </small>
                            </td>
                            <td>
                                <span class="badge" :class="getStatusClass(summary.status)">
                                    <i :class="getStatusIcon(summary.status)" class="me-1"></i>
                                    {{ formatStatus(summary.status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <router-link
                                        :to="`/discharge-summary/${summary.discharge_summary_id}`"
                                        class="btn btn-outline-primary"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <router-link
                                        v-if="summary.status !== 'signed'"
                                        :to="`/discharge-summary/${summary.discharge_summary_id}?mode=edit`"
                                        class="btn btn-outline-warning"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </router-link>
                                    <button
                                        class="btn btn-outline-secondary"
                                        @click="printSummary(summary)"
                                        title="Print"
                                    >
                                        <i class="bi bi-printer"></i>
                                    </button>
                                    <button
                                        v-if="summary.status !== 'signed'"
                                        class="btn btn-outline-danger"
                                        @click="deleteSummary(summary)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="modern-card mt-3" v-if="pagination.total > 0">
                <div class="modern-card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ pagination.from || 0 }} to {{ pagination.to || 0 }} of {{ pagination.total || 0 }} summaries
                    </div>
                    <nav v-if="pagination.last_page > 1">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                <button class="page-link" @click="changePage(pagination.current_page - 1)">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            </li>
                            <li
                                v-for="page in getPageNumbers()"
                                :key="page"
                                class="page-item"
                                :class="{ active: page === pagination.current_page }"
                            >
                                <button class="page-link" @click="changePage(page)" v-if="page !== '...'">{{ page }}</button>
                                <span class="page-link" v-else>...</span>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                <button class="page-link" @click="changePage(pagination.current_page + 1)">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Modern Dashboard Styles */
.discharge-summary-list {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 1.5rem;
}

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

.stat-card-gradient-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
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

/* Modern Table */
.modern-table {
    font-size: 0.875rem;
}

.modern-table thead th {
    background: #fafafa;
    color: #6c757d;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 1.25rem;
    border-bottom: 2px solid #f0f0f0;
}

.modern-table tbody td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    color: #2c3e50;
}

.modern-table tbody tr {
    transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
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
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const summaries = ref([]);
const search = ref('');
const statusFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const loading = ref(false);
const showFilters = ref(false);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
});

const summary = reactive({
    total: 0,
    draft: 0,
    completed: 0,
    signed: 0
});

let searchTimeout = null;

// Computed
const hasActiveFilters = computed(() => {
    return search.value !== '' ||
           statusFilter.value !== '' ||
           fromDate.value !== '' ||
           toDate.value !== '';
});

const fetchSummaries = async (page = 1) => {
    try {
        loading.value = true;
        const params = {
            page,
            per_page: pagination.value.per_page
        };
        if (search.value) params.search = search.value;
        if (statusFilter.value) params.status = statusFilter.value;
        if (fromDate.value) params.from_date = fromDate.value;
        if (toDate.value) params.to_date = toDate.value;

        const response = await axios.get('/api/discharge-summaries', { params });

        if (response.data.data) {
            summaries.value = response.data.data;
            pagination.value = {
                current_page: response.data.current_page || 1,
                last_page: response.data.last_page || 1,
                per_page: response.data.per_page || 15,
                total: response.data.total || 0,
                from: response.data.from || 0,
                to: response.data.to || 0
            };
        } else {
            summaries.value = response.data;
        }

        // Calculate summary statistics
        summary.total = pagination.value.total;
        summary.draft = summaries.value.filter(s => s.status === 'draft').length;
        summary.completed = summaries.value.filter(s => s.status === 'completed').length;
        summary.signed = summaries.value.filter(s => s.status === 'signed').length;
    } catch (error) {
        console.error('Error fetching discharge summaries:', error);
        alert('Error loading discharge summaries');
    } finally {
        loading.value = false;
    }
};

const debouncedSearch = () => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchSummaries(1);
    }, 500);
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchSummaries(page);
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
    fromDate.value = '';
    toDate.value = '';
    fetchSummaries(1);
};

onMounted(() => fetchSummaries(1));

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const formatStatus = (status) => {
    const statuses = {
        'draft': 'Draft',
        'completed': 'Completed',
        'signed': 'Signed'
    };
    return statuses[status] || status?.toUpperCase() || 'N/A';
};

const getStatusClass = (status) => {
    const classes = {
        'draft': 'bg-secondary',
        'completed': 'bg-warning text-dark',
        'signed': 'bg-success'
    };
    return classes[status] || 'bg-secondary';
};

const getStatusIcon = (status) => {
    const icons = {
        'draft': 'bi bi-pencil-square',
        'completed': 'bi bi-check-circle',
        'signed': 'bi bi-award'
    };
    return icons[status] || 'bi bi-question-circle';
};

const printSummary = (summary) => {
    const ipdId = summary.ipd_id;
    if (ipdId) {
        window.open(`/print/discharge-summary/${ipdId}`, '_blank');
    } else {
        alert('IPD ID not found for this discharge summary');
    }
};

const deleteSummary = async (summary) => {
    if (!confirm(`Are you sure you want to delete discharge summary ${summary.discharge_summary_number}?\n\nThis action cannot be undone.`)) {
        return;
    }

    try {
        await axios.delete(`/api/discharge-summaries/${summary.discharge_summary_id}`);
        alert('Discharge summary deleted successfully!');
        fetchSummaries(pagination.value.current_page);
    } catch (error) {
        console.error('Error deleting summary:', error);
        alert(error.response?.data?.message || 'Error deleting discharge summary');
    }
};
</script>
