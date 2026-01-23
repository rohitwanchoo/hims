<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>
                <i class="bi bi-radioactive me-2"></i>
                {{ isEdit ? 'Edit Radiology Order' : (isView ? 'View Radiology Order' : 'New Radiology Order') }}
            </h4>
            <router-link to="/radiology/orders" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </router-link>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient *</label>
                            <select v-model="form.patient_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Patient</option>
                                <option v-for="patient in patients" :key="patient?.patient_id" :value="patient?.patient_id" v-if="patient && patient.patient_id">
                                    {{ patient.patient_name }} ({{ patient.uhid || patient.pcd }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referring Doctor</label>
                            <select v-model="form.referring_doctor_id" class="form-select" :disabled="isView">
                                <option value="">Select Doctor</option>
                                <option v-for="doctor in doctors" :key="doctor?.doctor_id" :value="doctor?.doctor_id" v-if="doctor && doctor.doctor_id">
                                    {{ doctor.full_name || doctor.doctor_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Priority *</label>
                        <select v-model="form.priority" class="form-select" :disabled="isView" required>
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="stat">STAT</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Clinical Indication</label>
                        <textarea v-model="form.clinical_indication" class="form-control" rows="2" :disabled="isView" placeholder="Clinical reason for the test"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea v-model="form.notes" class="form-control" rows="2" :disabled="isView" placeholder="Additional notes"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tests</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Special Instructions</th>
                                        <th width="100" v-if="!isView">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(test, index) in form.tests" :key="index">
                                        <td>
                                            <select v-model="test.radiology_test_id" class="form-select form-select-sm" :disabled="isView">
                                                <option value="">Select Test</option>
                                                <option v-for="t in availableTests" :key="t?.radiology_test_id" :value="t?.radiology_test_id" v-if="t && t.radiology_test_id">
                                                    {{ t.test_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" v-model="test.special_instructions" class="form-control form-control-sm" :disabled="isView" placeholder="e.g., With contrast, specific positioning">
                                        </td>
                                        <td v-if="!isView">
                                            <button type="button" @click="removeTest(index)" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button v-if="!isView" type="button" @click="addTest" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Test
                        </button>
                    </div>

                    <div v-if="isView && order" class="mb-3">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge" :class="getStatusClass(order.status)">{{ order.status }}</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2" v-if="!isView">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEdit ? 'Update Order' : 'Create Order' }}
                        </button>
                        <router-link to="/radiology/orders" class="btn btn-outline-secondary">Cancel</router-link>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const order = ref(null);
const patients = ref([]);
const doctors = ref([]);
const modalities = ref([]);
const availableTests = ref([]);
const loading = ref(false);

const form = ref({
    patient_id: '',
    referring_doctor_id: '',
    priority: 'routine',
    clinical_indication: '',
    notes: '',
    tests: [{ radiology_test_id: '', special_instructions: '' }]
});

const isEdit = computed(() => route.name === 'radiology.orders.edit');
const isView = computed(() => route.name === 'radiology.orders.view');

const loadOrder = async () => {
    if (!route.params.id) return;
    try {
        const response = await axios.get(`/api/radiology/orders/${route.params.id}`);
        order.value = response.data.order || response.data;
        form.value = {
            patient_id: order.value.patient_id,
            referring_doctor_id: order.value.referring_doctor_id || '',
            priority: order.value.priority || 'routine',
            clinical_indication: order.value.clinical_indication || '',
            notes: order.value.notes || '',
            tests: order.value.order_details?.map(d => ({
                radiology_test_id: d.radiology_test_id,
                special_instructions: d.special_instructions || ''
            })) || []
        };
    } catch (error) {
        console.error('Failed to load order:', error);
        alert('Failed to load order details: ' + (error.response?.data?.message || error.message));
    }
};

const loadData = async () => {
    try {
        const [patientsRes, doctorsRes, modalitiesRes, testsRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/doctors'),
            axios.get('/api/radiology/modalities'),
            axios.get('/api/radiology/tests')
        ]);

        // Ensure arrays and filter out null/undefined values
        const patientsData = patientsRes.data.data || patientsRes.data || [];
        patients.value = (Array.isArray(patientsData) ? patientsData : []).filter(p => p && p.patient_id);

        const doctorsData = Array.isArray(doctorsRes.data) ? doctorsRes.data : (doctorsRes.data.data || []);
        doctors.value = (Array.isArray(doctorsData) ? doctorsData : []).filter(d => d && d.doctor_id);

        const modalitiesData = modalitiesRes.data.modalities || modalitiesRes.data.data || modalitiesRes.data || [];
        modalities.value = (Array.isArray(modalitiesData) ? modalitiesData : []).filter(m => m && m.modality_id);

        const testsData = testsRes.data.tests || testsRes.data.data || testsRes.data || [];
        availableTests.value = (Array.isArray(testsData) ? testsData : []).filter(t => t && t.radiology_test_id);
    } catch (error) {
        console.error('Failed to load data:', error);
        if (error.response?.status === 401) {
            alert('Session expired. Please login again.');
        } else if (error.response?.status === 404) {
            alert('Some required data endpoints are not available. Please contact your administrator.');
        } else {
            alert('Failed to load form data: ' + (error.response?.data?.message || error.message));
        }
    }
};

const addTest = () => {
    form.value.tests.push({ radiology_test_id: '', special_instructions: '' });
};

const removeTest = (index) => {
    form.value.tests.splice(index, 1);
};

const getStatusClass = (status) => {
    const classes = {
        'ordered': 'bg-secondary',
        'scheduled': 'bg-info',
        'in_progress': 'bg-warning',
        'completed': 'bg-success',
        'cancelled': 'bg-danger'
    };
    return classes[status] || 'bg-secondary';
};

const submitForm = async () => {
    loading.value = true;
    try {
        const payload = {
            ...form.value,
            tests: form.value.tests.filter(t => t.radiology_test_id)
        };

        if (isEdit.value) {
            await axios.put(`/api/radiology/orders/${route.params.id}`, payload);
        } else {
            await axios.post('/api/radiology/orders', payload);
        }
        router.push('/radiology/orders');
    } catch (error) {
        console.error('Failed to save order:', error);
        const errorMessage = error.response?.data?.message || error.message || 'Failed to save order';
        const validationErrors = error.response?.data?.errors;
        if (validationErrors) {
            const errorList = Object.values(validationErrors).flat().join('\n');
            alert(`Validation errors:\n${errorList}`);
        } else {
            alert(errorMessage);
        }
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadData();
    if (route.params.id) {
        loadOrder();
    }
});
</script>
