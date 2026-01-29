<template>
    <div class="bill-form-container">
        <!-- Compact Header with Summary -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-0">{{ getPageTitle() }}</h5>
                <small class="text-muted">
                    {{ form.patient_id ? getPatientName() : 'Select patient to continue' }}
                </small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-end me-3">
                    <small class="text-muted d-block">Total Amount</small>
                    <h4 class="mb-0 text-primary">{{ formatCurrency(total) }}</h4>
                </div>
                <div class="btn-group" v-if="!$route.params.id">
                    <button class="btn btn-primary" @click="saveBill" :disabled="loading">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <router-link to="/billing" class="btn btn-outline-secondary">Cancel</router-link>
                </div>
                <div class="btn-group" v-else-if="editMode">
                    <button class="btn btn-success" @click="updateBill" :disabled="loading">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <button class="btn btn-outline-secondary" @click="cancelEdit">Cancel</button>
                </div>
                <div class="btn-group" v-else>
                    <button class="btn btn-outline-primary" @click="printBill" title="Print">
                        <i class="bi bi-printer"></i>
                    </button>
                    <router-link to="/billing" class="btn btn-outline-secondary">Back</router-link>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Left Column -->
            <div class="col-md-8">
                <!-- Compact Patient Information -->
                <div class="card shadow-sm mb-2">
                    <div class="card-header bg-white py-2 cursor-pointer" @click="showPatientInfo = !showPatientInfo">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 small"><i class="bi bi-person-circle me-2"></i>Patient Information</h6>
                            <i :class="showPatientInfo ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                        </div>
                    </div>
                    <div class="card-body py-2" v-show="showPatientInfo">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Bill Type</label>
                                <select class="form-select form-select-sm" v-model="form.bill_type" :disabled="isViewMode" @change="onBillTypeChange">
                                    <option value="general">General</option>
                                    <option value="opd">OPD</option>
                                    <option value="ipd">IPD</option>
                                    <option value="pharmacy">Pharmacy</option>
                                    <option value="lab">Laboratory</option>
                                </select>
                            </div>
                            <div class="col-md-6" v-if="form.bill_type === 'ipd'">
                                <label class="form-label small mb-1">IPD Admission</label>
                                <select class="form-select form-select-sm" v-model="selectedIpdId" :disabled="isViewMode" @change="onIpdAdmissionChange">
                                    <option value="">Select IPD Patient</option>
                                    <option v-for="admission in ipdAdmissions" :key="admission.ipd_id" :value="admission.ipd_id">
                                        {{ admission.ipd_number }} - {{ admission.patient?.first_name }} {{ admission.patient?.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6" v-else>
                                <label class="form-label small mb-1">Patient</label>
                                <select class="form-select form-select-sm" v-model="form.patient_id" :disabled="isViewMode">
                                    <option value="">Select Patient</option>
                                    <option v-for="p in patients" :key="p?.patient_id" :value="p?.patient_id" v-if="p && p.patient_id">
                                        {{ p.patient_code }} - {{ p.first_name }} {{ p.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Bill Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.bill_date" :disabled="isViewMode">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compact IPD Running Bill -->
                <div class="card shadow-sm mb-2" v-if="form.bill_type === 'ipd' && runningBill">
                    <div class="card-header bg-white py-2 cursor-pointer" @click="showIpdBill = !showIpdBill">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 small"><i class="bi bi-clipboard-data me-2"></i>IPD Running Bill Summary</h6>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-primary">{{ formatCurrency(runningBill.billing?.net_total) }}</span>
                                <i :class="showIpdBill ? 'bi bi-chevron-up' : 'bi bi-chevron-down'"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-body py-2" v-show="showIpdBill">
                        <div class="row g-2 mb-2 small">
                            <div class="col-md-6">
                                <span class="text-muted">IPD #:</span> {{ runningBill.patient?.ipd_number }} |
                                <span class="text-muted">LOS:</span> {{ runningBill.patient?.los_days }} days
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="text-muted">Ward:</span> {{ runningBill.bed_details?.ward }} |
                                <span class="text-muted">Bed:</span> {{ runningBill.bed_details?.bed }}
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                            <table class="table table-sm table-bordered mb-0">
                                <tbody>
                                    <tr v-for="(summary, type) in runningBill.services_summary" :key="type" v-if="type !== 'bed'" class="small">
                                        <td>{{ type.replace('_', ' ').toUpperCase() }}</td>
                                        <td class="text-end" width="60">{{ summary.count }}</td>
                                        <td class="text-end" width="100">{{ formatCurrency(summary.total) }}</td>
                                    </tr>
                                    <tr class="table-light cursor-pointer" @click="showBillItems = !showBillItems">
                                        <td colspan="2">
                                            <strong class="small">Services Total</strong>
                                            <i :class="showBillItems ? 'bi bi-chevron-up' : 'bi bi-chevron-down'" class="ms-2"></i>
                                        </td>
                                        <td class="text-end"><strong class="small">{{ formatCurrency(runningBill.billing?.services_total) }}</strong></td>
                                    </tr>
                                    <tr class="table-primary small">
                                        <td colspan="2"><strong>Gross Total</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.gross_total) }}</strong></td>
                                    </tr>
                                    <tr class="small">
                                        <td colspan="2">Discount</td>
                                        <td class="text-end">{{ formatCurrency(runningBill.billing?.discount) }}</td>
                                    </tr>
                                    <tr class="table-success small">
                                        <td colspan="2"><strong>Net Total</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.net_total) }}</strong></td>
                                    </tr>
                                    <tr class="small">
                                        <td colspan="2">Advance Paid</td>
                                        <td class="text-end">{{ formatCurrency(runningBill.billing?.advance_paid) }}</td>
                                    </tr>
                                    <tr class="table-warning small">
                                        <td colspan="2"><strong>Balance Due</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(runningBill.billing?.balance_due) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Bill Items Details - Toggleable -->
                        <div v-if="showBillItems && ipdServices.length > 0" class="mt-2">
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr class="small">
                                            <th>Service Name</th>
                                            <th width="100">Cost Head</th>
                                            <th class="text-end" width="50">Qty</th>
                                            <th class="text-end" width="80">Rate</th>
                                            <th class="text-end" width="100">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(service, index) in ipdServices" :key="index" class="small">
                                            <td>{{ service.service_name }}</td>
                                            <td>{{ service.cost_head_name }}</td>
                                            <td class="text-end">{{ service.quantity }}</td>
                                            <td class="text-end">{{ formatCurrency(service.rate) }}</td>
                                            <td class="text-end">{{ formatCurrency(service.net_amount || (service.quantity * service.rate)) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compact Bill Items with Scrollable Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 small"><i class="bi bi-list-ul me-2"></i>Bill Items ({{ form.items.length }})</h6>
                        <button class="btn btn-sm btn-primary" @click="addItem" v-if="!isViewMode">
                            <i class="bi bi-plus"></i> Add
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <!-- View Mode: Grouped by Cost Head -->
                        <div v-if="isViewMode" class="p-2">
                            <div v-for="(group, costHeadName) in groupedItemsByCostHead" :key="costHeadName" class="mb-2">
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded cursor-pointer" @click="toggleCostHead(costHeadName)">
                                    <div>
                                        <i :class="expandedCostHeads[costHeadName] ? 'bi bi-chevron-down' : 'bi bi-chevron-right'" class="me-2"></i>
                                        <strong>{{ costHeadName }}</strong>
                                        <span class="badge bg-secondary ms-2">{{ group.items.length }}</span>
                                    </div>
                                    <strong>{{ formatCurrency(group.total) }}</strong>
                                </div>
                                <div v-show="expandedCostHeads[costHeadName]" class="mt-2">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr class="small">
                                                <th>Service</th>
                                                <th width="130">Date & Time</th>
                                                <th width="120">Doctor</th>
                                                <th width="50" class="text-center">Qty</th>
                                                <th width="80" class="text-end">Rate</th>
                                                <th width="90" class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in group.items" :key="item.service_id" class="small">
                                                <td>{{ getServiceName(item.service_id) }}</td>
                                                <td>{{ formatDateTime(item.service_date) }}</td>
                                                <td>{{ getDoctorName(item.doctor_id) }}</td>
                                                <td class="text-center">{{ item.quantity }}</td>
                                                <td class="text-end">{{ formatCurrency(item.unit_price) }}</td>
                                                <td class="text-end">{{ formatCurrency(item.amount) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Mode: Regular Table -->
                        <div v-else class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light sticky-top">
                                    <tr class="small">
                                        <th width="150">Cost Head</th>
                                        <th width="180">Service</th>
                                        <th width="130">Date & Time</th>
                                        <th width="120">Doctor</th>
                                        <th width="50">Qty</th>
                                        <th width="80">Rate</th>
                                        <th width="90">Amount</th>
                                        <th width="40"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="small">
                                        <td class="p-1">
                                            <select class="form-select form-select-sm" v-model="item.cost_head_id" @change="onCostHeadChange(item, index)">
                                                <option value="">Select</option>
                                                <option v-for="ch in costHeads" :key="ch.cost_head_id" :value="ch.cost_head_id">
                                                    {{ ch.cost_head_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="p-1">
                                            <select class="form-select form-select-sm" v-model="item.service_id" :disabled="!!$route.params.id || !item.cost_head_id" @change="onServiceChange(item)">
                                                <option value="">Select</option>
                                                <option v-for="svc in getFilteredServices(item.cost_head_id)" :key="svc.hospital_service_id" :value="svc.hospital_service_id">
                                                    {{ svc.service_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="p-1">
                                            <input type="datetime-local" class="form-control form-control-sm" v-model="item.service_date">
                                        </td>
                                        <td class="p-1">
                                            <select class="form-select form-select-sm" v-model="item.doctor_id">
                                                <option value="">Select</option>
                                                <option v-for="doctor in doctors" :key="doctor.doctor_id" :value="doctor.doctor_id">
                                                    {{ doctor.full_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="p-1">
                                            <input type="number" class="form-control form-control-sm" v-model.number="item.quantity" @input="calculateAmount(item)" min="1">
                                        </td>
                                        <td class="p-1">
                                            <input type="number" class="form-control form-control-sm" v-model.number="item.unit_price" @input="calculateAmount(item)">
                                        </td>
                                        <td class="p-1">
                                            <input type="number" class="form-control form-control-sm" v-model.number="item.amount" readonly>
                                        </td>
                                        <td class="p-1 text-center">
                                            <button class="btn btn-sm btn-outline-danger" @click="removeItem(index)" style="padding: 0.15rem 0.3rem;">
                                                <i class="bi bi-trash" style="font-size: 0.75rem;"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Compact Summary -->
            <div class="col-md-4">
                <div class="card shadow-sm sticky-summary">
                    <div class="card-header bg-primary text-white py-2">
                        <h6 class="mb-0 small"><i class="bi bi-calculator me-2"></i>Bill Summary</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span>Subtotal:</span>
                            <strong>{{ formatCurrency(subtotal) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span>Discount:</span>
                            <input type="number" class="form-control form-control-sm text-end" style="width: 100px;" v-model.number="form.discount_amount" :disabled="isViewMode" step="0.01">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span>GST:</span>
                            <input type="number" class="form-control form-control-sm text-end" style="width: 100px;" v-model.number="form.tax_amount" :disabled="isViewMode" step="0.01">
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span>Refund:</span>
                            <input type="number" class="form-control form-control-sm text-end" style="width: 100px;" v-model.number="form.refund_amount" :disabled="isViewMode" step="0.01">
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total:</strong>
                            <strong class="text-primary fs-5">{{ formatCurrency(total) }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm mt-2" v-if="$route.params.id && !editMode">
                    <div class="card-header bg-white py-2">
                        <h6 class="mb-0 small"><i class="bi bi-lightning me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="d-grid gap-1">
                            <button class="btn btn-sm btn-outline-primary" @click="printBill">
                                <i class="bi bi-printer"></i> Print Bill
                            </button>
                            <button class="btn btn-sm btn-outline-success" v-if="canEdit" @click="enableEditMode">
                                <i class="bi bi-pencil"></i> Edit Bill
                            </button>
                            <button class="btn btn-sm btn-outline-info">
                                <i class="bi bi-share"></i> Share Bill
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bill-form-container {
    max-height: calc(100vh - 120px);
    overflow: hidden;
}

.cursor-pointer {
    cursor: pointer;
}

.sticky-summary {
    position: sticky;
    top: 10px;
}

.table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

.table-responsive::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f7fafc;
}

.table-responsive::-webkit-scrollbar-thumb {
    background-color: #cbd5e0;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background-color: #a0aec0;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}

.cursor-pointer {
    cursor: pointer;
    user-select: none;
}

.cursor-pointer:hover {
    background-color: #e9ecef !important;
}
</style>

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
const doctors = ref([]);
const editMode = ref(false);
const originalFormData = ref(null);
const canEdit = ref(false);
const expandedCostHeads = ref({});
const showBillItems = ref(false);
const showPatientInfo = ref(true);
const showIpdBill = ref(true);

const form = ref({
    patient_id: '',
    ipd_id: '',
    bill_date: new Date().toISOString().split('T')[0],
    bill_type: 'general',
    discount_amount: 0,
    discount_percent: 0,
    tax_amount: 0,
    refund_amount: 0,
    items: [{ cost_head_id: '', service_id: '', item_name: '', quantity: 1, unit_price: 0, amount: 0 }]
});

const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + (item.amount || 0), 0));
const total = computed(() => {
    const sub = subtotal.value;
    const discount = form.value.discount_amount || 0;
    const tax = form.value.tax_amount || 0;
    const refund = form.value.refund_amount || 0;
    return sub - discount + tax - refund;
});

const isViewMode = computed(() => !!route.params.id && !editMode.value);

const groupedItemsByCostHead = computed(() => {
    const grouped = {};
    form.value.items.forEach(item => {
        const costHeadName = getCostHeadName(item.cost_head_id);
        if (!grouped[costHeadName]) {
            grouped[costHeadName] = {
                items: [],
                total: 0
            };
            // Initialize as expanded by default
            if (!expandedCostHeads.value.hasOwnProperty(costHeadName)) {
                expandedCostHeads.value[costHeadName] = true;
            }
        }
        grouped[costHeadName].items.push(item);
        grouped[costHeadName].total += (item.amount || 0);
    });
    return grouped;
});

const getCostHeadName = (costHeadId) => {
    const costHead = costHeads.value.find(ch => ch.cost_head_id == costHeadId);
    return costHead ? costHead.cost_head_name : 'Unknown';
};

const getServiceName = (serviceId) => {
    const service = allServices.value.find(s => s.hospital_service_id == serviceId);
    return service ? service.service_name : 'Unknown Service';
};

const toggleCostHead = (costHeadName) => {
    expandedCostHeads.value[costHeadName] = !expandedCostHeads.value[costHeadName];
};

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
        const [patientsRes, servicesRes, costHeadsRes, doctorsRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/hospital-services'),
            axios.get('/api/cost-heads'),
            axios.get('/api/doctors')
        ]);

        // Filter out any null/undefined entries
        const patientData = patientsRes.data.data || patientsRes.data || [];
        patients.value = Array.isArray(patientData) ? patientData.filter(p => p && p.patient_id) : [];

        allServices.value = servicesRes.data || [];
        costHeads.value = costHeadsRes.data || [];
        doctors.value = doctorsRes.data.data || doctorsRes.data || [];

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
                refund_amount: Number(bill.refund_amount || bill.adjustment) || 0,
                items: (bill.details || []).map(detail => ({
                    ...detail,
                    service_id: detail.item_id,
                    quantity: Number(detail.quantity) || 1,
                    amount: Number(detail.amount) || 0,
                    // If unit_price is 0 or missing but amount exists, calculate it
                    unit_price: Number(detail.unit_price) > 0 ? Number(detail.unit_price) : (Number(detail.amount) / (Number(detail.quantity) || 1))
                }))
            };
        } else {
            // Auto-select IPD patient if ipd_id query parameter is present
            if (route.query.ipd_id) {
                form.value.bill_type = 'ipd';
                await fetchIpdAdmissions();
                selectedIpdId.value = route.query.ipd_id;
                await onIpdSelect();
            }
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
    const now = new Date();
    const dateTimeLocal = now.toISOString().slice(0, 16);
    form.value.items.push({
        cost_head_id: '',
        service_id: '',
        item_name: '',
        service_date: dateTimeLocal,
        doctor_id: '',
        quantity: 1,
        unit_price: 0,
        amount: 0
    });
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

const formatDateTime = (dateTime) => {
    if (!dateTime) return '-';
    const date = new Date(dateTime);
    return date.toLocaleString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

const getDoctorName = (doctorId) => {
    if (!doctorId) return '-';
    const doctor = doctors.value.find(d => d.doctor_id == doctorId);
    return doctor ? doctor.full_name : '-';
};

const getPatientName = () => {
    const patient = patients.value.find(p => p?.patient_id === form.value.patient_id);
    if (patient) {
        return `${patient.patient_code} - ${patient.first_name} ${patient.last_name}`;
    }
    return '';
};

const printBill = () => {
    if (route.params.id) {
        window.open(`/api/bills/${route.params.id}/print`, '_blank');
    }
};

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
            refund_amount: form.value.refund_amount,
            adjustment: form.value.refund_amount // For backward compatibility
        };

        await axios.put(`/api/bills/${route.params.id}`, updateData);
        alert('Bill updated successfully!');

        // Navigate back to billing list
        router.push('/billing');
    } catch (error) {
        console.error('Error updating bill:', error);
        alert(error.response?.data?.message || 'Error updating bill');
    } finally {
        loading.value = false;
    }
};
</script>
