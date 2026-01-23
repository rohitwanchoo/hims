<template>
    <div>
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Loading patient details...</p>
        </div>

        <!-- Patient Details -->
        <div v-else-if="patient">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-xl me-3" :class="getAvatarClass(patient.gender || patient.gender_relation?.gender_name)">
                        <img v-if="patient.photo" :src="`/storage/${patient.photo}`" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                        <span v-else style="font-size: 1.5rem;">{{ getInitials(patient.patient_name) }}</span>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ patient.patient_name }}</h4>
                        <p class="text-muted mb-1">
                            <span class="badge bg-secondary me-2">{{ patient.pcd }}</span>
                            <span class="badge" :class="patient.is_active ? 'bg-success' : 'bg-danger'">
                                {{ patient.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                        <p class="mb-0 small text-muted">
                            Registered: {{ formatDate(patient.registration_date || patient.created_at) }}
                        </p>
                    </div>
                </div>
                <div class="btn-group">
                    <router-link :to="`/patients/${route.params.id}/edit`" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </router-link>
                    <router-link :to="`/opd/create?patient=${patient.patient_id}`" class="btn btn-success">
                        <i class="bi bi-clipboard-plus me-1"></i> New OPD
                    </router-link>
                    <router-link to="/patients" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </router-link>
                </div>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Personal Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Full Name</td>
                                            <td class="fw-medium">{{ patient.patient_name || '-' }}</td>
                                        </tr>
                                        <tr v-if="patient.first_name">
                                            <td class="text-muted">First Name</td>
                                            <td>{{ patient.first_name }}</td>
                                        </tr>
                                        <tr v-if="patient.middle_name">
                                            <td class="text-muted">Middle Name</td>
                                            <td>{{ patient.middle_name }}</td>
                                        </tr>
                                        <tr v-if="patient.last_name">
                                            <td class="text-muted">Last Name</td>
                                            <td>{{ patient.last_name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Guardian Name</td>
                                            <td>{{ patient.guardian_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Relation</td>
                                            <td>{{ patient.relation || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Date of Birth</td>
                                            <td>{{ formatDate(patient.dob) || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Age</td>
                                            <td>{{ formatAge(patient) }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Gender</td>
                                            <td>
                                                <span :class="getGenderBadge(patient)">{{ getGenderDisplay(patient) }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Blood Group</td>
                                            <td>
                                                <span v-if="getBloodGroup(patient)" class="badge bg-danger">{{ getBloodGroup(patient) }}</span>
                                                <span v-else>-</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Marital Status</td>
                                            <td>{{ getMaritalStatus(patient) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Occupation</td>
                                            <td>{{ patient.occupation || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Nationality</td>
                                            <td>{{ patient.nationality || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Religion</td>
                                            <td>{{ patient.religion || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Patient Type</td>
                                            <td>{{ getPatientType(patient) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-telephone me-2"></i>Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Mobile</td>
                                            <td>
                                                <a v-if="patient.mobile || patient.permanent_mobile" :href="`tel:${patient.mobile || patient.permanent_mobile}`">
                                                    <i class="bi bi-telephone me-1"></i>{{ patient.mobile || patient.permanent_mobile }}
                                                </a>
                                                <span v-else>-</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Phone</td>
                                            <td>{{ patient.phone || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email</td>
                                            <td>
                                                <a v-if="patient.email || patient.permanent_email" :href="`mailto:${patient.email || patient.permanent_email}`">
                                                    <i class="bi bi-envelope me-1"></i>{{ patient.email || patient.permanent_email }}
                                                </a>
                                                <span v-else>-</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;">Subscribe SMS</td>
                                            <td>
                                                <i :class="patient.subscribe_sms ? 'bi bi-check-circle text-success' : 'bi bi-x-circle text-muted'"></i>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Subscribe WhatsApp</td>
                                            <td>
                                                <i :class="patient.subscribe_whatsapp ? 'bi bi-check-circle text-success' : 'bi bi-x-circle text-muted'"></i>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Subscribe Email</td>
                                            <td>
                                                <i :class="patient.subscribe_email ? 'bi bi-check-circle text-success' : 'bi bi-x-circle text-muted'"></i>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Address Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Permanent Address -->
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Permanent Address</h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 35%;">Address</td>
                                            <td>{{ patient.permanent_address || patient.address || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Area/Village</td>
                                            <td>{{ patient.permanent_area?.area_name || patient.area || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">City/Taluka</td>
                                            <td>{{ patient.permanent_city?.city_name || patient.city || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">District</td>
                                            <td>{{ patient.permanent_district?.district_name || patient.district || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">State</td>
                                            <td>{{ patient.permanent_state?.state_name || patient.state || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Country</td>
                                            <td>{{ patient.permanent_country?.country_name || patient.country || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Pin Code</td>
                                            <td>{{ patient.permanent_pincode || patient.pincode || '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <!-- Current Address -->
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">
                                        Current Address
                                        <span v-if="patient.same_as_permanent" class="badge bg-info ms-2">Same as Permanent</span>
                                    </h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr>
                                            <td class="text-muted" style="width: 35%;">Address</td>
                                            <td>{{ patient.current_address || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Area/Village</td>
                                            <td>{{ patient.current_area?.area_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">City/Taluka</td>
                                            <td>{{ patient.current_city?.city_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">District</td>
                                            <td>{{ patient.current_district?.district_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">State</td>
                                            <td>{{ patient.current_state?.state_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Country</td>
                                            <td>{{ patient.current_country?.country_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Pin Code</td>
                                            <td>{{ patient.current_pincode || '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Medical Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted small mb-2">Allergies</h6>
                                    <p>{{ patient.allergies || 'No known allergies' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted small mb-2">Medical History</h6>
                                    <p>{{ patient.medical_history || 'No medical history recorded' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Identity Documents -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Identity Documents</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted">Aadhaar No.</td>
                                    <td>{{ patient.aadhaar_number || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">PAN No.</td>
                                    <td>{{ patient.pan_number || '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Emergency Contact</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted">Name</td>
                                    <td>{{ patient.emergency_contact_name || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Phone</td>
                                    <td>
                                        <a v-if="patient.emergency_contact_phone" :href="`tel:${patient.emergency_contact_phone}`">
                                            {{ patient.emergency_contact_phone }}
                                        </a>
                                        <span v-else>-</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Relation</td>
                                    <td>{{ patient.emergency_contact_relation || '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Insurance Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Insurance Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="text-muted">Insurance Company</td>
                                    <td>{{ patient.insurance_company_relation?.company_name || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Policy Number</td>
                                    <td>{{ patient.insurance_policy_number || patient.insurance_policy_no || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">TPA ID</td>
                                    <td>{{ patient.tpa_id || '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Cashless Ref. No.</td>
                                    <td>{{ patient.cashless_referral_no || '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Reference Doctor -->
                    <div class="card mb-4" v-if="patient.reference_doctor">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Reference Doctor</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1 fw-medium">{{ patient.reference_doctor.full_name || patient.reference_doctor.doctor_name }}</p>
                            <p class="mb-0 text-muted small">{{ patient.reference_doctor.specialization || '' }}</p>
                        </div>
                    </div>

                    <!-- Documents -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-file-earmark me-2"></i>Documents</h6>
                            <span class="badge bg-primary">{{ documents.length }}</span>
                        </div>
                        <div class="card-body">
                            <div v-if="documents.length > 0" class="list-group list-group-flush">
                                <div v-for="doc in documents" :key="doc.document_id" class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div class="text-truncate" style="max-width: 150px;">
                                        <i :class="getDocumentIcon(doc.file_name)" class="me-2"></i>
                                        <span :title="doc.document_title || doc.file_name">{{ doc.document_title || doc.file_name }}</span>
                                    </div>
                                    <a :href="`/storage/${doc.file_path}`" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <p v-else class="text-muted small mb-0">No documents uploaded</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Not Found -->
        <div v-else class="text-center py-5">
            <i class="bi bi-exclamation-circle text-warning" style="font-size: 3rem;"></i>
            <p class="mt-3 text-muted">Patient not found</p>
            <router-link to="/patients" class="btn btn-primary">Back to Patients</router-link>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const patient = ref(null);
const documents = ref([]);
const loading = ref(true);

// Fetch patient details
const fetchPatient = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/patients/${route.params.id}`);
        patient.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error fetching patient:', error);
        patient.value = null;
    }
    loading.value = false;
};

// Fetch patient documents
const fetchDocuments = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/documents`);
        documents.value = response.data.data || response.data || [];
    } catch (error) {
        console.error('Error fetching documents:', error);
    }
};

// Format date
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Format age
const formatAge = (patient) => {
    if (patient.age_years || patient.age_months || patient.age_days) {
        const parts = [];
        if (patient.age_years) parts.push(`${patient.age_years} Years`);
        if (patient.age_months) parts.push(`${patient.age_months} Months`);
        if (patient.age_days) parts.push(`${patient.age_days} Days`);
        return parts.join(', ') || '-';
    }
    if (patient.age) {
        return `${patient.age} ${patient.age_unit || 'Years'}`;
    }
    if (patient.dob) {
        const birthDate = new Date(patient.dob);
        const today = new Date();
        let years = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            years--;
        }
        return `${years} Years`;
    }
    return '-';
};

// Get gender display
const getGenderDisplay = (patient) => {
    if (patient.gender_relation?.gender_name) return patient.gender_relation.gender_name;
    if (patient.gender) return patient.gender.charAt(0).toUpperCase() + patient.gender.slice(1);
    return '-';
};

// Get gender badge class
const getGenderBadge = (patient) => {
    const gender = (patient.gender_relation?.gender_name || patient.gender || '').toLowerCase();
    const classes = {
        'male': 'badge bg-primary',
        'female': 'badge bg-danger',
        'other': 'badge bg-secondary'
    };
    return classes[gender] || 'badge bg-secondary';
};

// Get blood group
const getBloodGroup = (patient) => {
    return patient.blood_group_relation?.blood_group_name || patient.blood_group || null;
};

// Get marital status
const getMaritalStatus = (patient) => {
    if (patient.marital_status_relation?.marital_status_name) return patient.marital_status_relation.marital_status_name;
    if (patient.marital_status) return patient.marital_status.charAt(0).toUpperCase() + patient.marital_status.slice(1);
    return '-';
};

// Get patient type
const getPatientType = (patient) => {
    if (patient.patient_type_relation?.patient_type_name) return patient.patient_type_relation.patient_type_name;
    if (patient.patient_type) return patient.patient_type;
    return '-';
};

// Get initials
const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

// Get avatar class
const getAvatarClass = (gender) => {
    const g = (gender || '').toLowerCase();
    return g === 'female' ? 'bg-danger text-white' : 'bg-primary text-white';
};

// Get document icon
const getDocumentIcon = (fileName) => {
    if (!fileName) return 'bi bi-file-earmark';
    const ext = fileName.split('.').pop().toLowerCase();
    const icons = {
        'pdf': 'bi bi-file-earmark-pdf text-danger',
        'doc': 'bi bi-file-earmark-word text-primary',
        'docx': 'bi bi-file-earmark-word text-primary',
        'jpg': 'bi bi-file-earmark-image text-info',
        'jpeg': 'bi bi-file-earmark-image text-info',
        'png': 'bi bi-file-earmark-image text-info'
    };
    return icons[ext] || 'bi bi-file-earmark';
};

onMounted(() => {
    fetchPatient();
    fetchDocuments();
});
</script>

<style scoped>
.avatar-xl {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.table-borderless td {
    padding: 0.4rem 0.5rem;
}
</style>
