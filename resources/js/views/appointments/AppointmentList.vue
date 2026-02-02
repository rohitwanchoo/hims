<template>
    <div class="appointment-list">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold">Appointments Dashboard</h2>
                <p class="text-muted mb-0 small">Manage and track patient appointments</p>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="fetchAppointments">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh</span>
                </button>
                <button class="modern-btn modern-btn-outline" @click="showTransferModal = true">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>Transfer</span>
                </button>
                <router-link to="/appointments/create" class="modern-btn modern-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Appointment</span>
                </router-link>
            </div>
        </div>

        <!-- Summary Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card-modern stat-card-gradient-primary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total</div>
                        <div class="stat-value-large">{{ summary.total }}</div>
                        <div class="stat-description">All appointments</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card-modern stat-card-gradient-warning">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Scheduled</div>
                        <div class="stat-value-large">{{ summary.scheduled }}</div>
                        <div class="stat-description">Upcoming slots</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card-modern stat-card-gradient-info">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Arrived</div>
                        <div class="stat-value-large">{{ summary.confirmed }}</div>
                        <div class="stat-description">Patients arrived</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card-modern stat-card-gradient-success">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Completed</div>
                        <div class="stat-value-large">{{ summary.completed }}</div>
                        <div class="stat-description">Finished visits</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card-modern stat-card-gradient-danger">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Cancelled</div>
                        <div class="stat-value-large">{{ summary.cancelled }}</div>
                        <div class="stat-description">Cancelled slots</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-md-6">
                <div class="stat-card-modern stat-card-gradient-secondary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">No Show</div>
                        <div class="stat-value-large">{{ summary.no_show || 0 }}</div>
                        <div class="stat-description">Missed appointments</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
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
                            <input type="date" class="modern-input" v-model="filters.date" @change="fetchAppointments">
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Doctor</label>
                            <select class="modern-select" v-model="filters.doctor_id" @change="fetchAppointments">
                                <option value="">All Doctors</option>
                                <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                    {{ doc.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Department</label>
                            <select class="modern-select" v-model="filters.department_id" @change="fetchAppointments">
                                <option value="">All Departments</option>
                                <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                    {{ dept.department_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Status</label>
                            <select class="modern-select" v-model="filters.status" @change="fetchAppointments">
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
                        <div class="col-md-3">
                            <label class="modern-label">Search</label>
                            <input type="text" class="modern-input" v-model="filters.search" @input="debounceSearch" placeholder="Patient/Mobile">
                        </div>
                        <div class="col-md-1">
                            <label class="modern-label">&nbsp;</label>
                            <button class="modern-btn-reset w-100" @click="resetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Appointments Table Card -->
        <div class="modern-card">
            <div class="modern-card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Appointment List</h6>
                    <span class="badge bg-primary">{{ appointments.length }} appointments</span>
                </div>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 modern-table">
                    <thead>
                        <tr>
                            <th>Slot</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Mobile</th>
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
                            <td colspan="11" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Loading appointments...</p>
                            </td>
                        </tr>
                        <tr v-else-if="appointments.length === 0">
                            <td colspan="11" class="text-center py-5">
                                <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">No appointments found for this date</p>
                            </td>
                        </tr>
                        <tr v-for="apt in appointments" :key="apt.appointment_id" :class="getRowClass(apt.status, apt)">
                            <td>
                                <span class="badge badge-soft-secondary">{{ apt.slot_number || '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ formatDate(apt.appointment_date) }}</div>
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
                                <span class="text-nowrap">{{ apt.patient?.mobile || '-' }}</span>
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
                                    <button v-if="apt.status === 'scheduled' && !isAppointmentInPast(apt)" class="btn btn-sm btn-soft-success" @click="confirmAppointment(apt)" title="Mark Arrived">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status) && !apt.opd_id && !isAppointmentInPast(apt)" class="btn btn-sm btn-soft-info" @click="convertToOpd(apt)" title="Convert to OPD">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status) && !isAppointmentInPast(apt)" class="btn btn-sm btn-soft-danger" @click="openCancelModal(apt)" title="Cancel">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <span v-if="isAppointmentInPast(apt) && ['scheduled', 'confirmed'].includes(apt.status)" class="badge badge-soft-secondary" title="Time has passed">
                                        <i class="bi bi-clock-history"></i> Past
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
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
import { ref, reactive, computed, onMounted, watch } from 'vue';
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
const showFilters = ref(false);

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

// Computed
const hasActiveFilters = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return filters.date !== today ||
           filters.doctor_id !== '' ||
           filters.department_id !== '' ||
           filters.status !== '' ||
           filters.search !== '';
});

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

const formatDate = (dateStr) => {
    if (!dateStr) return '-';

    // Extract just the date part if it's a datetime string
    const datePart = typeof dateStr === 'string' ? dateStr.split('T')[0] : dateStr;

    // Parse the date parts manually to avoid timezone issues
    const [year, month, day] = datePart.split('-').map(Number);

    if (!year || !month || !day) return '-';

    const date = new Date(year, month - 1, day);

    const options = {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    };
    return date.toLocaleDateString('en-US', options);
};

const getRowClass = (status, appointment) => {
    if (isAppointmentInPast(appointment) && ['scheduled', 'confirmed'].includes(status)) {
        return 'table-row-past';
    }
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

const isAppointmentInPast = (appointment) => {
    // Check if appointment date is in the past
    const appointmentDate = appointment.appointment_date;
    const today = new Date().toISOString().split('T')[0];

    if (appointmentDate < today) {
        return true; // Past date
    }

    if (appointmentDate > today) {
        return false; // Future date
    }

    // Same date - check time
    if (!appointment.appointment_time) {
        return false; // No time set, allow actions
    }

    const now = new Date();
    const currentHours = now.getHours();
    const currentMinutes = now.getMinutes();
    const currentTimeInMinutes = currentHours * 60 + currentMinutes;

    const [aptHours, aptMinutes] = appointment.appointment_time.split(':').map(Number);
    const aptTimeInMinutes = aptHours * 60 + aptMinutes;

    return aptTimeInMinutes < currentTimeInMinutes;
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
/* Modern Dashboard Styles */
.appointment-list {
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
.stat-card-modern {
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

.stat-card-modern:hover {
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

/* Legacy Styles */
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.table-row-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.table-row-danger {
    background-color: rgba(220, 53, 69, 0.1) !important;
}

.table-row-past {
    background-color: #f8f9fa !important;
    opacity: 0.7;
    color: #6c757d;
}
</style>
