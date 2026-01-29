<template>
    <div>
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Discharge Summaries</h4>
                <p class="text-muted mb-0 small">ABDM compliant discharge documentation</p>
            </div>
            <router-link to="/discharge-summary/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Discharge Summary
            </router-link>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Summary #, patient name..."
                            v-model="search"
                            @input="debouncedSearch"
                        >
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select class="form-select" v-model="statusFilter" @change="fetchSummaries">
                            <option value="">All Status</option>
                            <option value="draft">Draft</option>
                            <option value="completed">Completed</option>
                            <option value="signed">Signed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">From Date</label>
                        <input type="date" class="form-control" v-model="fromDate" @change="fetchSummaries">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">To Date</label>
                        <input type="date" class="form-control" v-model="toDate" @change="fetchSummaries">
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

        <!-- Summaries Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Discharge Summaries List</h6>
                    <button class="btn btn-sm btn-outline-secondary" @click="fetchSummaries" :disabled="loading">
                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loading }"></i> Refresh
                    </button>
                </div>
            </div>
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
                <table class="table table-hover mb-0 align-middle" v-else>
                    <thead class="table-light">
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

            <!-- Pagination -->
            <div class="card-footer bg-white border-top" v-if="pagination.total > 0">
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

const summaries = ref([]);
const search = ref('');
const statusFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const loading = ref(false);
const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
});

let searchTimeout = null;

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
    window.open(`/api/discharge-summaries/${summary.discharge_summary_id}/print`, '_blank');
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
