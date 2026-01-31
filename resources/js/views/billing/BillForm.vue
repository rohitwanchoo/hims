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
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Payment Mode</label>
                                <select class="form-select form-select-sm" v-model="form.payment_mode" :disabled="isViewMode">
                                    <option value="cash">Cash</option>
                                    <option value="cashless">Cashless</option>
                                    <option value="insurance">Insurance</option>
                                </select>
                            </div>
                            <div class="col-md-3" v-if="form.bill_type === 'ipd'">
                                <label class="form-label small mb-1">IPD Admission</label>
                                <select class="form-select form-select-sm" v-model="selectedIpdId" :disabled="isViewMode" @change="onIpdAdmissionChange">
                                    <option value="">Select IPD Patient</option>
                                    <option v-for="admission in ipdAdmissions" :key="admission.ipd_id" :value="admission.ipd_id">
                                        {{ admission.ipd_number }} - {{ admission.patient?.first_name }} {{ admission.patient?.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3" v-else>
                                <label class="form-label small mb-1">
                                    Patient
                                    <span v-if="form.bill_type === 'opd' && opdPatients.length > 0" class="text-success small">({{ opdPatients.length }} Today's OPD)</span>
                                    <span v-else-if="form.bill_type === 'opd'" class="text-muted small">(All Patients)</span>
                                </label>
                                <select class="form-select form-select-sm" v-model="form.patient_id" :disabled="isViewMode">
                                    <option value="">Select Patient</option>
                                    <template v-for="p in filteredPatients" :key="p?.patient_id">
                                        <option v-if="p && p.patient_id" :value="p.patient_id">
                                            {{ p.pcd }} - {{ p.patient_name || (p.first_name + ' ' + p.last_name) }}
                                        </option>
                                    </template>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Bill Date</label>
                                <input type="date" class="form-control form-control-sm" v-model="form.bill_date" :disabled="isViewMode">
                            </div>
                        </div>
                        <!-- Bed Charge Dates for IPD -->
                        <div v-if="form.bill_type === 'ipd' && currentAdmission && currentAdmission.bed" class="row g-2 mt-1 pt-2 border-top">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Bed Charges From</label>
                                <input type="date" class="form-control form-control-sm" v-model="bedChargeFromDate" :disabled="isViewMode" @change="recalculateBedCharges">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Bed Charges To</label>
                                <input type="date" class="form-control form-control-sm" v-model="bedChargeToDate" :disabled="isViewMode" @change="recalculateBedCharges">
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Bed: {{ currentAdmission.bed.bed_number }}</small>
                                <small class="text-muted d-block">Rate: ₹{{ getBedRate().toFixed(2) }}/day</small>
                            </div>
                        </div>
                        <!-- Insurance/Cashless Details -->
                        <div v-if="form.payment_mode === 'cashless' || form.payment_mode === 'insurance'" class="row g-2 mt-1 pt-2 border-top">
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Class</label>
                                <select class="form-select form-select-sm" v-model="form.insurance_company" :disabled="isViewMode">
                                    <option value="">Select Class</option>
                                    <option v-for="company in insuranceCompanies" :key="company.insurance_id" :value="company.company_name">
                                        {{ company.company_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Policy Number</label>
                                <input type="text" class="form-control form-control-sm" v-model="form.policy_number" :disabled="isViewMode" placeholder="Enter policy number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1">Approved Amount</label>
                                <input type="number" class="form-control form-control-sm" v-model.number="form.approved_amount" :disabled="isViewMode" step="0.01" placeholder="0.00">
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
                                                <th width="120">Ward Name</th>
                                                <th width="100">Bed Name</th>
                                                <th width="130">Date & Time</th>
                                                <th width="120">Doctor</th>
                                                <th width="50" class="text-center">Qty</th>
                                                <th width="80" class="text-end">Rate</th>
                                                <th width="90" class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="item in group.items" :key="item.service_id" class="small">
                                                <td>{{ item.item_name || getServiceName(item.service_id) }}</td>
                                                <td>{{ item.ward_name || '-' }}</td>
                                                <td>{{ item.bed_name || '-' }}</td>
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
                                        <th width="120">Ward Name</th>
                                        <th width="100">Bed Name</th>
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
                                            <select class="form-select form-select-sm" v-model="item.service_id" :disabled="isViewMode || !item.cost_head_id" @change="onServiceChange(item)">
                                                <option value="">Select</option>
                                                <option v-for="svc in getFilteredServices(item.cost_head_id)" :key="svc.hospital_service_id" :value="svc.hospital_service_id">
                                                    {{ svc.service_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td class="p-1">
                                            <input type="text" class="form-control form-control-sm" v-model="item.ward_name" readonly :placeholder="item.ward_name ? '' : '-'">
                                        </td>
                                        <td class="p-1">
                                            <input type="text" class="form-control form-control-sm" v-model="item.bed_name" readonly :placeholder="item.bed_name ? '' : '-'">
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
                        <div v-if="form.bill_type === 'ipd' && runningBill" class="d-flex justify-content-between mb-2 small">
                            <span>Advance Paid</span>
                            <strong class="text-success">{{ formatCurrency(advancePaid) }}</strong>
                        </div>
                        <div v-if="form.bill_type === 'ipd' && runningBill" class="d-flex justify-content-between mb-2 small">
                            <span>Refund</span>
                            <strong class="text-info">{{ formatCurrency(refundAmount) }}</strong>
                        </div>
                        <div v-else class="d-flex justify-content-between align-items-center mb-2 small">
                            <span>Refund:</span>
                            <input type="number" class="form-control form-control-sm text-end" style="width: 100px;" v-model.number="form.refund_amount" :disabled="isViewMode" step="0.01">
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>Total:</strong>
                            <strong class="text-primary fs-5">{{ formatCurrency(total) }}</strong>
                        </div>

                        <!-- Insurance/Cashless Billing -->
                        <div v-if="form.payment_mode === 'cashless' || form.payment_mode === 'insurance'" class="mt-2 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span>Insurance Amount:</span>
                                <input type="number" class="form-control form-control-sm text-end" style="width: 100px;" v-model.number="form.insurance_amount" :disabled="isViewMode" step="0.01">
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span>Co-pay (Patient):</span>
                                <strong class="text-warning">{{ formatCurrency(copayAmount) }}</strong>
                            </div>
                        </div>

                        <!-- IPD Balance Due -->
                        <div v-if="form.bill_type === 'ipd' && runningBill" class="d-flex justify-content-between mb-2 pt-2 border-top">
                            <strong class="text-danger">Balance Due:</strong>
                            <strong class="text-danger fs-5">{{ formatCurrency(balanceDue) }}</strong>
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
const opdPatients = ref([]);
const allServices = ref([]);
const ipdAdmissions = ref([]);
const selectedIpdId = ref('');
const runningBill = ref(null);
const ipdServices = ref([]);
const costHeads = ref([]);
const doctors = ref([]);
const gstPlans = ref([]);
const editMode = ref(false);
const originalFormData = ref(null);
const canEdit = ref(false);
const expandedCostHeads = ref({});
const showPatientInfo = ref(true);
const insuranceCompanies = ref([]);
const selectedPatient = ref(null);
const currentAdmission = ref(null);
const bedChargeFromDate = ref('');
const bedChargeToDate = ref('');

const form = ref({
    patient_id: '',
    ipd_id: '',
    bill_date: new Date().toISOString().split('T')[0],
    bill_type: 'general',
    payment_mode: 'cash',
    insurance_company: '',
    policy_number: '',
    approved_amount: 0,
    copay_amount: 0,
    insurance_amount: 0,
    discount_amount: 0,
    discount_percent: 0,
    tax_amount: 0,
    refund_amount: 0,
    items: [{ cost_head_id: '', service_id: '', item_name: '', ward_name: '', bed_name: '', quantity: 1, unit_price: 0, amount: 0 }]
});

const subtotal = computed(() => form.value.items.reduce((sum, item) => sum + (item.amount || 0), 0));

const total = computed(() => {
    const sub = subtotal.value;
    const discount = form.value.discount_amount || 0;
    const tax = form.value.tax_amount || 0;

    // For IPD bills, total is just the bill amount (not reduced by refund)
    if (form.value.bill_type === 'ipd' && runningBill.value) {
        return sub - discount + tax;
    }

    // For non-IPD bills, subtract manual refund
    const refund = form.value.refund_amount || 0;
    return sub - discount + tax - refund;
});

const isViewMode = computed(() => !!route.params.id && !editMode.value);

const advancePaid = computed(() => {
    return Number(runningBill.value?.billing?.advance_paid) || 0;
});

const refundAmount = computed(() => {
    if (form.value.bill_type === 'ipd' && runningBill.value) {
        // If advance is more than total, calculate refund
        if (advancePaid.value > total.value) {
            return advancePaid.value - total.value;
        }
        return 0;
    }
    // For non-IPD bills, use manual refund amount
    return form.value.refund_amount || 0;
});

const copayAmount = computed(() => {
    const insurance = form.value.insurance_amount || 0;
    return Math.max(0, total.value - insurance);
});

const balanceDue = computed(() => {
    if (form.value.bill_type === 'ipd' && runningBill.value) {
        // If advance is more than total, balance due is 0 (patient gets refund instead)
        if (advancePaid.value >= total.value) {
            return 0;
        }
        // Patient still owes this amount
        return total.value - advancePaid.value;
    }
    // For non-IPD bills, balance is total minus any payments
    return total.value - advancePaid.value;
});

// Show today's OPD patients when bill type is OPD, otherwise show all patients
const filteredPatients = computed(() => {
    // For OPD bills, show OPD patients if available, otherwise fall back to all patients
    if (form.value.bill_type === 'opd') {
        if (opdPatients.value.length > 0) {
            console.log('Showing OPD patients:', opdPatients.value.length);
            return opdPatients.value;
        } else {
            console.log('No OPD patients, showing all patients');
            return patients.value;
        }
    }
    return patients.value;
});

// Function to auto-populate insurance data from patient
const autoPopulateInsuranceData = (patient) => {
    if (!patient) return;

    // Auto-populate insurance data from patient if available and fields are empty
    if (patient.insurance_company_relation && !form.value.insurance_company) {
        form.value.insurance_company = patient.insurance_company_relation.company_name;
    }

    if (patient.insurance_policy_no && !form.value.policy_number) {
        form.value.policy_number = patient.insurance_policy_no;
    }
};

// Watch for patient selection to auto-populate insurance data
watch(() => form.value.patient_id, async (newPatientId) => {
    if (newPatientId && (form.value.payment_mode === 'insurance' || form.value.payment_mode === 'cashless')) {
        try {
            // Fetch full patient data with insurance relationship
            const patientRes = await axios.get(`/api/patients/${newPatientId}`);
            const patient = patientRes.data.data || patientRes.data;
            selectedPatient.value = patient;

            if (patient && patient.insurance_company_id) {
                autoPopulateInsuranceData(patient);
            }
        } catch (error) {
            console.error('Error fetching patient data:', error);
        }
    }
});

// Watch for payment mode changes to auto-populate insurance data and recalculate
watch(() => form.value.payment_mode, async (newMode, oldMode) => {
    if ((newMode === 'insurance' || newMode === 'cashless') && form.value.patient_id) {
        try {
            // Fetch full patient data with insurance relationship
            const patientRes = await axios.get(`/api/patients/${form.value.patient_id}`);
            const patient = patientRes.data.data || patientRes.data;

            if (patient && patient.insurance_company_id) {
                autoPopulateInsuranceData(patient);
            }

            // Auto-set approved amount to total bill amount when switching from cash to cashless/insurance
            if (oldMode === 'cash') {
                form.value.approved_amount = total.value;
                form.value.insurance_amount = total.value;
                console.log(`Payment mode changed from cash to ${newMode}: Auto-set approved amount to ₹${total.value}`);
            }
        } catch (error) {
            console.error('Error fetching patient data:', error);
        }
    } else if (newMode === 'cash') {
        // When switching back to cash, reset insurance-related fields
        form.value.insurance_company = '';
        form.value.policy_number = '';
        form.value.approved_amount = 0;
        form.value.insurance_amount = 0;
        console.log('Payment mode changed to cash: Reset insurance fields');
    }
});

// Watch for approved amount changes to auto-set insurance amount
watch(() => form.value.approved_amount, (newApprovedAmount) => {
    if ((form.value.payment_mode === 'insurance' || form.value.payment_mode === 'cashless') && newApprovedAmount) {
        form.value.insurance_amount = newApprovedAmount;
    }
});

// Watch for items changes to auto-calculate GST
watch(() => form.value.items, () => {
    if (isViewMode.value) return; // Don't auto-calculate in view mode

    let totalGst = 0;

    console.log('Calculating GST for items:', form.value.items.length);

    // Check each item for GST settings
    for (const item of form.value.items) {
        if (!item.service_id || !item.amount) continue;

        // Find the service in allServices
        const service = allServices.value.find(s => s.hospital_service_id == item.service_id);

        if (service && service.gst_plan_id) {
            const gstAboveAmount = Number(service.gst_above_amount) || 0;

            console.log(`Service: ${service.service_name}, Amount: ${item.amount}, GST Above: ${gstAboveAmount}`);

            // Check if item amount exceeds the threshold
            if (item.amount >= gstAboveAmount) {
                // Find GST plan in cached list
                const gstPlan = gstPlans.value.find(p => p.gst_plan_id == service.gst_plan_id);

                if (gstPlan && gstPlan.gst_percentage) {
                    const gstAmount = (item.amount * gstPlan.gst_percentage) / 100;
                    totalGst += gstAmount;

                    console.log(`GST Plan: ${gstPlan.plan_name}, Percentage: ${gstPlan.gst_percentage}%, GST Amount: ${gstAmount}`);
                }
            }
        }
    }

    // Update tax amount
    form.value.tax_amount = Math.round(totalGst * 100) / 100; // Round to 2 decimal places
    console.log('Total GST calculated:', form.value.tax_amount);
}, { deep: true });

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
        const [patientsRes, servicesRes, costHeadsRes, doctorsRes, insuranceRes, gstPlansRes] = await Promise.all([
            axios.get('/api/patients?per_page=1000'),
            axios.get('/api/hospital-services'),
            axios.get('/api/cost-heads'),
            axios.get('/api/doctors'),
            axios.get('/api/insurance-companies-active'),
            axios.get('/api/gst-plans/active')
        ]);

        // Filter out any null/undefined entries
        // Handle both paginated response (data.data.data) and direct array (data.data or data)
        let patientData = patientsRes.data.data?.data || patientsRes.data.data || patientsRes.data || [];
        patients.value = Array.isArray(patientData) ? patientData.filter(p => p && p.patient_id) : [];

        console.log('Loaded patients:', patients.value.length);

        allServices.value = servicesRes.data || [];
        costHeads.value = costHeadsRes.data || [];
        doctors.value = doctorsRes.data.data || doctorsRes.data || [];
        insuranceCompanies.value = insuranceRes.data || [];
        gstPlans.value = gstPlansRes.data || [];

        console.log('Loaded GST plans:', gstPlans.value.length);

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
                payment_mode: bill.payment_mode || 'cash',
                insurance_company: bill.insurance_company || '',
                policy_number: bill.policy_number || '',
                approved_amount: Number(bill.approved_amount) || 0,
                copay_amount: Number(bill.copay_amount) || 0,
                insurance_amount: Number(bill.insurance_amount) || 0,
                discount_amount: Number(bill.discount_amount) || 0,
                discount_percent: Number(bill.discount_percent) || 0,
                tax_amount: Number(bill.tax_amount) || 0,
                refund_amount: Number(bill.refund_amount || bill.adjustment) || 0,
                items: (bill.details || []).map(detail => ({
                    ...detail,
                    service_id: detail.item_id,
                    ward_name: detail.ward_name || '',
                    bed_name: detail.bed_name || '',
                    service_date: detail.service_date ? detail.service_date.slice(0, 16) : null,
                    quantity: Number(detail.quantity) || 1,
                    amount: Number(detail.amount) || 0,
                    // If unit_price is 0 or missing but amount exists, calculate it
                    unit_price: Number(detail.unit_price) > 0 ? Number(detail.unit_price) : (Number(detail.amount) / (Number(detail.quantity) || 1))
                }))
            };

            // If this is an IPD bill, load IPD admissions and set selected IPD
            if (bill.bill_type === 'ipd' && bill.ipd_id) {
                await fetchIpdAdmissions(true); // Include all statuses for existing bills
                selectedIpdId.value = bill.ipd_id;

                // Load running bill to show advance payments and billing summary
                try {
                    const billResponse = await axios.get(`/api/ipd-admissions/${bill.ipd_id}/running-bill`);
                    runningBill.value = billResponse.data;
                } catch (error) {
                    console.error('Failed to load running bill:', error);
                }
            }

            // Auto-populate insurance data from patient if fields are empty
            if ((form.value.payment_mode === 'insurance' || form.value.payment_mode === 'cashless') && form.value.patient_id) {
                try {
                    const patientRes = await axios.get(`/api/patients/${form.value.patient_id}`);
                    const patientData = patientRes.data.data || patientRes.data;

                    // Only populate if fields are empty
                    if (!form.value.insurance_company && patientData.insurance_company_relation?.company_name) {
                        form.value.insurance_company = patientData.insurance_company_relation.company_name;
                    }
                    if (!form.value.policy_number && patientData.insurance_policy_no) {
                        form.value.policy_number = patientData.insurance_policy_no;
                    }
                } catch (error) {
                    console.error('Error fetching patient insurance data:', error);
                }
            }
        } else {
            // Auto-populate OPD bill if opd_id and patient_id query parameters are present
            if (route.query.opd_id && route.query.patient_id) {
                try {
                    // Load OPD visit data
                    const opdRes = await axios.get(`/api/opd-visits/${route.query.opd_id}`);
                    const opdVisit = opdRes.data;

                    // Set form defaults for OPD bill
                    form.value.bill_type = 'opd';
                    form.value.opd_id = Number(route.query.opd_id);
                    form.value.patient_id = Number(route.query.patient_id);
                    form.value.payment_mode = 'cash';
                    form.value.bill_date = new Date().toISOString().split('T')[0];

                    // Set selectedPatient for any UI components that might use it
                    const patient = patients.value.find(p => p && p.patient_id == route.query.patient_id);
                    if (patient) {
                        selectedPatient.value = patient;
                    }

                    // Find OPD cost head
                    const opdCostHead = costHeads.value.find(ch =>
                        ch.cost_head_name.toLowerCase().includes('opd') ||
                        ch.cost_head_name.toLowerCase() === 'consultation'
                    );

                    // Add consultation fee as first item if it exists
                    if (opdVisit.consultation_fee > 0) {
                        const consultationService = allServices.value.find(s =>
                            s.service_name.toLowerCase().includes('consultation') ||
                            s.service_name.toLowerCase().includes('opd')
                        );

                        form.value.items = [{
                            service_id: consultationService?.hospital_service_id || '',
                            cost_head_id: opdCostHead?.cost_head_id || '',
                            item_name: 'OPD Consultation',
                            item_type: 'service',
                            quantity: 1,
                            unit_price: opdVisit.consultation_fee,
                            amount: opdVisit.consultation_fee,
                            service_date: null,
                            doctor_id: opdVisit.doctor_id || null
                        }];
                    }

                    // Add OPD services if any
                    if (opdVisit.services && opdVisit.services.length > 0) {
                        const serviceItems = opdVisit.services.map(svc => ({
                            service_id: svc.service_id || '',
                            cost_head_id: opdCostHead?.cost_head_id || '',
                            item_name: svc.service?.service_name || 'Service',
                            item_type: 'service',
                            quantity: svc.quantity || 1,
                            unit_price: svc.rate || 0,
                            amount: svc.amount || 0,
                            service_date: null,
                            doctor_id: opdVisit.doctor_id || null
                        }));

                        if (form.value.items.length > 0) {
                            form.value.items.push(...serviceItems);
                        } else {
                            form.value.items = serviceItems;
                        }
                    }
                } catch (error) {
                    console.error('Error loading OPD visit data:', error);
                    // Still set basic defaults even if OPD data fails to load
                    form.value.bill_type = 'opd';
                    form.value.patient_id = parseInt(route.query.patient_id);
                    form.value.payment_mode = 'cash';
                    form.value.bill_date = new Date().toISOString().split('T')[0];
                }
            }
            // Auto-select IPD patient if ipd_id query parameter is present
            else if (route.query.ipd_id) {
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

const calculateBedChargesItem = (admission) => {
    console.log('=== CALCULATE BED CHARGES - WITH BED TRANSFER SUPPORT ===');
    console.log('Admission data:', admission);

    if (!admission || !admission.bed) {
        console.log('No admission or bed found');
        return [];
    }

    const transferCount = admission.bed_transfers?.length || 0;
    console.log('Current bed:', admission.bed?.bed_number);
    console.log('Bed transfers:', admission.bed_transfers);
    console.log('Number of bed transfers:', transferCount);

    // Find "Bed Charges" service from hospital services
    const bedChargeService = allServices.value.find(s =>
        s.service_name && s.service_name.toLowerCase().includes('bed charge')
    );

    const serviceId = bedChargeService?.hospital_service_id || '';

    // Get cost head from bed charge service or find bed charges cost head
    let costHeadId = '';
    if (bedChargeService && bedChargeService.cost_head_id) {
        costHeadId = bedChargeService.cost_head_id;
    } else {
        const bedCostHead = costHeads.value.find(ch =>
            ch.cost_head_name.toLowerCase().includes('bed') ||
            ch.cost_head_name.toLowerCase().includes('accommodation')
        );
        costHeadId = bedCostHead?.cost_head_id || '';
    }

    // Helper function to get bed rate for a specific bed
    const getBedRateForBed = (bed) => {
        if (!bed) return 0;

        // Try hospital service room price first
        if (bedChargeService && bed.room_id && bedChargeService.prices && bedChargeService.prices.length > 0) {
            const roomPrice = bedChargeService.prices.find(p => p.room_id == bed.room_id);
            if (roomPrice && roomPrice.price > 0) {
                return Number(roomPrice.price);
            }
        }

        // Fall back to service base price
        if (bedChargeService && bedChargeService.base_price && bedChargeService.base_price > 0) {
            return Number(bedChargeService.base_price);
        }

        // Fall back to bed/ward charges
        if (bed.charges_per_day && bed.charges_per_day > 0) {
            return Number(bed.charges_per_day);
        }

        if (bed.ward && bed.ward.charges_per_day > 0) {
            return Number(bed.ward.charges_per_day);
        }

        return 0;
    };

    // Use selected dates or defaults
    const fromDate = new Date(bedChargeFromDate.value || admission.admission_date);
    const toDate = new Date(bedChargeToDate.value || (admission.discharge_date || new Date()));

    // Build bed periods from admission and transfers
    const bedPeriods = [];

    // Sort bed transfers by transfer datetime
    // Note: Laravel serializes to camelCase (bedTransfers, fromBed, toBed) in JSON
    const transfers = (admission.bed_transfers || admission.bedTransfers || []).sort((a, b) => {
        const dateA = new Date(a.transfer_datetime || 0);
        const dateB = new Date(b.transfer_datetime || 0);
        return dateA - dateB;
    });

    if (transfers.length > 0) {
        console.log('Processing transfers:', transfers.length);

        // First period: admission to day before first transfer
        const firstTransfer = transfers[0];
        console.log('First transfer:', firstTransfer);

        // Support both snake_case and camelCase (Laravel can return either)
        const fromBed = firstTransfer.from_bed || firstTransfer.fromBed;
        const toBed = firstTransfer.to_bed || firstTransfer.toBed;

        console.log('First transfer fromBed:', fromBed);
        console.log('First transfer fromBed.ward:', fromBed?.ward);
        console.log('First transfer fromBed.ward.ward_name:', fromBed?.ward?.ward_name);

        // Get just the date string (YYYY-MM-DD) from transfer datetime
        const transferDateStr = firstTransfer.transfer_datetime.split('T')[0];
        const transferDate = new Date(transferDateStr + 'T00:00:00');

        // Day before transfer (set to end of that day for proper comparison)
        const dayBeforeTransfer = new Date(transferDate);
        dayBeforeTransfer.setDate(dayBeforeTransfer.getDate() - 1);
        dayBeforeTransfer.setHours(23, 59, 59, 999); // End of day

        console.log('Transfer datetime:', firstTransfer.transfer_datetime);
        console.log('Transfer date (start of day):', transferDate);
        console.log('Day before transfer:', dayBeforeTransfer);

        if (fromBed) {
            console.log('Creating first bed period for bed:', fromBed.bed_number);
            console.log('fromBed details:', {
                bed_id: fromBed.bed_id,
                bed_number: fromBed.bed_number,
                ward_id: fromBed.ward_id,
                ward_name: fromBed.ward?.ward_name
            });
            bedPeriods.push({
                bed: fromBed,
                fromDate: new Date(fromDate),
                toDate: dayBeforeTransfer
            });
        } else {
            console.log('WARNING: No fromBed data in first transfer!');
        }

        // Middle periods: between transfers
        for (let i = 0; i < transfers.length - 1; i++) {
            const currentTransfer = transfers[i];
            const nextTransfer = transfers[i + 1];

            const currentToBed = currentTransfer.to_bed || currentTransfer.toBed;

            const currentDateStr = currentTransfer.transfer_datetime.split('T')[0];
            const currentDayStart = new Date(currentDateStr + 'T00:00:00');

            const nextDateStr = nextTransfer.transfer_datetime.split('T')[0];
            const nextDayStart = new Date(nextDateStr + 'T00:00:00');
            const dayBeforeNext = new Date(nextDayStart);
            dayBeforeNext.setDate(dayBeforeNext.getDate() - 1);

            bedPeriods.push({
                bed: currentToBed,
                fromDate: currentDayStart,
                toDate: dayBeforeNext
            });
        }

        // Last period: from last transfer to discharge/today
        const lastTransfer = transfers[transfers.length - 1];
        const lastToBed = lastTransfer.to_bed || lastTransfer.toBed;
        console.log('lastToBed details:', {
            bed_id: lastToBed?.bed_id,
            bed_number: lastToBed?.bed_number,
            ward_id: lastToBed?.ward_id,
            ward_name: lastToBed?.ward?.ward_name
        });
        const lastDateStr = lastTransfer.transfer_datetime.split('T')[0];
        const lastDayStart = new Date(lastDateStr + 'T00:00:00');

        bedPeriods.push({
            bed: lastToBed,
            fromDate: lastDayStart,
            toDate: new Date(toDate)
        });
    } else {
        // No transfers - single bed for entire period
        bedPeriods.push({
            bed: admission.bed,
            fromDate: new Date(fromDate),
            toDate: new Date(toDate)
        });
    }

    console.log('Bed periods created:', bedPeriods.length);

    // DEBUG: Check what's actually in bedPeriods
    console.log('=== BED PERIODS ARRAY ===');
    bedPeriods.forEach((period, index) => {
        console.log(`Period ${index + 1}:`, {
            bedNumber: period.bed?.bed_number,
            bedId: period.bed?.bed_id,
            wardName: period.bed?.ward?.ward_name,
            wardId: period.bed?.ward_id,
            fromDate: period.fromDate,
            toDate: period.toDate
        });
    });

    // Create line items for each bed period
    const bedChargeItems = [];

    for (const period of bedPeriods) {
        console.log('Processing bed period:', {
            bedNumber: period.bed?.bed_number,
            ward: period.bed?.ward?.ward_name,
            room: period.bed?.room?.room_name,
            fromDate: period.fromDate,
            toDate: period.toDate
        });

        const bedRate = getBedRateForBed(period.bed);

        if (bedRate <= 0) {
            console.log(`No rate found for bed ${period.bed?.bed_number}`);
            continue;
        }

        // Capture values as constants to prevent reference issues
        const periodBedNumber = String(period.bed?.bed_number || 'Unknown');
        const periodRoomName = String(period.bed?.room?.room_name || '');
        const periodWardName = String(period.bed?.ward?.ward_name || 'Unknown Ward');

        console.log(`Creating charges for: Ward="${periodWardName}", Bed=${periodBedNumber}, Rate=${bedRate}`);
        console.log('Full period.bed object:', JSON.stringify({
            bed_number: period.bed?.bed_number,
            ward_id: period.bed?.ward_id,
            ward: period.bed?.ward,
            room: period.bed?.room
        }));

        // Build label in format: "Bed Charges - Ward Name - Bed Number"
        let bedLabel = 'Bed Charges';
        if (periodWardName) {
            bedLabel += ` - ${periodWardName}`;
        }
        bedLabel += ` - Bed ${periodBedNumber}`;

        const currentDate = new Date(period.fromDate);

        while (currentDate <= period.toDate) {
            // Get local date string to avoid timezone issues
            const year = currentDate.getFullYear();
            const month = String(currentDate.getMonth() + 1).padStart(2, '0');
            const day = String(currentDate.getDate()).padStart(2, '0');
            const dateStr = `${year}-${month}-${day}`;
            const dateTimeStr = dateStr + 'T00:00';

            // Create object with literal values (not any kind of reference)
            bedChargeItems.push({
                cost_head_id: costHeadId,
                service_id: serviceId,
                item_name: 'Bed Charges' + (periodWardName ? ' - ' + periodWardName : '') + ' - Bed ' + periodBedNumber,
                ward_name: periodWardName + '', // Force string concatenation
                bed_name: 'Bed ' + periodBedNumber,
                service_date: dateTimeStr,
                doctor_id: null,
                quantity: 1,
                unit_price: bedRate,
                amount: bedRate
            });

            console.log(`PUSHED ITEM: ward=${bedChargeItems[bedChargeItems.length - 1].ward_name}, bed=${bedChargeItems[bedChargeItems.length - 1].bed_name}, date=${dateStr}`);

            currentDate.setDate(currentDate.getDate() + 1);
        }
    }

    console.log('Created bed charge items:', bedChargeItems.length);
    if (bedChargeItems.length > 0) {
        console.log('ALL BED CHARGE ITEMS:');
        bedChargeItems.forEach((item, index) => {
            console.log(`Item ${index + 1}:`, {
                item_name: item.item_name,
                ward_name: item.ward_name,
                bed_name: item.bed_name,
                service_date: item.service_date,
                rate: item.unit_price
            });
        });
    }

    return bedChargeItems;
};

const recalculateBedCharges = () => {
    if (!currentAdmission.value || !currentAdmission.value.bed) return;

    // Remove all existing bed charge items
    form.value.items = form.value.items.filter(item =>
        !item.item_name || !item.item_name.includes('Bed Charges')
    );

    // Calculate new bed charge items (one per day)
    const newBedChargeItems = calculateBedChargesItem(currentAdmission.value);

    if (newBedChargeItems && newBedChargeItems.length > 0) {
        // Add bed charge items at the beginning
        form.value.items = [...newBedChargeItems, ...form.value.items];
    }
};

const onBillTypeChange = async () => {
    if (form.value.bill_type === 'ipd') {
        // Fetch IPD admitted patients
        await fetchIpdAdmissions();
        form.value.patient_id = '';
        selectedIpdId.value = '';
        runningBill.value = null;
    } else if (form.value.bill_type === 'opd') {
        // Fetch today's OPD patients
        await fetchTodayOpdPatients();
        // Clear IPD data
        ipdAdmissions.value = [];
        selectedIpdId.value = '';
        runningBill.value = null;
        form.value.ipd_id = '';
    } else {
        // Clear IPD data
        ipdAdmissions.value = [];
        selectedIpdId.value = '';
        runningBill.value = null;
        form.value.ipd_id = '';
    }
};

const fetchIpdAdmissions = async (includeAll = false) => {
    try {
        loading.value = true;
        const params = {
            per_page: 1000
        };

        // Only filter by 'admitted' status when creating new bills
        if (!includeAll) {
            params.status = 'admitted';
        }

        const response = await axios.get('/api/ipd-admissions', { params });
        ipdAdmissions.value = response.data.data || response.data || [];
    } catch (error) {
        console.error('Error fetching IPD admissions:', error);
        alert('Error loading IPD admissions');
    } finally {
        loading.value = false;
    }
};

const fetchTodayOpdPatients = async () => {
    try {
        loading.value = true;
        const today = new Date().toISOString().split('T')[0];
        const response = await axios.get('/api/opd-visits', {
            params: {
                date: today
            }
        });

        console.log('OPD API response:', response.data);

        // Extract unique patients from OPD visits
        const visits = response.data.visits || [];
        const uniquePatients = [];
        const patientIds = new Set();

        visits.forEach(visit => {
            if (visit.patient && visit.patient.patient_id && !patientIds.has(visit.patient.patient_id)) {
                patientIds.add(visit.patient.patient_id);
                uniquePatients.push({
                    patient_id: visit.patient.patient_id,
                    pcd: visit.patient.pcd || visit.patient.patient_code,
                    patient_name: visit.patient.patient_name || `${visit.patient.first_name || ''} ${visit.patient.last_name || ''}`.trim(),
                    first_name: visit.patient.first_name,
                    last_name: visit.patient.last_name
                });
            }
        });

        opdPatients.value = uniquePatients;
        console.log('Loaded today\'s OPD patients:', uniquePatients.length, uniquePatients);
    } catch (error) {
        console.error('Error fetching OPD patients:', error);
        // Fall back to all patients if OPD fetch fails
        opdPatients.value = [];
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

        // Fetch full admission details with bed information
        const admissionResponse = await axios.get(`/api/ipd-admissions/${selectedIpdId.value}`);
        const admission = admissionResponse.data.admission;

        console.log('IPD Admission data:', admission);
        console.log('Patient ID:', admission?.patient_id);

        if (admission) {
            form.value.patient_id = admission.patient_id;
            form.value.ipd_id = selectedIpdId.value;
            currentAdmission.value = admission;

            // Set default bed charge dates
            if (admission.bed) {
                bedChargeFromDate.value = admission.admission_date ? admission.admission_date.split('T')[0] : '';
                bedChargeToDate.value = admission.discharge_date ? admission.discharge_date.split('T')[0] : new Date().toISOString().split('T')[0];
            }
        }

        // Prepare bill items array
        let billItems = [];

        // Add bed charges if bed is assigned
        if (admission.bed) {
            console.log('=== CALCULATING BED CHARGES ===');
            const bedChargesItems = calculateBedChargesItem(admission);
            console.log('Bed charges items generated:', bedChargesItems.length);

            if (bedChargesItems && bedChargesItems.length > 0) {
                billItems = [...billItems, ...bedChargesItems];
                console.log('Total bill items after adding bed charges:', billItems.length);
            }
        }

        // Add IPD services (excluding old bed charges - we calculate them separately now)
        if (ipdServices.value.length > 0) {
            const serviceItems = ipdServices.value
                .filter(service => {
                    // Exclude bed charges - we auto-calculate them separately
                    const isBedCharge = service.service_name &&
                        (service.service_name.toLowerCase().includes('bed charge') ||
                         service.service_name.toLowerCase() === 'bed charges');
                    return !isBedCharge;
                })
                .map(service => {
                    // Format service_date for datetime-local input
                    let formattedDate = null;
                    if (service.service_date) {
                        const dateObj = new Date(service.service_date);
                        const year = dateObj.getFullYear();
                        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        const hours = String(dateObj.getHours()).padStart(2, '0');
                        const minutes = String(dateObj.getMinutes()).padStart(2, '0');
                        formattedDate = `${year}-${month}-${day}T${hours}:${minutes}`;
                    }

                    return {
                        cost_head_id: service.cost_head_id || '',
                        service_id: service.service_id || '',
                        item_name: service.service_name,
                        ward_name: service.ward_name || '',
                        bed_name: service.bed_name || '',
                        service_date: formattedDate,
                        doctor_id: service.doctor_id || null,
                        quantity: Number(service.quantity) || 1,
                        unit_price: Number(service.rate) || 0,
                        amount: Number(service.net_amount) || (Number(service.quantity) * Number(service.rate))
                    };
                });
            billItems = [...billItems, ...serviceItems];

            console.log('Filtered out old bed charges, added other services:', serviceItems.length);
        }

        // Set bill items
        if (billItems.length > 0) {
            form.value.items = billItems;
            console.log('Final form items set:', form.value.items.length);

            // Log bed charge summary
            const bedChargeCount = billItems.filter(item => item.item_name && item.item_name.includes('Bed Charges')).length;
            if (bedChargeCount > 0) {
                console.log(`Created ${bedChargeCount} bed charge items with ward-specific labels`);
            }
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
        ward_name: '',
        bed_name: '',
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
    const patient = patients.value.find(p => p?.patient_id == form.value.patient_id);
    if (patient) {
        const patientCode = patient.pcd || patient.patient_code || '';
        const fullName = patient.patient_name || `${patient.first_name || ''} ${patient.last_name || ''}`.trim();
        return patientCode ? `${patientCode} - ${fullName}` : fullName;
    }
    return '';
};

// Get actual bed rate from hospital services
const getBedRate = () => {
    if (!currentAdmission.value || !currentAdmission.value.bed) {
        return 0;
    }

    // Find "Bed Charges" service from hospital services
    const bedChargeService = allServices.value.find(s =>
        s.service_name && s.service_name.toLowerCase().includes('bed charge')
    );

    if (bedChargeService) {
        // Look for room-specific price first
        if (currentAdmission.value.bed.room_id && bedChargeService.prices && bedChargeService.prices.length > 0) {
            const roomPrice = bedChargeService.prices.find(p => p.room_id == currentAdmission.value.bed.room_id);
            if (roomPrice && roomPrice.price > 0) {
                return Number(roomPrice.price);
            }
        }

        // Fall back to service base price
        if (bedChargeService.base_price && bedChargeService.base_price > 0) {
            return Number(bedChargeService.base_price);
        }
    }

    // Fall back to bed/ward charges
    if (currentAdmission.value.bed.charges_per_day && currentAdmission.value.bed.charges_per_day > 0) {
        return Number(currentAdmission.value.bed.charges_per_day);
    }

    if (currentAdmission.value.bed.ward && currentAdmission.value.bed.ward.charges_per_day > 0) {
        return Number(currentAdmission.value.bed.ward.charges_per_day);
    }

    return 0;
};

const printBill = () => {
    if (route.params.id) {
        window.open(`/api/bills/${route.params.id}/print`, '_blank');
    }
};

const saveBill = async () => {
    console.log('Form patient_id:', form.value.patient_id);
    console.log('Form data:', form.value);

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

        const response = await axios.post('/api/bills', billData);

        // If bill was created from OPD, update OPD payment status and redirect to OPD list
        if (route.query.opd_id) {
            try {
                console.log('Updating OPD payment status for:', route.query.opd_id);
                const paymentResponse = await axios.post(`/api/opd-visits/${route.query.opd_id}/record-payment`, {
                    amount: total.value,
                    payment_mode: form.value.payment_mode,
                    reference_number: response.data.bill_number
                });
                console.log('OPD payment updated:', paymentResponse.data);
                alert('Bill created and payment recorded successfully!');
                // Add timestamp to force refresh
                router.push(`/opd?refresh=${Date.now()}`);
            } catch (error) {
                console.error('Error updating OPD payment status:', error);
                alert('Bill created but failed to update OPD payment status: ' + (error.response?.data?.message || error.message));
                router.push('/opd');
            }
        } else {
            alert('Bill created successfully!');
            router.push('/billing');
        }
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
        // Send all updatable fields including items for service_date and doctor_id
        const updateData = {
            payment_mode: form.value.payment_mode,
            insurance_company: form.value.insurance_company,
            policy_number: form.value.policy_number,
            approved_amount: form.value.approved_amount,
            copay_amount: copayAmount.value,
            insurance_amount: form.value.insurance_amount,
            discount_amount: form.value.discount_amount,
            discount_percent: form.value.discount_percent,
            tax_amount: form.value.tax_amount,
            refund_amount: refundAmount.value,
            adjustment: refundAmount.value, // For backward compatibility
            items: form.value.items.map(item => ({
                bill_detail_id: item.bill_detail_id,
                item_id: item.service_id || item.item_id,
                cost_head_id: item.cost_head_id,
                service_id: item.service_id,
                service_date: item.service_date,
                doctor_id: item.doctor_id,
                item_name: item.item_name,
                quantity: item.quantity,
                rate: item.unit_price,
                amount: item.amount
            }))
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
