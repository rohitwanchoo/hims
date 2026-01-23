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
                                <div class="col-md-12">
                                    <label class="form-label">Patient <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <div class="flex-grow-1 position-relative">
                                            <input
                                                type="text"
                                                class="form-control pe-5"
                                                v-model="patientSearch"
                                                @input="filterPatients"
                                                @focus="showPatientDropdown = true"
                                                @blur="hidePatientDropdown"
                                                placeholder="Search by name or UHID..."
                                                autocomplete="off"
                                            />
                                            <button
                                                v-if="form.patient_id && patientSearch"
                                                type="button"
                                                class="btn-clear-patient"
                                                @click="clearPatient"
                                                title="Clear selected patient">
                                                <i class="bi bi-x-circle-fill"></i>
                                            </button>
                                            <!-- Patient Dropdown -->
                                            <div v-if="showPatientDropdown && filteredPatients.length > 0"
                                                 class="patient-dropdown">
                                                <div class="patient-dropdown-item"
                                                     v-for="p in filteredPatients.slice(0, 10)"
                                                     :key="p.patient_id"
                                                     @mousedown.prevent="selectPatient(p)"
                                                     :class="{ 'active': form.patient_id === p.patient_id }">
                                                    <div class="patient-info">
                                                        <div class="patient-name">{{ p.patient_name }}</div>
                                                        <div class="patient-details">
                                                            <span class="badge bg-secondary me-1">{{ p.pcd }}</span>
                                                            <small class="text-muted">
                                                                {{ p.gender?.gender_name || 'N/A' }} | {{ p.age || 'N/A' }} yrs
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-if="patients.length > 10" class="patient-dropdown-footer">
                                                    Showing {{ Math.min(10, filteredPatients.length) }} of {{ filteredPatients.length }} patients
                                                </div>
                                            </div>
                                        </div>
                                        <router-link
                                            :to="{ path: '/patients/create', query: { returnTo: $route.fullPath } }"
                                            class="btn btn-primary d-flex align-items-center"
                                            style="white-space: nowrap;"
                                            title="Add New Patient">
                                            <i class="bi bi-plus-lg me-1"></i> Add Patient
                                        </router-link>
                                    </div>
                                    <input type="hidden" v-model="form.patient_id" required>
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
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Select Time Slot <span class="text-danger">*</span></label>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            @click="fetchTimeSlots"
                                            :disabled="loadingSlots"
                                            title="Refresh availability">
                                        <i class="bi bi-arrow-clockwise" :class="{ 'spin': loadingSlots }"></i>
                                        Refresh
                                    </button>
                                </div>

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
                                             'unavailable': !slot.available,
                                             'limited': slot.available && (slot.max_patients - slot.booked) <= 1
                                         }"
                                         @click="selectSlot(slot)"
                                         :title="slot.available ? `${slot.max_patients - slot.booked} of ${slot.max_patients} slots available` : 'Fully booked'">
                                        <div class="slot-header">
                                            <div class="slot-time">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ formatTime(slot.start_time) }}
                                            </div>
                                            <div class="slot-status-icon">
                                                <i v-if="selectedSlot === slot.slot_number" class="bi bi-check-circle-fill text-primary"></i>
                                                <i v-else-if="!slot.available" class="bi bi-lock-fill text-danger"></i>
                                                <i v-else-if="(slot.max_patients - slot.booked) <= 1" class="bi bi-exclamation-circle-fill text-warning"></i>
                                                <i v-else class="bi bi-circle text-success"></i>
                                            </div>
                                        </div>
                                        <div class="slot-availability">
                                            <div class="availability-bar">
                                                <div class="availability-progress"
                                                     :style="{ width: ((slot.booked / slot.max_patients) * 100) + '%' }"
                                                     :class="{
                                                         'bg-danger': !slot.available,
                                                         'bg-warning': slot.available && (slot.max_patients - slot.booked) <= 1,
                                                         'bg-success': slot.available && (slot.max_patients - slot.booked) > 1
                                                     }">
                                                </div>
                                            </div>
                                            <div class="slot-info mt-1">
                                                <small v-if="slot.available" class="text-success">
                                                    <i class="bi bi-person-check-fill me-1"></i>
                                                    {{ slot.max_patients - slot.booked }}/{{ slot.max_patients }} available
                                                </small>
                                                <small v-else class="text-danger">
                                                    <i class="bi bi-x-circle-fill me-1"></i>
                                                    Fully Booked
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3" v-if="timeSlots.length > 0">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="text-muted small">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Total {{ availableSlotsCount }} of {{ timeSlots.length }} slots available
                                        </div>
                                        <div class="text-muted small" v-if="lastRefreshedText">
                                            <i class="bi bi-clock-history me-1"></i>
                                            Updated {{ lastRefreshedText }}
                                        </div>
                                    </div>
                                    <div class="slot-legend">
                                        <div class="legend-item">
                                            <i class="bi bi-circle text-success"></i>
                                            <span>Available</span>
                                        </div>
                                        <div class="legend-item">
                                            <i class="bi bi-exclamation-circle-fill text-warning"></i>
                                            <span>Limited</span>
                                        </div>
                                        <div class="legend-item">
                                            <i class="bi bi-lock-fill text-danger"></i>
                                            <span>Fully Booked</span>
                                        </div>
                                        <div class="legend-item">
                                            <i class="bi bi-check-circle-fill text-primary"></i>
                                            <span>Selected</span>
                                        </div>
                                    </div>
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
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue';
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
const patientSearch = ref('');
const filteredPatients = ref([]);
const showPatientDropdown = ref(false);
const lastRefreshed = ref(null);
let autoRefreshInterval = null;

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

