<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-file-earmark-check me-2"></i>Patient Consents</h4>
            <div>
                <button @click="showCreateModal = true" class="btn btn-primary me-2">
                    <i class="bi bi-plus-lg me-1"></i>Record Consent
                </button>
                <router-link to="/mrd" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to MRD
                </router-link>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" v-model="filters.search" class="form-control" placeholder="Search patient name or UHID...">
                    </div>
                    <div class="col-md-2">
                        <select v-model="filters.consent_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="treatment">Treatment</option>
                            <option value="surgery">Surgery</option>
                            <option value="anesthesia">Anesthesia</option>
                            <option value="blood_transfusion">Blood Transfusion</option>
                            <option value="research">Research</option>
                            <option value="data_sharing">Data Sharing</option>
                            <option value="photography">Photography</option>
                            <option value="hiv_test">HIV Test</option>
                            <option value="medico_legal">Medico-Legal</option>
                            <option value="discharge_against_advice">DAMA</option>
                            <option value="high_risk_procedure">High Risk Procedure</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select v-model="filters.status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="revoked">Revoked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" v-model="filters.from_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <input type="date" v-model="filters.to_date" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button @click="loadConsents" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Consents Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Patient</th>
                            <th>Consent Type</th>
                            <th>Consent For</th>
                            <th>Given By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                        <tr v-else-if="consents.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">No consents found</td>
                        </tr>
                        <tr v-for="consent in consents" :key="consent.consent_id">
                            <td>
                                {{ consent.patient?.patient_name }}<br>
                                <small class="text-muted">{{ consent.patient?.pcd }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="getTypeBadgeClass(consent.consent_type)">
                                    {{ formatConsentType(consent.consent_type) }}
                                </span>
                            </td>
                            <td>{{ consent.consent_for || '-' }}</td>
                            <td>
                                {{ consent.given_by }}<br>
                                <small class="text-muted">{{ consent.relationship || 'Self' }}</small>
                            </td>
                            <td>{{ formatDate(consent.consent_date || consent.created_at) }}</td>
                            <td>
                                <span v-if="consent.revoked_at" class="badge bg-danger">Revoked</span>
                                <span v-else-if="consent.is_given" class="badge bg-success">Active</span>
                                <span v-else class="badge bg-warning">Refused</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button @click="viewConsent(consent)" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button v-if="consent.consent_form_path" @click="downloadForm(consent)" class="btn btn-outline-secondary" title="Download Form">
                                        <i class="bi bi-download"></i>
                                    </button>
                                    <button v-if="consent.is_given && !consent.revoked_at" @click="revokeConsent(consent)" class="btn btn-outline-danger" title="Revoke">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Consent Modal -->
        <div class="modal fade" :class="{ show: showCreateModal }" :style="{ display: showCreateModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Patient Consent</h5>
                        <button type="button" class="btn-close" @click="showCreateModal = false"></button>
                    </div>
                    <form @submit.prevent="createConsent">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Patient *</label>
                                    <select v-model="form.patient_id" class="form-select" required>
                                        <option value="">Select Patient</option>
                                        <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                            {{ patient.patient_name }} ({{ patient.pcd }})
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consent Type *</label>
                                    <select v-model="form.consent_type" class="form-select" required>
                                        <option value="treatment">Treatment</option>
                                        <option value="surgery">Surgery</option>
                                        <option value="anesthesia">Anesthesia</option>
                                        <option value="blood_transfusion">Blood Transfusion</option>
                                        <option value="research">Research Participation</option>
                                        <option value="data_sharing">Data Sharing</option>
                                        <option value="photography">Photography/Recording</option>
                                        <option value="hiv_test">HIV Testing</option>
                                        <option value="medico_legal">Medico-Legal Case</option>
                                        <option value="discharge_against_advice">Discharge Against Medical Advice</option>
                                        <option value="high_risk_procedure">High Risk Procedure</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Consent For (Procedure/Treatment Details)</label>
                                    <input type="text" v-model="form.consent_for" class="form-control" placeholder="e.g., Appendectomy, CT Scan with contrast">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consent Given By *</label>
                                    <input type="text" v-model="form.given_by" class="form-control" required placeholder="Name of person giving consent">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Relationship to Patient</label>
                                    <select v-model="form.relationship" class="form-select">
                                        <option value="self">Self</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="parent">Parent</option>
                                        <option value="child">Child</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="legal_guardian">Legal Guardian</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consent Date *</label>
                                    <input type="date" v-model="form.consent_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Consent Decision *</label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input type="radio" v-model="form.is_given" :value="true" class="form-check-input" id="consent_yes">
                                            <label class="form-check-label" for="consent_yes">Consent Given</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="radio" v-model="form.is_given" :value="false" class="form-check-input" id="consent_no">
                                            <label class="form-check-label" for="consent_no">Consent Refused</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Witness Name</label>
                                    <input type="text" v-model="form.witness_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Attending Doctor</label>
                                    <select v-model="form.doctor_id" class="form-select">
                                        <option value="">Select Doctor</option>
                                        <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                            {{ doctor.full_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Record Consent
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showCreateModal" class="modal-backdrop fade show"></div>

        <!-- View Consent Modal -->
        <div class="modal fade" :class="{ show: showViewModal }" :style="{ display: showViewModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Consent Details</h5>
                        <button type="button" class="btn-close" @click="showViewModal = false"></button>
                    </div>
                    <div class="modal-body" v-if="selectedConsent">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Patient:</th>
                                <td>{{ selectedConsent.patient?.patient_name }}</td>
                            </tr>
                            <tr>
                                <th>Consent Type:</th>
                                <td>{{ formatConsentType(selectedConsent.consent_type) }}</td>
                            </tr>
                            <tr>
                                <th>Consent For:</th>
                                <td>{{ selectedConsent.consent_for || '-' }}</td>
                            </tr>
                            <tr>
                                <th>Given By:</th>
                                <td>{{ selectedConsent.given_by }} ({{ selectedConsent.relationship || 'Self' }})</td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>{{ formatDate(selectedConsent.consent_date || selectedConsent.created_at) }}</td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span v-if="selectedConsent.revoked_at" class="badge bg-danger">Revoked</span>
                                    <span v-else-if="selectedConsent.is_given" class="badge bg-success">Active</span>
                                    <span v-else class="badge bg-warning">Refused</span>
                                </td>
                            </tr>
                            <tr v-if="selectedConsent.witness_name">
                                <th>Witness:</th>
                                <td>{{ selectedConsent.witness_name }}</td>
                            </tr>
                            <tr v-if="selectedConsent.doctor">
                                <th>Doctor:</th>
                                <td>{{ selectedConsent.doctor?.full_name }}</td>
                            </tr>
                            <tr v-if="selectedConsent.notes">
                                <th>Notes:</th>
                                <td>{{ selectedConsent.notes }}</td>
                            </tr>
                            <tr v-if="selectedConsent.revoked_at">
                                <th>Revoked On:</th>
                                <td>{{ formatDate(selectedConsent.revoked_at) }}</td>
                            </tr>
                            <tr v-if="selectedConsent.revocation_reason">
                                <th>Revocation Reason:</th>
                                <td>{{ selectedConsent.revocation_reason }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showViewModal = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showViewModal" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const consents = ref([]);
const patients = ref([]);
const doctors = ref([]);
const loading = ref(false);
const saving = ref(false);
const showCreateModal = ref(false);
const showViewModal = ref(false);
const selectedConsent = ref(null);

const filters = ref({
    search: '',
    consent_type: '',
    status: '',
    from_date: '',
    to_date: ''
});

const form = ref({
    patient_id: '',
    consent_type: 'treatment',
    consent_for: '',
    given_by: '',
    relationship: 'self',
    consent_date: new Date().toISOString().split('T')[0],
    is_given: true,
    witness_name: '',
    doctor_id: '',
    notes: ''
});

const loadConsents = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.consent_type) params.append('consent_type', filters.value.consent_type);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);

        const response = await axios.get(`/api/mrd/consents?${params.toString()}`);
        consents.value = response.data.consents || response.data.data || [];
    } catch (error) {
        console.error('Failed to load consents:', error);
    } finally {
        loading.value = false;
    }
};

