<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-shield-check me-2"></i>ABHA Management</h4>
        </div>

        <!-- Search Patient -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-search me-2"></i>Link ABHA to Patient
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <input type="text" v-model="searchQuery" class="form-control"
                            placeholder="Search patient by name, ID, or mobile..." @keyup.enter="searchPatient">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" @click="searchPatient">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <div v-if="searchResults.length > 0" class="card mb-4">
            <div class="card-header">Search Results</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Age/Gender</th>
                            <th>ABHA Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="patient in searchResults" :key="patient.patient_id">
                            <td><code>{{ patient.pcd }}</code></td>
                            <td>{{ patient.patient_name }}</td>
                            <td>{{ patient.age }} {{ patient.age_unit }} / {{ patient.gender }}</td>
                            <td>
                                <span v-if="patient.abha_registration" class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>Linked
                                </span>
                                <span v-else class="badge bg-secondary">Not Linked</span>
                            </td>
                            <td>
                                <button v-if="!patient.abha_registration" @click="startAbhaFlow(patient)" class="btn btn-sm btn-primary">
                                    <i class="bi bi-link-45deg me-1"></i>Link ABHA
                                </button>
                                <button v-else @click="viewAbha(patient)" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i>View
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ABHA Link Flow Modal -->
        <div v-if="showAbhaModal" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-check me-2"></i>Link ABHA - {{ selectedPatient?.patient_name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="closeAbhaModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Step 1: Enter Aadhaar -->
                        <div v-if="step === 1">
                            <h6>Step 1: Enter Aadhaar Number</h6>
                            <div class="mb-3">
                                <label class="form-label">Aadhaar Number</label>
                                <input type="text" v-model="aadhaar" class="form-control form-control-lg"
                                    maxlength="12" placeholder="Enter 12-digit Aadhaar">
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                An OTP will be sent to the mobile number registered with Aadhaar.
                            </div>
                            <button class="btn btn-primary" @click="generateOtp" :disabled="aadhaar.length !== 12 || loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Generate OTP
                            </button>
                        </div>

                        <!-- Step 2: Verify OTP -->
                        <div v-if="step === 2">
                            <h6>Step 2: Verify OTP</h6>
                            <div class="mb-3">
                                <label class="form-label">Enter OTP</label>
                                <input type="text" v-model="otp" class="form-control form-control-lg"
                                    maxlength="6" placeholder="Enter 6-digit OTP">
                            </div>
                            <button class="btn btn-primary" @click="verifyOtp" :disabled="otp.length !== 6 || loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                Verify OTP
                            </button>
                            <button class="btn btn-link" @click="generateOtp">Resend OTP</button>
                        </div>

                        <!-- Step 3: Create/Link ABHA -->
                        <div v-if="step === 3">
                            <h6>Step 3: ABHA Details</h6>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                OTP verified successfully!
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">ABHA Number</label>
                                    <input type="text" v-model="abhaDetails.abha_number" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ABHA Address</label>
                                    <input type="text" v-model="abhaDetails.abha_address" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Name (as per Aadhaar)</label>
                                    <input type="text" v-model="abhaDetails.name" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="text" v-model="abhaDetails.date_of_birth" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="form-check mt-3">
                                <input type="checkbox" v-model="consent" class="form-check-input" id="consent">
                                <label class="form-check-label" for="consent">
                                    I consent to link my ABHA with this hospital and share health records as per ABDM guidelines.
                                </label>
                            </div>
                            <button class="btn btn-success mt-3" @click="linkAbha" :disabled="!consent || loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                <i class="bi bi-link-45deg me-1"></i>Link ABHA to Patient
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABHA View Modal -->
        <div v-if="showViewModal" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-shield-check me-2"></i>ABHA Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="showViewModal = false"></button>
                    </div>
                    <div class="modal-body" v-if="viewingAbha">
                        <div class="text-center mb-4">
                            <div class="display-6 text-success">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h5>ABHA Linked</h5>
                        </div>
                        <table class="table">
                            <tr>
                                <th>ABHA Number</th>
                                <td>{{ viewingAbha.abha_number }}</td>
                            </tr>
                            <tr>
                                <th>ABHA Address</th>
                                <td>{{ viewingAbha.abha_address }}</td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td>{{ viewingAbha.name }}</td>
                            </tr>
                            <tr>
                                <th>KYC Status</th>
                                <td><span class="badge bg-success">{{ viewingAbha.kyc_status }}</span></td>
                            </tr>
                            <tr>
                                <th>Linked On</th>
                                <td>{{ formatDate(viewingAbha.linked_at) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const searchQuery = ref('');
const searchResults = ref([]);
const showAbhaModal = ref(false);
const showViewModal = ref(false);
const selectedPatient = ref(null);
const viewingAbha = ref(null);
const step = ref(1);
const loading = ref(false);
const aadhaar = ref('');
const otp = ref('');
const txnId = ref('');
const consent = ref(false);
const abhaDetails = ref({
    abha_number: '',
    abha_address: '',
    name: '',
    date_of_birth: ''
});

const searchPatient = async () => {
    if (!searchQuery.value) return;
    try {
        const response = await axios.get(`/api/patients-search?search=${searchQuery.value}`);
        searchResults.value = response.data.patients || response.data;
    } catch (error) {
        console.error('Search failed:', error);
    }
};

const startAbhaFlow = (patient) => {
    selectedPatient.value = patient;
    step.value = 1;
    aadhaar.value = '';
    otp.value = '';
    consent.value = false;
    showAbhaModal.value = true;
};

const closeAbhaModal = () => {
    showAbhaModal.value = false;
    selectedPatient.value = null;
};

const generateOtp = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/api/abha/generate-aadhaar-otp', {
            aadhaar: aadhaar.value
        });
        txnId.value = response.data.txn_id;
        step.value = 2;
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to generate OTP');
    } finally {
        loading.value = false;
    }
};

const verifyOtp = async () => {
    loading.value = true;
    try {
        const response = await axios.post('/api/abha/verify-aadhaar-otp', {
            txn_id: txnId.value,
            otp: otp.value
        });

        // Create ABHA
        const createResponse = await axios.post('/api/abha/create-abha', {
            txn_id: response.data.txn_id,
            consent: true
        });

        abhaDetails.value = {
            abha_number: createResponse.data.abha_number,
            abha_address: createResponse.data.abha_address,
            name: createResponse.data.name,
            date_of_birth: createResponse.data.date_of_birth
        };
        step.value = 3;
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to verify OTP');
    } finally {
        loading.value = false;
    }
};

const linkAbha = async () => {
    loading.value = true;
    try {
        await axios.post(`/api/abha/link-patient/${selectedPatient.value.patient_id}`, {
            ...abhaDetails.value,
            consent: true
        });
        alert('ABHA linked successfully!');
        closeAbhaModal();
        searchPatient(); // Refresh results
    } catch (error) {
        alert(error.response?.data?.message || 'Failed to link ABHA');
    } finally {
        loading.value = false;
    }
};

const viewAbha = async (patient) => {
    try {
        const response = await axios.get(`/api/abha/patient/${patient.patient_id}`);
        viewingAbha.value = response.data.registration;
        showViewModal.value = true;
    } catch (error) {
        console.error('Failed to load ABHA:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();
</script>
