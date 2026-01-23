<template>
    <div>
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Appointments</h4>
                <p class="text-muted mb-0">Manage patient appointments</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" @click="showTransferModal = true">
                    <i class="bi bi-arrow-left-right me-1"></i>Transfer
                </button>
                <router-link to="/appointments/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>New Appointment
                </router-link>
            </div>
        </div>

        <!-- Summary Stats Cards -->
        <div class="stats-row mb-4">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-content">
                    <div class="stat-value">{{ summary.total }}</div>
                    <div class="stat-label">Total Appointments</div>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-content">
                    <div class="stat-value">{{ summary.scheduled }}</div>
                    <div class="stat-label">Scheduled</div>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-content">
                    <div class="stat-value">{{ summary.confirmed }}</div>
                    <div class="stat-label">Arrived</div>
                </div>
            </div>
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value">{{ summary.completed }}</div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value">{{ summary.cancelled }}</div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" v-model="filters.date" @change="fetchAppointments">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Doctor</label>
                        <select class="form-select" v-model="filters.doctor_id" @change="fetchAppointments">
                            <option value="">All Doctors</option>
                            <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                {{ doc.full_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Department</label>
                        <select class="form-select" v-model="filters.department_id" @change="fetchAppointments">
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" v-model="filters.status" @change="fetchAppointments">
                            <option value="">All Status</option>
                            <option value="scheduled">Scheduled</option>
                            <option value="confirmed">Arrived</option>
                            <option value="checked_in">Checked In</option>
                            <option value="in_consultation">In Consultation</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" v-model="filters.search" @input="debounceSearch" placeholder="Patient/Mobile">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-light w-100" @click="resetFilters">
                            <i class="bi bi-arrow-clockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Table Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Appointment List</h5>
                <span class="badge badge-soft-primary">{{ appointments.length }} appointments</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Slot</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Type</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>OPD Reg.</th>
                            <th class="text-center" width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Loading appointments...</p>
                            </td>
                        </tr>
                        <tr v-else-if="appointments.length === 0">
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">No appointments found for this date</p>
                            </td>
                        </tr>
                        <tr v-for="apt in appointments" :key="apt.appointment_id" :class="getRowClass(apt.status)">
                            <td>
                                <span class="badge badge-soft-secondary">{{ apt.slot_number || '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ formatTime(apt.appointment_time) }}</div>
                                <small class="text-muted">{{ apt.duration_minutes || 15 }} min</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm avatar-soft-primary me-2">
                                        {{ getInitials(apt.patient?.patient_name) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ apt.patient?.patient_name }}</div>
                                        <small class="text-muted">{{ apt.patient?.pcd }}</small>
                                        <span v-if="apt.priority === 'urgent'" class="badge badge-soft-warning ms-1">Urgent</span>
                                        <span v-if="apt.priority === 'emergency'" class="badge badge-soft-danger ms-1">Emergency</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ apt.doctor?.full_name }}</div>
                                <small class="text-muted">{{ apt.department?.department_name }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="apt.service_type === 'followup' ? 'badge-soft-info' : 'badge-soft-primary'">
                                    {{ apt.service_type === 'followup' ? 'Follow-up' : 'First Visit' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i v-if="apt.booking_mode === 'online'" class="bi bi-globe text-primary me-1" title="Online"></i>
                                    <i v-else-if="apt.booking_mode === 'telephonic'" class="bi bi-telephone text-success me-1" title="Telephonic"></i>
                                    <i v-else class="bi bi-person-walking text-secondary me-1" title="Walk-in"></i>
                                    <span class="small">{{ getBookingModeLabel(apt.booking_mode) }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="getStatusBadgeClass(apt.status)">
                                    {{ getStatusLabel(apt.status) }}
                                </span>
                                <div v-if="apt.arrived_at" class="small text-success mt-1">
                                    <i class="bi bi-check-circle"></i> Arrived
                                </div>
                            </td>
                            <td>
                                <router-link v-if="apt.opd_id" :to="`/opd/${apt.opd_id}`" class="badge badge-soft-success">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    OPD #{{ apt.opd_visit?.opd_number || apt.opd_id }}
                                </router-link>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <div class="table-actions justify-content-center">
                                    <router-link :to="`/appointments/${apt.appointment_id}`" class="btn btn-sm btn-soft-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <button v-if="apt.status === 'scheduled'" class="btn btn-sm btn-soft-success" @click="confirmAppointment(apt)" title="Mark Arrived">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status) && !apt.opd_id" class="btn btn-sm btn-soft-info" @click="convertToOpd(apt)" title="Convert to OPD">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status)" class="btn btn-sm btn-soft-danger" @click="openCancelModal(apt)" title="Cancel">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div class="modal fade" ref="cancelModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>Cancel Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning d-flex align-items-center" v-if="selectedAppointment">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                Cancel appointment for <strong>{{ selectedAppointment.patient?.patient_name }}</strong>
                                with <strong>Dr. {{ selectedAppointment.doctor?.full_name }}</strong>?
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cancel Reason <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="cancelForm.cancel_reason_id">
                                <option value="">Select reason...</option>
                                <option v-for="reason in cancelReasons" :key="reason.cancel_reason_id" :value="reason.cancel_reason_id">
                                    {{ reason.reason_name }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional Remarks</label>
                            <textarea class="form-control" v-model="cancelForm.cancel_remarks" rows="2" placeholder="Enter any additional remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" @click="cancelAppointment" :disabled="cancelling">
                            <span v-if="cancelling" class="spinner-border spinner-border-sm me-1"></span>
                            Cancel Appointment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Modal -->
        <div class="modal fade" ref="transferModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Transfer Appointments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">Transfer all appointments from one doctor to another for a specific date.</p>
                        <div class="mb-3">
                            <label class="form-label">From Doctor <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="transferForm.from_doctor_id" required>
                                <option value="">Select doctor...</option>
                                <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                    {{ doc.full_name }} - {{ doc.specialization }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">To Doctor <span class="text-danger">*</span></label>
                            <select class="form-select" v-model="transferForm.to_doctor_id" required>
                                <option value="">Select doctor...</option>
                                <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id" :disabled="doc.doctor_id === transferForm.from_doctor_id">
                                    {{ doc.full_name }} - {{ doc.specialization }}
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" v-model="transferForm.date" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <input type="text" class="form-control" v-model="transferForm.reason" placeholder="e.g., Doctor on leave">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" @click="transferAppointments" :disabled="transferring || !transferForm.from_doctor_id || !transferForm.to_doctor_id">
                            <span v-if="transferring" class="spinner-border spinner-border-sm me-1"></span>
                            Transfer Appointments
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const appointments = ref([]);
const doctors = ref([]);
const departments = ref([]);
const cancelReasons = ref([]);
const loading = ref(false);
const cancelling = ref(false);
const transferring = ref(false);
const selectedAppointment = ref(null);
const showTransferModal = ref(false);

const cancelModal = ref(null);
const transferModal = ref(null);
let cancelModalInstance = null;
let transferModalInstance = null;

const summary = ref({
    total: 0,
    scheduled: 0,
    confirmed: 0,
    checked_in: 0,
    in_consultation: 0,
    completed: 0,
    cancelled: 0,
    no_show: 0
});

const filters = reactive({
    date: new Date().toISOString().split('T')[0],
    doctor_id: '',
    department_id: '',
    status: '',
    booking_mode: '',
    search: ''
});

const cancelForm = reactive({
    cancel_reason_id: '',
    cancel_remarks: ''
});

const transferForm = reactive({
    from_doctor_id: '',
    to_doctor_id: '',
    date: new Date().toISOString().split('T')[0],
    reason: ''
});

let searchTimeout = null;

const fetchAppointments = async () => {
    loading.value = true;
    try {
        const params = {};
        if (filters.date) params.date = filters.date;
        if (filters.doctor_id) params.doctor_id = filters.doctor_id;
        if (filters.department_id) params.department_id = filters.department_id;
        if (filters.status) params.status = filters.status;
        if (filters.search) params.search = filters.search;

        const response = await axios.get('/api/appointments', { params });
        appointments.value = response.data.appointments || response.data.data || response.data;
        summary.value = response.data.summary || summary.value;
    } catch (error) {
        console.error('Error fetching appointments:', error);
    } finally {
        loading.value = false;
    }
};

const fetchDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors');
        doctors.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching doctors:', error);
    }
};

const fetchDepartments = async () => {
    try {
        const response = await axios.get('/api/departments');
        departments.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching departments:', error);
    }
};

const fetchCancelReasons = async () => {
    try {
        const response = await axios.get('/api/cancel-reasons');
        cancelReasons.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching cancel reasons:', error);
    }
};

const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchAppointments, 500);
};

const resetFilters = () => {
    filters.date = new Date().toISOString().split('T')[0];
    filters.doctor_id = '';
    filters.department_id = '';
    filters.status = '';
    filters.search = '';
    fetchAppointments();
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatTime = (time) => {
    if (!time) return '-';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${hour}:${minutes} ${ampm}`;
};

const getRowClass = (status) => {
    if (status === 'confirmed') return 'table-row-success';
    if (status === 'cancelled') return 'table-row-danger';
    return '';
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'scheduled': 'badge-soft-warning',
        'confirmed': 'badge-soft-info',
        'checked_in': 'badge-soft-primary',
        'in_consultation': 'badge-soft-secondary',
        'completed': 'badge-soft-success',
        'cancelled': 'badge-soft-danger',
        'no_show': 'badge-soft-secondary',
        'transferred': 'badge-soft-secondary'
    };
    return classes[status] || 'badge-soft-secondary';
};

const getStatusLabel = (status) => {
    const labels = {
        'scheduled': 'Scheduled',
        'confirmed': 'Arrived',
        'checked_in': 'Checked In',
        'in_consultation': 'In Consultation',
        'completed': 'Completed',
        'cancelled': 'Cancelled',
        'no_show': 'No Show',
        'transferred': 'Transferred'
    };
    return labels[status] || status;
};

const getBookingModeLabel = (mode) => {
    const labels = {
        'walk_in': 'Walk-in',
        'telephonic': 'Phone',
        'online': 'Online'
    };
    return labels[mode] || mode;
};

const confirmAppointment = async (apt) => {
    try {
        await axios.post(`/api/appointments/${apt.appointment_id}/confirm`);
        await fetchAppointments();
    } catch (error) {
        alert(error.response?.data?.message || 'Error confirming appointment');
    }
};

const openCancelModal = (apt) => {
    selectedAppointment.value = apt;
    cancelForm.cancel_reason_id = '';
    cancelForm.cancel_remarks = '';
    cancelModalInstance?.show();
};

const cancelAppointment = async () => {
    if (!selectedAppointment.value) return;
    cancelling.value = true;
    try {
        await axios.post(`/api/appointments/${selectedAppointment.value.appointment_id}/cancel`, cancelForm);
        cancelModalInstance?.hide();
        await fetchAppointments();
    } catch (error) {
        alert(error.response?.data?.message || 'Error cancelling appointment');
    } finally {
        cancelling.value = false;
    }
};

const convertToOpd = async (apt) => {
    if (!confirm('Convert this appointment to OPD registration?')) return;
    try {
        const response = await axios.post(`/api/appointments/${apt.appointment_id}/convert-to-opd`);
        alert(response.data.message);
        await fetchAppointments();
    } catch (error) {
        alert(error.response?.data?.message || 'Error converting to OPD');
    }
};

const transferAppointments = async () => {
    transferring.value = true;
    try {
        const response = await axios.post('/api/appointments-transfer', transferForm);
        alert(response.data.message);
        transferModalInstance?.hide();
        await fetchAppointments();
    } catch (error) {
        alert(error.response?.data?.message || 'Error transferring appointments');
    } finally {
        transferring.value = false;
    }
};

watch(showTransferModal, (val) => {
    if (val) {
        transferForm.date = filters.date;
        transferModalInstance?.show();
    }
});

onMounted(() => {
    fetchAppointments();
    fetchDoctors();
    fetchDepartments();
    fetchCancelReasons();

    if (cancelModal.value) {
        cancelModalInstance = new Modal(cancelModal.value);
    }
    if (transferModal.value) {
        transferModalInstance = new Modal(transferModal.value);
        transferModal.value.addEventListener('hidden.bs.modal', () => {
            showTransferModal.value = false;
        });
    }
});
</script>

<style scoped>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.table-row-success {
    background-color: var(--success-light) !important;
}

.table-row-danger {
    background-color: var(--danger-light) !important;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
}

@media (max-width: 1199.98px) {
    .stats-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 767.98px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
