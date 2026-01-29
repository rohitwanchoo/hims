<template>
    <div>
        <h4 class="mb-4">{{ getPageTitle() }}</h4>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Patient Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Bill Type</label>
                                <select class="form-select" v-model="form.bill_type" :disabled="isViewMode" @change="onBillTypeChange">
                                    <option value="general">General</option>
                                    <option value="opd">OPD</option>
                                    <option value="ipd">IPD</option>
                                    <option value="pharmacy">Pharmacy</option>
                                    <option value="lab">Laboratory</option>
                                </select>
                            </div>
                            <div class="col-md-6" v-if="form.bill_type === 'ipd'">
                                <label class="form-label">IPD Admission</label>
                                <select class="form-select" v-model="selectedIpdId" :disabled="isViewMode" @change="onIpdAdmissionChange">
                                    <option value="">Select IPD Patient</option>
                                    <option v-for="admission in ipdAdmissions" :key="admission.ipd_id" :value="admission.ipd_id">
                                        {{ admission.ipd_number }} - {{ admission.patient?.first_name }} {{ admission.patient?.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6" v-else>
                                <label class="form-label">Patient</label>
                                <select class="form-select" v-model="form.patient_id" :disabled="isViewMode">
                                    <option value="">Select Patient</option>
                                    <option v-for="p in patients" :key="p?.patient_id" :value="p?.patient_id" v-if="p && p.patient_id">
                                        {{ p.patient_code }} - {{ p.first_name }} {{ p.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Bill Date</label>
                                <input type="date" class="form-control" v-model="form.bill_date" :disabled="isViewMode">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Running Bill Display for IPD -->
                <div class="card mb-3" v-if="form.bill_type === 'ipd' && runningBill">
                    <div class="card-header">
                        <h6 class="mb-0">IPD Running Bill</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Patient:</strong> {{ runningBill.patient?.name }}</p>
                                <p class="mb-1"><strong>IPD Number:</strong> {{ runningBill.patient?.ipd_number }}</p>
                                <p class="mb-1"><strong>Admission Date:</strong> {{ runningBill.patient?.admission_date }}</p>
                                <p class="mb-1"><strong>Length of Stay:</strong> {{ runningBill.patient?.los_days }} days</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Ward:</strong> {{ runningBill.bed_details?.ward }}</p>
                                <p class="mb-1"><strong>Bed:</strong> {{ runningBill.bed_details?.bed }}</p>
                                <p class="mb-1"><strong>Bed Charges/Day:</strong> {{ formatCurrency(runningBill.bed_details?.charges_per_day) }}</p>
                                <p class="mb-1"><strong>Total Bed Charges:</strong> {{ formatCurrency(runningBill.bed_details?.total_charges) }}</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service Type</th>
                                        <th class="text-end">Count</th>
                                        <th class="text-end">Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(summary, type) in runningBill.services_summary" :key="type" v-if="type !== 'bed'">
                                        <td>{{ type.replace('_', ' ').toUpperCase() }}</td>
                                        <td class="text-end">{{ summary.count }}</td>
                                        <td class="text-end">{{ formatCurrency(summary.total) }}</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="2"><strong>Services Total</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.services_total) }}</strong></td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td colspan="2"><strong>Gross Total</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.gross_total) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">Discount</td>
                                        <td class="text-end">{{ formatCurrency(runningBill.billing?.discount) }}</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td colspan="2"><strong>Net Total</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.net_total) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">Advance Paid</td>
                                        <td class="text-end">{{ formatCurrency(runningBill.billing?.advance_paid) }}</td>
                                    </tr>
                                    <tr class="table-warning">
                                        <td colspan="2"><strong>Balance Due</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.balance_due) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Bill Items</h6>
                        <button class="btn btn-sm btn-primary" @click="addItem" v-if="!isViewMode">
                            <i class="bi bi-plus"></i> Add Services
                        </button>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th width="200">Cost Head</th>
                                    <th>Service</th>
                                    <th width="80">Qty</th>
                                    <th width="100">Rate</th>
                                    <th width="100">Amount</th>
                                    <th width="50" v-if="!isViewMode"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td>
                                        <select class="form-select form-select-sm" v-model="item.cost_head_id" :disabled="isViewMode" @change="onCostHeadChange(item, index)">
                                            <option value="">Select Cost Head</option>
                                            <option v-for="ch in costHeads" :key="ch.cost_head_id" :value="ch.cost_head_id">
                                                {{ ch.cost_head_name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" v-model="item.service_id" :disabled="!!$route.params.id || !item.cost_head_id" @change="onServiceChange(item)">
                                            <option value="">Select Service</option>
                                            <option v-for="svc in getFilteredServices(item.cost_head_id)" :key="svc.hospital_service_id" :value="svc.hospital_service_id">
                                                {{ svc.service_name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model.number="item.quantity" @input="calculateAmount(item)" min="1" :disabled="isViewMode">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model.number="item.unit_price" @input="calculateAmount(item)" :disabled="isViewMode">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm" v-model.number="item.amount" readonly>
                                    </td>
                                    <td v-if="!isViewMode">
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
                                <input type="number" class="form-control" v-model.number="form.discount_amount" :disabled="isViewMode" step="0.01">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <input type="number" class="form-control" v-model.number="form.tax_amount" :disabled="isViewMode" step="0.01">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Adjustment:</span>
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <input type="number" class="form-control" v-model.number="form.adjustment" :disabled="isViewMode" step="0.01">
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-primary">{{ formatCurrency(total) }}</strong>
                        </div>

                        <div class="d-grid gap-2" v-if="!$route.params.id">
                            <button class="btn btn-primary" @click="saveBill" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                Save Bill
                            </button>
                            <router-link to="/billing" class="btn btn-secondary">Cancel</router-link>
                        </div>
                        <div class="d-grid gap-2" v-else-if="editMode">
                            <button class="btn btn-success" @click="updateBill" :disabled="loading">
                                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                                Save Changes
                            </button>
                            <button class="btn btn-secondary" @click="cancelEdit">Cancel</button>
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
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const loading = ref(false);
const patients = ref([]);
const allServices = ref([]);
const ipdAdmissions = ref([]);
const selectedIpdId = ref('');
const runningBill = ref(null);
const ipdServices = ref([]);
const costHeads = ref([]);
const editMode = ref(false);
const originalFormData = ref(null);
const canEdit = ref(false);

const form = ref({
    patient_id: '',
    ipd_id: '',
    bill_date: new Date().toISOString().split('T')[0],
    bill_type: 'general',
    discount_amount: 0,
    discount_percent: 0,
    tax_amount: 0,
    adjustment: 0,
    items: [{ cost_head_id: '', service_id: '', item_name: '', quantity: 1, unit_price: 0, amount: 0 }]
});

const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + (item.amount || 0), 0));
const total = computed(() => {
    const sub = subtotal.value;
    const discount = form.value.discount_amount || 0;
    const tax = form.value.tax_amount || 0;
    const adj = form.value.adjustment || 0;
    return sub - discount + tax + adj;
});

const isViewMode = computed(() => !!route.params.id && !editMode.value);

const getPageTitle = () => {
    if (!route.params.id) return 'New Bill';
    return editMode.value ? 'Edit Bill' : 'View Bill';
};

const enableEditMode = () => {
    originalFormData.value = JSON.parse(JSON.stringify(form.value));
    editMode.value = true;
};

const cancelEdit = () => {
    if (originalFormData.value) {
        form.value = JSON.parse(JSON.stringify(originalFormData.value));
    }
    editMode.value = false;
    // Navigate back to view mode
    if (route.params.id) {
        router.push(`/billing/${route.params.id}`);
    }
};

onMounted(async () => {
    try {
        const [patientsRes, servicesRes, costHeadsRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/hospital-services'),
            axios.get('/api/cost-heads')
        ]);

        // Filter out any null/undefined entries
        const patientData = patientsRes.data.data || patientsRes.data || [];
        patients.value = Array.isArray(patientData) ? patientData.filter(p => p && p.patient_id) : [];

        allServices.value = servicesRes.data || [];
        costHeads.value = costHeadsRes.data || [];

        if (route.params.id) {
            const billRes = await axios.get(`/api/bills/${route.params.id}`);
            const bill = billRes.data;

            // Check if bill can be edited (only pending bills)
            canEdit.value = bill.payment_status === 'pending';

            // Auto-enable edit mode if mode=edit query parameter is present
            if (route.query.mode === 'edit' && canEdit.value) {
                editMode.value = true;
            }

            form.value = {
                patient_id: bill.patient_id,
                ipd_id: bill.ipd_id,
                bill_date: bill.bill_date ? bill.bill_date.split('T')[0] : '',
                bill_type: bill.bill_type,
                discount_amount: Number(bill.discount_amount) || 0,
                discount_percent: Number(bill.discount_percent) || 0,
                tax_amount: Number(bill.tax_amount) || 0,
                adjustment: Number(bill.adjustment) || 0,
                items: (bill.details || []).map(detail => ({
                    ...detail,
                    service_id: detail.item_id,
                    quantity: Number(detail.quantity) || 1,
                    amount: Number(detail.amount) || 0,
                    // If unit_price is 0 or missing but amount exists, calculate it
                    unit_price: Number(detail.unit_price) > 0 ? Number(detail.unit_price) : (Number(detail.amount) / (Number(detail.quantity) || 1))
                }))
            };
        }
    } catch (error) {
        console.error('Error loading form data:', error);
        alert('Error loading form data');
    }
});

const onBillTypeChange = async () => {
    if (form.value.bill_type === 'ipd') {
        // Fetch IPD admitted patients
        await fetchIpdAdmissions();
        form.value.patient_id = '';
        selectedIpdId.value = '';
        runningBill.value = null;
    } else {
        // Clear IPD data
        ipdAdmissions.value = [];
        selectedIpdId.value = '';
        runningBill.value = null;
        form.value.ipd_id = '';
    }
};

const fetchIpdAdmissions = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/api/ipd-admissions', {
            params: {
                status: 'admitted',
                per_page: 1000
            }
        });
        ipdAdmissions.value = response.data.data || response.data || [];
    } catch (error) {
        console.error('Error fetching IPD admissions:', error);
        alert('Error loading IPD admissions');
    } finally {
        loading.value = false;
    }
};

