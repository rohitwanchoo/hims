<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-folder2-open me-2"></i>Patient Medical Records</h4>
            <router-link to="/mrd" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to MRD
            </router-link>
        </div>

        <!-- Patient Info Card -->
        <div v-if="patient" class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            {{ patient.patient_name?.charAt(0) || 'P' }}
                        </div>
                    </div>
                    <div class="col">
                        <h5 class="mb-1">{{ patient.patient_name }}</h5>
                        <p class="text-muted mb-0">
                            <span class="me-3"><i class="bi bi-credit-card me-1"></i>{{ patient.uhid }}</span>
                            <span class="me-3"><i class="bi bi-gender-ambiguous me-1"></i>{{ patient.gender }}</span>
                            <span class="me-3"><i class="bi bi-calendar me-1"></i>{{ calculateAge(patient.dob) }} years</span>
                            <span><i class="bi bi-telephone me-1"></i>{{ patient.phone || 'N/A' }}</span>
                        </p>
                    </div>
                    <div class="col-auto">
                        <button @click="printRecords" class="btn btn-outline-primary me-2">
                            <i class="bi bi-printer me-1"></i>Print Summary
                        </button>
                        <button @click="exportRecords" class="btn btn-outline-success">
                            <i class="bi bi-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading patient records...</p>
        </div>

        <!-- Records Tabs -->
        <div v-else>
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'overview' }" @click="activeTab = 'overview'">
                        <i class="bi bi-grid me-1"></i>Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'visits' }" @click="activeTab = 'visits'">
                        <i class="bi bi-calendar-check me-1"></i>Visits ({{ visits.length }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'admissions' }" @click="activeTab = 'admissions'">
                        <i class="bi bi-hospital me-1"></i>Admissions ({{ admissions.length }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'lab' }" @click="activeTab = 'lab'">
                        <i class="bi bi-droplet me-1"></i>Lab Reports ({{ labReports.length }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'radiology' }" @click="activeTab = 'radiology'">
                        <i class="bi bi-image me-1"></i>Radiology ({{ radiologyReports.length }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'prescriptions' }" @click="activeTab = 'prescriptions'">
                        <i class="bi bi-capsule me-1"></i>Prescriptions ({{ prescriptions.length }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'documents' }" @click="activeTab = 'documents'">
                        <i class="bi bi-file-earmark me-1"></i>Documents ({{ documents.length }})
                    </button>
                </li>
            </ul>

            <!-- Overview Tab -->
            <div v-if="activeTab === 'overview'" class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-heart-pulse me-2"></i>Medical History</div>
                        <div class="card-body">
                            <div v-if="patient?.medical_history" v-html="patient.medical_history"></div>
                            <p v-else class="text-muted mb-0">No medical history recorded</p>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-exclamation-triangle me-2"></i>Allergies</div>
                        <div class="card-body">
                            <div v-if="patient?.allergies">
                                <span v-for="allergy in patient.allergies.split(',')" :key="allergy" class="badge bg-danger me-1">{{ allergy.trim() }}</span>
                            </div>
                            <p v-else class="text-muted mb-0">No allergies recorded</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header"><i class="bi bi-clock-history me-2"></i>Recent Activity</div>
                        <ul class="list-group list-group-flush">
                            <li v-for="activity in recentActivity" :key="activity.id" class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <i class="bi me-2" :class="getActivityIcon(activity.type)"></i>
                                        {{ activity.description }}
                                    </div>
                                    <small class="text-muted">{{ formatDate(activity.date) }}</small>
                                </div>
                            </li>
                            <li v-if="recentActivity.length === 0" class="list-group-item text-muted">No recent activity</li>
                        </ul>
                    </div>
                    <div class="card">
                        <div class="card-header"><i class="bi bi-graph-up me-2"></i>Statistics</div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h4 class="text-primary">{{ visits.length }}</h4>
                                    <small class="text-muted">OPD Visits</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-success">{{ admissions.length }}</h4>
                                    <small class="text-muted">Admissions</small>
                                </div>
                                <div class="col-4">
                                    <h4 class="text-info">{{ labReports.length + radiologyReports.length }}</h4>
                                    <small class="text-muted">Reports</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visits Tab -->
            <div v-if="activeTab === 'visits'" class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Chief Complaint</th>
                                <th>Diagnosis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="visit in visits" :key="visit.visit_id">
                                <td>{{ formatDate(visit.visit_date) }}</td>
                                <td><span class="badge bg-info">{{ visit.visit_type }}</span></td>
                                <td>{{ visit.department_name }}</td>
                                <td>Dr. {{ visit.doctor_name }}</td>
                                <td>{{ visit.chief_complaints || '-' }}</td>
                                <td>{{ visit.diagnosis || '-' }}</td>
                            </tr>
                            <tr v-if="visits.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No visits recorded</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Admissions Tab -->
            <div v-if="activeTab === 'admissions'" class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Admission Date</th>
                                <th>Discharge Date</th>
                                <th>Ward/Bed</th>
                                <th>Attending Doctor</th>
                                <th>Diagnosis</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="admission in admissions" :key="admission.admission_id">
                                <td>{{ formatDate(admission.admission_date) }}</td>
                                <td>{{ admission.discharge_date ? formatDate(admission.discharge_date) : '-' }}</td>
                                <td>{{ admission.ward_name }} / {{ admission.bed_number }}</td>
                                <td>Dr. {{ admission.doctor_name }}</td>
                                <td>{{ admission.primary_diagnosis || '-' }}</td>
                                <td>
                                    <span class="badge" :class="admission.status === 'discharged' ? 'bg-success' : 'bg-warning'">
                                        {{ admission.status }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="admissions.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No admissions recorded</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Lab Reports Tab -->
            <div v-if="activeTab === 'lab'" class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Test Name</th>
                                <th>Sample ID</th>
                                <th>Result</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in labReports" :key="report.test_id">
                                <td>{{ formatDate(report.created_at) }}</td>
                                <td>{{ report.test_name }}</td>
                                <td>{{ report.sample_id }}</td>
                                <td>{{ report.result || '-' }}</td>
                                <td>
                                    <span class="badge" :class="report.status === 'completed' ? 'bg-success' : 'bg-warning'">
                                        {{ report.status }}
                                    </span>
                                </td>
                                <td>
                                    <button v-if="report.status === 'completed'" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="labReports.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No lab reports</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Radiology Tab -->
            <div v-if="activeTab === 'radiology'" class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Modality</th>
                                <th>Study</th>
                                <th>Radiologist</th>
                                <th>Findings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="report in radiologyReports" :key="report.order_id">
                                <td>{{ formatDate(report.created_at) }}</td>
                                <td>{{ report.modality }}</td>
                                <td>{{ report.study_name }}</td>
                                <td>Dr. {{ report.radiologist_name || '-' }}</td>
                                <td>{{ report.findings ? report.findings.substring(0, 50) + '...' : '-' }}</td>
                                <td>
                                    <button v-if="report.status === 'completed'" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="radiologyReports.length === 0">
                                <td colspan="6" class="text-center py-4 text-muted">No radiology reports</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Prescriptions Tab -->
            <div v-if="activeTab === 'prescriptions'" class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Doctor</th>
                                <th>Medications</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rx in prescriptions" :key="rx.prescription_id">
                                <td>{{ formatDate(rx.created_at) }}</td>
                                <td>Dr. {{ rx.doctor_name }}</td>
                                <td>
                                    <span v-for="(med, idx) in rx.medications?.slice(0, 3)" :key="idx" class="badge bg-secondary me-1">
                                        {{ med.name }}
                                    </span>
                                    <span v-if="rx.medications?.length > 3" class="text-muted">+{{ rx.medications.length - 3 }} more</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="prescriptions.length === 0">
                                <td colspan="4" class="text-center py-4 text-muted">No prescriptions</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Documents Tab -->
            <div v-if="activeTab === 'documents'" class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="doc in documents" :key="doc.document_id">
                                <td>{{ formatDate(doc.created_at) }}</td>
                                <td>{{ doc.document_name }}</td>
                                <td><span class="badge bg-info">{{ doc.document_type }}</span></td>
                                <td>{{ doc.uploaded_by_name }}</td>
                                <td>
                                    <button @click="viewDocument(doc)" class="btn btn-sm btn-outline-primary me-1">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button @click="downloadDocument(doc)" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-download"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="documents.length === 0">
                                <td colspan="5" class="text-center py-4 text-muted">No documents</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const patientId = computed(() => route.params.id);

const patient = ref(null);
const loading = ref(true);
const activeTab = ref('overview');

const visits = ref([]);
const admissions = ref([]);
const labReports = ref([]);
const radiologyReports = ref([]);
const prescriptions = ref([]);
const documents = ref([]);

const recentActivity = computed(() => {
    const activities = [];

    visits.value.slice(0, 3).forEach(v => {
        activities.push({
            id: `visit-${v.visit_id}`,
            type: 'visit',
            description: `OPD Visit - ${v.department_name}`,
            date: v.visit_date
        });
    });

    admissions.value.slice(0, 2).forEach(a => {
        activities.push({
            id: `admission-${a.admission_id}`,
            type: 'admission',
            description: `${a.status === 'discharged' ? 'Discharged from' : 'Admitted to'} ${a.ward_name}`,
            date: a.status === 'discharged' ? a.discharge_date : a.admission_date
        });
    });

    return activities.sort((a, b) => new Date(b.date) - new Date(a.date)).slice(0, 5);
});

const loadPatient = async () => {
    try {
        const response = await axios.get(`/api/patients/${patientId.value}`);
        patient.value = response.data.patient || response.data;
    } catch (error) {
        console.error('Failed to load patient:', error);
    }
};

const loadRecords = async () => {
    loading.value = true;
    try {
        const [visitsRes, admissionsRes, labRes, radRes, rxRes, docsRes] = await Promise.all([
            axios.get(`/api/patients/${patientId.value}/visits`).catch(() => ({ data: { visits: [] } })),
            axios.get(`/api/patients/${patientId.value}/admissions`).catch(() => ({ data: { admissions: [] } })),
            axios.get(`/api/patients/${patientId.value}/lab-reports`).catch(() => ({ data: { reports: [] } })),
            axios.get(`/api/patients/${patientId.value}/radiology-reports`).catch(() => ({ data: { reports: [] } })),
            axios.get(`/api/patients/${patientId.value}/prescriptions`).catch(() => ({ data: { prescriptions: [] } })),
            axios.get(`/api/mrd/documents?patient_id=${patientId.value}`).catch(() => ({ data: { documents: [] } }))
        ]);

        visits.value = visitsRes.data.visits || visitsRes.data.data || [];
        admissions.value = admissionsRes.data.admissions || admissionsRes.data.data || [];
        labReports.value = labRes.data.reports || labRes.data.data || [];
        radiologyReports.value = radRes.data.reports || radRes.data.data || [];
        prescriptions.value = rxRes.data.prescriptions || rxRes.data.data || [];
        documents.value = docsRes.data.documents || docsRes.data.data || [];
    } catch (error) {
        console.error('Failed to load records:', error);
    } finally {
        loading.value = false;
    }
};

const calculateAge = (dob) => {
    if (!dob) return 'N/A';
    const today = new Date();
    const birthDate = new Date(dob);
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString();
};

const getActivityIcon = (type) => {
    const icons = {
        visit: 'bi-calendar-check text-primary',
        admission: 'bi-hospital text-success',
        lab: 'bi-droplet text-info',
        radiology: 'bi-image text-warning'
    };
    return icons[type] || 'bi-circle text-secondary';
};

const viewDocument = (doc) => {
    window.open(`/api/mrd/documents/${doc.document_id}/view`, '_blank');
};

const downloadDocument = (doc) => {
    window.location.href = `/api/mrd/documents/${doc.document_id}/download`;
};

const printRecords = () => {
    window.print();
};

const exportRecords = async () => {
    try {
        window.location.href = `/api/mrd/patients/${patientId.value}/export`;
    } catch (error) {
        alert('Export failed');
    }
};

onMounted(() => {
    loadPatient();
    loadRecords();
});
</script>