const loadPatients = async () => {
    try {
        const response = await axios.get('/api/patients?per_page=500');
        patients.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load patients:', error);
    }
};

const loadDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors?per_page=100');
        doctors.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load doctors:', error);
    }
};

const createConsent = async () => {
    saving.value = true;
    try {
        await axios.post(`/api/mrd/patients/${form.value.patient_id}/consents`, form.value);
        showCreateModal.value = false;
        resetForm();
        loadConsents();
    } catch (error) {
        console.error('Failed to create consent:', error);
        alert(error.response?.data?.message || 'Failed to record consent');
    } finally {
        saving.value = false;
    }
};

const revokeConsent = async (consent) => {
    const reason = prompt('Enter revocation reason:');
    if (!reason) return;
    try {
        await axios.post(`/api/mrd/consents/${consent.consent_id}/revoke`, { reason });
        loadConsents();
    } catch (error) {
        console.error('Failed to revoke consent:', error);
        alert(error.response?.data?.message || 'Failed to revoke consent');
    }
};

const viewConsent = (consent) => {
    selectedConsent.value = consent;
    showViewModal.value = true;
};

const downloadForm = (consent) => {
    if (consent.consent_form_path) {
        window.open(`/storage/${consent.consent_form_path}`, '_blank');
    }
};

const resetForm = () => {
    form.value = {
        patient_id: '',
        consent_type: 'treatment',
        consent_for: '',
        given_by: '',
        relationship: 'self',
        consent_date: new Date().toISOString().split('T')[0],
        is_given: true,
        witness_name: '',
        doctor_id: '',
        notes: ''
    };
};

const formatDate = (date) => date ? new Date(date).toLocaleDateString() : '-';

const formatConsentType = (type) => {
    const types = {
        treatment: 'Treatment',
        surgery: 'Surgery',
        anesthesia: 'Anesthesia',
        blood_transfusion: 'Blood Transfusion',
        research: 'Research',
        data_sharing: 'Data Sharing',
        photography: 'Photography',
        hiv_test: 'HIV Test',
        medico_legal: 'Medico-Legal',
        discharge_against_advice: 'DAMA',
        high_risk_procedure: 'High Risk',
        other: 'Other'
    };
    return types[type] || type;
};

const getTypeBadgeClass = (type) => {
    const classes = {
        surgery: 'bg-danger',
        anesthesia: 'bg-warning',
        blood_transfusion: 'bg-danger',
        high_risk_procedure: 'bg-danger',
        treatment: 'bg-primary',
        research: 'bg-info',
        medico_legal: 'bg-dark',
        discharge_against_advice: 'bg-warning'
    };
    return classes[type] || 'bg-secondary';
};

onMounted(() => {
    loadConsents();
    loadPatients();
    loadDoctors();
});
</script>
