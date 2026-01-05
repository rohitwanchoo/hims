<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Rate Change Requests</h4>
                <p class="text-muted mb-0">Manage doctor approval workflow for rate changes</p>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" v-model="statusFilter" style="width: 150px;">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button class="btn btn-outline-primary btn-sm" @click="loadRequests">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-warning bg-opacity-10 border-warning">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-warning mb-0">Pending</h6>
                                <h3 class="mb-0">{{ pendingCount }}</h3>
                            </div>
                            <i class="bi bi-clock-history text-warning" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-10 border-success">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-success mb-0">Approved</h6>
                                <h3 class="mb-0">{{ approvedCount }}</h3>
                            </div>
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger bg-opacity-10 border-danger">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-danger mb-0">Rejected</h6>
                                <h3 class="mb-0">{{ rejectedCount }}</h3>
                            </div>
                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selectedRequests.length > 0" class="alert alert-info d-flex justify-content-between align-items-center mb-3">
            <span>{{ selectedRequests.length }} request(s) selected</span>
            <div>
                <button class="btn btn-success btn-sm me-2" @click="bulkApprove">
                    <i class="bi bi-check-all me-1"></i> Approve Selected
                </button>
                <button class="btn btn-secondary btn-sm" @click="selectedRequests = []">
                    Clear Selection
                </button>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input"
                                       @change="toggleSelectAll"
                                       :checked="allSelected"
                                       :disabled="pendingRequests.length === 0">
                            </th>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Service/OPD</th>
                            <th>Original Rate</th>
                            <th>Requested Rate</th>
                            <th>Reason</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="10" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm me-2"></div>
                                Loading...
                            </td>
                        </tr>
                        <tr v-else-if="requests.length === 0">
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mb-0 mt-2">No rate change requests found</p>
                            </td>
                        </tr>
                        <tr v-for="request in filteredRequests" :key="request.request_id">
                            <td>
                                <input type="checkbox" class="form-check-input"
                                       v-model="selectedRequests"
                                       :value="request.request_id"
                                       :disabled="request.status !== 'pending'">
                            </td>
                            <td>#{{ request.request_id }}</td>
                            <td>{{ formatDate(request.created_at) }}</td>
                            <td>
                                <span v-if="request.service">{{ request.service.service_name }}</span>
                                <span v-else-if="request.opd_visit">OPD #{{ request.opd_visit.opd_number }}</span>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td class="text-end">₹{{ parseFloat(request.original_rate).toFixed(2) }}</td>
                            <td class="text-end">
                                <span :class="request.requested_rate < request.original_rate ? 'text-danger' : 'text-success'">
                                    ₹{{ parseFloat(request.requested_rate).toFixed(2) }}
                                </span>
                                <br>
                                <small :class="request.requested_rate < request.original_rate ? 'text-danger' : 'text-success'">
                                    ({{ getDifferencePercent(request) }})
                                </small>
                            </td>
                            <td>
                                <span class="text-truncate d-inline-block" style="max-width: 150px;" :title="request.reason">
                                    {{ request.reason }}
                                </span>
                            </td>
                            <td>{{ request.requested_by?.full_name || '-' }}</td>
                            <td>
                                <span class="badge" :class="getStatusBadge(request.status)">
                                    {{ request.status }}
                                </span>
                            </td>
                            <td>
                                <div v-if="request.status === 'pending'" class="btn-group btn-group-sm">
                                    <button class="btn btn-success" @click="approveRequest(request)" title="Approve">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    <button class="btn btn-danger" @click="showRejectModal(request)" title="Reject">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <button v-else class="btn btn-sm btn-outline-secondary" @click="viewDetails(request)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <nav v-if="pagination.last_page > 1" class="mt-3">
            <ul class="pagination pagination-sm justify-content-center">
                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                    <a class="page-link" href="#" @click.prevent="loadRequests(pagination.current_page - 1)">Previous</a>
                </li>
                <li class="page-item" v-for="page in pagination.last_page" :key="page" :class="{ active: page === pagination.current_page }">
                    <a class="page-link" href="#" @click.prevent="loadRequests(page)">{{ page }}</a>
                </li>
                <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                    <a class="page-link" href="#" @click.prevent="loadRequests(pagination.current_page + 1)">Next</a>
                </li>
            </ul>
        </nav>

        <!-- Reject Modal -->
        <div class="modal fade" ref="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Rate Change Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" v-model="rejectRemarks" rows="3"
                                      placeholder="Enter reason for rejecting this request..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="rejectRequest" :disabled="!rejectRemarks.trim()">
                            Reject Request
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div class="modal fade" ref="detailsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Request Details #{{ selectedRequest?.request_id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" v-if="selectedRequest">
                        <table class="table table-sm">
                            <tr>
                                <th>Status</th>
                                <td><span class="badge" :class="getStatusBadge(selectedRequest.status)">{{ selectedRequest.status }}</span></td>
                            </tr>
                            <tr>
                                <th>Original Rate</th>
                                <td>₹{{ parseFloat(selectedRequest.original_rate).toFixed(2) }}</td>
                            </tr>
                            <tr>
                                <th>Requested Rate</th>
                                <td>₹{{ parseFloat(selectedRequest.requested_rate).toFixed(2) }}</td>
                            </tr>
                            <tr>
                                <th>Reason</th>
                                <td>{{ selectedRequest.reason }}</td>
                            </tr>
                            <tr>
                                <th>Requested By</th>
                                <td>{{ selectedRequest.requested_by?.full_name }}</td>
                            </tr>
                            <tr>
                                <th>Request Date</th>
                                <td>{{ formatDateTime(selectedRequest.created_at) }}</td>
                            </tr>
                            <tr v-if="selectedRequest.approved_by">
                                <th>{{ selectedRequest.status === 'approved' ? 'Approved' : 'Rejected' }} By</th>
                                <td>{{ selectedRequest.approved_by?.full_name }}</td>
                            </tr>
                            <tr v-if="selectedRequest.approved_at">
                                <th>{{ selectedRequest.status === 'approved' ? 'Approved' : 'Rejected' }} At</th>
                                <td>{{ formatDateTime(selectedRequest.approved_at) }}</td>
                            </tr>
                            <tr v-if="selectedRequest.remarks">
                                <th>Remarks</th>
                                <td>{{ selectedRequest.remarks }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const requests = ref([]);
const loading = ref(true);
const statusFilter = ref('');
const selectedRequests = ref([]);
const selectedRequest = ref(null);
const rejectRemarks = ref('');
const rejectModal = ref(null);
const detailsModal = ref(null);
let rejectModalInstance = null;
let detailsModalInstance = null;

const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0
});