const onIpdAdmissionChange = async () => {
    if (!selectedIpdId.value) {
        runningBill.value = null;
        ipdServices.value = [];
        form.value.patient_id = '';
        form.value.ipd_id = '';
        form.value.items = [{ cost_head_id: '', service_id: '', item_name: '', quantity: 1, unit_price: 0, amount: 0 }];
        return;
    }

    try {
        loading.value = true;

        // Fetch running bill
        const billResponse = await axios.get(`/api/ipd-admissions/${selectedIpdId.value}/running-bill`);
        runningBill.value = billResponse.data;

        // Populate discount from running bill
        form.value.discount_amount = Number(runningBill.value?.billing?.discount) || 0;

        // Fetch services
        const servicesResponse = await axios.get(`/api/ipd-admissions/${selectedIpdId.value}/services`, {
            params: {
                per_page: 1000
            }
        });
        ipdServices.value = servicesResponse.data.data || servicesResponse.data || [];

        // Find the admission to get patient_id
        const admission = ipdAdmissions.value.find(a => a.ipd_id == selectedIpdId.value);
        if (admission) {
            form.value.patient_id = admission.patient_id;
            form.value.ipd_id = selectedIpdId.value;
        }

        // Populate bill items from IPD services
        if (ipdServices.value.length > 0) {
            form.value.items = ipdServices.value.map(service => ({
                cost_head_id: service.cost_head_id || '',
                service_id: service.service_id || '',
                item_name: service.service_name,
                quantity: Number(service.quantity) || 1,
                unit_price: Number(service.rate) || 0,
                amount: Number(service.net_amount) || (Number(service.quantity) * Number(service.rate))
            }));
        }

    } catch (error) {
        console.error('Error fetching IPD data:', error);
        alert('Error loading IPD patient data');
    } finally {
        loading.value = false;
    }
};

