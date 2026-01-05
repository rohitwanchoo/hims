<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">{{ isEdit ? 'Edit Appointment' : 'New Appointment' }}</h5>
                <p class="text-muted mb-0">{{ isEdit ? 'Update appointment details' : 'Schedule a new appointment' }}</p>
            </div>
            <router-link to="/appointments" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </router-link>
        </div>

        <!-- Loading State -->
        <div v-if="loadingData" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading form data...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="loadError" class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ loadError }}
            <button class="btn btn-sm btn-outline-danger ms-3" @click="fetchData">
                <i class="bi bi-arrow-clockwise me-1"></i> Retry
            </button>
        </div>

        <div class="row" v-else>
            <div class="col-lg-8">
                <form @submit.prevent="saveAppointment">
                    <!-- Patient Selection -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Patient Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.patient_id" required @change="onPatientChange">
                                        <option value="">Select Patient ({{ patients.length }} available)</option>
                                        <option v-for="p in patients" :key="p.patient_id" :value="p.patient_id">
                                            {{ p.pcd }} - {{ p.patient_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Patient Class</label>
                                    <select class="form-select" v-model="form.class_id">
                                        <option value="">Select Class</option>
                                        <option v-for="c in patientClasses" :key="c.class_id" :value="c.class_id">
                                            {{ c.class_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-12" v-if="selectedPatient">
                                    <div class="alert alert-info mb-0 py-2">
                                        <small>
                                            <strong>Phone:</strong> {{ selectedPatient.mobile_number || 'N/A' }} |
                                            <strong>Gender:</strong> {{ selectedPatient.gender || 'N/A' }} |
                                            <strong>Age:</strong> {{ selectedPatient.age || 'N/A' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Booking Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Booking Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.booking_mode" required>
                                        <option value="walk_in">Walk-in</option>
                                        <option value="telephonic">Telephonic</option>
                                        <option value="online">Online</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Service Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.service_type" required>
                                        <option value="first">First Visit / Consultation</option>
                                        <option value="followup">Follow-up</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Priority</label>
                                    <select class="form-select" v-model="form.priority">
                                        <option value="normal">Normal</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Appointment Type</label>
                                    <select class="form-select" v-model="form.appointment_type">
                                        <option value="consultation">Consultation</option>
                                        <option value="follow_up">Follow Up</option>
                                        <option value="emergency">Emergency</option>
                                        <option value="routine_checkup">Routine Checkup</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Booking Source</label>
                                    <select class="form-select" v-model="form.booking_source">
                                        <option value="reception">Reception</option>
                                        <option value="portal">Patient Portal</option>
                                        <option value="mobile_app">Mobile App</option>
                                        <option value="call_center">Call Center</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Duration (minutes)</label>
                                    <select class="form-select" v-model="form.duration_minutes">
                                        <option value="10">10 minutes</option>
                                        <option value="15">15 minutes</option>
                                        <option value="20">20 minutes</option>
                                        <option value="30">30 minutes</option>
                                        <option value="45">45 minutes</option>
                                        <option value="60">60 minutes</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" v-model="form.is_online" id="isOnline">
                                        <label class="form-check-label" for="isOnline">
                                            Online/Telemedicine Appointment
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doctor Selection -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Doctor & Schedule</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" v-model="form.department_id" @change="onDepartmentChange">
                                        <option value="">All Departments</option>
                                        <option v-for="d in departments" :key="d.department_id" :value="d.department_id">
                                            {{ d.department_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Specialty</label>
                                    <select class="form-select" v-model="form.skill_set_id" @change="onSkillSetChange">
                                        <option value="">All Specialties</option>
                                        <option v-for="s in skillSets" :key="s.skill_set_id" :value="s.skill_set_id">
                                            {{ s.skill_set_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Doctor Group/Unit</label>
                                    <select class="form-select" v-model="form.group_id">
                                        <option value="">No Group</option>
                                        <option v-for="g in doctorGroups" :key="g.group_id" :value="g.group_id">
                                            {{ g.group_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Doctor <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.doctor_id" required @change="onDoctorChange">
                                        <option value="">Select Doctor</option>
                                        <option v-for="d in filteredDoctors" :key="d.doctor_id" :value="d.doctor_id">
                                            {{ d.full_name }} - {{ d.specialization }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reference Doctor</label>
                                    <select class="form-select" v-model="form.reference_doctor_id">
                                        <option value="">No Reference</option>
                                        <option v-for="r in referenceDoctors" :key="r.reference_doctor_id" :value="r.reference_doctor_id">
                                            {{ r.doctor_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" v-model="form.appointment_date"
                                           required :min="minDate" @change="fetchTimeSlots">
                                </div>
                            </div>

                            <!-- Time Slot Selection -->
                            <div class="mt-4" v-if="form.doctor_id && form.appointment_date">
                                <label class="form-label">Select Time Slot <span class="text-danger">*</span></label>

                                <div v-if="loadingSlots" class="text-center py-3">
                                    <span class="spinner-border spinner-border-sm me-2"></span> Loading available slots...
                                </div>

                                <div v-else-if="timeSlots.length === 0" class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No time slots available for this doctor on the selected date.
                                    <span v-if="noSlotsMessage">{{ noSlotsMessage }}</span>
                                </div>

                                <div v-else class="time-slots-grid">
                                    <div v-for="slot in timeSlots" :key="slot.slot_number"
                                         class="time-slot"
                                         :class="{
                                             'selected': selectedSlot === slot.slot_number,
                                             'unavailable': !slot.available
                                         }"
                                         @click="selectSlot(slot)">
                                        <div class="slot-time">{{ formatTime(slot.start_time) }}</div>
                                        <div class="slot-info">
                                            <small v-if="slot.available">
                                                {{ slot.max_patients - slot.booked }} available
                                            </small>
                                            <small v-else class="text-danger">Full</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2 text-muted small" v-if="timeSlots.length > 0">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Total {{ availableSlotsCount }} slots available out of {{ timeSlots.length }}
                                </div>
                            </div>

                            <!-- Manual Time Entry (fallback) -->
                            <div class="row g-3 mt-2" v-if="!form.doctor_id || timeSlots.length === 0">
                                <div class="col-md-6">
                                    <label class="form-label">Appointment Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" v-model="form.appointment_time" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reason & Notes -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-chat-text me-2"></i>Additional Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Reason for Visit</label>
                                    <textarea class="form-control" v-model="form.reason" rows="2"
                                              placeholder="Enter reason for appointment"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" v-model="form.notes" rows="2"
                                              placeholder="Additional notes for the doctor"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" :disabled="saving">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg me-1"></i>
                            {{ isEdit ? 'Update' : 'Schedule' }} Appointment
                        </button>
                        <router-link to="/appointments" class="btn btn-light">Cancel</router-link>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Doctor Schedule Card -->
                <div class="card mb-3" v-if="doctorSchedule && form.doctor_id">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Doctor's Weekly Schedule</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr v-for="(slots, day) in doctorSchedule" :key="day">
                                    <td class="fw-semibold text-capitalize" style="width: 100px;">{{ day }}</td>
                                    <td>
                                        <span v-if="slots.length > 0">
                                            <span v-for="(s, i) in slots" :key="i" class="badge bg-light text-dark me-1">
                                                {{ formatTime(s.start_time) }} - {{ formatTime(s.end_time) }}
                                            </span>
                                        </span>
                                        <span v-else class="text-muted">Off</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tips Card -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Booking Tips</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Select patient from registry
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Choose booking mode (walk-in/phone/online)
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Filter doctors by department/specialty
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Pick an available time slot
                            </li>
                            <li>
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Add reason for better preparation
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

// Data
const patients = ref([]);
const doctors = ref([]);
const departments = ref([]);
const skillSets = ref([]);
const doctorGroups = ref([]);
const patientClasses = ref([]);
const referenceDoctors = ref([]);
const timeSlots = ref([]);
const doctorSchedule = ref(null);
const loadingSlots = ref(false);
const noSlotsMessage = ref('');
const selectedSlot = ref(null);
const saving = ref(false);
const loadingData = ref(true);
const loadError = ref('');

// Form data
const form = reactive({
    patient_id: '',
    doctor_id: '',
    department_id: '',
    skill_set_id: '',
    group_id: '',
    class_id: '',
    reference_doctor_id: '',
    appointment_date: new Date().toISOString().split('T')[0],
    appointment_time: '',
    appointment_type: 'consultation',
    booking_mode: 'walk_in',
    service_type: 'first',
    is_online: false,
    priority: 'normal',
    duration_minutes: 15,
    booking_source: 'reception',
    slot_number: null,
    slot_start_time: null,
    slot_end_time: null,
    reason: '',
    notes: '',
    status: 'scheduled'
});

// Computed
const minDate = computed(() => new Date().toISOString().split('T')[0]);

const selectedPatient = computed(() => {
    return patients.value.find(p => p.patient_id === form.patient_id);
});

const filteredDoctors = computed(() => {
    let result = doctors.value;
    if (form.department_id) {
        result = result.filter(d => d.department_id === form.department_id);
    }
    if (form.skill_set_id) {
        result = result.filter(d => d.skill_set_id === form.skill_set_id);
    }
    return result;
});

const availableSlotsCount = computed(() => {
    return timeSlots.value.filter(s => s.available).length;
});

// Methods
const fetchData = async () => {
    loadingData.value = true;
    loadError.value = '';

    try {
        const [
            patientsRes,
            doctorsRes,
            departmentsRes,
            skillSetsRes,
            groupsRes,
            classesRes,
            refDoctorsRes
        ] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/doctors'),
            axios.get('/api/departments'),
            axios.get('/api/skill-sets'),
            axios.get('/api/doctor-groups'),
            axios.get('/api/patient-classes'),
            axios.get('/api/reference-doctors')
        ]);

        // Handle nested response format: {success: true, data: {data: [...]}} or {data: [...]}
        const extractData = (res) => {
            if (res.data?.data?.data) return res.data.data.data; // {success, data: {data: []}}
            if (Array.isArray(res.data?.data)) return res.data.data;
            if (Array.isArray(res.data)) return res.data;
            return [];
        };

        patients.value = extractData(patientsRes);
        doctors.value = extractData(doctorsRes);
        departments.value = extractData(departmentsRes);
        skillSets.value = extractData(skillSetsRes);
        doctorGroups.value = extractData(groupsRes);
        patientClasses.value = extractData(classesRes);
        referenceDoctors.value = extractData(refDoctorsRes);

        console.log('Patients loaded:', patients.value.length);
        console.log('Doctors loaded:', doctors.value.length);

        // Load existing appointment if editing
        if (route.params.id) {
            const response = await axios.get(`/api/appointments/${route.params.id}`);
            const appt = response.data;
            Object.assign(form, {
                patient_id: appt.patient_id,
                doctor_id: appt.doctor_id,
                department_id: appt.department_id || '',
                skill_set_id: appt.skill_set_id || '',
                group_id: appt.group_id || '',
                class_id: appt.class_id || '',
                reference_doctor_id: appt.reference_doctor_id || '',
                appointment_date: appt.appointment_date?.split('T')[0] || appt.appointment_date,
                appointment_time: appt.appointment_time,
                appointment_type: appt.appointment_type || 'consultation',
                booking_mode: appt.booking_mode || 'walk_in',
                service_type: appt.service_type || 'first',
                is_online: appt.is_online || false,
                priority: appt.priority || 'normal',
                duration_minutes: appt.duration_minutes || 15,
                booking_source: appt.booking_source || 'reception',
                slot_number: appt.slot_number,
                slot_start_time: appt.slot_start_time,
                slot_end_time: appt.slot_end_time,
                reason: appt.reason || '',
                notes: appt.notes || '',
                status: appt.status
            });
            selectedSlot.value = appt.slot_number;

            // Fetch slots for the date
            if (appt.doctor_id && appt.appointment_date) {
                await fetchTimeSlots();
                await fetchDoctorSchedule();
            }
        }
    } catch (error) {
        console.error('Error loading data:', error);
        loadError.value = error.response?.data?.message || error.message || 'Failed to load form data. Please refresh the page.';
    } finally {
        loadingData.value = false;
    }
};

const onPatientChange = () => {
    // Auto-fill patient class if patient has one
    const patient = selectedPatient.value;
    if (patient && patient.class_id) {
        form.class_id = patient.class_id;
    }
};

const onDepartmentChange = () => {
    // Reset doctor if not in selected department
    if (form.doctor_id) {
        const doctor = doctors.value.find(d => d.doctor_id === form.doctor_id);
        if (doctor && form.department_id && doctor.department_id !== form.department_id) {
            form.doctor_id = '';
            timeSlots.value = [];
            doctorSchedule.value = null;
        }
    }
};

const onSkillSetChange = () => {
    // Reset doctor if not matching skill set
    if (form.doctor_id) {
        const doctor = doctors.value.find(d => d.doctor_id === form.doctor_id);
        if (doctor && form.skill_set_id && doctor.skill_set_id !== form.skill_set_id) {
            form.doctor_id = '';
            timeSlots.value = [];
            doctorSchedule.value = null;
        }
    }
};

const onDoctorChange = async () => {
    selectedSlot.value = null;
    form.slot_number = null;
    form.slot_start_time = null;
    form.slot_end_time = null;

    if (form.doctor_id) {
        await Promise.all([
            fetchTimeSlots(),
            fetchDoctorSchedule()
        ]);
    } else {
        timeSlots.value = [];
        doctorSchedule.value = null;
    }
};

const fetchTimeSlots = async () => {
    if (!form.doctor_id || !form.appointment_date) return;

    loadingSlots.value = true;
    noSlotsMessage.value = '';

    try {
        const response = await axios.get(`/api/appointments/doctor/${form.doctor_id}/available-time-slots`, {
            params: { date: form.appointment_date }
        });

        timeSlots.value = response.data.slots || [];

        if (response.data.message) {
            noSlotsMessage.value = response.data.message;
        }
    } catch (error) {
        console.error('Error fetching time slots:', error);
        timeSlots.value = [];
    }

    loadingSlots.value = false;
};

const fetchDoctorSchedule = async () => {
    if (!form.doctor_id) return;

    try {
        const response = await axios.get(`/api/appointments/doctor/${form.doctor_id}/duty-schedule`);
        doctorSchedule.value = response.data.schedule || null;
    } catch (error) {
        console.error('Error fetching schedule:', error);
        doctorSchedule.value = null;
    }
};

const selectSlot = (slot) => {
    if (!slot.available) return;

    selectedSlot.value = slot.slot_number;
    form.slot_number = slot.slot_number;
    form.slot_start_time = slot.start_time;
    form.slot_end_time = slot.end_time;
    form.appointment_time = slot.start_time;
};

const formatTime = (time) => {
    if (!time) return '';
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
};

const saveAppointment = async () => {
    saving.value = true;
    try {
        const payload = { ...form };

        // Clean up empty values
        Object.keys(payload).forEach(key => {
            if (payload[key] === '' || payload[key] === null) {
                delete payload[key];
            }
        });

        if (isEdit.value) {
            await axios.put(`/api/appointments/${route.params.id}`, payload);
        } else {
            await axios.post('/api/appointments', payload);
        }
        router.push('/appointments');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving appointment');
    }
    saving.value = false;
};

// Watch for date changes
watch(() => form.appointment_date, (newDate, oldDate) => {
    if (newDate !== oldDate && form.doctor_id) {
        fetchTimeSlots();
    }
});

onMounted(fetchData);
</script>

<style scoped>
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.5rem;
}

.time-slot {
    padding: 0.5rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.time-slot:hover:not(.unavailable) {
    border-color: #3699ff;
    background-color: rgba(54, 153, 255, 0.05);
}

.time-slot.selected {
    border-color: #3699ff;
    background-color: rgba(54, 153, 255, 0.1);
    box-shadow: 0 0 0 2px rgba(54, 153, 255, 0.25);
}

.time-slot.unavailable {
    background-color: #f8f9fa;
    cursor: not-allowed;
    opacity: 0.6;
}

.slot-time {
    font-weight: 600;
    font-size: 0.9rem;
}

.slot-info {
    font-size: 0.75rem;
    color: #6c757d;
}

.time-slot.selected .slot-info {
    color: #3699ff;
}
</style>
