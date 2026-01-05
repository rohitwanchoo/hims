<template>
    <div>
        <h4 class="mb-4">
            <i class="bi bi-file-medical me-2"></i>
            {{ isEdit ? 'Edit Death Registration' : 'New Death Registration' }}
        </h4>
        <form @submit.prevent="handleSubmit">
            <!-- Deceased Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Deceased Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient *</label>
                            <select class="form-select" v-model="form.patient_id" required @change="onPatientChange">
                                <option value="">Select Patient</option>
                                <option v-for="p in patients" :key="p.patient_id" :value="p.patient_id">
                                    {{ p.patient_name }} ({{ p.uhid }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IPD Admission</label>
                            <select class="form-select" v-model="form.ipd_id">
                                <option value="">Not Linked</option>
                                <option v-for="ipd in ipdAdmissions" :key="ipd.ipd_id" :value="ipd.ipd_id">
                                    {{ ipd.ipd_number }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deceased Name *</label>
                            <input type="text" class="form-control" v-model="form.deceased_name" required maxlength="100">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Age (Years)</label>
                            <input type="number" class="form-control" v-model="form.deceased_age" min="0" max="150">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Gender *</label>
                            <select class="form-select" v-model="form.deceased_gender" required>
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aadhar Number</label>
                            <input type="text" class="form-control" v-model="form.deceased_aadhar" maxlength="12" placeholder="12 digits">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Death Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Death Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date of Death *</label>
                            <input type="date" class="form-control" v-model="form.date_of_death" required :max="today">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Time of Death *</label>
                            <input type="time" class="form-control" v-model="form.time_of_death" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Place of Death</label>
                            <input type="text" class="form-control" v-model="form.place_of_death" maxlength="100" placeholder="e.g., ICU, Ward">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Manner of Death *</label>
                            <select class="form-select" v-model="form.manner_of_death" required>
                                <option value="">Select</option>
                                <option value="natural">Natural</option>
                                <option value="accident">Accident</option>
                                <option value="suicide">Suicide</option>
                                <option value="homicide">Homicide</option>
                                <option value="pending">Pending Investigation</option>
                                <option value="unknown">Unknown</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cause of Death -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Cause of Death</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Immediate Cause *</label>
                            <input type="text" class="form-control" v-model="form.cause_of_death_immediate" required placeholder="Disease or condition directly leading to death">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Antecedent Cause</label>
                            <input type="text" class="form-control" v-model="form.cause_of_death_antecedent" placeholder="Condition that led to immediate cause">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Underlying Cause</label>
                            <input type="text" class="form-control" v-model="form.cause_of_death_underlying" placeholder="Root cause that initiated events">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Certifying Doctor *</label>
                            <select class="form-select" v-model="form.certifying_doctor_id" required>
                                <option value="">Select Doctor</option>
                                <option v-for="d in doctors" :key="d.doctor_id" :value="d.doctor_id">
                                    Dr. {{ d.full_name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MLC & Autopsy -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>MLC & Autopsy Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="isMlc" v-model="form.is_mlc_case">
                                <label class="form-check-label" for="isMlc">MLC Case</label>
                            </div>
                        </div>
                        <div class="col-md-3" v-if="form.is_mlc_case">
                            <label class="form-label">MLC Number</label>
                            <input type="text" class="form-control" v-model="form.mlc_number" maxlength="50">
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="isAutopsy" v-model="form.is_autopsy_performed">
                                <label class="form-check-label" for="isAutopsy">Autopsy Performed</label>
                            </div>
                        </div>
                        <div class="col-md-12" v-if="form.is_autopsy_performed">
                            <label class="form-label">Autopsy Findings</label>
                            <textarea class="form-control" v-model="form.autopsy_findings" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informant Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Informant Details</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Informant Name *</label>
                            <input type="text" class="form-control" v-model="form.informant_name" required maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Relation to Deceased *</label>
                            <select class="form-select" v-model="form.informant_relation" required>
                                <option value="">Select</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Father">Father</option>
                                <option value="Mother">Mother</option>
                                <option value="Brother">Brother</option>
                                <option value="Sister">Sister</option>
                                <option value="Other Relative">Other Relative</option>
                                <option value="Friend">Friend</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Informant Address</label>
                            <input type="text" class="form-control" v-model="form.informant_address">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permanent Address -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <strong>Permanent Address of Deceased</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" v-model="form.permanent_address" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="mt-4">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                    {{ isEdit ? 'Update' : 'Register' }} Death
                </button>
                <router-link to="/death-registrations" class="btn btn-secondary ms-2">Cancel</router-link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const saving = ref(false);
const isEdit = computed(() => !!route.params.id);
const today = new Date().toISOString().split('T')[0];

const patients = ref([]);
const doctors = ref([]);
const ipdAdmissions = ref([]);

const form = reactive({
    patient_id: '',
    ipd_id: '',
    deceased_name: '',
    deceased_age: null,
    deceased_gender: '',
    deceased_aadhar: '',
    date_of_death: today,
    time_of_death: '',
    place_of_death: '',
    manner_of_death: '',
    cause_of_death_immediate: '',
    cause_of_death_antecedent: '',
    cause_of_death_underlying: '',
    certifying_doctor_id: '',
    is_mlc_case: false,
    mlc_number: '',
    is_autopsy_performed: false,
    autopsy_findings: '',
    informant_name: '',
    informant_relation: '',
    informant_address: '',
    permanent_address: ''
});

const loadData = async () => {
    try {
        const [patientsRes, doctorsRes, ipdRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/doctors'),
            axios.get('/api/ipd-admissions?per_page=500')
        ]);
        patients.value = patientsRes.data.data || patientsRes.data;
        doctors.value = doctorsRes.data.data || doctorsRes.data;
        ipdAdmissions.value = ipdRes.data.data || ipdRes.data;
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const onPatientChange = () => {
    const patient = patients.value.find(p => p.patient_id === form.patient_id);
    if (patient) {
        form.deceased_name = patient.patient_name;
        form.deceased_age = patient.age;
        form.deceased_gender = patient.gender;
        form.permanent_address = patient.address || '';
    }
};

const fetchRegistration = async () => {
    if (isEdit.value) {
        try {
            const response = await axios.get(`/api/death-registrations/${route.params.id}`);
            const reg = response.data.registration || response.data;
            Object.assign(form, {
                patient_id: reg.patient_id,
                ipd_id: reg.ipd_id || '',
                deceased_name: reg.deceased_name,
                deceased_age: reg.deceased_age,
                deceased_gender: reg.deceased_gender,
                deceased_aadhar: reg.deceased_aadhar || '',
                date_of_death: reg.date_of_death?.split('T')[0],
                time_of_death: reg.time_of_death,
                place_of_death: reg.place_of_death || '',
                manner_of_death: reg.manner_of_death,
                cause_of_death_immediate: reg.cause_of_death_immediate,
                cause_of_death_antecedent: reg.cause_of_death_antecedent || '',
                cause_of_death_underlying: reg.cause_of_death_underlying || '',
                certifying_doctor_id: reg.certifying_doctor_id,
                is_mlc_case: reg.is_mlc_case || false,
                mlc_number: reg.mlc_number || '',
                is_autopsy_performed: reg.is_autopsy_performed || false,
                autopsy_findings: reg.autopsy_findings || '',
                informant_name: reg.informant_name,
                informant_relation: reg.informant_relation,
                informant_address: reg.informant_address || '',
                permanent_address: reg.permanent_address || ''
            });
        } catch (error) {
            console.error('Failed to load registration:', error);
        }
    }
};

const handleSubmit = async () => {
    saving.value = true;
    try {
        if (isEdit.value) {
            await axios.put(`/api/death-registrations/${route.params.id}`, form);
        } else {
            await axios.post('/api/death-registrations', form);
        }
        router.push('/death-registrations');
    } catch (error) {
        const msg = error.response?.data?.message || 'Error saving registration';
        alert(msg);
        console.error('Save error:', error);
    }
    saving.value = false;
};

onMounted(async () => {
    await loadData();
    await fetchRegistration();
});
</script>
