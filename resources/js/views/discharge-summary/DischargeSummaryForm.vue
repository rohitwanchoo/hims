<template>
    <div>
        <h4 class="mb-4">{{ getPageTitle() }}</h4>

        <div class="row">
            <div class="col-md-12">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'basic' }" @click="activeTab = 'basic'" href="#">
                            <i class="bi bi-person-badge"></i> Basic Info
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'" href="#">
                            <i class="bi bi-clock-history"></i> Medical History
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'diagnosis' }" @click="activeTab = 'diagnosis'" href="#">
                            <i class="bi bi-clipboard2-pulse"></i> Diagnosis & Treatment
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'medications' }" @click="activeTab = 'medications'" href="#">
                            <i class="bi bi-capsule"></i> Medications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="{ active: activeTab === 'discharge' }" @click="activeTab = 'discharge'" href="#">
                            <i class="bi bi-house-check"></i> Discharge Details
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <!-- Basic Info Tab -->
                        <div v-show="activeTab === 'basic'">
                            <div class="row g-3">
                                <div class="col-md-6" v-if="!$route.params.id">
                                    <label class="form-label">Select IPD Patient *</label>
                                    <select class="form-select" v-model="form.ipd_id" @change="loadPatientData" :disabled="loading">
                                        <option value="">Choose patient...</option>
                                        <option v-for="admission in dischargedPatients" :key="admission.ipd_id" :value="admission.ipd_id">
                                            {{ admission.ipd_number }} - {{ admission.patient?.first_name }} {{ admission.patient?.last_name }} - {{ admission.status }}
                                        </option>
                                    </select>
                                    <small class="text-muted">Shows all admitted and discharged IPD patients</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admission Date *</label>
                                    <input type="datetime-local" class="form-control" v-model="form.admission_date" :disabled="isViewMode">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Discharge Date *</label>
                                    <input type="datetime-local" class="form-control" v-model="form.discharge_date" :disabled="isViewMode">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Admission Type *</label>
                                    <select class="form-select" v-model="form.admission_type" :disabled="isViewMode">
                                        <option value="emergency">Emergency</option>
                                        <option value="planned">Planned</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Treating Doctor</label>
                                    <select class="form-select" v-model="form.treating_doctor_id" :disabled="isViewMode">
                                        <option value="">Select doctor...</option>
                                        <option v-for="doctor in doctors" :key="doctor.user_id" :value="doctor.user_id">
                                            {{ doctor.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consultant Doctor</label>
                                    <select class="form-select" v-model="form.consultant_doctor_id" :disabled="isViewMode">
                                        <option value="">Select doctor...</option>
                                        <option v-for="doctor in doctors" :key="doctor.user_id" :value="doctor.user_id">
                                            {{ doctor.name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">ABHA Address</label>
                                    <input type="text" class="form-control" v-model="form.abha_address" placeholder="patient@abdm" :disabled="isViewMode">
                                    <small class="text-muted">Ayushman Bharat Health Account address for ABDM integration</small>
                                </div>
                            </div>
                        </div>

                        <!-- Medical History Tab -->
                        <div v-show="activeTab === 'history'">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Chief Complaints</label>
                                    <textarea class="form-control" v-model="form.chief_complaints" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">History of Present Illness</label>
                                    <textarea class="form-control" v-model="form.history_of_present_illness" rows="4" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Past Medical History</label>
                                    <textarea class="form-control" v-model="form.past_medical_history" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Family History</label>
                                    <textarea class="form-control" v-model="form.family_history" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Physical Examination</label>
                                    <textarea class="form-control" v-model="form.physical_examination" rows="4" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Vital Signs</label>
                                    <textarea class="form-control" v-model="form.vital_signs" rows="2" placeholder="BP, Pulse, Temp, SpO2, etc." :disabled="isViewMode"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Diagnosis & Treatment Tab -->
                        <div v-show="activeTab === 'diagnosis'">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Provisional Diagnosis</label>
                                    <textarea class="form-control" v-model="form.provisional_diagnosis" rows="2" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Final Diagnosis *</label>
                                    <textarea class="form-control" v-model="form.final_diagnosis" rows="2" :disabled="isViewMode" required></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Secondary Diagnosis</label>
                                    <textarea class="form-control" v-model="form.secondary_diagnosis" rows="2" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">ICD-10 Codes</label>
                                    <input type="text" class="form-control" v-model="form.icd_codes" placeholder="E.g., A01.0, B20" :disabled="isViewMode">
                                    <small class="text-muted">International Classification of Diseases codes (comma separated)</small>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Course in Hospital</label>
                                    <textarea class="form-control" v-model="form.course_in_hospital" rows="4" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Procedures Performed</label>
                                    <textarea class="form-control" v-model="form.procedures_performed" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Operation Notes</label>
                                    <textarea class="form-control" v-model="form.operation_notes" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Investigations</label>
                                    <textarea class="form-control" v-model="form.investigations" rows="4" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Treatment Given</label>
                                    <textarea class="form-control" v-model="form.treatment_given" rows="4" :disabled="isViewMode"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Medications Tab -->
                        <div v-show="activeTab === 'medications'">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Medications on Admission</label>
                                    <textarea class="form-control" v-model="form.medications_on_admission" rows="6" placeholder="List medications patient was taking before admission" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Medications on Discharge</label>
                                    <textarea class="form-control" v-model="form.medications_on_discharge" rows="6" placeholder="List medications prescribed at discharge with dosage and duration" :disabled="isViewMode"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Discharge Details Tab -->
                        <div v-show="activeTab === 'discharge'">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Condition at Discharge *</label>
                                    <select class="form-select" v-model="form.condition_at_discharge" :disabled="isViewMode" required>
                                        <option value="">Select condition...</option>
                                        <option value="improved">Improved</option>
                                        <option value="same">Same</option>
                                        <option value="deteriorated">Deteriorated</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Follow-up Date</label>
                                    <input type="date" class="form-control" v-model="form.follow_up_date" :disabled="isViewMode">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Discharge Advice</label>
                                    <textarea class="form-control" v-model="form.discharge_advice" rows="4" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Follow-up Instructions</label>
                                    <textarea class="form-control" v-model="form.follow_up_instructions" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Dietary Instructions</label>
                                    <textarea class="form-control" v-model="form.dietary_instructions" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Activity Restrictions</label>
                                    <textarea class="form-control" v-model="form.activity_restrictions" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea class="form-control" v-model="form.notes" rows="3" :disabled="isViewMode"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" v-model="form.status" :disabled="isViewMode">
                                        <option value="draft">Draft</option>
                                        <option value="completed">Completed</option>
                                        <option value="signed">Signed</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between">
                            <router-link to="/discharge-summary" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to List
                            </router-link>
                            <div class="btn-group" v-if="!isViewMode">
                                <button class="btn btn-primary" @click="saveSummary" :disabled="loading">
                                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                    <i v-else class="bi bi-save"></i>
                                    {{ $route.params.id ? 'Update' : 'Save' }} Summary
                                </button>
                                <button class="btn btn-outline-secondary" @click="saveDraft" :disabled="loading" v-if="!$route.params.id">
                                    Save as Draft
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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

const loading = ref(false);
const activeTab = ref('basic');
const dischargedPatients = ref([]);
const doctors = ref([]);
const editMode = ref(false);

const form = ref({
    ipd_id: '',
    admission_date: '',
    discharge_date: '',
    admission_type: 'emergency',
    chief_complaints: '',
    history_of_present_illness: '',
    past_medical_history: '',
    family_history: '',
    physical_examination: '',
    vital_signs: '',
    provisional_diagnosis: '',
    final_diagnosis: '',
    secondary_diagnosis: '',
    icd_codes: '',
    course_in_hospital: '',
    procedures_performed: '',
    operation_notes: '',
    investigations: '',
    treatment_given: '',
    medications_on_admission: '',
    medications_on_discharge: '',
    condition_at_discharge: '',
    discharge_advice: '',
    follow_up_instructions: '',
    follow_up_date: '',
    dietary_instructions: '',
    activity_restrictions: '',
    treating_doctor_id: '',
    consultant_doctor_id: '',
    abha_address: '',
    notes: '',
    status: 'draft'
});

const isViewMode = computed(() => !!route.params.id && !editMode.value);

const getPageTitle = () => {
    if (!route.params.id) return 'New Discharge Summary';
    return editMode.value ? 'Edit Discharge Summary' : 'View Discharge Summary';
};

onMounted(async () => {
    try {
        loading.value = true;

        // Fetch doctors
        const doctorsRes = await axios.get('/api/users?role=doctor');
        doctors.value = doctorsRes.data.data || doctorsRes.data || [];

        if (route.params.id) {
            // Load existing summary
            const summaryRes = await axios.get(`/api/discharge-summaries/${route.params.id}`);
            const summary = summaryRes.data;

            // Auto-enable edit mode if mode=edit query parameter
            if (route.query.mode === 'edit' && summary.status !== 'signed') {
                editMode.value = true;
            }

            // Format dates for inputs
            form.value = {
                ...summary,
                admission_date: summary.admission_date ? summary.admission_date.substring(0, 16) : '',
                discharge_date: summary.discharge_date ? summary.discharge_date.substring(0, 16) : '',
                follow_up_date: summary.follow_up_date || ''
            };
        } else {
            // Fetch discharged patients
            const patientsRes = await axios.get('/api/discharge-summaries/discharged-patients');
            dischargedPatients.value = patientsRes.data || [];
        }
    } catch (error) {
        console.error('Error loading form data:', error);
        alert('Error loading form data');
    } finally {
        loading.value = false;
    }
});

const loadPatientData = async () => {
    if (!form.value.ipd_id) return;

    try {
        const admission = dischargedPatients.value.find(a => a.ipd_id == form.value.ipd_id);
        if (admission) {
            form.value.admission_date = admission.admission_date ? admission.admission_date.substring(0, 16) : '';
            form.value.discharge_date = admission.discharge_date ? admission.discharge_date.substring(0, 16) : '';
        }
    } catch (error) {
        console.error('Error loading patient data:', error);
    }
};

const saveSummary = async () => {
    if (!form.value.ipd_id && !route.params.id) {
        alert('Please select a patient');
        activeTab.value = 'basic';
        return;
    }

    if (!form.value.final_diagnosis) {
        alert('Please enter final diagnosis');
        activeTab.value = 'diagnosis';
        return;
    }

    if (!form.value.condition_at_discharge) {
        alert('Please select condition at discharge');
        activeTab.value = 'discharge';
        return;
    }

    loading.value = true;
    try {
        if (route.params.id) {
            await axios.put(`/api/discharge-summaries/${route.params.id}`, form.value);
            alert('Discharge summary updated successfully!');
            router.push('/discharge-summary');
        } else {
            form.value.status = 'completed';
            await axios.post('/api/discharge-summaries', form.value);
            alert('Discharge summary created successfully!');
            router.push('/discharge-summary');
        }
    } catch (error) {
        console.error('Error saving summary:', error);
        alert(error.response?.data?.message || 'Error saving discharge summary');
    } finally {
        loading.value = false;
    }
};

const saveDraft = async () => {
    if (!form.value.ipd_id) {
        alert('Please select a patient');
        activeTab.value = 'basic';
        return;
    }

    loading.value = true;
    try {
        form.value.status = 'draft';
        await axios.post('/api/discharge-summaries', form.value);
        alert('Draft saved successfully!');
        router.push('/discharge-summary');
    } catch (error) {
        console.error('Error saving draft:', error);
        alert(error.response?.data?.message || 'Error saving draft');
    } finally {
        loading.value = false;
    }
};
</script>