const getFilteredServices = (costHeadId) => {
    if (!costHeadId || !allServices.value) return [];
    return allServices.value.filter(s => s.cost_head_id == costHeadId);
};

const onCostHeadChange = (item, index) => {
    // Reset service selection when cost head changes
    item.service_id = '';
    item.item_name = '';
    item.unit_price = 0;
    item.amount = 0;
};

const onServiceChange = (item) => {
    if (!item.service_id) return;

    // Find the selected service
    const selectedService = allServices.value.find(s => s.hospital_service_id == item.service_id);
    if (selectedService) {
        item.item_name = selectedService.service_name;
        item.unit_price = Number(selectedService.base_price) || 0;
        calculateAmount(item);
    }
};

const addItem = () => {
    form.value.items.push({ cost_head_id: '', service_id: '', item_name: '', quantity: 1, unit_price: 0, amount: 0 });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

const calculateAmount = (item) => {
    const quantity = Number(item.quantity) || 0;
    const unitPrice = Number(item.unit_price) || 0;
    item.amount = quantity * unitPrice;
};

const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);

const saveBill = async () => {
    if (!form.value.patient_id) {
        alert('Please select a patient');
        return;
    }

    if (form.value.items.length === 0 || !form.value.items[0].item_name) {
        alert('Please add at least one service');
        return;
    }

    loading.value = true;
    try {
        // Prepare bill data with item_id and item_type for backward compatibility
        const billData = {
            ...form.value,
            items: form.value.items.map(item => ({
                item_type: 'service',
                item_id: item.service_id || null,
                cost_head_id: item.cost_head_id || null,
                item_name: item.item_name,
                quantity: item.quantity,
                unit_price: item.unit_price,
                amount: item.amount
            }))
        };

        await axios.post('/api/bills', billData);
        alert('Bill created successfully!');
        router.push('/billing');
    } catch (error) {
        console.error('Error saving bill:', error);
        alert(error.response?.data?.message || 'Error saving bill');
    } finally {
        loading.value = false;
    }
};

