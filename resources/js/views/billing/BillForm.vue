<template>
    <div>
        <h4 class="mb-4">{{ $route.params.id ? 'View Bill' : 'New Bill' }}</h4>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Patient Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Patient</label>
                                <select class="form-select" v-model="form.patient_id" :disabled="!!$route.params.id">
                                    <option value="">Select Patient</option>
                                    <option v-for="p in patients" :key="p.patient_id" :value="p.patient_id">
                                        {{ p.patient_code }} - {{ p.first_name }} {{ p.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bill Date</label>
                                <input type="date" class="form-control" v-model="form.bill_date" :disabled="!!$route.params.id">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Bill Items</h6>
                        <button class="btn btn-sm btn-primary" @click="addItem" v-if="!$route.params.id">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Service/Item</th>
                                    <th width="100">Qty</th>
                                    <th width="120">Rate</th>
                                    <th width="120">Amount</th>
                                    <th width="50" v-if="!$route.params.id"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td>
                                        <select class="form-select form-select-sm" v-model="item.service_id" @change="updateItemRate(item)" :disabled="!!$route.params.id">
                                            <option value="">Select Service</option>
                                            <option v-for="s in services" :key="s.service_id" :value="s.service_id">
                                                {{ s.service_name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model="item.quantity" @input="calculateAmount(item)" min="1" :disabled="!!$route.params.id">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model="item.rate" @input="calculateAmount(item)" :disabled="!!$route.params.id">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model="item.amount" readonly>
                                    </td>
                                    <td v-if="!$route.params.id">
                                        <button class="btn btn-sm btn-outline-danger" @click="removeItem(index)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Bill Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span>{{ formatCurrency(subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Discount:</span>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <input type="number" class="form-control" v-model="form.discount_amount" :disabled="!!$route.params.id">
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong>{{ formatCurrency(total) }}</strong>
                        </div>

                        <div class="d-grid gap-2" v-if="!$route.params.id">
                            <button class="btn btn-primary" @click="saveBill" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                Save Bill
                            </button>
                            <router-link to="/billing" class="btn btn-secondary">Cancel</router-link>
                        </div>
                        <div class="d-grid" v-else>
                            <router-link to="/billing" class="btn btn-secondary">Back to List</router-link>
                        </div>
                    </div>
                </div>
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

const loading = ref(false);
const patients = ref([]);
const services = ref([]);

const form = ref({
    patient_id: '',
    bill_date: new Date().toISOString().split('T')[0],
    discount_amount: 0,
    items: [{ service_id: '', quantity: 1, rate: 0, amount: 0 }]
});

const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + (item.amount || 0), 0));
const total = computed(() => subtotal.value - (form.value.discount_amount || 0));

onMounted(async () => {
    const [patientsRes, servicesRes] = await Promise.all([
        axios.get('/api/patients?per_page=1000'),
        axios.get('/api/services')
    ]);
    patients.value = patientsRes.data.data || patientsRes.data;
    services.value = servicesRes.data;

    if (route.params.id) {
        const billRes = await axios.get(`/api/bills/${route.params.id}`);
        const bill = billRes.data;
        form.value = {
            patient_id: bill.patient_id,
            bill_date: bill.bill_date,
            discount_amount: bill.discount_amount,
            items: bill.details || []
        };
    }
});

const addItem = () => {
    form.value.items.push({ service_id: '', quantity: 1, rate: 0, amount: 0 });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

const updateItemRate = (item) => {
    const service = services.value.find(s => s.service_id === item.service_id);
    if (service) {
        item.rate = service.rate;
        calculateAmount(item);
    }
};

const calculateAmount = (item) => {
    item.amount = (item.quantity || 0) * (item.rate || 0);
};

const formatCurrency = (amount) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);

const saveBill = async () => {
    loading.value = true;
    try {
        await axios.post('/api/bills', {
            ...form.value,
            total_amount: total.value
        });
        router.push('/billing');
    } catch (error) {
        alert(error.response?.data?.message || 'Error saving bill');
    } finally {
        loading.value = false;
    }
};
</script>
