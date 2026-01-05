<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">Appointments</h5>
                <p class="text-muted mb-0">Manage patient appointments</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" @click="showTransferModal = true">
                    <i class="bi bi-arrow-left-right me-1"></i> Transfer
                </button>
                <router-link to="/appointments/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New Appointment
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card text-center">
                    <div class="card-body py-3">
                        <h4 class="mb-0">{{ summary.total }}</h4>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center bg-warning bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-warning">{{ summary.scheduled }}</h4>
                        <small class="text-muted">Scheduled</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center bg-info bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-info">{{ summary.confirmed }}</h4>
                        <small class="text-muted">Arrived</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center bg-primary bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-primary">{{ summary.checked_in }}</h4>
                        <small class="text-muted">Checked In</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center bg-success bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-success">{{ summary.completed }}</h4>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card text-center bg-danger bg-opacity-10">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-danger">{{ summary.cancelled }}</h4>
                        <small class="text-muted">Cancelled</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small">Date</label>
                        <input type="date" class="form-control" v-model="filters.date" @change="fetchAppointments">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Doctor</label>
                        <select class="form-select" v-model="filters.doctor_id" @change="fetchAppointments">
                            <option value="">All Doctors</option>
                            <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                {{ doc.full_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Department</label>
                        <select class="form-select" v-model="filters.department_id" @change="fetchAppointments">
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
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
                        <label class="form-label small">Booking Mode</label>
                        <select class="form-select" v-model="filters.booking_mode" @change="fetchAppointments">
                            <option value="">All Modes</option>
                            <option value="walk_in">Walk-in</option>
                            <option value="telephonic">Telephonic</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Search</label>
                        <input type="text" class="form-control" v-model="filters.search" @input="debounceSearch" placeholder="Patient name/mobile">
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Slot</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Type</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>OPD Reg.</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="9" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm me-2"></div> Loading...
                            </td>
                        </tr>
                        <tr v-else-if="appointments.length === 0">
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x" style="font-size: 2rem;"></i>
                                <p class="mb-0 mt-2">No appointments found for this date</p>
                            </td>
                        </tr>
                        <tr v-for="apt in appointments" :key="apt.appointment_id" :class="{'table-success': apt.status === 'confirmed', 'table-danger': apt.status === 'cancelled'}">
                            <td>
                                <span class="badge bg-secondary">{{ apt.slot_number || '-' }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ formatTime(apt.appointment_time) }}</div>
                                <small class="text-muted">{{ apt.duration_minutes || 15 }} min</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ apt.patient?.patient_name }}</div>
                                <small class="text-muted">{{ apt.patient?.pcd }}</small>
                                <span v-if="apt.priority === 'urgent'" class="badge bg-warning ms-1">Urgent</span>
                                <span v-if="apt.priority === 'emergency'" class="badge bg-danger ms-1">Emergency</span>
                            </td>
                            <td>
                                <div>{{ apt.doctor?.full_name }}</div>
                                <small class="text-muted">{{ apt.department?.department_name }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="apt.service_type === 'followup' ? 'bg-info' : 'bg-primary'">
                                    {{ apt.service_type === 'followup' ? 'Follow-up' : 'First Visit' }}
                                </span>
                            </td>
                            <td>
                                <i v-if="apt.booking_mode === 'online'" class="bi bi-globe text-primary" title="Online"></i>
                                <i v-else-if="apt.booking_mode === 'telephonic'" class="bi bi-telephone text-success" title="Telephonic"></i>
                                <i v-else class="bi bi-person-walking text-secondary" title="Walk-in"></i>
                                <span class="ms-1 small">{{ getBookingModeLabel(apt.booking_mode) }}</span>
                            </td>
                            <td>
                                <span class="badge" :class="getStatusClass(apt.status)">
                                    {{ getStatusLabel(apt.status) }}
                                </span>
                                <div v-if="apt.arrived_at" class="small text-success mt-1">
                                    <i class="bi bi-check-circle"></i> Arrived
                                </div>
                            </td>
                            <td>
                                <router-link v-if="apt.opd_id" :to="`/opd/${apt.opd_id}`" class="text-success">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    OPD #{{ apt.opd_visit?.opd_number || apt.opd_id }}
                                </router-link>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <router-link :to="`/appointments/${apt.appointment_id}`" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <button v-if="apt.status === 'scheduled'" class="btn btn-outline-success" @click="confirmAppointment(apt)" title="Mark Arrived">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status) && !apt.opd_id" class="btn btn-outline-info" @click="convertToOpd(apt)" title="Convert to OPD">
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status)" class="btn btn-outline-danger" @click="openCancelModal(apt)" title="Cancel">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <button v-if="['scheduled', 'confirmed'].includes(apt.status)" class="btn btn-outline-secondary" @click="markNoShow(apt)" title="No Show">
                                        <i class="bi bi-person-x"></i>
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
                        <h5 class="modal-title">Cancel Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p v-if="selectedAppointment">
                            Cancel appointment for <strong>{{ selectedAppointment.patient?.patient_name }}</strong>
                            with <strong>Dr. {{ selectedAppointment.doctor?.full_name }}</strong>?
                        </p>
                        <div class="mb-3">
                            <label class="form-label">Cancel Reason</label>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
                        <h5 class="modal-title">Transfer Appointments</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">Transfer all appointments from one doctor to another for a specific date.</p>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

const formatTime = (time) => {
    if (!time) return '-';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${hour}:${minutes} ${ampm}`;
};

const getStatusClass = (status) => {
    const classes = {
        'scheduled': 'bg-warning',
        'confirmed': 'bg-info',
        'checked_in': 'bg-primary',
        'in_consultation': 'bg-purple',
        'completed': 'bg-success',
        'cancelled': 'bg-danger',
        'no_show': 'bg-secondary',
        'transferred': 'bg-dark'
    };
    return classes[status] || 'bg-secondary';
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

const markNoShow = async (apt) => {
    if (!confirm('Mark this appointment as No Show?')) return;
    try {
        await axios.post(`/api/appointments/${apt.appointment_id}/no-show`);
        await fetchAppointments();
    } catch (error) {
        alert(error.response?.data?.message || 'Error marking as no show');
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
.bg-purple {
    background-color: #6f42c1 !important;
}
</style>
