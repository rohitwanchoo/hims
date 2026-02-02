<template>
    <div class="opd-list">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold">OPD Dashboard</h2>
                <p class="text-muted mb-0 small">Manage outpatient registrations and consultations</p>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="refreshData">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh</span>
                </button>
                <router-link to="/opd/create" class="modern-btn modern-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Registration</span>
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-primary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total Visits</div>
                        <div class="stat-value-large">{{ summary.total }}</div>
                        <div class="stat-description">Today's registrations</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-warning">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Waiting</div>
                        <div class="stat-value-large">{{ summary.waiting }}</div>
                        <div class="stat-description">In queue for consultation</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-info">
                    <div class="stat-content-full">
                        <div class="stat-label-top">In Consultation</div>
                        <div class="stat-value-large">{{ summary.in_consultation }}</div>
                        <div class="stat-description">Currently with doctor</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-success">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Completed</div>
                        <div class="stat-value-large">{{ summary.completed }}</div>
                        <div class="stat-description">Finished consultations</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-danger">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Cancelled</div>
                        <div class="stat-value-large">{{ summary.cancelled }}</div>
                        <div class="stat-description">Cancelled appointments</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-secondary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Avg Wait Time</div>
                        <div class="stat-value-large">{{ avgWaitTime }}</div>
                        <div class="stat-description">Average waiting period</div>
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
                        <div class="col-md-2">
                            <label class="modern-label">Date</label>
                            <input type="date" class="modern-input" v-model="filters.date" @change="fetchVisits">
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Department</label>
                            <select class="modern-select" v-model="filters.department_id" @change="fetchVisits">
                                <option value="">All Departments</option>
                                <option v-for="d in departments" :key="d.department_id" :value="d.department_id">
                                    {{ d.department_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Doctor</label>
                            <select class="modern-select" v-model="filters.doctor_id" @change="fetchVisits">
                                <option value="">All Doctors</option>
                                <option v-for="d in filteredDoctors" :key="d.doctor_id" :value="d.doctor_id">
                                    {{ d.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Status</label>
                            <select class="modern-select" v-model="filters.status" @change="fetchVisits">
                                <option value="">All Status</option>
                                <option value="waiting">Waiting</option>
                                <option value="in_consultation">In Consultation</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="modern-label">Search</label>
                            <input type="text" class="modern-input" v-model="filters.search"
                                   placeholder="Patient name, UHID, mobile..." @keyup.enter="fetchVisits">
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
        <div class="modern-card" v-if="viewMode === 'list'">
            <div class="modern-card-header">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Patient Visits</h6>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 modern-table">
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
                                    <div v-if="visit.bill" class="small text-muted">
                                        Bill: {{ visit.bill.bill_number }}
                                    </div>
                                </div>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <!-- Primary Action Button -->
                                    <router-link :to="`/opd/${visit.opd_id}`"
                                                 class="btn btn-sm btn-primary"
                                                 title="View Details">
                                        <i class="bi bi-eye me-1"></i> View
                                    </router-link>

                                    <!-- Actions Dropdown -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border"
                                                type="button"
                                                :id="`dropdown-${visit.opd_id}`"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                                title="More Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" :aria-labelledby="`dropdown-${visit.opd_id}`">
                                            <!-- Admit to IPD -->
                                            <li>
                                                <router-link :to="`/ipd/create?patient_id=${visit.patient_id}&source=opd`"
                                                             class="dropdown-item">
                                                    <i class="bi bi-hospital text-warning me-2"></i>
                                                    <span>Admit to IPD</span>
                                                </router-link>
                                            </li>

                                            <li><hr class="dropdown-divider" v-if="visit.status === 'waiting' || visit.payment_status !== 'paid'"></li>

                                            <!-- Start Consultation -->
                                            <li v-if="visit.status === 'waiting'">
                                                <a class="dropdown-item" href="#" @click.prevent="startConsultation(visit)">
                                                    <i class="bi bi-play-circle text-success me-2"></i>
                                                    <span>Start Consultation</span>
                                                </a>
                                            </li>

                                            <!-- Payment Actions -->
                                            <li v-if="visit.payment_status !== 'paid' && visit.net_amount > 0">
                                                <a class="dropdown-item" href="#" @click.prevent="collectPayment(visit)">
                                                    <i class="bi bi-cash-coin text-info me-2"></i>
                                                    <span>Collect Payment</span>
                                                </a>
                                            </li>

                                            <li v-if="visit.payment_status === 'paid' || visit.payment_status === 'partial'">
                                                <a class="dropdown-item" href="#" @click.prevent="editPayment(visit)">
                                                    <i class="bi bi-pencil-square text-primary me-2"></i>
                                                    <span>Edit Payment</span>
                                                </a>
                                            </li>

                                            <li v-if="(visit.payment_status === 'paid' || visit.payment_status === 'partial') && visit.bill">
                                                <a class="dropdown-item" href="#" @click.prevent="viewBillHistory(visit)">
                                                    <i class="bi bi-clock-history text-secondary me-2"></i>
                                                    <span>Bill History</span>
                                                </a>
                                            </li>

                                            <li v-if="canCancel(visit)"><hr class="dropdown-divider"></li>

                                            <!-- Cancel -->
                                            <li v-if="canCancel(visit)">
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="cancelVisit(visit)">
                                                    <i class="bi bi-x-circle me-2"></i>
                                                    <span>Cancel Visit</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>
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

        <!-- Bill History Modal -->
        <div class="modal fade" id="billHistoryModal" tabindex="-1" ref="billHistoryModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Bill History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loadingHistory" class="text-center py-4">
                            <span class="spinner-border spinner-border-sm me-2"></span> Loading history...
                        </div>
                        <div v-else-if="billHistory.length === 0" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            No history available
                        </div>
                        <div v-else>
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <strong>Current Bill</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>Bill Number:</strong> {{ currentBill?.bill_number }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Subtotal:</strong> {{ formatCurrency(currentBill?.subtotal || 0) }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Discount:</strong> {{ formatCurrency(currentBill?.discount_amount || 0) }}
                                                <span v-if="currentBill?.discount_percent > 0" class="text-muted">
                                                    ({{ currentBill?.discount_percent }}%)
                                                </span>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Tax:</strong> {{ formatCurrency(currentBill?.tax_amount || 0) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>Adjustment:</strong> {{ formatCurrency(currentBill?.adjustment || 0) }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Total:</strong> {{ formatCurrency(currentBill?.total_amount || 0) }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Paid:</strong> {{ formatCurrency(currentBill?.paid_amount || 0) }}
                                            </div>
                                            <div class="mb-2">
                                                <strong>Due:</strong> {{ formatCurrency(currentBill?.due_amount || 0) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-3">Previous Changes</h6>
                            <div class="timeline">
                                <div v-for="(history, index) in billHistory" :key="history.history_id" class="card mb-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span>
                                            <strong>{{ history.action }}</strong>
                                            <span class="badge bg-secondary ms-2">{{ formatDateTime(history.created_at) }}</span>
                                        </span>
                                        <span class="text-muted small">
                                            By: {{ history.changed_by?.full_name || 'System' }}
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row small">
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <strong>Subtotal:</strong> {{ formatCurrency(history.subtotal) }}
                                                    <span v-if="showChange(history.subtotal, getNextValue(index, 'subtotal'))"
                                                          class="text-danger ms-1">
                                                        → {{ formatCurrency(getNextValue(index, 'subtotal')) }}
                                                    </span>
                                                </div>
                                                <div class="mb-1">
                                                    <strong>Discount:</strong> {{ formatCurrency(history.discount_amount) }}
                                                    <span v-if="history.discount_percent > 0" class="text-muted">
                                                        ({{ history.discount_percent }}%)
                                                    </span>
                                                    <span v-if="showChange(history.discount_amount, getNextValue(index, 'discount_amount'))"
                                                          class="text-danger ms-1">
                                                        → {{ formatCurrency(getNextValue(index, 'discount_amount')) }}
                                                    </span>
                                                </div>
                                                <div class="mb-1">
                                                    <strong>Tax:</strong> {{ formatCurrency(history.tax_amount) }}
                                                    <span v-if="showChange(history.tax_amount, getNextValue(index, 'tax_amount'))"
                                                          class="text-danger ms-1">
                                                        → {{ formatCurrency(getNextValue(index, 'tax_amount')) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <strong>Adjustment:</strong> {{ formatCurrency(history.adjustment) }}
                                                    <span v-if="showChange(history.adjustment, getNextValue(index, 'adjustment'))"
                                                          class="text-danger ms-1">
                                                        → {{ formatCurrency(getNextValue(index, 'adjustment')) }}
                                                    </span>
                                                </div>
                                                <div class="mb-1">
                                                    <strong>Total:</strong> {{ formatCurrency(history.total_amount) }}
                                                    <span v-if="showChange(history.total_amount, getNextValue(index, 'total_amount'))"
                                                          class="text-danger ms-1">
                                                        → {{ formatCurrency(getNextValue(index, 'total_amount')) }}
                                                    </span>
                                                </div>
                                                <div class="mb-1">
                                                    <strong>Payment Status:</strong>
                                                    <span class="badge" :class="getPaymentStatusBadge(history.payment_status)">
                                                        {{ history.payment_status }}
                                                    </span>
                                                    <span v-if="history.payment_status !== getNextValue(index, 'payment_status')"
                                                          class="ms-1">
                                                        → <span class="badge" :class="getPaymentStatusBadge(getNextValue(index, 'payment_status'))">
                                                            {{ getNextValue(index, 'payment_status') }}
                                                        </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
const billHistoryModal = ref(null);
const billHistory = ref([]);
const currentBill = ref(null);
const loadingHistory = ref(false);
const opdConfig = ref({
    show_continue_consultation_button: true
});
let cancelModalInstance = null;
let billHistoryModalInstance = null;
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

const showFilters = ref(false);

// Computed
const hasActiveFilters = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return filters.date !== today ||
           filters.department_id !== '' ||
           filters.doctor_id !== '' ||
           filters.status !== '' ||
           filters.search !== '';
});

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

const collectPayment = (visit) => {
    // Redirect to billing page with OPD visit details
    router.push(`/billing/create?opd_id=${visit.opd_id}&patient_id=${visit.patient_id}`);
};

const editPayment = async (visit) => {
    try {
        // Find the bill for this OPD visit
        const response = await axios.get(`/api/bills`, {
            params: {
                opd_id: visit.opd_id,
                patient_id: visit.patient_id,
                per_page: 1
            }
        });

        const bills = response.data.data || [];
        if (bills.length > 0) {
            // Redirect to edit the most recent bill
            router.push(`/billing/${bills[0].bill_id}?mode=edit`);
        } else {
            // No bill found, create new one
            router.push(`/billing/create?opd_id=${visit.opd_id}&patient_id=${visit.patient_id}`);
        }
    } catch (error) {
        console.error('Error finding bill:', error);
        alert('Error loading payment details');
    }
};

const viewBillHistory = async (visit) => {
    if (!visit.bill || !visit.bill.bill_id) {
        alert('No bill found for this visit');
        return;
    }

    loadingHistory.value = true;
    billHistory.value = [];
    currentBill.value = null;

    if (!billHistoryModalInstance) {
        billHistoryModalInstance = new Modal(billHistoryModal.value);
    }
    billHistoryModalInstance.show();

    try {
        const response = await axios.get(`/api/bills/${visit.bill.bill_id}/history`);
        currentBill.value = response.data.current;
        billHistory.value = response.data.history || [];
    } catch (error) {
        console.error('Error loading bill history:', error);
        alert('Error loading bill history');
    } finally {
        loadingHistory.value = false;
    }
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

const formatDateTime = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getPaymentStatusBadge = (status) => {
    const badges = {
        pending: 'bg-warning text-dark',
        partial: 'bg-info',
        paid: 'bg-success'
    };
    return badges[status] || 'bg-secondary';
};

const getNextValue = (currentIndex, field) => {
    // Get the next state (either from next history entry or from current bill)
    if (currentIndex === 0) {
        return currentBill.value?.[field];
    }
    return billHistory.value[currentIndex - 1]?.[field];
};

const showChange = (oldValue, newValue) => {
    return oldValue !== newValue && newValue !== undefined;
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
/* Modern Dashboard Styles */
.opd-list {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 1.5rem;
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

.bg-light-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

/* Modern Actions Dropdown */
.dropdown-menu {
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 8px;
    padding: 0.5rem 0;
    min-width: 200px;
}

.dropdown-item {
    padding: 0.6rem 1rem;
    font-size: 14px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.dropdown-item:hover {
    background-color: rgba(54, 153, 255, 0.08);
    padding-left: 1.2rem;
}

.dropdown-item i {
    font-size: 16px;
    width: 20px;
}

.dropdown-divider {
    margin: 0.5rem 0;
    opacity: 0.1;
}

.btn-light.border {
    border-color: rgba(0, 0, 0, 0.1) !important;
    transition: all 0.2s ease;
}

.btn-light.border:hover {
    background-color: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.15) !important;
}
</style>
