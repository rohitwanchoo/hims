<template>
    <div v-if="visit">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">
                    <span class="badge bg-info me-2">{{ visit.opd_number }}</span>
                    Consultation
                </h5>
                <p class="text-muted mb-0">
                    {{ visit.patient?.patient_name }} | {{ visit.patient?.pcd }} |
                    {{ visit.patient?.gender }} | {{ visit.patient?.age }} {{ visit.patient?.age_unit }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge fs-6" :class="visit.status === 'in_consultation' ? 'bg-success' : 'bg-warning'">
                    {{ visit.status === 'in_consultation' ? 'In Progress' : visit.status }}
                </span>
                <router-link to="/opd" class="btn btn-light">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </router-link>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Vitals -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Vitals</h6>
                        <button class="btn btn-sm btn-outline-primary" @click="toggleVitals">
                            {{ showVitals ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                    <div class="card-body" v-show="showVitals">
                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <label class="form-label small">BP (Systolic)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" v-model="form.vitals_bp_systolic">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">BP (Diastolic)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" v-model="form.vitals_bp_diastolic">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Pulse</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" v-model="form.vitals_pulse">
                                    <span class="input-group-text">/min</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Temperature</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.1" class="form-control" v-model="form.vitals_temperature">
                                    <span class="input-group-text">°F</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">SpO2</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control" v-model="form.vitals_spo2">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Weight</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.1" class="form-control" v-model="form.vitals_weight">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">Height</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.1" class="form-control" v-model="form.vitals_height">
                                    <span class="input-group-text">cm</span>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label small">BMI</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" :value="bmi" readonly>
                                    <span class="input-group-text">kg/m²</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clinical Notes -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-journal-medical me-2"></i>Clinical Notes</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Chief Complaints</label>
                                <textarea class="form-control" v-model="form.chief_complaints" rows="2"
                                          placeholder="Patient's main complaints..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">History of Present Illness</label>
                                <textarea class="form-control" v-model="form.history_of_illness" rows="2"
                                          placeholder="Detailed history..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Examination Notes</label>
                                <textarea class="form-control" v-model="form.examination_notes" rows="2"
                                          placeholder="Physical examination findings..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Diagnosis</label>
                                <textarea class="form-control" v-model="form.diagnosis" rows="2"
                                          placeholder="Provisional/Final diagnosis..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prescription -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-capsule me-2"></i>Prescription</h6>
                        <button class="btn btn-sm btn-primary" @click="addPrescriptionItem">
                            <i class="bi bi-plus"></i> Add Medicine
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="prescriptionItems.length === 0" class="text-center text-muted py-3">
                            <i class="bi bi-capsule fs-3 d-block mb-2"></i>
                            No medicines added yet
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th style="width: 100px;">Dose</th>
                                        <th style="width: 120px;">Frequency</th>
                                        <th style="width: 80px;">Duration</th>
                                        <th style="width: 100px;">Instructions</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in prescriptionItems" :key="index">
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                   v-model="item.medicine_name" placeholder="Medicine name">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                   v-model="item.dosage" placeholder="e.g., 500mg">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" v-model="item.frequency">
                                                <option value="OD">Once daily (OD)</option>
                                                <option value="BD">Twice daily (BD)</option>
                                                <option value="TDS">Thrice daily (TDS)</option>
                                                <option value="QID">Four times (QID)</option>
                                                <option value="SOS">As needed (SOS)</option>
                                                <option value="STAT">Immediately</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                   v-model="item.duration" placeholder="e.g., 5 days">
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" v-model="item.instructions">
                                                <option value="before_food">Before food</option>
                                                <option value="after_food">After food</option>
                                                <option value="with_food">With food</option>
                                                <option value="empty_stomach">Empty stomach</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" @click="removePrescriptionItem(index)">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Lab Orders -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-droplet me-2"></i>Lab / Radiology Orders</h6>
                        <button class="btn btn-sm btn-primary" @click="showLabModal = true">
                            <i class="bi bi-plus"></i> Add Investigation
                        </button>
                    </div>
                    <div class="card-body">
                        <div v-if="investigations.length === 0" class="text-center text-muted py-3">
                            <i class="bi bi-droplet fs-3 d-block mb-2"></i>
                            No investigations ordered
                        </div>
                        <div v-else>
                            <div v-for="inv in investigations" :key="inv.investigation_id"
                                 class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong>{{ inv.investigation_name }}</strong>
                                    <span class="badge ms-2" :class="getInvTypeBadge(inv.investigation_type)">
                                        {{ inv.investigation_type }}
                                    </span>
                                </div>
                                <span class="badge" :class="getInvStatusBadge(inv.status)">
                                    {{ inv.status || 'ordered' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advice & Follow-up -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Advice & Follow-up</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Advice / Instructions</label>
                                <textarea class="form-control" v-model="form.advice" rows="2"
                                          placeholder="Dietary advice, precautions, lifestyle modifications..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" class="form-control" v-model="form.followup_date" :min="minFollowupDate">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Follow-up Instructions</label>
                                <input type="text" class="form-control" v-model="form.followup_instructions"
                                       placeholder="e.g., Repeat blood tests, review in 1 week">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-primary" @click="saveConsultation" :disabled="saving">
                        <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i v-else class="bi bi-save me-1"></i> Save
                    </button>
                    <button class="btn btn-success" @click="completeConsultation" :disabled="saving">
                        <i class="bi bi-check-circle me-1"></i> Complete Consultation
                    </button>
                    <button class="btn btn-outline-primary" @click="printPrescription">
                        <i class="bi bi-printer me-1"></i> Print Prescription
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Patient Info Card -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-person me-2"></i>Patient Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted">UHID</td>
                                <td class="fw-semibold">{{ visit.patient?.pcd }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Name</td>
                                <td class="fw-semibold">{{ visit.patient?.patient_name }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Age / Gender</td>
                                <td>{{ visit.patient?.age }} {{ visit.patient?.age_unit }} / {{ visit.patient?.gender }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Mobile</td>
                                <td>{{ visit.patient?.mobile || 'N/A' }}</td>
                            </tr>
                            <tr v-if="visit.patient?.blood_group">
                                <td class="text-muted">Blood Group</td>
                                <td>{{ visit.patient?.blood_group }}</td>
                            </tr>
                            <tr v-if="visit.patient?.allergies">
                                <td class="text-muted">Allergies</td>
                                <td class="text-danger">{{ visit.patient?.allergies }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Visit Info -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Visit Info</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted">OPD No.</td>
                                <td class="fw-semibold">{{ visit.opd_number }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Token</td>
                                <td><span class="badge bg-primary">{{ visit.token_number }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Date</td>
                                <td>{{ formatDate(visit.visit_date) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Type</td>
                                <td>
                                    <span class="badge" :class="visit.visit_type === 'new' ? 'bg-primary' : 'bg-info'">
                                        {{ visit.visit_type }}
                                    </span>
                                    <span v-if="visit.is_free_followup" class="badge bg-success ms-1">Free</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Department</td>
                                <td>{{ visit.department?.department_name || '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Previous Visits -->
                <div class="card mb-3" v-if="previousVisits.length > 0">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Previous Visits</h6>
                    </div>
                    <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                        <div class="list-group list-group-flush">
                            <a v-for="pv in previousVisits" :key="pv.opd_id"
                               :href="`/opd/${pv.opd_id}`" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between">
                                    <small class="fw-semibold">{{ pv.opd_number }}</small>
                                    <small class="text-muted">{{ formatDate(pv.visit_date) }}</small>
                                </div>
                                <small class="text-muted d-block">
                                    {{ pv.doctor?.full_name || 'No doctor' }}
                                </small>
                                <small v-if="pv.diagnosis" class="text-truncate d-block">
                                    Dx: {{ pv.diagnosis }}
                                </small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Investigation Modal -->
        <div class="modal fade" :class="{ show: showLabModal }" :style="{ display: showLabModal ? 'block' : 'none' }"
             tabindex="-1" @click.self="showLabModal = false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Investigation</h5>
                        <button type="button" class="btn-close" @click="showLabModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" v-model="newInvestigation.investigation_type">
                                <option value="pathology">Pathology (Lab)</option>
                                <option value="radiology">Radiology</option>
                                <option value="procedure">Procedure</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Investigation Name</label>
                            <input type="text" class="form-control" v-model="newInvestigation.investigation_name"
                                   placeholder="e.g., CBC, LFT, X-Ray Chest">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-select" v-model="newInvestigation.priority">
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT (Immediate)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Clinical Notes</label>
                            <textarea class="form-control" v-model="newInvestigation.clinical_notes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showLabModal = false">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="addInvestigation">Add</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showLabModal" class="modal-backdrop fade show"></div>
    </div>

    <!-- Loading State -->
    <div v-else class="text-center py-5">
        <span class="spinner-border"></span>
        <p class="mt-2">Loading consultation...</p>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

// Data
const visit = ref(null);
const previousVisits = ref([]);
const prescriptionItems = ref([]);
const investigations = ref([]);
const saving = ref(false);
const showVitals = ref(true);
const showLabModal = ref(false);

// Form
const form = reactive({
    chief_complaints: '',
    history_of_illness: '',
    examination_notes: '',
    diagnosis: '',
    advice: '',
    followup_date: '',
    followup_instructions: '',
    vitals_bp_systolic: null,
    vitals_bp_diastolic: null,
    vitals_pulse: null,
    vitals_temperature: null,
    vitals_spo2: null,
    vitals_weight: null,
    vitals_height: null
});

const newInvestigation = reactive({
    investigation_type: 'pathology',
    investigation_name: '',
    priority: 'routine',
    clinical_notes: ''
});

// Computed
const bmi = computed(() => {
    if (!form.vitals_weight || !form.vitals_height) return '-';
    const heightM = form.vitals_height / 100;
    const bmiValue = form.vitals_weight / (heightM * heightM);
    return bmiValue.toFixed(1);
});

const minFollowupDate = computed(() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
});

// Methods
const fetchVisit = async () => {
    try {
        const response = await axios.get(`/api/opd-visits/${route.params.id}`);
        visit.value = response.data;

        // Populate form
        Object.assign(form, {
            chief_complaints: response.data.chief_complaints || '',
            history_of_illness: response.data.history_of_illness || '',
            examination_notes: response.data.examination_notes || '',
            diagnosis: response.data.diagnosis || '',
            advice: response.data.advice || '',
            followup_date: response.data.followup_date?.split('T')[0] || '',
            followup_instructions: response.data.followup_instructions || '',
            vitals_bp_systolic: response.data.vitals_bp_systolic,
            vitals_bp_diastolic: response.data.vitals_bp_diastolic,
            vitals_pulse: response.data.vitals_pulse,
            vitals_temperature: response.data.vitals_temperature,
            vitals_spo2: response.data.vitals_spo2,
            vitals_weight: response.data.vitals_weight,
            vitals_height: response.data.vitals_height
        });

        // Load prescriptions
        if (response.data.prescriptions?.length > 0) {
            prescriptionItems.value = response.data.prescriptions[0]?.items || [];
        }

        // Load investigations
        investigations.value = response.data.investigations || [];

        // Load previous visits
        if (visit.value.patient_id) {
            const historyRes = await axios.get(`/api/opd-visits/patient/${visit.value.patient_id}/history`);
            previousVisits.value = (historyRes.data || [])
                .filter(v => v.opd_id !== visit.value.opd_id)
                .slice(0, 5);
        }
    } catch (error) {
        console.error('Error fetching visit:', error);
        alert('Error loading consultation');
        router.push('/opd');
    }
};

const toggleVitals = () => {
    showVitals.value = !showVitals.value;
};

const addPrescriptionItem = () => {
    prescriptionItems.value.push({
        medicine_name: '',
        dosage: '',
        frequency: 'OD',
        duration: '',
        instructions: 'after_food'
    });
};

const removePrescriptionItem = (index) => {
    prescriptionItems.value.splice(index, 1);
};

const addInvestigation = async () => {
    if (!newInvestigation.investigation_name) {
        alert('Please enter investigation name');
        return;
    }

    try {
        const response = await axios.post(`/api/opd-visits/${route.params.id}/add-investigation`, newInvestigation);
        investigations.value.push(response.data);
        showLabModal.value = false;

        // Reset form
        newInvestigation.investigation_name = '';
        newInvestigation.clinical_notes = '';
        newInvestigation.priority = 'routine';
    } catch (error) {
        alert(error.response?.data?.message || 'Error adding investigation');
    }
};

const saveConsultation = async () => {
    saving.value = true;
    try {
        await axios.put(`/api/opd-visits/${route.params.id}`, form);

        // Save prescription if items exist
        if (prescriptionItems.value.length > 0) {
            // TODO: Create prescription API
        }

        alert('Consultation saved successfully');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving consultation');
    }
    saving.value = false;
};

const completeConsultation = async () => {
    if (!confirm('Are you sure you want to complete this consultation?')) return;

    saving.value = true;
    try {
        await axios.post(`/api/opd-visits/${route.params.id}/complete-consultation`, {
            diagnosis: form.diagnosis,
            advice: form.advice,
            followup_date: form.followup_date,
            followup_instructions: form.followup_instructions
        });
        router.push('/opd');
    } catch (error) {
        alert(error.response?.data?.message || 'Error completing consultation');
    }
    saving.value = false;
};

const printPrescription = () => {
    window.open(`/opd/${route.params.id}/prescription/print`, '_blank');
};

const getInvTypeBadge = (type) => {
    const badges = {
        pathology: 'bg-info',
        radiology: 'bg-warning text-dark',
        procedure: 'bg-secondary'
    };
    return badges[type] || 'bg-secondary';
};

const getInvStatusBadge = (status) => {
    const badges = {
        ordered: 'bg-warning text-dark',
        collected: 'bg-info',
        completed: 'bg-success',
        cancelled: 'bg-danger'
    };
    return badges[status] || 'bg-warning text-dark';
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

onMounted(fetchVisit);
</script>
