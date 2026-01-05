<template>
    <div>
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">{{ isEdit ? 'Edit OPD Visit' : 'New OPD Registration' }}</h5>
                <p class="text-muted mb-0">{{ isEdit ? 'Update visit details' : 'Register patient for OPD consultation' }}</p>
            </div>
            <router-link to="/opd" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </router-link>
        </div>

        <form @submit.prevent="saveVisit">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Patient Selection / Registration -->
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Patient Information</h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn" :class="patientMode === 'existing' ? 'btn-primary' : 'btn-outline-primary'"
                                        @click="patientMode = 'existing'">
                                    Existing Patient
                                </button>
                                <button type="button" class="btn" :class="patientMode === 'new' ? 'btn-primary' : 'btn-outline-primary'"
                                        @click="patientMode = 'new'">
                                    New Patient
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Existing Patient Search -->
                            <div v-if="patientMode === 'existing'">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Search Patient <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" v-model="patientSearch"
                                                   placeholder="Enter UHID, name, or mobile number..."
                                                   @keyup.enter="searchPatients">
                                            <button type="button" class="btn btn-outline-primary" @click="searchPatients">
                                                <i class="bi bi-search"></i> Search
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Search Results -->
                                <div v-if="searchResults.length > 0" class="mt-3">
                                    <label class="form-label small">Search Results</label>
                                    <div class="list-group">
                                        <button type="button" v-for="p in searchResults" :key="p.patient_id"
                                                class="list-group-item list-group-item-action"
                                                :class="{'active': form.patient_id === p.patient_id}"
                                                @click="selectPatient(p)">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>{{ p.patient_name }}</strong>
                                                    <span class="badge bg-secondary ms-2">{{ p.pcd }}</span>
                                                </div>
                                                <small>{{ p.mobile }}</small>
                                            </div>
                                            <small class="text-muted">
                                                {{ p.gender }} | {{ p.age }} {{ p.age_unit }} |
                                                {{ p.address || 'No address' }}
                                            </small>
                                        </button>
                                    </div>
                                </div>

                                <!-- Selected Patient Info -->
                                <div v-if="selectedPatient" class="alert alert-success mt-3 mb-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ selectedPatient.patient_name }}</strong>
                                            <span class="badge bg-primary ms-2">{{ selectedPatient.pcd }}</span>
                                            <div class="small mt-1">
                                                <i class="bi bi-phone me-1"></i> {{ selectedPatient.mobile || 'N/A' }} |
                                                <i class="bi bi-gender-ambiguous me-1"></i> {{ selectedPatient.gender }} |
                                                <i class="bi bi-calendar me-1"></i> {{ selectedPatient.age }} {{ selectedPatient.age_unit }}
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="clearPatient">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- New Patient Form -->
                            <div v-if="patientMode === 'new'">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Patient Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" v-model="newPatient.patient_name" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Gender <span class="text-danger">*</span></label>
                                        <select class="form-select" v-model="newPatient.gender" required>
                                            <option value="">Select</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Mobile</label>
                                        <input type="tel" class="form-control" v-model="newPatient.mobile"
                                               placeholder="10 digit mobile">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Age <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" v-model="newPatient.age" min="0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Unit</label>
                                        <select class="form-select" v-model="newPatient.age_unit">
                                            <option value="years">Years</option>
                                            <option value="months">Months</option>
                                            <option value="days">Days</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Guardian Name</label>
                                        <input type="text" class="form-control" v-model="newPatient.guardian_name">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Aadhaar (Optional)</label>
                                        <input type="text" class="form-control" v-model="newPatient.aadhar_number"
                                               placeholder="12 digit Aadhaar">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" v-model="newPatient.address">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" v-model="newPatient.city">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">PIN Code</label>
                                        <input type="text" class="form-control" v-model="newPatient.pincode">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Visit Details -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-clipboard2-pulse me-2"></i>Visit Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Registration Purpose <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.registration_purpose" required>
                                        <option value="normal">Normal OPD</option>
                                        <option value="direct">Direct Consultation</option>
                                        <option value="health_checkup">Health Checkup</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Visit Type <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.visit_type" required>
                                        <option value="new">New Visit</option>
                                        <option value="followup">Follow-up</option>
                                        <option value="referral">Referral</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Patient Class</label>
                                    <select class="form-select" v-model="form.class_id">
                                        <option value="">Select Class</option>
                                        <option v-for="c in patientClasses" :key="c.class_id" :value="c.class_id">
                                            {{ c.class_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" v-model="form.department_id" @change="onDepartmentChange">
                                        <option value="">Select Department</option>
                                        <option v-for="d in departments" :key="d.department_id" :value="d.department_id">
                                            {{ d.department_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Doctor</label>
                                    <select class="form-select" v-model="form.doctor_id" @change="checkFreeFollowup">
                                        <option value="">Select Doctor</option>
                                        <option v-for="d in filteredDoctors" :key="d.doctor_id" :value="d.doctor_id">
                                            {{ d.full_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reference Doctor</label>
                                    <select class="form-select" v-model="form.reference_doctor_id">
                                        <option value="">No Reference</option>
                                        <option v-for="r in referenceDoctors" :key="r.reference_doctor_id" :value="r.reference_doctor_id">
                                            {{ r.doctor_name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Free Follow-up Alert -->
                            <div v-if="isFreeFollowup" class="alert alert-success mt-3 mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                <strong>Free Follow-up!</strong> This patient is eligible for a free follow-up visit.
                                Last visit: {{ lastVisitDate }}
                            </div>

                            <!-- Health Package Selection -->
                            <div v-if="form.registration_purpose === 'health_checkup'" class="mt-3">
                                <label class="form-label">Health Package <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.health_package_id" required>
                                    <option value="">Select Package</option>
                                    <option v-for="p in healthPackages" :key="p.package_id" :value="p.package_id">
                                        {{ p.package_name }} - {{ formatCurrency(p.package_rate) }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- MLC / Insurance -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>MLC / Insurance</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" v-model="form.is_mlc" id="isMlc">
                                        <label class="form-check-label" for="isMlc">
                                            <strong>Medico-Legal Case (MLC)</strong>
                                        </label>
                                    </div>
                                    <div v-if="form.is_mlc" class="mt-2">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <input type="text" class="form-control form-control-sm"
                                                       v-model="form.mlc_number" placeholder="MLC Number">
                                            </div>
                                            <div class="col-6">
                                                <input type="text" class="form-control form-control-sm"
                                                       v-model="form.police_station" placeholder="Police Station">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" v-model="form.is_insurance" id="isInsurance">
                                        <label class="form-check-label" for="isInsurance">
                                            <strong>Insurance / TPA</strong>
                                        </label>
                                    </div>
                                    <div v-if="form.is_insurance" class="mt-2">
                                        <input type="text" class="form-control form-control-sm"
                                               v-model="form.insurance_company_name" placeholder="Insurance Company Name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chief Complaints -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-chat-text me-2"></i>Chief Complaints</h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" v-model="form.chief_complaints" rows="3"
                                      placeholder="Enter patient's chief complaints..."></textarea>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-2 mb-4">
                        <button type="submit" class="btn btn-primary" :disabled="saving || !canSubmit">
                            <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                            <i v-else class="bi bi-check-lg me-1"></i>
                            {{ isEdit ? 'Update Visit' : 'Register & Generate Token' }}
                        </button>
                        <button type="button" class="btn btn-success" @click="saveAndPrint" :disabled="saving || !canSubmit">
                            <i class="bi bi-printer me-1"></i> Register & Print
                        </button>
                        <router-link to="/opd" class="btn btn-light">Cancel</router-link>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Billing Summary -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-receipt me-2"></i>Billing Summary</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td>Consultation Fee</td>
                                        <td class="text-end">{{ formatCurrency(consultationFee) }}</td>
                                    </tr>
                                    <tr v-if="isFreeFollowup">
                                        <td class="text-success">Free Follow-up Discount</td>
                                        <td class="text-end text-success">-{{ formatCurrency(consultationFee) }}</td>
                                    </tr>
                                    <tr v-if="form.health_package_id">
                                        <td>Health Package</td>
                                        <td class="text-end">{{ formatCurrency(packageAmount) }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <th>Total Amount</th>
                                        <th class="text-end">{{ formatCurrency(totalAmount) }}</th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Patient History (if existing patient) -->
                    <div class="card mb-3" v-if="selectedPatient && patientHistory.length > 0">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Visits</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div v-for="visit in patientHistory" :key="visit.opd_id" class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ visit.opd_number }}</strong>
                                        <small class="text-muted">{{ formatDate(visit.visit_date) }}</small>
                                    </div>
                                    <small>
                                        {{ visit.doctor?.full_name || 'No doctor' }} |
                                        <span class="badge" :class="visit.status === 'completed' ? 'bg-success' : 'bg-secondary'">
                                            {{ visit.status }}
                                        </span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Quick Tips</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-2">
                                    <i class="bi bi-check text-success me-1"></i>
                                    Search existing patient by UHID or mobile
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check text-success me-1"></i>
                                    New patients get auto-generated UHID
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-check text-success me-1"></i>
                                    Follow-up within validity period is free
                                </li>
                                <li>
                                    <i class="bi bi-check text-success me-1"></i>
                                    Token number is auto-generated
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);

// Data
const departments = ref([]);
const doctors = ref([]);
const patientClasses = ref([]);
const referenceDoctors = ref([]);
const healthPackages = ref([]);
const searchResults = ref([]);
const patientHistory = ref([]);
const saving = ref(false);
const patientMode = ref('existing');
const patientSearch = ref('');
const selectedPatient = ref(null);
const isFreeFollowup = ref(false);
const lastVisitDate = ref('');

// New patient form
const newPatient = reactive({
    patient_name: '',
    gender: '',
    mobile: '',
    age: '',
    age_unit: 'years',
    guardian_name: '',
    aadhar_number: '',
    address: '',
    city: '',
    pincode: ''
});

// Visit form
const form = reactive({
    patient_id: null,
    registration_purpose: 'normal',
    visit_type: 'new',
    department_id: '',
    doctor_id: '',
    reference_doctor_id: '',
    class_id: '',
    health_package_id: '',
    is_mlc: false,
    mlc_number: '',
    police_station: '',
    is_insurance: false,
    insurance_company_name: '',
    chief_complaints: ''
});

// Computed
const canSubmit = computed(() => {
    if (patientMode.value === 'existing') {
        return !!form.patient_id;
    }
    return newPatient.patient_name && newPatient.gender && newPatient.age;
});

const filteredDoctors = computed(() => {
    if (!form.department_id) return doctors.value;
    return doctors.value.filter(d => d.department_id === parseInt(form.department_id));
});

const consultationFee = computed(() => {
    if (!form.doctor_id) return 0;
    const doctor = doctors.value.find(d => d.doctor_id === parseInt(form.doctor_id));
    return doctor?.consultation_fee || 0;
});

const packageAmount = computed(() => {
    if (!form.health_package_id) return 0;
    const pkg = healthPackages.value.find(p => p.package_id === parseInt(form.health_package_id));
    return pkg?.package_rate || 0;
});

const totalAmount = computed(() => {
    let total = 0;

    if (form.registration_purpose === 'health_checkup') {
        total = packageAmount.value;
    } else {
        total = isFreeFollowup.value ? 0 : consultationFee.value;
    }

    return total;
});

// Methods
const fetchData = async () => {
    try {
        const [deptRes, docRes, classRes, refDocRes, pkgRes] = await Promise.all([
            axios.get('/api/departments'),
            axios.get('/api/doctors'),
            axios.get('/api/patient-classes'),
            axios.get('/api/reference-doctors'),
            axios.get('/api/health-packages')
        ]);

        departments.value = deptRes.data.data || deptRes.data;
        doctors.value = docRes.data.data || docRes.data;
        patientClasses.value = classRes.data.data || classRes.data;
        referenceDoctors.value = refDocRes.data.data || refDocRes.data;
        healthPackages.value = pkgRes.data.data || pkgRes.data;

        // Load existing visit if editing
        if (route.params.id) {
            const response = await axios.get(`/api/opd-visits/${route.params.id}`);
            const visit = response.data;
            Object.assign(form, {
                patient_id: visit.patient_id,
                registration_purpose: visit.registration_purpose,
                visit_type: visit.visit_type,
                department_id: visit.department_id || '',
                doctor_id: visit.doctor_id || '',
                reference_doctor_id: visit.reference_doctor_id || '',
                class_id: visit.class_id || '',
                health_package_id: visit.health_package_id || '',
                is_mlc: visit.is_mlc,
                mlc_number: visit.mlc_number || '',
                police_station: visit.police_station || '',
                is_insurance: visit.is_insurance,
                insurance_company_name: visit.insurance_company_name || '',
                chief_complaints: visit.chief_complaints || ''
            });
            selectedPatient.value = visit.patient;
            patientMode.value = 'existing';
        }
    } catch (error) {
        console.error('Error loading data:', error);
    }
};

const searchPatients = async () => {
    if (!patientSearch.value.trim()) return;

    try {
        const response = await axios.get('/api/patients-search', {
            params: { search: patientSearch.value }
        });
        searchResults.value = response.data.data || response.data;
    } catch (error) {
        console.error('Error searching patients:', error);
    }
};

const selectPatient = async (patient) => {
    selectedPatient.value = patient;
    form.patient_id = patient.patient_id;
    form.class_id = patient.class_id || '';
    form.reference_doctor_id = patient.reference_doctor_id || '';
    searchResults.value = [];

    // Fetch patient history
    try {
        const response = await axios.get(`/api/opd-visits/patient/${patient.patient_id}/history`);
        patientHistory.value = response.data;
    } catch (error) {
        console.error('Error fetching history:', error);
    }

    // Check for free followup
    checkFreeFollowup();
};

const clearPatient = () => {
    selectedPatient.value = null;
    form.patient_id = null;
    patientHistory.value = [];
    isFreeFollowup.value = false;
};

const onDepartmentChange = () => {
    // Reset doctor if not in selected department
    if (form.doctor_id) {
        const doctor = doctors.value.find(d => d.doctor_id === parseInt(form.doctor_id));
        if (doctor && form.department_id && doctor.department_id !== parseInt(form.department_id)) {
            form.doctor_id = '';
        }
    }
};

const checkFreeFollowup = async () => {
    isFreeFollowup.value = false;
    lastVisitDate.value = '';

    if (!form.patient_id || !form.doctor_id || form.visit_type === 'new') return;

    // Check from patient history
    const lastVisit = patientHistory.value.find(v =>
        v.doctor_id === parseInt(form.doctor_id) && v.status === 'completed'
    );

    if (lastVisit) {
        const visitDate = new Date(lastVisit.visit_date);
        const today = new Date();
        const daysDiff = Math.floor((today - visitDate) / (1000 * 60 * 60 * 24));

        // Assume 7 days validity for free followup (this should come from backend)
        if (daysDiff <= 7) {
            isFreeFollowup.value = true;
            lastVisitDate.value = formatDate(lastVisit.visit_date);
        }
    }
};

const saveVisit = async (printAfter = false) => {
    saving.value = true;
    try {
        const payload = { ...form };

        // Add new patient data if creating new patient
        if (patientMode.value === 'new') {
            payload.patient = { ...newPatient };
            delete payload.patient_id;
        }

        // Clean empty values
        Object.keys(payload).forEach(k => {
            if (payload[k] === '' || payload[k] === null) {
                delete payload[k];
            }
        });

        let response;
        if (isEdit.value) {
            response = await axios.put(`/api/opd-visits/${route.params.id}`, payload);
        } else {
            response = await axios.post('/api/opd-visits', payload);
        }

        if (printAfter) {
            // Open print window
            window.open(`/opd/${response.data.opd_id}/print`, '_blank');
        }

        router.push('/opd');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving visit');
    }
    saving.value = false;
};

const saveAndPrint = () => {
    saveVisit(true);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 0
    }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Watch visit type to check free followup
watch(() => form.visit_type, () => {
    checkFreeFollowup();
});

onMounted(fetchData);
</script>
