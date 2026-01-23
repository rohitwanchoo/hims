<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Consult Master</h4>
            <button class="btn btn-primary" @click="openModal()">
                <i class="bi bi-plus-lg me-2"></i>Add Schedule
            </button>
        </div>

        <div class="card">
            <div class="card-body">
                <!-- Filters -->
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <input
                            type="text"
                            class="form-control"
                            placeholder="Search doctor or department..."
                            v-model="search"
                            @input="fetchSchedules"
                        >
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterDepartment" @change="fetchSchedules">
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterTimePeriod" @change="fetchSchedules">
                            <option value="">All Time Periods</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                            <option value="evening">Evening</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filterStatus" @change="fetchSchedules">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Day/Date</th>
                                <th>Time Period</th>
                                <th>Time Range</th>
                                <th>Slot Duration</th>
                                <th>Total Slots</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="10" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm me-2"></div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="schedules.length === 0">
                                <td colspan="10" class="text-center py-4 text-muted">
                                    No schedules found
                                </td>
                            </tr>
                            <tr v-for="(schedule, index) in schedules" :key="schedule.consult_master_id">
                                <td>{{ index + 1 }}</td>
                                <td>
                                    <div class="fw-medium">{{ schedule.department?.department_name }}</div>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ schedule.doctor?.full_name }}</div>
                                </td>
                                <td>
                                    <span v-if="schedule.specific_date" class="badge bg-info">
                                        {{ formatDate(schedule.specific_date) }}
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        {{ schedule.day_name }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" :class="getTimePeriodClass(schedule.time_period)">
                                        <i :class="getTimePeriodIcon(schedule.time_period)" class="me-1"></i>
                                        {{ schedule.time_period_label }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">{{ schedule.slot_duration }} min</span>
                                </td>
                                <td>
                                    <button
                                        class="btn btn-sm btn-outline-info"
                                        @click="viewSlots(schedule)"
                                        title="View time slots"
                                    >
                                        <i class="bi bi-clock-history me-1"></i>
                                        {{ schedule.time_slots?.length || 0 }} slots
                                    </button>
                                </td>
                                <td>
                                    <span :class="schedule.is_active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ schedule.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" @click="openModal(schedule)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            class="btn btn-outline-danger"
                                            @click="deleteSchedule(schedule)"
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
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" id="scheduleModal" tabindex="-1" ref="modalRef">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-calendar-check me-2"></i>
                            {{ editingSchedule ? 'Edit Consult Schedule' : 'Add Consult Schedule' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form @submit.prevent="saveSchedule">
                        <div class="modal-body">
                            <div class="row g-3">
                                <!-- Department -->
                                <div class="col-md-6">
                                    <label class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.department_id" @change="loadDoctors" required>
                                        <option value="">Select Department</option>
                                        <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                            {{ dept.department_name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Doctor -->
                                <div class="col-md-6">
                                    <label class="form-label">Doctor <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.doctor_id" required :disabled="!form.department_id">
                                        <option value="">Select Doctor</option>
                                        <option v-for="doctor in doctorsByDept" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                            {{ doctor.full_name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Schedule Type -->
                                <div class="col-md-6">
                                    <label class="form-label">Schedule Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="scheduleType" required>
                                        <option value="weekly">Weekly (Recurring)</option>
                                        <option value="specific">Specific Date</option>
                                    </select>
                                </div>

                                <!-- Day of Week (for weekly) -->
                                <div class="col-md-6" v-if="scheduleType === 'weekly'">
                                    <label class="form-label">Day of Week</label>
                                    <select class="form-select" v-model="form.day_of_week">
                                        <option :value="null">All Days</option>
                                        <option :value="1">Monday</option>
                                        <option :value="2">Tuesday</option>
                                        <option :value="3">Wednesday</option>
                                        <option :value="4">Thursday</option>
                                        <option :value="5">Friday</option>
                                        <option :value="6">Saturday</option>
                                        <option :value="7">Sunday</option>
                                    </select>
                                </div>

                                <!-- Specific Date -->
                                <div class="col-md-6" v-if="scheduleType === 'specific'">
                                    <label class="form-label">Specific Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="form.specific_date" :min="today" :required="scheduleType === 'specific'">
                                </div>

                                <!-- Time Period -->
                                <div class="col-md-4">
                                    <label class="form-label">Time Period <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.time_period" required>
                                        <option value="morning">Morning</option>
                                        <option value="afternoon">Afternoon</option>
                                        <option value="evening">Evening</option>
                                    </select>
                                </div>

                                <!-- Start Time -->
                                <div class="col-md-4">
                                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" v-model="form.start_time" required @change="previewSlots">
                                </div>

                                <!-- End Time -->
                                <div class="col-md-4">
                                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" v-model="form.end_time" required @change="previewSlots">
                                </div>

                                <!-- Slot Duration -->
                                <div class="col-md-6">
                                    <label class="form-label">Slot Duration <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.slot_duration" required @change="previewSlots">
                                        <option :value="5">5 minutes</option>
                                        <option :value="10">10 minutes</option>
                                        <option :value="15">15 minutes</option>
                                        <option :value="20">20 minutes</option>
                                        <option :value="30">30 minutes</option>
                                    </select>
                                </div>

                                <!-- Max Patients Per Slot -->
                                <div class="col-md-6">
                                    <label class="form-label">Max Patients Per Slot</label>
                                    <input type="number" class="form-control" v-model="form.max_patients_per_slot" min="1" max="10" placeholder="1">
                                </div>

                                <!-- Slot Preview -->
                                <div class="col-12" v-if="previewedSlots.length > 0">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading">
                                            <i class="bi bi-clock-history me-2"></i>
                                            Time Slots Preview ({{ previewedSlots.length }} slots)
                                        </h6>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <span v-for="(slot, idx) in previewedSlots" :key="idx" class="badge bg-light text-dark border">
                                                {{ slot.label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" v-model="form.notes" rows="2" maxlength="500"></textarea>
                                </div>

                                <!-- Active Status -->
                                <div class="col-12">
                                    <div class="form-check">
                                        <input
                                            type="checkbox"
                                            class="form-check-input"
                                            id="is_active"
                                            v-model="form.is_active"
                                        >
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editingSchedule ? 'Update Schedule' : 'Save Schedule' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Slots View Modal -->
        <div class="modal fade" id="slotsModal" tabindex="-1" ref="slotsModalRef">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-clock me-2"></i>
                            Time Slots
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="viewingSchedule">
                            <div class="mb-3">
                                <strong>Doctor:</strong> {{ viewingSchedule.doctor?.full_name }}<br>
                                <strong>Department:</strong> {{ viewingSchedule.department?.department_name }}<br>
                                <strong>Time Period:</strong>
                                <span class="badge" :class="getTimePeriodClass(viewingSchedule.time_period)">
                                    {{ viewingSchedule.time_period_label }}
                                </span>
                            </div>
                            <hr>
                            <div class="d-flex flex-wrap gap-2">
                                <div
                                    v-for="(slot, idx) in viewingSchedule.time_slots"
                                    :key="idx"
                                    class="border rounded p-2 text-center"
                                    style="min-width: 150px;"
                                >
                                    <div class="fw-bold">{{ slot.label }}</div>
                                    <small class="text-muted">Slot {{ idx + 1 }}</small>
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
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const schedules = ref([]);
const departments = ref([]);
const doctorsByDept = ref([]);
const loading = ref(false);
const saving = ref(false);
const search = ref('');
const filterDepartment = ref('');
const filterTimePeriod = ref('');
const filterStatus = ref('');
const editingSchedule = ref(null);
const viewingSchedule = ref(null);
const modalRef = ref(null);
const slotsModalRef = ref(null);
const scheduleType = ref('weekly');
const previewedSlots = ref([]);
let modal = null;
let slotsModal = null;

const today = computed(() => {
    const date = new Date();
    return date.toISOString().split('T')[0];
});

const form = reactive({
    department_id: '',
    doctor_id: '',
    day_of_week: null,
    specific_date: null,
    time_period: 'morning',
    start_time: '09:00',
    end_time: '12:00',
    slot_duration: 15,
    max_patients_per_slot: 1,
    is_active: true,
    notes: ''
});

const fetchSchedules = async () => {
    loading.value = true;
    try {
        const params = {};
        if (search.value) params.search = search.value;
        if (filterDepartment.value) params.department_id = filterDepartment.value;
        if (filterTimePeriod.value) params.time_period = filterTimePeriod.value;
        if (filterStatus.value !== '') params.is_active = filterStatus.value;

        const response = await axios.get('/api/consult-masters', { params });
        schedules.value = response.data;
    } catch (error) {
        console.error('Error fetching schedules:', error);
    }
    loading.value = false;
};

const fetchDepartments = async () => {
    try {
        const response = await axios.get('/api/departments');
        departments.value = response.data;
    } catch (error) {
        console.error('Error fetching departments:', error);
    }
};

const loadDoctors = async () => {
    if (!form.department_id) {
        doctorsByDept.value = [];
        return;
    }
    try {
        const response = await axios.get(`/api/consult-masters/doctors-by-department/${form.department_id}`);
        doctorsByDept.value = response.data;
    } catch (error) {
        console.error('Error fetching doctors:', error);
    }
};

const previewSlots = async () => {
    if (!form.start_time || !form.end_time || !form.slot_duration) {
        previewedSlots.value = [];
        return;
    }

    try {
        const response = await axios.post('/api/consult-masters/preview-slots', {
            start_time: form.start_time,
            end_time: form.end_time,
            slot_duration: form.slot_duration
        });
        previewedSlots.value = response.data.slots || [];
    } catch (error) {
        console.error('Error previewing slots:', error);
        previewedSlots.value = [];
    }
};

const openModal = (schedule = null) => {
    editingSchedule.value = schedule;
    previewedSlots.value = [];

    if (schedule) {
        scheduleType.value = schedule.specific_date ? 'specific' : 'weekly';
        form.department_id = schedule.department_id;
        form.doctor_id = schedule.doctor_id;
        form.day_of_week = schedule.day_of_week;
        form.specific_date = schedule.specific_date;
        form.time_period = schedule.time_period;
        form.start_time = schedule.start_time;
        form.end_time = schedule.end_time;
        form.slot_duration = schedule.slot_duration;
        form.max_patients_per_slot = schedule.max_patients_per_slot;
        form.is_active = schedule.is_active;
        form.notes = schedule.notes || '';

        loadDoctors();
        previewSlots();
    } else {
        scheduleType.value = 'weekly';
        form.department_id = '';
        form.doctor_id = '';
        form.day_of_week = null;
        form.specific_date = null;
        form.time_period = 'morning';
        form.start_time = '09:00';
        form.end_time = '12:00';
        form.slot_duration = 15;
        form.max_patients_per_slot = 1;
        form.is_active = true;
        form.notes = '';
        doctorsByDept.value = [];
    }

    modal.show();
};

const saveSchedule = async () => {
    saving.value = true;
    try {
        const payload = { ...form };

        // Clear day_of_week or specific_date based on schedule type
        if (scheduleType.value === 'weekly') {
            payload.specific_date = null;
        } else {
            payload.day_of_week = null;
        }

        if (editingSchedule.value) {
            await axios.put(`/api/consult-masters/${editingSchedule.value.consult_master_id}`, payload);
        } else {
            await axios.post('/api/consult-masters', payload);
        }

        modal.hide();
        fetchSchedules();
        alert('Schedule saved successfully!');
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            alert('Validation Error:\n' + errors.join('\n'));
        } else if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error saving schedule');
        }
    }
    saving.value = false;
};

const deleteSchedule = async (schedule) => {
    if (!confirm(`Are you sure you want to delete this schedule?`)) return;

    try {
        await axios.delete(`/api/consult-masters/${schedule.consult_master_id}`);
        fetchSchedules();
        alert('Schedule deleted successfully!');
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting schedule');
    }
};

const viewSlots = (schedule) => {
    viewingSchedule.value = schedule;
    slotsModal.show();
};

const getTimePeriodClass = (period) => {
    const classes = {
        morning: 'bg-warning',
        afternoon: 'bg-info',
        evening: 'bg-primary'
    };
    return classes[period] || 'bg-secondary';
};

const getTimePeriodIcon = (period) => {
    const icons = {
        morning: 'bi bi-sunrise',
        afternoon: 'bi bi-sun',
        evening: 'bi bi-moon-stars'
    };
    return icons[period] || 'bi bi-clock';
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTime = (timeString) => {
    if (!timeString) return '-';
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};

onMounted(() => {
    modal = new Modal(modalRef.value);
    slotsModal = new Modal(slotsModalRef.value);
    fetchDepartments();
    fetchSchedules();
});
</script>

<style scoped>
.badge {
    font-size: 0.875rem;
}

.table td {
    vertical-align: middle;
}
</style>
