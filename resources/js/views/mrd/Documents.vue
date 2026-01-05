<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-file-earmark-medical me-2"></i>Medical Documents</h4>
            <div>
                <button @click="showUploadModal = true" class="btn btn-primary me-2">
                    <i class="bi bi-upload me-1"></i>Upload Document
                </button>
                <router-link to="/mrd" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to MRD
                </router-link>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" v-model="filters.search" class="form-control"
                               placeholder="Search patient or document..." @keyup.enter="loadDocuments">
                    </div>
                    <div class="col-md-2">
                        <select v-model="filters.document_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="discharge_summary">Discharge Summary</option>
                            <option value="prescription">Prescription</option>
                            <option value="lab_report">Lab Report</option>
                            <option value="radiology_report">Radiology Report</option>
                            <option value="consent_form">Consent Form</option>
                            <option value="operation_note">Operation Note</option>
                            <option value="referral_letter">Referral Letter</option>
                            <option value="death_certificate">Death Certificate</option>
                            <option value="birth_certificate">Birth Certificate</option>
                            <option value="medico_legal">Medico-Legal</option>
                            <option value="insurance">Insurance Document</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" v-model="filters.from_date" class="form-control" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" v-model="filters.to_date" class="form-control" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button @click="loadDocuments" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Grid -->
        <div class="row">
            <div v-if="loading" class="col-12 text-center py-5">
                <div class="spinner-border text-primary"></div>
            </div>
            <div v-else-if="documents.length === 0" class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5 text-muted">
                        <i class="bi bi-folder2-open display-4 mb-3"></i>
                        <p>No documents found</p>
                    </div>
                </div>
            </div>
            <div v-else v-for="doc in documents" :key="doc.document_id" class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="bi display-4" :class="getDocumentIcon(doc.document_type)"></i>
                        </div>
                        <h6 class="card-title text-truncate" :title="doc.document_name">{{ doc.document_name }}</h6>
                        <p class="card-text small text-muted mb-1">
                            <i class="bi bi-person me-1"></i>{{ doc.patient?.patient_name || 'N/A' }}
                        </p>
                        <p class="card-text small text-muted mb-1">
                            <i class="bi bi-tag me-1"></i>{{ formatDocType(doc.document_type) }}
                        </p>
                        <p class="card-text small text-muted">
                            <i class="bi bi-calendar me-1"></i>{{ formatDate(doc.created_at) }}
                        </p>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100">
                            <button @click="viewDocument(doc)" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> View
                            </button>
                            <button @click="downloadDocument(doc)" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download"></i>
                            </button>
                            <button @click="deleteDocument(doc)" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="d-flex justify-content-center mt-4">
            <nav>
                <ul class="pagination">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                        <button class="page-link" @click="changePage(currentPage - 1)">Previous</button>
                    </li>
                    <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === currentPage }">
                        <button class="page-link" @click="changePage(page)">{{ page }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                        <button class="page-link" @click="changePage(currentPage + 1)">Next</button>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Upload Modal -->
        <div class="modal fade" :class="{ show: showUploadModal }" :style="{ display: showUploadModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Document</h5>
                        <button type="button" class="btn-close" @click="showUploadModal = false"></button>
                    </div>
                    <form @submit.prevent="uploadDocument">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Patient *</label>
                                <select v-model="uploadForm.patient_id" class="form-select" required>
                                    <option value="">Select Patient</option>
                                    <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                        {{ patient.patient_name }} ({{ patient.uhid }})
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Document Type *</label>
                                <select v-model="uploadForm.document_type" class="form-select" required>
                                    <option value="discharge_summary">Discharge Summary</option>
                                    <option value="prescription">Prescription</option>
                                    <option value="lab_report">Lab Report</option>
                                    <option value="radiology_report">Radiology Report</option>
                                    <option value="consent_form">Consent Form</option>
                                    <option value="operation_note">Operation Note</option>
                                    <option value="referral_letter">Referral Letter</option>
                                    <option value="death_certificate">Death Certificate</option>
                                    <option value="birth_certificate">Birth Certificate</option>
                                    <option value="medico_legal">Medico-Legal Document</option>
                                    <option value="insurance">Insurance Document</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Document Name *</label>
                                <input type="text" v-model="uploadForm.document_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File *</label>
                                <input type="file" @change="handleFileSelect" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                <small class="text-muted">Allowed: PDF, JPG, PNG, DOC (Max 10MB)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea v-model="uploadForm.description" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showUploadModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="uploading">
                                <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-upload me-1"></i>Upload
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showUploadModal" class="modal-backdrop fade show"></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const documents = ref([]);
const patients = ref([]);
const loading = ref(false);
const uploading = ref(false);
const showUploadModal = ref(false);
const currentPage = ref(1);
const totalPages = ref(1);
const perPage = ref(12);

const filters = ref({
    search: '',
    document_type: '',
    from_date: '',
    to_date: ''
});

const uploadForm = ref({
    patient_id: '',
    document_type: 'discharge_summary',
    document_name: '',
    description: '',
    file: null
});

const visiblePages = computed(() => {
    const pages = [];
    const start = Math.max(1, currentPage.value - 2);
    const end = Math.min(totalPages.value, currentPage.value + 2);
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

const loadDocuments = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        params.append('page', currentPage.value);
        params.append('per_page', perPage.value);
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.document_type) params.append('document_type', filters.value.document_type);
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);

        const response = await axios.get(`/api/mrd/documents?${params.toString()}`);
        documents.value = response.data.documents || response.data.data || [];
        totalPages.value = response.data.last_page || 1;
    } catch (error) {
        console.error('Failed to load documents:', error);
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

const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            event.target.value = '';
            return;
        }
        uploadForm.value.file = file;
        if (!uploadForm.value.document_name) {
            uploadForm.value.document_name = file.name.replace(/\.[^/.]+$/, '');
        }
    }
};

