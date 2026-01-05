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
                                <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                    {{ patient.patient_name }} ({{ patient.uhid }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referring Doctor</label>
                            <select v-model="form.referring_doctor_id" class="form-select" :disabled="isView">
                                <option value="">Select Doctor</option>
                                <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                    {{ doctor.full_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Priority *</label>
                            <select v-model="form.priority" class="form-select" :disabled="isView" required>
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modality</label>
                            <select v-model="form.modality_id" class="form-select" :disabled="isView">
                                <option value="">Select Modality</option>
                                <option v-for="modality in modalities" :key="modality.modality_id" :value="modality.modality_id">
                                    {{ modality.modality_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Clinical Notes</label>
                        <textarea v-model="form.clinical_notes" class="form-control" rows="3" :disabled="isView"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tests</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Body Part</th>
                                        <th width="100" v-if="!isView">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(test, index) in form.tests" :key="index">
                                        <td>
                                            <select v-model="test.test_id" class="form-select form-select-sm" :disabled="isView">
                                                <option value="">Select Test</option>
                                                <option v-for="t in availableTests" :key="t.test_id" :value="t.test_id">
                                                    {{ t.test_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" v-model="test.body_part" class="form-control form-control-sm" :disabled="isView">
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
    modality_id: '',
    priority: 'routine',
    clinical_notes: '',
    tests: [{ test_id: '', body_part: '' }]
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
            modality_id: order.value.modality_id || '',
            priority: order.value.priority,
            clinical_notes: order.value.clinical_notes || '',
            tests: order.value.order_details?.map(d => ({
                test_id: d.test_id,
                body_part: d.body_part || ''
            })) || []
        };
    } catch (error) {
        console.error('Failed to load order:', error);
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
        patients.value = patientsRes.data.data || patientsRes.data || [];
        doctors.value = Array.isArray(doctorsRes.data) ? doctorsRes.data : (doctorsRes.data.data || []);
        modalities.value = modalitiesRes.data.modalities || modalitiesRes.data.data || modalitiesRes.data || [];
        availableTests.value = testsRes.data.tests || testsRes.data.data || testsRes.data || [];
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const addTest = () => {
    form.value.tests.push({ test_id: '', body_part: '' });
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
            tests: form.value.tests.filter(t => t.test_id)
        };

        if (isEdit.value) {
            await axios.put(`/api/radiology/orders/${route.params.id}`, payload);
        } else {
            await axios.post('/api/radiology/orders', payload);
        }
        router.push('/radiology/orders');
    } catch (error) {
        console.error('Failed to save order:', error);
        alert(error.response?.data?.message || 'Failed to save order');
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
