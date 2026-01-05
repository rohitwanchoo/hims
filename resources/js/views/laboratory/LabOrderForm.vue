<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>
                <i class="bi bi-flask me-2"></i>
                {{ isEdit ? 'Edit Lab Order' : 'New Lab Order' }}
            </h4>
            <router-link to="/laboratory/orders" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </router-link>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Patient *</label>
                            <select v-model="form.patient_id" class="form-select" required>
                                <option value="">Select Patient</option>
                                <option v-for="patient in patients" :key="patient.patient_id" :value="patient.patient_id">
                                    {{ patient.pcd }} - {{ patient.patient_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referring Doctor</label>
                            <select v-model="form.doctor_id" class="form-select">
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
                            <select v-model="form.priority" class="form-select" required>
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Order Date *</label>
                            <input type="date" v-model="form.order_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Clinical Notes</label>
                        <textarea v-model="form.clinical_notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tests *</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Test</th>
                                        <th>Rate</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.tests" :key="index">
                                        <td>
                                            <select v-model="item.test_id" class="form-select form-select-sm" @change="updateTestRate(index)">
                                                <option value="">Select Test</option>
                                                <option v-for="test in availableTests" :key="test.test_id" :value="test.test_id">
                                                    {{ test.test_name }} ({{ test.test_code }})
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" v-model="item.rate" class="form-control form-control-sm" step="0.01" readonly>
                                        </td>
                                        <td>
                                            <button type="button" @click="removeTest(index)" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td class="text-end fw-bold">Total:</td>
                                        <td class="fw-bold">{{ totalAmount.toFixed(2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <button type="button" @click="addTest" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Test
                        </button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            {{ isEdit ? 'Update Order' : 'Create Order' }}
                        </button>
                        <router-link to="/laboratory/orders" class="btn btn-outline-secondary">Cancel</router-link>
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

const patients = ref([]);
const doctors = ref([]);
const availableTests = ref([]);
const loading = ref(false);

const form = ref({
    patient_id: '',
    doctor_id: '',
    priority: 'routine',
    order_date: new Date().toISOString().split('T')[0],
    clinical_notes: '',
    tests: [{ test_id: '', rate: 0 }]
});

const isEdit = computed(() => !!route.params.id);

const totalAmount = computed(() => {
    return form.value.tests.reduce((sum, item) => sum + (parseFloat(item.rate) || 0), 0);
});

const loadData = async () => {
    try {
        const [patientsRes, doctorsRes, testsRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/doctors'),
            axios.get('/api/lab-tests')
        ]);
        patients.value = patientsRes.data.data || patientsRes.data || [];
        doctors.value = Array.isArray(doctorsRes.data) ? doctorsRes.data : (doctorsRes.data.data || []);
        availableTests.value = Array.isArray(testsRes.data) ? testsRes.data : (testsRes.data.data || []);
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const loadOrder = async () => {
    if (!route.params.id) return;
    try {
        const response = await axios.get(`/api/lab-orders/${route.params.id}`);
        const order = response.data.data || response.data;
        form.value = {
            patient_id: order.patient_id,
            doctor_id: order.doctor_id || '',
            priority: order.priority || 'routine',
            order_date: order.order_date?.split('T')[0] || order.order_date,
            clinical_notes: order.clinical_notes || '',
            tests: order.details?.map(d => ({
                test_id: d.test_id,
                rate: d.rate || 0
            })) || [{ test_id: '', rate: 0 }]
        };
    } catch (error) {
        console.error('Failed to load order:', error);
    }
};

const addTest = () => {
    form.value.tests.push({ test_id: '', rate: 0 });
};

const removeTest = (index) => {
    if (form.value.tests.length > 1) {
        form.value.tests.splice(index, 1);
    }
};

const updateTestRate = (index) => {
    const testId = form.value.tests[index].test_id;
    const test = availableTests.value.find(t => t.test_id === testId);
    form.value.tests[index].rate = test ? parseFloat(test.rate) : 0;
};

const submitForm = async () => {
    loading.value = true;
    try {
        const payload = {
            patient_id: form.value.patient_id,
            doctor_id: form.value.doctor_id || null,
            priority: form.value.priority,
            order_date: form.value.order_date,
            clinical_notes: form.value.clinical_notes,
            total_amount: totalAmount.value,
            tests: form.value.tests.filter(t => t.test_id).map(t => ({
                test_id: t.test_id,
                rate: t.rate
            }))
        };

        if (isEdit.value) {
            await axios.put(`/api/lab-orders/${route.params.id}`, payload);
        } else {
            await axios.post('/api/lab-orders', payload);
        }
        router.push('/laboratory/orders');
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