const uploadDocument = async () => {
    uploading.value = true;
    try {
        const formData = new FormData();
        formData.append('patient_id', uploadForm.value.patient_id);
        formData.append('document_type', uploadForm.value.document_type);
        formData.append('document_name', uploadForm.value.document_name);
        formData.append('description', uploadForm.value.description);
        formData.append('file', uploadForm.value.file);

        await axios.post('/api/mrd/documents', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        showUploadModal.value = false;
        resetUploadForm();
        loadDocuments();
    } catch (error) {
        console.error('Failed to upload document:', error);
        alert(error.response?.data?.message || 'Failed to upload document');
    } finally {
        uploading.value = false;
    }
};

const viewDocument = (doc) => {
    window.open(`/api/mrd/documents/${doc.document_id}/view`, '_blank');
};

const downloadDocument = (doc) => {
    window.location.href = `/api/mrd/documents/${doc.document_id}/download`;
};

const deleteDocument = async (doc) => {
    if (!confirm(`Delete document "${doc.document_name}"?`)) return;

    try {
        await axios.delete(`/api/mrd/documents/${doc.document_id}`);
        loadDocuments();
    } catch (error) {
        console.error('Failed to delete document:', error);
        alert(error.response?.data?.message || 'Failed to delete document');
    }
};

const changePage = (page) => {
    if (page < 1 || page > totalPages.value) return;
    currentPage.value = page;
    loadDocuments();
};

const resetUploadForm = () => {
    uploadForm.value = {
        patient_id: '',
        document_type: 'discharge_summary',
        document_name: '',
        description: '',
        file: null
    };
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const formatDocType = (type) => {
    const types = {
        discharge_summary: 'Discharge Summary',
        prescription: 'Prescription',
        lab_report: 'Lab Report',
        radiology_report: 'Radiology Report',
        consent_form: 'Consent Form',
        operation_note: 'Operation Note',
        referral_letter: 'Referral Letter',
        death_certificate: 'Death Certificate',
        birth_certificate: 'Birth Certificate',
        medico_legal: 'Medico-Legal',
        insurance: 'Insurance',
        other: 'Other'
    };
    return types[type] || type;
};

const getDocumentIcon = (type) => {
    const icons = {
        discharge_summary: 'bi-file-earmark-text text-primary',
        prescription: 'bi-file-earmark-medical text-success',
        lab_report: 'bi-file-earmark-bar-graph text-info',
        radiology_report: 'bi-file-earmark-image text-warning',
        consent_form: 'bi-file-earmark-check text-secondary',
        operation_note: 'bi-file-earmark-richtext text-danger',
        referral_letter: 'bi-file-earmark-arrow-up text-primary',
        death_certificate: 'bi-file-earmark-x text-dark',
        birth_certificate: 'bi-file-earmark-person text-success',
        medico_legal: 'bi-file-earmark-lock text-danger',
        insurance: 'bi-file-earmark-ruled text-info',
        other: 'bi-file-earmark text-secondary'
    };
    return icons[type] || 'bi-file-earmark text-secondary';
};

onMounted(() => {
    loadDocuments();
    loadPatients();
});
</script>
