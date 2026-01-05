<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>
                <i class="bi bi-calendar2-week me-2"></i>
                {{ isEdit ? 'Edit Surgery Schedule' : (isView ? 'Surgery Details' : 'Schedule Surgery') }}
            </h4>
            <router-link to="/ot/schedules" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </router-link>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient *</label>
                            <select v-model="form.patient_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Patient</option>
                                <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                    {{ patient.patient_name }} ({{ patient.uhid }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IPD Admission</label>
                            <select v-model="form.admission_id" class="form-select" :disabled="isView">
                                <option value="">Select Admission (if applicable)</option>
                                <option v-for="admission in admissions" :key="admission.admission_id" :value="admission.admission_id">
                                    {{ admission.admission_number }} - {{ admission.patient?.patient_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Operation Theater *</label>
                            <select v-model="form.ot_id" class="form-select" :disabled="isView" required>
                                <option value="">Select OT</option>
                                <option v-for="ot in theaters" :key="ot.ot_id" :value="ot.ot_id">
                                    {{ ot.ot_name }} ({{ ot.ot_code }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Surgery Type *</label>
                            <select v-model="form.surgery_type_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Surgery Type</option>
                                <option v-for="surgery in surgeryTypes" :key="surgery.surgery_type_id" :value="surgery.surgery_type_id">
                                    {{ surgery.surgery_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Scheduled Date *</label>
                            <input type="date" v-model="form.scheduled_date" class="form-control" :disabled="isView" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Time *</label>
                            <input type="time" v-model="form.scheduled_start_time" class="form-control" :disabled="isView" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Duration (mins)</label>
                            <input type="number" v-model="form.estimated_duration" class="form-control" :disabled="isView" min="15" step="15">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Surgeon *</label>
                            <select v-model="form.surgeon_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Surgeon</option>
                                <option v-for="doctor in surgeons" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                    {{ doctor.full_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Anesthetist</label>
                            <select v-model="form.anesthetist_id" class="form-select" :disabled="isView">
                                <option value="">Select Anesthetist</option>
                                <option v-for="doctor in anesthetists" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                    {{ doctor.full_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Anesthesia Type</label>
                            <select v-model="form.anesthesia_type" class="form-select" :disabled="isView">
                                <option value="">Select Type</option>
                                <option value="general">General</option>
                                <option value="local">Local</option>
                                <option value="spinal">Spinal</option>
                                <option value="epidural">Epidural</option>
                                <option value="regional">Regional Block</option>
                                <option value="sedation">Sedation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select v-model="form.priority" class="form-select" :disabled="isView">
                                <option value="elective">Elective</option>
                                <option value="urgent">Urgent</option>
                                <option value="emergency">Emergency</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pre-operative Notes</label>
                        <textarea v-model="form.pre_op_notes" class="form-control" rows="3" :disabled="isView"></textarea>
                    </div>

                    <div v-if="isView && schedule" class="mb-3">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge" :class="getStatusClass(schedule.status)">{{ schedule.status }}</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2" v-if="!isView">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEdit ? 'Update Schedule' : 'Schedule Surgery' }}
                        </button>
                        <router-link to="/ot/schedules" class="btn btn-outline-secondary">Cancel</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const schedule = ref(null);
const patients = ref([]);
const admissions = ref([]);
const theaters = ref([]);
const surgeryTypes = ref([]);
const surgeons = ref([]);
const anesthetists = ref([]);
const loading = ref(false);

const form = ref({
    patient_id: '',
    admission_id: '',
    ot_id: '',
    surgery_type_id: '',
    scheduled_date: new Date().toISOString().split('T')[0],
    scheduled_start_time: '',
    estimated_duration: 60,
    surgeon_id: '',
    anesthetist_id: '',
    anesthesia_type: '',
    priority: 'elective',
    pre_op_notes: ''
});

const isEdit = computed(() => route.name === 'ot.schedules.edit');
const isView = computed(() => route.name === 'ot.schedules.view');

const loadSchedule = async () => {
    if (!route.params.id) return;
    try {
        const response = await axios.get(`/api/ot/schedules/${route.params.id}`);
        schedule.value = response.data.schedule || response.data;
        form.value = {
            patient_id: schedule.value.patient_id,
            admission_id: schedule.value.admission_id || '',
            ot_id: schedule.value.ot_id,
            surgery_type_id: schedule.value.surgery_type_id,
            scheduled_date: schedule.value.scheduled_date,
            scheduled_start_time: schedule.value.scheduled_start_time,
            estimated_duration: schedule.value.estimated_duration || 60,
            surgeon_id: schedule.value.surgeon_id,
            anesthetist_id: schedule.value.anesthetist_id || '',
            anesthesia_type: schedule.value.anesthesia_type || '',
            priority: schedule.value.priority || 'elective',
            pre_op_notes: schedule.value.pre_op_notes || ''
        };
    } catch (error) {
        console.error('Failed to load schedule:', error);
    }
};

const loadData = async () => {
    try {
        const [patientsRes, theatersRes, surgeryTypesRes, doctorsRes, admissionsRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/ot/theaters'),
            axios.get('/api/ot/surgery-types'),
            axios.get('/api/doctors'),
            axios.get('/api/ipd-admissions?status=admitted')
        ]);
        patients.value = patientsRes.data.data || [];
        theaters.value = theatersRes.data.theaters || [];
        surgeryTypes.value = surgeryTypesRes.data.surgery_types || [];
        const doctors = doctorsRes.data.data || [];
        surgeons.value = doctors;
        anesthetists.value = doctors.filter(d => d.specialization?.toLowerCase().includes('anesthes'));
        admissions.value = admissionsRes.data.data || [];
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const getStatusClass = (status) => {
    const classes = {
        'scheduled': 'bg-secondary',
        'confirmed': 'bg-info',
        'in_progress': 'bg-warning',
        'completed': 'bg-success',
        'postponed': 'bg-dark',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const submitForm = async () => {
    loading.value = true;
    try {
        if (isEdit.value) {
            await axios.put(`/api/ot/schedules/${route.params.id}`, form.value);
        } else {
            await axios.post('/api/ot/schedules', form.value);
        }
        router.push('/ot/schedules');
    } catch (error) {
        console.error('Failed to save schedule:', error);
        alert(error.response?.data?.message || 'Failed to save schedule');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadData();
    if (route.params.id) {
        loadSchedule();
    }
});
</script>
