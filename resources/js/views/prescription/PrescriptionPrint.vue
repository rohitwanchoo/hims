<template>
    <div class="prescription-print">
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary"></div>
            <p>Loading prescription...</p>
        </div>

        <div v-else-if="prescription" class="print-container">
            <!-- Hospital Header -->
            <div class="hospital-header text-center mb-4">
                <h2 class="mb-1">{{ hospital.name || 'Hospital Name' }}</h2>
                <p class="mb-0" v-if="hospital.address">{{ hospital.address }}</p>
                <p class="mb-0" v-if="hospital.phone">Phone: {{ hospital.phone }}</p>
                <p class="mb-0" v-if="hospital.email">Email: {{ hospital.email }}</p>
                <hr class="my-3">
                <h4>PRESCRIPTION</h4>
            </div>

            <!-- Patient Info -->
            <div class="patient-info mb-4">
                <div class="row">
                    <div class="col-6">
                        <p><strong>Patient Name:</strong> {{ patient?.patient_name }}</p>
                        <p><strong>Patient ID:</strong> {{ patient?.pcd }}</p>
                        <p v-if="patient?.mobile_number"><strong>Mobile:</strong> {{ patient.mobile_number }}</p>
                    </div>
                    <div class="col-6 text-end">
                        <p v-if="patient?.age"><strong>Age/Gender:</strong> {{ patient.age }} {{ patient.age_unit || 'years' }} / {{ patient.gender }}</p>
                        <p><strong>Date:</strong> {{ formatDate(prescription.prescription_date) }}</p>
                        <p v-if="doctor"><strong>Doctor:</strong> Dr. {{ doctor.full_name }}</p>
                    </div>
                </div>
            </div>

            <!-- Prescription Medicines Table -->
            <div class="medicines-section mb-4">
                <h5 class="mb-3">Prescribed Medicines</h5>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">Sr.</th>
                            <th width="30%">Medicine Name</th>
                            <th width="15%">Type</th>
                            <th width="25%">Dose Advice</th>
                            <th width="10%">Days</th>
                            <th width="15%" v-if="prescription.qty_display_on_print">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(drug, index) in prescription.drugs" :key="drug.prescription_drug_id">
                            <td>{{ index + 1 }}</td>
                            <td><strong>{{ drug.drug_name }}</strong></td>
                            <td>{{ drug.drug_type || '-' }}</td>
                            <td>{{ drug.dose_advice || '-' }}</td>
                            <td>{{ drug.days || '-' }}</td>
                            <td v-if="prescription.qty_display_on_print">{{ drug.qty || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Advice -->
            <div v-if="prescription.advice" class="advice-section mb-4">
                <h5>Advice:</h5>
                <p style="white-space: pre-line;">{{ prescription.advice }}</p>
            </div>

            <!-- Investigations -->
            <div v-if="prescription.investigations" class="investigations-section mb-4">
                <h5>Investigations:</h5>
                <p style="white-space: pre-line;">{{ prescription.investigations }}</p>
            </div>

            <!-- Consultation Data -->
            <div v-if="showConsultation && consultationData" class="consultation-section mb-4">
                <h5>Consultation Details:</h5>
                <div class="consultation-data">
                    <template v-for="field in consultationForm?.fields" :key="field.field_id">
                        <div v-if="consultationData.form_data?.[field.field_key] && consultationData.form_data[field.field_key] !== ''" class="consultation-field mb-2">
                            <strong>{{ field.field_label }}:</strong>
                            <span class="ms-2">{{ formatFieldValue(consultationData.form_data[field.field_key], field.field_type) }}</span>
                        </div>
                    </template>
                    <div v-if="consultationData.notes" class="consultation-field mb-2">
                        <strong>Notes:</strong>
                        <p class="ms-2 mb-0" style="white-space: pre-line;">{{ consultationData.notes }}</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer-section mt-5">
                <div class="row">
                    <div class="col-6">
                        <p class="small text-muted">Generated on: {{ new Date().toLocaleString() }}</p>
                    </div>
                    <div class="col-6 text-end">
                        <p class="mb-0">_________________________</p>
                        <p class="small">Doctor's Signature</p>
                    </div>
                </div>
            </div>

            <!-- Print Button (hide when printing) -->
            <div class="text-center mt-4 no-print">
                <button class="btn btn-primary btn-lg" @click="printPage">
                    <i class="bi bi-printer me-2"></i> Print Prescription
                </button>
                <button class="btn btn-secondary btn-lg ms-2" @click="closePage">
                    <i class="bi bi-x-lg me-2"></i> Close
                </button>
            </div>
        </div>

        <div v-else class="text-center py-5">
            <p class="text-danger">Prescription not found</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const prescriptionId = ref(route.params.id);
const showConsultation = ref(route.query.consultation === '1');

const loading = ref(true);
const prescription = ref(null);
const patient = ref(null);
const doctor = ref(null);
const hospital = ref({});
const consultationData = ref(null);
const consultationForm = ref(null);

const fetchPrescription = async () => {
    try {
        const response = await axios.get(`/api/prescriptions/${prescriptionId.value}`);
        prescription.value = response.data;
        patient.value = response.data.patient;
        doctor.value = response.data.doctor;

        // Fetch hospital info
        const user = await axios.get('/api/user');
        hospital.value = user.data.hospital || {};

        // Fetch consultation data if enabled
        if (showConsultation.value && patient.value?.patient_id) {
            await fetchConsultationData(patient.value.patient_id);
        }
    } catch (error) {
        console.error('Error fetching prescription:', error);
    } finally {
        loading.value = false;
    }
};

const fetchConsultationData = async (patientId) => {
    try {
        const response = await axios.get(`/api/consultation-records/last/${patientId}`);
        if (response.data.success && response.data.data) {
            consultationData.value = response.data.data;
            consultationForm.value = response.data.data.form;
        }
    } catch (error) {
        console.error('Error fetching consultation data:', error);
    }
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatFieldValue = (value, fieldType) => {
    if (!value) return '';

    if (fieldType === 'checkbox' && Array.isArray(value)) {
        return value.join(', ');
    }

    if (fieldType === 'date' || fieldType === 'datetime') {
        return formatDate(value);
    }

    return value;
};

const printPage = () => {
    window.print();
};

const closePage = () => {
    window.close();
};

onMounted(() => {
    fetchPrescription();
});
</script>

<style scoped>
.prescription-print {
    padding: 20px;
    max-width: 900px;
    margin: 0 auto;
    background: white;
}

.print-container {
    font-family: Arial, sans-serif;
}

.hospital-header h2 {
    color: #2c3e50;
    font-weight: bold;
}

.table {
    font-size: 14px;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.advice-section,
.investigations-section,
.consultation-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.advice-section h5,
.investigations-section h5,
.consultation-section h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 10px;
}

.consultation-field {
    padding: 5px 0;
    border-bottom: 1px solid #e0e0e0;
}

.consultation-field:last-child {
    border-bottom: none;
}

@media print {
    .no-print {
        display: none !important;
    }

    .prescription-print {
        padding: 0;
    }

    body {
        background: white;
    }

    @page {
        margin: 1cm;
    }
}
</style>