const filteredRequests = computed(() => {
    if (!statusFilter.value) return requests.value;
    return requests.value.filter(r => r.status === statusFilter.value);
});

const pendingRequests = computed(() => requests.value.filter(r => r.status === 'pending'));
const pendingCount = computed(() => requests.value.filter(r => r.status === 'pending').length);
const approvedCount = computed(() => requests.value.filter(r => r.status === 'approved').length);
const rejectedCount = computed(() => requests.value.filter(r => r.status === 'rejected').length);

const allSelected = computed(() => {
    return pendingRequests.value.length > 0 &&
           pendingRequests.value.every(r => selectedRequests.value.includes(r.request_id));
});

onMounted(() => {
    loadRequests();
    if (rejectModal.value) {
        rejectModalInstance = new Modal(rejectModal.value);
    }
    if (detailsModal.value) {
        detailsModalInstance = new Modal(detailsModal.value);
    }
});

const loadRequests = async (page = 1) => {
    loading.value = true;
    try {
        const params = { page };
        if (statusFilter.value) params.status = statusFilter.value;

        const response = await axios.get('/api/rate-change-requests', { params });
        requests.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            total: response.data.total
        };
    } catch (error) {
        console.error('Error loading requests:', error);
    } finally {
        loading.value = false;
    }
};

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedRequests.value = pendingRequests.value.map(r => r.request_id);
    } else {
        selectedRequests.value = [];
    }
};

const approveRequest = async (request) => {
    if (!confirm('Are you sure you want to approve this rate change request?')) return;

    try {
        await axios.post(`/api/rate-change-requests/${request.request_id}/approve`, {
            remarks: 'Approved'
        });
        loadRequests(pagination.value.current_page);
    } catch (error) {
        alert(error.response?.data?.message || 'Error approving request');
    }
};

const showRejectModal = (request) => {
    selectedRequest.value = request;
    rejectRemarks.value = '';
    rejectModalInstance?.show();
};

const rejectRequest = async () => {
    if (!rejectRemarks.value.trim()) return;

    try {
        await axios.post(`/api/rate-change-requests/${selectedRequest.value.request_id}/reject`, {
            remarks: rejectRemarks.value
        });
        rejectModalInstance?.hide();
        loadRequests(pagination.value.current_page);
    } catch (error) {
        alert(error.response?.data?.message || 'Error rejecting request');
    }
};

const bulkApprove = async () => {
    if (!confirm(`Are you sure you want to approve ${selectedRequests.value.length} request(s)?`)) return;

    try {
        await axios.post('/api/rate-change-requests/bulk-approve', {
            request_ids: selectedRequests.value,
            remarks: 'Bulk approved'
        });
        selectedRequests.value = [];
        loadRequests(pagination.value.current_page);
    } catch (error) {
        alert(error.response?.data?.message || 'Error approving requests');
    }
};

const viewDetails = (request) => {
    selectedRequest.value = request;
    detailsModalInstance?.show();
};

const getStatusBadge = (status) => {
    return {
        'pending': 'bg-warning',
        'approved': 'bg-success',
        'rejected': 'bg-danger'
    }[status] || 'bg-secondary';
};

const getDifferencePercent = (request) => {
    const diff = ((request.requested_rate - request.original_rate) / request.original_rate) * 100;
    const sign = diff >= 0 ? '+' : '';
    return `${sign}${diff.toFixed(1)}%`;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatDateTime = (date) => {
    return new Date(date).toLocaleString();
};
</script>
