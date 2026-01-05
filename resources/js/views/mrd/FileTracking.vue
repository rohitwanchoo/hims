<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-folder-symlink me-2"></i>File Tracking</h4>
            <router-link to="/mrd" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to MRD
            </router-link>
        </div>

        <!-- Issue File Form -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-box-arrow-right me-2"></i>Issue File
            </div>
            <div class="card-body">
                <form @submit.prevent="issueFile">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Patient *</label>
                            <select v-model="issueForm.patient_id" class="form-select" required @change="loadPatientFile">
                                <option value="">Search patient...</option>
                                <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                    {{ patient.patient_name }} ({{ patient.uhid }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">File Number</label>
                            <input type="text" v-model="issueForm.file_number" class="form-control" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Issue To *</label>
                            <input type="text" v-model="issueForm.to_location" class="form-control" placeholder="Department/Person" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100" :disabled="issuing">
                                <span v-if="issuing" class="spinner-border spinner-border-sm me-1"></span>
                                Issue File
                            </button>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="form-label">Purpose</label>
                            <input type="text" v-model="issueForm.purpose" class="form-control" placeholder="Purpose of file request">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Files Currently Out -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark d-flex justify-content-between">
                <span><i class="bi bi-exclamation-triangle me-2"></i>Files Currently Out ({{ filesOut.length }})</span>
                <button @click="loadFilesOut" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>File Number</th>
                            <th>Patient</th>
                            <th>Issued To</th>
                            <th>Issued By</th>
                            <th>Issued Date</th>
                            <th>Days Out</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                        <tr v-else-if="filesOut.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">No files currently out</td>
                        </tr>
                        <tr v-for="file in filesOut" :key="file.movement_id" :class="getDaysOutClass(file.days_out)">
                            <td><strong>{{ file.file_number }}</strong></td>
                            <td>{{ file.patient?.patient_name }}</td>
                            <td>{{ file.to_location }}</td>
                            <td>{{ file.issued_by?.full_name }}</td>
                            <td>{{ formatDateTime(file.issued_at) }}</td>
                            <td>
                                <span class="badge" :class="file.days_out > 7 ? 'bg-danger' : (file.days_out > 3 ? 'bg-warning' : 'bg-success')">
                                    {{ file.days_out }} days
                                </span>
                            </td>
                            <td>
                                <button @click="returnFile(file)" class="btn btn-sm btn-success">
                                    <i class="bi bi-box-arrow-in-left me-1"></i>Return
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent File Movements -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>Recent File Movements
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>File Number</th>
                            <th>Patient</th>
                            <th>Action</th>
                            <th>Location</th>
                            <th>Date/Time</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="movement in recentMovements" :key="movement.movement_id">
                            <td>{{ movement.file_number }}</td>
                            <td>{{ movement.patient?.patient_name }}</td>
                            <td>
                                <span class="badge" :class="movement.status === 'issued' ? 'bg-warning' : 'bg-success'">
                                    {{ movement.status }}
                                </span>
                            </td>
                            <td>{{ movement.to_location || movement.from_location }}</td>
                            <td>{{ formatDateTime(movement.created_at) }}</td>
                            <td>{{ movement.issued_by?.full_name || movement.returned_by?.full_name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const patients = ref([]);
const filesOut = ref([]);
const recentMovements = ref([]);
const loading = ref(false);
const issuing = ref(false);

const issueForm = ref({
    patient_id: '',
    file_number: '',
    to_location: '',
    purpose: ''
});

const loadPatients = async () => {
    try {
        const response = await axios.get('/api/patients?per_page=500');
        patients.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load patients:', error);
    }
};

const loadPatientFile = () => {
    const patient = patients.value.find(p => p.patient_id === issueForm.value.patient_id);
    if (patient) {
        issueForm.value.file_number = patient.uhid || `MRD-${patient.patient_id}`;
    }
};

const loadFilesOut = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/mrd/file-movements?status=issued');
        filesOut.value = (response.data.movements || response.data.data || []).map(f => ({
            ...f,
            days_out: Math.floor((new Date() - new Date(f.issued_at)) / (1000 * 60 * 60 * 24))
        }));
    } catch (error) {
        console.error('Failed to load files:', error);
    } finally {
        loading.value = false;
    }
};

const loadRecentMovements = async () => {
    try {
        const response = await axios.get('/api/mrd/file-movements?limit=20');
        recentMovements.value = response.data.movements || response.data.data || [];
    } catch (error) {
        console.error('Failed to load movements:', error);
    }
};

const issueFile = async () => {
    issuing.value = true;
    try {
        await axios.post(`/api/mrd/patients/${issueForm.value.patient_id}/issue-file`, {
            to_location: issueForm.value.to_location,
            purpose: issueForm.value.purpose
        });
        issueForm.value = { patient_id: '', file_number: '', to_location: '', purpose: '' };
        loadFilesOut();
        loadRecentMovements();
    } catch (error) {
        console.error('Failed to issue file:', error);
        alert(error.response?.data?.message || 'Failed to issue file');
    } finally {
        issuing.value = false;
    }
};

const returnFile = async (file) => {
    try {
        await axios.post(`/api/mrd/file-movements/${file.movement_id}/return`);
        loadFilesOut();
        loadRecentMovements();
    } catch (error) {
        console.error('Failed to return file:', error);
        alert(error.response?.data?.message || 'Failed to return file');
    }
};

const formatDateTime = (date) => new Date(date).toLocaleString();

const getDaysOutClass = (days) => {
    if (days > 7) return 'table-danger';
    if (days > 3) return 'table-warning';
    return '';
};

onMounted(() => {
    loadPatients();
    loadFilesOut();
    loadRecentMovements();
});
</script>