const updateBill = async () => {
    if (!form.value.patient_id) {
        alert('Please select a patient');
        return;
    }

    if (form.value.items.length === 0 || !form.value.items[0].item_name) {
        alert('Please add at least one service');
        return;
    }

    loading.value = true;
    try {
        // Only send updatable fields
        const updateData = {
            discount_amount: form.value.discount_amount,
            discount_percent: form.value.discount_percent,
            tax_amount: form.value.tax_amount,
            adjustment: form.value.adjustment
        };

        await axios.put(`/api/bills/${route.params.id}`, updateData);
        alert('Bill updated successfully!');

        // Navigate back to view mode
        router.push(`/billing/${route.params.id}`);

        // Reload the bill data
        const billRes = await axios.get(`/api/bills/${route.params.id}`);
        const bill = billRes.data;
        form.value = {
            patient_id: bill.patient_id,
            ipd_id: bill.ipd_id,
            bill_date: bill.bill_date ? bill.bill_date.split('T')[0] : '',
            bill_type: bill.bill_type,
            discount_amount: Number(bill.discount_amount) || 0,
            discount_percent: Number(bill.discount_percent) || 0,
            tax_amount: Number(bill.tax_amount) || 0,
            adjustment: Number(bill.adjustment) || 0,
            items: (bill.details || []).map(detail => ({
                ...detail,
                service_id: detail.item_id,
                quantity: Number(detail.quantity) || 1,
                amount: Number(detail.amount) || 0,
                unit_price: Number(detail.unit_price) > 0 ? Number(detail.unit_price) : (Number(detail.amount) / (Number(detail.quantity) || 1))
            }))
        };
    } catch (error) {
        console.error('Error updating bill:', error);
        alert(error.response?.data?.message || 'Error updating bill');
    } finally {
        loading.value = false;
    }
};
</script>
