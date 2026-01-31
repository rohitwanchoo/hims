<template>
    <div>
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">OPD Visits</h5>
                <p class="text-muted mb-0">Today's outpatient registrations and queue</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" @click="refreshData">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <router-link to="/opd/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New Registration
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-4 fw-bold">{{ summary.total }}</div>
                                <small>Total</small>
                            </div>
                            <i class="bi bi-clipboard2-pulse fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-4 fw-bold">{{ summary.waiting }}</div>
                                <small>Waiting</small>
                            </div>
                            <i class="bi bi-hourglass-split fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card bg-info text-white h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-4 fw-bold">{{ summary.in_consultation }}</div>
                                <small>In Consult</small>
                            </div>
                            <i class="bi bi-person-video3 fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-4 fw-bold">{{ summary.completed }}</div>
                                <small>Completed</small>
                            </div>
                            <i class="bi bi-check-circle fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-4 fw-bold">{{ summary.cancelled }}</div>
                                <small>Cancelled</small>
                            </div>
                            <i class="bi bi-x-circle fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card bg-secondary text-white h-100">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fs-4 fw-bold">{{ avgWaitTime }}</div>
                                <small>Avg Wait</small>
                            </div>
                            <i class="bi bi-clock fs-3 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small">Date</label>
                        <input type="date" class="form-control form-control-sm" v-model="filters.date" @change="fetchVisits">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Department</label>
                        <select class="form-select form-select-sm" v-model="filters.department_id" @change="fetchVisits">
                            <option value="">All Departments</option>
                            <option v-for="d in departments" :key="d.department_id" :value="d.department_id">
                                {{ d.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Doctor</label>
                        <select class="form-select form-select-sm" v-model="filters.doctor_id" @change="fetchVisits">
                            <option value="">All Doctors</option>
                            <option v-for="d in filteredDoctors" :key="d.doctor_id" :value="d.doctor_id">
                                {{ d.full_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select class="form-select form-select-sm" v-model="filters.status" @change="fetchVisits">
                            <option value="">All Status</option>
                            <option value="waiting">Waiting</option>
                            <option value="in_consultation">In Consultation</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" v-model="filters.search"
                                   placeholder="Patient name, UHID, mobile..." @keyup.enter="fetchVisits">
                            <button class="btn btn-outline-secondary" @click="fetchVisits">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-sm btn-outline-secondary w-100" @click="clearFilters">
                            <i class="bi bi-x-lg"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="btn-group btn-group-sm">
                <button class="btn" :class="viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary'"
                        @click="viewMode = 'list'">
                    <i class="bi bi-list-ul me-1"></i> List View
                </button>
                <button class="btn" :class="viewMode === 'queue' ? 'btn-primary' : 'btn-outline-primary'"
                        @click="viewMode = 'queue'">
                    <i class="bi bi-people me-1"></i> Queue View
                </button>
            </div>
            <small class="text-muted">
                <i class="bi bi-clock me-1"></i> Last updated: {{ lastUpdated }}
            </small>
        </div>

        <!-- List View -->
        <div class="card" v-if="viewMode === 'list'">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Token</th>
                            <th>OPD No.</th>
                            <th>Patient</th>
                            <th>Doctor / Dept</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="8" class="text-center py-4">
                                <span class="spinner-border spinner-border-sm me-2"></span> Loading...
                            </td>
                        </tr>
                        <tr v-else-if="visits.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                No OPD visits found for the selected criteria
                            </td>
                        </tr>
                        <tr v-for="visit in visits" :key="visit.opd_id" :class="getRowClass(visit)">
                            <td>
                                <span class="badge rounded-pill" :class="getTokenBadgeClass(visit)">
                                    {{ visit.token_number }}
                                </span>
                            </td>
                            <td>
                                <strong>{{ visit.opd_number }}</strong>
                                <div class="small text-muted">{{ formatTime(visit.visit_time) }}</div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ visit.patient?.patient_name }}</div>
                                <div class="small text-muted">
                                    {{ visit.patient?.pcd }} | {{ visit.patient?.gender }} |
                                    {{ visit.patient?.age }} {{ visit.patient?.age_unit }}
                                </div>
                            </td>
                            <td>
                                <div>{{ visit.doctor?.full_name || '-' }}</div>
                                <small class="text-muted">{{ visit.department?.department_name || '-' }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="getVisitTypeBadge(visit.visit_type)">
                                    {{ formatVisitType(visit.visit_type) }}
                                </span>
                                <span v-if="visit.is_free_followup" class="badge bg-success ms-1">Free</span>
                            </td>
                            <td>
                                <span class="badge" :class="getStatusBadge(visit.status)">
                                    {{ formatStatus(visit.status) }}
                                </span>
                            </td>
                            <td>
                                <div v-if="visit.net_amount > 0">
                                    <span class="fw-semibold">{{ formatCurrency(visit.net_amount) }}</span>
                                    <div class="small" :class="visit.payment_status === 'paid' ? 'text-success' : 'text-danger'">
                                        {{ visit.payment_status }}
                                    </div>
                                </div>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <router-link :to="`/opd/${visit.opd_id}`" class="btn btn-outline-primary"
                                                 title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <button v-if="visit.status === 'waiting'" class="btn btn-outline-success"
                                            @click="startConsultation(visit)" title="Start Consultation">
                                        <i class="bi bi-play-fill"></i>
                                    </button>
                                    <!-- Continue Consultation button temporarily hidden -->
                                    <!-- <button v-if="visit.status === 'in_consultation' && opdConfig.show_continue_consultation_button" class="btn btn-success"
                                            @click="openConsultation(visit)" title="Continue Consultation">
                                        <i class="bi bi-pencil"></i>
                                    </button> -->
                                    <button v-if="canCancel(visit)" class="btn btn-outline-danger"
                                            @click="cancelVisit(visit)" title="Cancel">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Queue View (Doctor-wise) -->
        <div v-if="viewMode === 'queue'" class="row g-4">
            <div v-for="doctor in doctorQueues" :key="doctor.doctor_id" class="col-lg-4 col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">{{ doctor.doctor_name }}</h6>
                            <small class="text-muted">{{ doctor.department }}</small>
                        </div>
                        <span class="badge bg-primary">{{ doctor.queue.length }} patients</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div v-if="doctor.queue.length === 0" class="list-group-item text-center text-muted py-4">
                                <i class="bi bi-inbox"></i> No patients in queue
                            </div>
                            <div v-for="(visit, index) in doctor.queue" :key="visit.opd_id"
                                 class="list-group-item" :class="{'bg-light-success': visit.status === 'in_consultation'}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge rounded-pill me-2"
                                              :class="visit.status === 'in_consultation' ? 'bg-success' : 'bg-secondary'">
                                            {{ visit.token_number }}
                                        </span>
                                        <strong>{{ visit.patient?.patient_name }}</strong>
                                        <div class="small text-muted mt-1">
                                            {{ visit.patient?.pcd }} |
                                            {{ formatVisitType(visit.visit_type) }}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span v-if="visit.status === 'in_consultation'" class="badge bg-success">
                                            <i class="bi bi-person-video3 me-1"></i> In Consult
                                        </span>
                                        <span v-else class="small text-muted">
                                            {{ getWaitTime(visit) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div class="modal fade" id="cancelModal" tabindex="-1" ref="cancelModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel OPD Visit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel this visit?</p>
                        <p><strong>{{ selectedVisit?.opd_number }}</strong> - {{ selectedVisit?.patient?.patient_name }}</p>
                        <div class="mb-3">
                            <label class="form-label">Cancel Reason</label>
                            <select class="form-select" v-model="cancelReasonId">
                                <option value="">Select reason</option>
                                <option v-for="r in cancelReasons" :key="r.cancel_reason_id" :value="r.cancel_reason_id">
                                    {{ r.reason_name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" @click="confirmCancel" :disabled="!cancelReasonId">
                            Cancel Visit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Modal } from 'bootstrap';

const router = useRouter();

// Data
const visits = ref([]);
const doctors = ref([]);
const departments = ref([]);
const cancelReasons = ref([]);
const loading = ref(false);
const viewMode = ref('list');
const lastUpdated = ref('');
const selectedVisit = ref(null);
const cancelReasonId = ref('');
const cancelModal = ref(null);
const opdConfig = ref({
    show_continue_consultation_button: true
});
let cancelModalInstance = null;
let refreshInterval = null;

const summary = reactive({
    total: 0,
    waiting: 0,
    in_consultation: 0,
    completed: 0,
    cancelled: 0
});

const filters = reactive({
    date: new Date().toISOString().split('T')[0],
    department_id: '',
    doctor_id: '',
    status: '',
    search: ''
});

// Computed
const filteredDoctors = computed(() => {
    if (!filters.department_id) return doctors.value;
    return doctors.value.filter(d => d.department_id === parseInt(filters.department_id));
});

const doctorQueues = computed(() => {
    const queues = {};
    visits.value
        .filter(v => ['waiting', 'in_consultation'].includes(v.status))
        .forEach(visit => {
            const doctorId = visit.doctor_id || 'unassigned';
            if (!queues[doctorId]) {
                queues[doctorId] = {
                    doctor_id: doctorId,
                    doctor_name: visit.doctor?.full_name || 'Unassigned',
                    department: visit.department?.department_name || '',
                    queue: []
                };
            }
            queues[doctorId].queue.push(visit);
        });

    // Sort each queue by token number
    Object.values(queues).forEach(q => {
        q.queue.sort((a, b) => {
            if (a.status === 'in_consultation') return -1;
            if (b.status === 'in_consultation') return 1;
            return a.token_number - b.token_number;
        });
    });

    return Object.values(queues);
});

const avgWaitTime = computed(() => {
    const waitingVisits = visits.value.filter(v => v.status === 'waiting');
    if (waitingVisits.length === 0) return '0m';

    const totalWait = waitingVisits.reduce((sum, v) => {
        const created = new Date(v.created_at);
        const now = new Date();
        return sum + (now - created) / 60000;
    }, 0);

    const avg = Math.round(totalWait / waitingVisits.length);
    return avg >= 60 ? `${Math.floor(avg / 60)}h ${avg % 60}m` : `${avg}m`;
});

// Methods
const fetchData = async () => {
    try {
        const [doctorsRes, deptsRes, reasonsRes] = await Promise.all([
            axios.get('/api/doctors'),
            axios.get('/api/departments'),
            axios.get('/api/cancel-reasons?applicable_for=opd')
        ]);
        doctors.value = doctorsRes.data.data || doctorsRes.data;
        departments.value = deptsRes.data.data || deptsRes.data;
        cancelReasons.value = reasonsRes.data.data || reasonsRes.data;
    } catch (error) {
        console.error('Error loading data:', error);
    }
};

const fetchVisits = async () => {
    loading.value = true;
    try {
        const params = { ...filters };
        Object.keys(params).forEach(k => {
            if (!params[k]) delete params[k];
        });

        const response = await axios.get('/api/opd-visits', { params });
        visits.value = response.data.visits || [];
        Object.assign(summary, response.data.summary || {});
        lastUpdated.value = new Date().toLocaleTimeString();
    } catch (error) {
        console.error('Error fetching visits:', error);
    }
    loading.value = false;
};

const refreshData = () => {
    fetchVisits();
};

const clearFilters = () => {
    filters.date = new Date().toISOString().split('T')[0];
    filters.department_id = '';
    filters.doctor_id = '';
    filters.status = '';
    filters.search = '';
    fetchVisits();
};

const startConsultation = async (visit) => {
    try {
        await axios.post(`/api/opd-visits/${visit.opd_id}/start-consultation`);
        router.push(`/consultation/${visit.opd_id}?patient_id=${visit.patient_id}&form_type=opd`);
    } catch (error) {
        alert(error.response?.data?.message || 'Error starting consultation');
    }
};

const openConsultation = (visit) => {
    router.push(`/opd/${visit.opd_id}/consultation`);
};

const canCancel = (visit) => {
    return ['waiting', 'in_consultation'].includes(visit.status);
};

const cancelVisit = (visit) => {
    selectedVisit.value = visit;
    cancelReasonId.value = '';
    if (!cancelModalInstance) {
        cancelModalInstance = new Modal(cancelModal.value);
    }
    cancelModalInstance.show();
};

const confirmCancel = async () => {
    try {
        await axios.put(`/api/opd-visits/${selectedVisit.value.opd_id}`, {
            status: 'cancelled',
            cancel_reason_id: cancelReasonId.value
        });
        cancelModalInstance.hide();
        fetchVisits();
    } catch (error) {
        alert(error.response?.data?.message || 'Error cancelling visit');
    }
};

const getRowClass = (visit) => {
    if (visit.status === 'in_consultation') return 'table-info';
    if (visit.status === 'completed') return 'table-success';
    if (visit.status === 'cancelled') return 'table-secondary';
    return '';
};

const getTokenBadgeClass = (visit) => {
    if (visit.status === 'in_consultation') return 'bg-success';
    if (visit.status === 'waiting') return 'bg-warning text-dark';
    return 'bg-secondary';
};

const getStatusBadge = (status) => {
    const badges = {
        waiting: 'bg-warning text-dark',
        in_consultation: 'bg-info',
        completed: 'bg-success',
        cancelled: 'bg-danger'
    };
    return badges[status] || 'bg-secondary';
};

const getVisitTypeBadge = (type) => {
    const badges = {
        new: 'bg-primary',
        followup: 'bg-info',
        referral: 'bg-warning text-dark',
        emergency: 'bg-danger'
    };
    return badges[type] || 'bg-secondary';
};

const formatStatus = (status) => {
    const labels = {
        waiting: 'Waiting',
        in_consultation: 'In Consultation',
        completed: 'Completed',
        cancelled: 'Cancelled'
    };
    return labels[status] || status;
};

const formatVisitType = (type) => {
    const labels = {
        new: 'New',
        followup: 'Follow-up',
        referral: 'Referral',
        emergency: 'Emergency'
    };
    return labels[type] || type;
};

const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0
    }).format(amount);
};

const getWaitTime = (visit) => {
    const created = new Date(visit.created_at);
    const now = new Date();
    const mins = Math.round((now - created) / 60000);
    if (mins < 60) return `${mins}m wait`;
    return `${Math.floor(mins / 60)}h ${mins % 60}m wait`;
};

// Auto-refresh every 30 seconds
const fetchOpdConfig = async () => {
    try {
        const response = await axios.get('/api/opd-configuration');
        if (response.data.config) {
            opdConfig.value = { ...opdConfig.value, ...response.data.config };
        }
    } catch (error) {
        console.error('Error loading OPD configuration:', error);
    }
};

onMounted(() => {
    fetchOpdConfig();
    fetchData();
    fetchVisits();
    refreshInterval = setInterval(fetchVisits, 30000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<style scoped>
.bg-light-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
}
</style>