const lastRefreshedText = computed(() => {
    if (!lastRefreshed.value) return '';
    const now = new Date();
    const diff = Math.floor((now - lastRefreshed.value) / 1000); // seconds
    if (diff < 10) return 'just now';
    if (diff < 60) return `${diff}s ago`;
    const minutes = Math.floor(diff / 60);
    if (minutes < 60) return `${minutes}m ago`;
    return lastRefreshed.value.toLocaleTimeString();
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

        // Initialize filtered patients
        filteredPatients.value = patients.value;

        // Check if returning from patient creation with a new patient
        if (route.query.patient_id) {
            form.patient_id = parseInt(route.query.patient_id);
            const newPatient = patients.value.find(p => p.patient_id === form.patient_id);
            if (newPatient) {
                patientSearch.value = `${newPatient.pcd} - ${newPatient.patient_name}`;
                onPatientChange();
            }
        }

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

const filterPatients = () => {
    const search = patientSearch.value.toLowerCase().trim();
    if (!search) {
        filteredPatients.value = patients.value;
    } else {
        filteredPatients.value = patients.value.filter(p =>
            p.patient_name?.toLowerCase().includes(search) ||
            p.pcd?.toLowerCase().includes(search) ||
            p.mobile_number?.includes(search)
        );
    }
};

const selectPatient = (patient) => {
    form.patient_id = patient.patient_id;
    patientSearch.value = `${patient.pcd} - ${patient.patient_name}`;
    showPatientDropdown.value = false;
    onPatientChange();
};

const clearPatient = () => {
    form.patient_id = '';
    patientSearch.value = '';
    form.class_id = '';
    showPatientDropdown.value = false;
};

const hidePatientDropdown = () => {
    setTimeout(() => {
        showPatientDropdown.value = false;
    }, 200);
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

        lastRefreshed.value = new Date();
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

// Auto-refresh time slots every 30 seconds when doctor and date are selected
const startAutoRefresh = () => {
    stopAutoRefresh();
    if (form.doctor_id && form.appointment_date) {
        autoRefreshInterval = setInterval(() => {
            if (form.doctor_id && form.appointment_date && !loadingSlots.value) {
                fetchTimeSlots();
            }
        }, 30000); // 30 seconds
    }
};

const stopAutoRefresh = () => {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
};

// Watch for doctor/date changes to manage auto-refresh
watch([() => form.doctor_id, () => form.appointment_date], () => {
    startAutoRefresh();
});

onMounted(async () => {
    await fetchData();
    startAutoRefresh();
});

onBeforeUnmount(() => {
    stopAutoRefresh();
});
</script>

<style scoped>
.time-slots-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.time-slot {
    padding: 0.75rem;
    border: 2px solid #dee2e6;
    border-radius: 0.5rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.time-slot:hover:not(.unavailable) {
    border-color: #3699ff;
    background-color: rgba(54, 153, 255, 0.03);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(54, 153, 255, 0.15);
}

.time-slot.selected {
    border-color: #3699ff;
    background: linear-gradient(135deg, rgba(54, 153, 255, 0.1) 0%, rgba(54, 153, 255, 0.05) 100%);
    box-shadow: 0 4px 16px rgba(54, 153, 255, 0.3);
    transform: translateY(-2px);
}

.time-slot.unavailable {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    cursor: not-allowed;
    opacity: 0.7;
    border-color: #ced4da;
}

.time-slot.unavailable:hover {
    transform: none;
    box-shadow: none;
}

.time-slot.limited:not(.selected) {
    border-color: #ffc107;
    background-color: rgba(255, 193, 7, 0.05);
}

.slot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.slot-time {
    font-weight: 600;
    font-size: 0.95rem;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.slot-status-icon {
    font-size: 1.1rem;
}

.slot-availability {
    margin-top: 0.5rem;
}

.availability-bar {
    width: 100%;
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.availability-progress {
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 2px;
}

.slot-info {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
}

.time-slot.selected .slot-time {
    color: #3699ff;
}

.time-slot.unavailable .slot-time {
    color: #6c757d;
}

/* Slot Legend */
.slot-legend {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    color: #6c757d;
}

.legend-item i {
    font-size: 0.9rem;
}

/* Refresh button animation */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.spin {
    animation: spin 1s linear infinite;
}

/* Clear Patient Button */
.btn-clear-patient {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    font-size: 1.2rem;
    line-height: 1;
    z-index: 10;
    transition: color 0.2s ease;
}

.btn-clear-patient:hover {
    color: #dc3545;
}

.btn-clear-patient i {
    display: block;
}

/* Patient Autocomplete Dropdown */
.patient-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 0.25rem;
}

.patient-dropdown-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f3f5;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.patient-dropdown-item:last-child {
    border-bottom: none;
}

.patient-dropdown-item:hover {
    background-color: #f8f9fa;
}

.patient-dropdown-item.active {
    background-color: rgba(54, 153, 255, 0.1);
    border-left: 3px solid #3699ff;
}

.patient-info .patient-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.patient-info .patient-details {
    font-size: 0.875rem;
    color: #6c757d;
}

.patient-dropdown-footer {
    padding: 0.5rem 1rem;
    background-color: #f8f9fa;
    text-align: center;
    font-size: 0.75rem;
    color: #6c757d;
    border-top: 1px solid #dee2e6;
}
</style>
