<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-file-earmark-text me-2"></i>Medical Record Requests</h4>
            <div>
                <button @click="showCreateModal = true" class="btn btn-primary me-2">
                    <i class="bi bi-plus-lg me-1"></i>New Request
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
                        <input type="text" v-model="filters.search" class="form-control" placeholder="Search by patient or request ID...">
                    </div>
                    <div class="col-md-2">
                        <select v-model="filters.status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select v-model="filters.request_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="copy">Copy Request</option>
                            <option value="view">View Only</option>
                            <option value="transfer">Transfer</option>
                            <option value="legal">Legal/Court</option>
                            <option value="insurance">Insurance</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" v-model="filters.from_date" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <input type="date" v-model="filters.to_date" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <button @click="loadRequests" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Request ID</th>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Requested By</th>
                            <th>Purpose</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="8" class="text-center py-4">
                                <div class="spinner-border text-primary"></div>
                            </td>
                        </tr>
                        <tr v-else-if="requests.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">No record requests found</td>
                        </tr>
                        <tr v-for="request in requests" :key="request.request_id">
                            <td><strong>{{ request.request_number }}</strong></td>
                            <td>{{ request.patient?.patient_name }}<br><small class="text-muted">{{ request.patient?.uhid }}</small></td>
                            <td>
                                <span class="badge" :class="getTypeBadgeClass(request.request_type)">
                                    {{ request.request_type }}
                                </span>
                            </td>
                            <td>{{ request.requester_name }}<br><small class="text-muted">{{ request.requester_relation }}</small></td>
                            <td>{{ request.purpose }}</td>
                            <td>{{ formatDate(request.created_at) }}</td>
                            <td>
                                <span class="badge" :class="getStatusBadgeClass(request.status)">
                                    {{ request.status }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button @click="viewRequest(request)" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button v-if="request.status === 'pending'" @click="approveRequest(request)" class="btn btn-outline-success" title="Approve">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                    <button v-if="request.status === 'pending'" @click="rejectRequest(request)" class="btn btn-outline-danger" title="Reject">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                    <button v-if="request.status === 'approved'" @click="completeRequest(request)" class="btn btn-outline-primary" title="Mark Complete">
                                        <i class="bi bi-check-all"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Request Modal -->
        <div class="modal fade" :class="{ show: showCreateModal }" :style="{ display: showCreateModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">New Record Request</h5>
                        <button type="button" class="btn-close" @click="showCreateModal = false"></button>
                    </div>
                    <form @submit.prevent="createRequest">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Patient *</label>
                                    <select v-model="form.patient_id" class="form-select" required>
                                        <option value="">Select Patient</option>
                                        <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                            {{ patient.patient_name }} ({{ patient.uhid }})
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Request Type *</label>
                                    <select v-model="form.request_type" class="form-select" required>
                                        <option value="copy">Copy Request</option>
                                        <option value="view">View Only</option>
                                        <option value="transfer">Transfer to Another Hospital</option>
                                        <option value="legal">Legal/Court Request</option>
                                        <option value="insurance">Insurance Claim</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Requester Name *</label>
                                    <input type="text" v-model="form.requester_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Relation to Patient *</label>
                                    <select v-model="form.requester_relation" class="form-select" required>
                                        <option value="self">Self</option>
                                        <option value="spouse">Spouse</option>
                                        <option value="parent">Parent</option>
                                        <option value="child">Child</option>
                                        <option value="sibling">Sibling</option>
                                        <option value="legal_guardian">Legal Guardian</option>
                                        <option value="attorney">Attorney</option>
                                        <option value="insurance">Insurance Company</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="tel" v-model="form.requester_phone" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" v-model="form.requester_email" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Purpose/Reason *</label>
                                    <textarea v-model="form.purpose" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Records From Date</label>
                                    <input type="date" v-model="form.records_from_date" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Records To Date</label>
                                    <input type="date" v-model="form.records_to_date" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Specific Records Needed</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" v-model="form.records_needed.discharge_summary" class="form-check-input" id="rec_ds">
                                                <label class="form-check-label" for="rec_ds">Discharge Summary</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" v-model="form.records_needed.opd_records" class="form-check-input" id="rec_opd">
                                                <label class="form-check-label" for="rec_opd">OPD Records</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" v-model="form.records_needed.lab_reports" class="form-check-input" id="rec_lab">
                                                <label class="form-check-label" for="rec_lab">Lab Reports</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" v-model="form.records_needed.radiology_reports" class="form-check-input" id="rec_rad">
                                                <label class="form-check-label" for="rec_rad">Radiology Reports</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input type="checkbox" v-model="form.records_needed.operation_notes" class="form-check-input" id="rec_ot">
                                                <label class="form-check-label" for="rec_ot">Operation Notes</label>
                                            </div>
                                            <div class="form-check">
                                                <input type="checkbox" v-model="form.records_needed.all_records" class="form-check-input" id="rec_all">
                                                <label class="form-check-label" for="rec_all">Complete Records</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Additional Notes</label>
                                    <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showCreateModal" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const requests = ref([]);
const patients = ref([]);
const loading = ref(false);
const saving = ref(false);
const showCreateModal = ref(false);

const filters = ref({
    search: '',
    status: '',
    request_type: '',
    from_date: '',
    to_date: ''
});

const form = ref({
    patient_id: '',
    request_type: 'copy',
    requester_name: '',
    requester_relation: 'self',
    requester_phone: '',
    requester_email: '',
    purpose: '',
    records_from_date: '',
    records_to_date: '',
    records_needed: {
        discharge_summary: false,
        opd_records: false,
        lab_reports: false,
        radiology_reports: false,
        operation_notes: false,
        all_records: false
    },
    notes: ''
});

const loadRequests = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.request_type) params.append('request_type', filters.value.request_type);
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);

        const response = await axios.get(`/api/mrd/record-requests?${params.toString()}`);
        requests.value = response.data.requests || response.data.data || [];
    } catch (error) {
        console.error('Failed to load requests:', error);
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

const createRequest = async () => {
    saving.value = true;
    try {
        await axios.post('/api/mrd/record-requests', form.value);
        showCreateModal.value = false;
        resetForm();
        loadRequests();
    } catch (error) {
        console.error('Failed to create request:', error);
        alert(error.response?.data?.message || 'Failed to create request');
    } finally {
        saving.value = false;
    }
};

const approveRequest = async (request) => {
    if (!confirm('Approve this record request?')) return;
    try {
        await axios.post(`/api/mrd/record-requests/${request.request_id}/approve`);
        loadRequests();
    } catch (error) {
        console.error('Failed to approve request:', error);
        alert(error.response?.data?.message || 'Failed to approve request');
    }
};

const rejectRequest = async (request) => {
    const reason = prompt('Enter rejection reason:');
    if (!reason) return;
    try {
        await axios.post(`/api/mrd/record-requests/${request.request_id}/reject`, { reason });
        loadRequests();
    } catch (error) {
        console.error('Failed to reject request:', error);
        alert(error.response?.data?.message || 'Failed to reject request');
    }
};

const completeRequest = async (request) => {
    if (!confirm('Mark this request as completed?')) return;
    try {
        await axios.post(`/api/mrd/record-requests/${request.request_id}/complete`);
        loadRequests();
    } catch (error) {
        console.error('Failed to complete request:', error);
        alert(error.response?.data?.message || 'Failed to complete request');
    }
};

const viewRequest = (request) => {
    alert(`Request Details:\n\nRequest #: ${request.request_number}\nPatient: ${request.patient?.patient_name}\nType: ${request.request_type}\nStatus: ${request.status}\nPurpose: ${request.purpose}`);
};

const resetForm = () => {
    form.value = {
        patient_id: '',
        request_type: 'copy',
        requester_name: '',
        requester_relation: 'self',
        requester_phone: '',
        requester_email: '',
        purpose: '',
        records_from_date: '',
        records_to_date: '',
        records_needed: {
            discharge_summary: false,
            opd_records: false,
            lab_reports: false,
            radiology_reports: false,
            operation_notes: false,
            all_records: false
        },
        notes: ''
    };
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-warning',
        approved: 'bg-info',
        processing: 'bg-primary',
        completed: 'bg-success',
        rejected: 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const getTypeBadgeClass = (type) => {
    const classes = {
        copy: 'bg-primary',
        view: 'bg-info',
        transfer: 'bg-warning',
        legal: 'bg-danger',
        insurance: 'bg-success'
    };
    return classes[type] || 'bg-secondary';
};

onMounted(() => {
    loadRequests();
    loadPatients();
});
</script>
