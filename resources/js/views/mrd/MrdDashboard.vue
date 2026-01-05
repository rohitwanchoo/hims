<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-folder2-open me-2"></i>Medical Records Department</h4>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-3">
                <router-link to="/mrd/file-tracking" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-folder-symlink display-4 text-primary"></i>
                        <h6 class="mt-2">File Tracking</h6>
                        <small class="text-muted">Issue & Return Files</small>
                    </div>
                </router-link>
            </div>
            <div class="col-md-3">
                <router-link to="/mrd/record-requests" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-arrow-down display-4 text-success"></i>
                        <h6 class="mt-2">Record Requests</h6>
                        <small class="text-muted">{{ pendingRequests }} Pending</small>
                    </div>
                </router-link>
            </div>
            <div class="col-md-3">
                <router-link to="/mrd/icd-coding" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-code-square display-4 text-info"></i>
                        <h6 class="mt-2">ICD Coding</h6>
                        <small class="text-muted">Diagnosis Coding</small>
                    </div>
                </router-link>
            </div>
            <div class="col-md-3">
                <router-link to="/mrd/documents" class="card text-decoration-none h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-medical display-4 text-warning"></i>
                        <h6 class="mt-2">Documents</h6>
                        <small class="text-muted">Upload & Manage</small>
                    </div>
                </router-link>
            </div>
        </div>

        <!-- Patient Search -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-search me-2"></i>Search Patient Records
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <input type="text" v-model="searchQuery" class="form-control form-control-lg"
                            placeholder="Enter Patient ID, Name, or Mobile Number..." @keyup.enter="searchPatient">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary btn-lg w-100" @click="searchPatient">
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
                            <th>Mobile</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="patient in searchResults" :key="patient.patient_id">
                            <td><code>{{ patient.pcd }}</code></td>
                            <td>{{ patient.patient_name }}</td>
                            <td>{{ patient.age }} {{ patient.age_unit }} / {{ patient.gender }}</td>
                            <td>{{ patient.mobile }}</td>
                            <td>
                                <router-link :to="`/mrd/patients/${patient.patient_id}/records`" class="btn btn-sm btn-primary">
                                    <i class="bi bi-folder2-open me-1"></i>View Records
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <!-- Files Out -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-folder-symlink me-2"></i>Files Currently Out
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div v-for="file in filesOut" :key="file.movement_id" class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ file.file_number }}</strong>
                                    <br><small class="text-muted">To: {{ file.to_location }}</small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">{{ formatDate(file.issued_at) }}</small>
                                    <br>
                                    <button @click="returnFile(file)" class="btn btn-sm btn-success">Return</button>
                                </div>
                            </div>
                            <div v-if="filesOut.length === 0" class="list-group-item text-center text-muted">
                                No files currently out
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <i class="bi bi-clock-history me-2"></i>Pending Record Requests
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div v-for="request in pendingRequestsList" :key="request.request_id" class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ request.patient?.patient_name }}</strong>
                                    <span class="badge bg-secondary">{{ request.requester_type }}</span>
                                </div>
                                <small class="text-muted">{{ request.purpose }}</small>
                                <div class="mt-2">
                                    <button @click="processRequest(request, 'approve')" class="btn btn-sm btn-success me-1">Approve</button>
                                    <button @click="processRequest(request, 'reject')" class="btn btn-sm btn-danger">Reject</button>
                                </div>
                            </div>
                            <div v-if="pendingRequestsList.length === 0" class="list-group-item text-center text-muted">
                                No pending requests
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const searchQuery = ref('');
const searchResults = ref([]);
const filesOut = ref([]);
const pendingRequestsList = ref([]);
const pendingRequests = ref(0);

const searchPatient = async () => {
    if (!searchQuery.value) return;
    try {
        const response = await axios.get(`/api/patients-search?search=${searchQuery.value}`);
        searchResults.value = response.data.patients || response.data;
    } catch (error) {
        console.error('Search failed:', error);
    }
};

const loadFilesOut = async () => {
    // This would need an API endpoint to get files currently out
};

const loadPendingRequests = async () => {
    try {
        const response = await axios.get('/api/mrd/record-requests?status=pending');
        pendingRequestsList.value = response.data.data || [];
        pendingRequests.value = pendingRequestsList.value.length;
    } catch (error) {
        console.error('Failed to load requests:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const returnFile = async (file) => {
    try {
        await axios.post(`/api/mrd/file-movements/${file.movement_id}/return`);
        loadFilesOut();
    } catch (error) {
        console.error('Failed to return file:', error);
    }
};

const processRequest = async (request, action) => {
    try {
        await axios.post(`/api/mrd/record-requests/${request.request_id}/process`, { action });
        loadPendingRequests();
    } catch (error) {
        console.error('Failed to process request:', error);
    }
};

onMounted(() => {
    loadFilesOut();
    loadPendingRequests();
});
</script>
