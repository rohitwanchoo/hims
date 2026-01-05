<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">OPD Time Slots</h4>
                <p class="text-muted mb-0">Manage doctor schedules and appointment slots</p>
            </div>
            <button class="btn btn-primary" @click="showAddModal">
                <i class="bi bi-plus-lg me-1"></i> Add Time Slot
            </button>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small">Doctor</label>
                        <select class="form-select form-select-sm" v-model="filters.doctor_id" @change="loadSlots">
                            <option value="">All Doctors</option>
                            <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                {{ doctor.full_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Department</label>
                        <select class="form-select form-select-sm" v-model="filters.department_id" @change="loadSlots">
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Day</label>
                        <select class="form-select form-select-sm" v-model="filters.day_of_week" @change="loadSlots">
                            <option value="">All Days</option>
                            <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">
                                {{ day.label }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Schedule Grid by Day -->
        <div class="row">
            <div class="col-md-6 col-lg-4 mb-4" v-for="day in daysOfWeek" :key="day.value">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <strong>{{ day.label }}</strong>
                        <span class="badge bg-primary ms-2">{{ getSlotsForDay(day.value).length }}</span>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="getSlotsForDay(day.value).length === 0" class="text-center text-muted py-4">
                            <i class="bi bi-calendar-x"></i>
                            <p class="mb-0 small">No slots configured</p>
                        </div>
                        <div v-else class="list-group list-group-flush">
                            <div v-for="slot in getSlotsForDay(day.value)" :key="slot.slot_id"
                                 class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold">
                                            {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-person me-1"></i>
                                            {{ slot.doctor?.full_name || 'All Doctors' }}
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>{{ slot.slot_duration_minutes }} min |
                                            <i class="bi bi-people me-1"></i>{{ slot.max_patients_per_slot }} patients/slot
                                        </small>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" @click="editSlot(slot)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" @click="deleteSlot(slot)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div class="modal fade" ref="slotModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingSlot ? 'Edit' : 'Add' }} Time Slot</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveSlot">
                            <div class="mb-3">
                                <label class="form-label">Doctor</label>
                                <select class="form-select" v-model="form.doctor_id">
                                    <option value="">All Doctors (Department Wide)</option>
                                    <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                        {{ doctor.full_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" v-model="form.department_id">
                                    <option value="">Select Department</option>
                                    <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                        {{ dept.department_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Day of Week <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.day_of_week" required>
                                    <option value="">Select Day</option>
                                    <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">
                                        {{ day.label }}
                                    </option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" v-model="form.start_time" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" v-model="form.end_time" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Slot Duration (minutes)</label>
                                    <select class="form-select" v-model="form.slot_duration_minutes">
                                        <option value="10">10 minutes</option>
                                        <option value="15">15 minutes</option>
                                        <option value="20">20 minutes</option>
                                        <option value="30">30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">60 minutes</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Max Patients per Slot</label>
                                    <input type="number" class="form-control" v-model="form.max_patients_per_slot" min="1">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Max Patients per Session</label>
                                <input type="number" class="form-control" v-model="form.max_patients_per_session" min="1"
                                       placeholder="Leave empty for unlimited">
                                <small class="text-muted">Total patients allowed in this time range</small>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" v-model="form.is_active" id="slotActive">
                                <label class="form-check-label" for="slotActive">Active</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveSlot" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            {{ editingSlot ? 'Update' : 'Create' }} Slot
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const slots = ref([]);
const doctors = ref([]);
const departments = ref([]);
const loading = ref(true);
const saving = ref(false);
const editingSlot = ref(null);
const slotModal = ref(null);
let modalInstance = null;

const daysOfWeek = [
    { value: 'monday', label: 'Monday' },
    { value: 'tuesday', label: 'Tuesday' },
    { value: 'wednesday', label: 'Wednesday' },
    { value: 'thursday', label: 'Thursday' },
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' }
];

const filters = ref({
    doctor_id: '',
    department_id: '',
    day_of_week: ''
});

const form = ref({
    doctor_id: '',
    department_id: '',
    day_of_week: '',
    start_time: '09:00',
    end_time: '17:00',
    slot_duration_minutes: 15,
    max_patients_per_slot: 1,
    max_patients_per_session: null,
    is_active: true
});

onMounted(async () => {
    await Promise.all([
        loadSlots(),
        loadDoctors(),
        loadDepartments()
    ]);
    if (slotModal.value) {
        modalInstance = new Modal(slotModal.value);
    }
});

const loadSlots = async () => {
    loading.value = true;
    try {
        const params = {};
        if (filters.value.doctor_id) params.doctor_id = filters.value.doctor_id;
        if (filters.value.department_id) params.department_id = filters.value.department_id;
        if (filters.value.day_of_week) params.day_of_week = filters.value.day_of_week;

        const response = await axios.get('/api/opd-time-slots', { params });
        slots.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error loading slots:', error);
    } finally {
        loading.value = false;
    }
};

const loadDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors');
        doctors.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error loading doctors:', error);
    }
};

const loadDepartments = async () => {
    try {
        const response = await axios.get('/api/departments');
        departments.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error loading departments:', error);
    }
};

const getSlotsForDay = (day) => {
    return slots.value.filter(s => s.day_of_week === day);
};

const showAddModal = () => {
    editingSlot.value = null;
    form.value = {
        doctor_id: filters.value.doctor_id || '',
        department_id: filters.value.department_id || '',
        day_of_week: '',
        start_time: '09:00',
        end_time: '17:00',
        slot_duration_minutes: 15,
        max_patients_per_slot: 1,
        max_patients_per_session: null,
        is_active: true
    };
    modalInstance?.show();
};

const editSlot = (slot) => {
    editingSlot.value = slot;
    form.value = {
        doctor_id: slot.doctor_id || '',
        department_id: slot.department_id || '',
        day_of_week: slot.day_of_week,
        start_time: slot.start_time?.substring(0, 5) || '',
        end_time: slot.end_time?.substring(0, 5) || '',
        slot_duration_minutes: slot.slot_duration_minutes,
        max_patients_per_slot: slot.max_patients_per_slot,
        max_patients_per_session: slot.max_patients_per_session,
        is_active: slot.is_active
    };
    modalInstance?.show();
};

const saveSlot = async () => {
    saving.value = true;
    try {
        const data = {
            ...form.value,
            start_time: form.value.start_time + ':00',
            end_time: form.value.end_time + ':00',
            doctor_id: form.value.doctor_id || null,
            department_id: form.value.department_id || null
        };

        if (editingSlot.value) {
            await axios.put(`/api/opd-time-slots/${editingSlot.value.slot_id}`, data);
        } else {
            await axios.post('/api/opd-time-slots', data);
        }

        modalInstance?.hide();
        loadSlots();
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving slot');
    } finally {
        saving.value = false;
    }
};

const deleteSlot = async (slot) => {
    if (!confirm('Are you sure you want to delete this time slot?')) return;

    try {
        await axios.delete(`/api/opd-time-slots/${slot.slot_id}`);
        loadSlots();
    } catch (error) {
        alert(error.response?.data?.message || 'Error deleting slot');
    }
};

const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${hour}:${minutes} ${ampm}`;
};
</script>
