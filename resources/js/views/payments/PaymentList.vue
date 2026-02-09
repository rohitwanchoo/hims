<template>
    <div class="payment-list">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <h4 class="mb-0 fw-bold">Payments Dashboard</h4>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="fetchPayments">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button class="modern-btn modern-btn-primary" @click="showModal = true">
                    <i class="bi bi-plus-lg"></i>
                    <span>Record Payment</span>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-2 mb-1">
            <div class="col-4">
                <div class="stat-card-compact stat-card-gradient-success">
                    <div class="stat-label-compact">Total Collections</div>
                    <div class="stat-value-compact">{{ formatCurrency(summary.total) }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card-compact stat-card-gradient-primary">
                    <div class="stat-label-compact">Total Payments</div>
                    <div class="stat-value-compact">{{ summary.count }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card-compact stat-card-gradient-info">
                    <div class="stat-label-compact">Today's Collection</div>
                    <div class="stat-value-compact">{{ formatCurrency(summary.today) }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="modern-card mb-1">
            <div class="modern-card-header clickable" @click="showFilters = !showFilters">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>Filters
                        <span v-if="hasActiveFilters" class="badge bg-primary ms-2" style="font-size: 0.7rem;">Active</span>
                    </h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0">
                        <i class="bi" :class="showFilters ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                </div>
            </div>
            <transition name="filter-collapse">
                <div v-show="showFilters" class="modern-card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="modern-label">From Date</label>
                            <input type="date" class="modern-input" v-model="filters.from_date" @change="fetchPayments">
                        </div>
                        <div class="col-md-3">
                            <label class="modern-label">To Date</label>
                            <input type="date" class="modern-input" v-model="filters.to_date" @change="fetchPayments">
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Payment Mode</label>
                            <select class="modern-select" v-model="filters.payment_mode" @change="fetchPayments">
                                <option value="">All Modes</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="upi">UPI</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="insurance">Insurance</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="modern-label">Search</label>
                            <input type="text" class="modern-input" v-model="filters.search" placeholder="Receipt #, Patient..." @keyup.enter="fetchPayments">
                        </div>
                        <div class="col-md-1">
                            <label class="modern-label">&nbsp;</label>
                            <button class="modern-btn-reset w-100" @click="clearFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'payments' }" @click="activeTab = 'payments'" style="cursor: pointer;">
                    <i class="bi bi-cash-coin me-1"></i>Payment Records
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" :class="{ active: activeTab === 'outstanding' }" @click="activeTab = 'outstanding'" style="cursor: pointer;">
                    <i class="bi bi-exclamation-circle me-1"></i>Outstanding Bills
                    <span v-if="unpaidBills.length > 0" class="badge bg-danger ms-1">{{ unpaidBills.length }}</span>
                </a>
            </li>
        </ul>

        <!-- Outstanding Bills Table -->
        <div v-if="activeTab === 'outstanding'" class="modern-card">
            <div class="modern-card-header">
                <h6 class="mb-0"><i class="bi bi-exclamation-circle me-2"></i>Outstanding Bills</h6>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 modern-table">
                        <thead>
                            <tr>
                                <th>Bill #</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Bill Amount</th>
                                <th>Paid</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="bill in unpaidBills" :key="bill.bill_id">
                                <td class="fw-semibold">{{ bill.bill_number }}</td>
                                <td>{{ formatDate(bill.bill_date) }}</td>
                                <td>{{ bill.patient?.first_name }} {{ bill.patient?.last_name }}</td>
                                <td class="fw-semibold">{{ formatCurrency(bill.total_amount) }}</td>
                                <td class="text-success">{{ formatCurrency(bill.paid_amount) }}</td>
                                <td class="fw-semibold text-danger">{{ formatCurrency(bill.due_amount) }}</td>
                                <td>
                                    <span class="badge" :class="{
                                        'bg-warning': bill.payment_status === 'partial',
                                        'bg-danger': bill.payment_status === 'pending'
                                    }">
                                        {{ bill.payment_status }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" @click="recordPaymentForBill(bill)" title="Record Payment">
                                        <i class="bi bi-cash-coin"></i> Pay
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="unpaidBills.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle fs-3 d-block mb-2 text-success"></i>
                                    No outstanding bills
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div v-if="activeTab === 'payments'" class="modern-card">
            <div class="modern-card-header">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Payment Records</h6>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 modern-table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Bill #</th>
                                <th>Patient</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Received By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in payments" :key="payment.payment_id">
                                <td class="fw-semibold">{{ payment.payment_number }}</td>
                                <td>{{ formatDate(payment.payment_date) }}</td>
                                <td>{{ payment.bill?.bill_number || '-' }}</td>
                                <td>{{ payment.patient?.first_name }} {{ payment.patient?.last_name }}</td>
                                <td class="fw-semibold text-success">{{ formatCurrency(payment.amount) }}</td>
                                <td><span class="badge bg-primary text-white text-capitalize">{{ payment.payment_mode }}</span></td>
                                <td>{{ payment.received_by_user?.full_name || '-' }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary" @click="printReceipt(payment)" title="Print Receipt">
                                        <i class="bi bi-printer"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="payments.length === 0">
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                    No payments found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" :class="{ show: showModal }" :style="{ display: showModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Payment</h5>
                        <button type="button" class="btn-close" @click="closeModal"></button>
                    </div>
                    <form @submit.prevent="savePayment">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Bill <span class="text-danger">*</span></label>
                                <select class="form-select" v-model="form.bill_id" required @change="onBillSelect">
                                    <option value="">Select Bill</option>
                                    <option v-for="bill in unpaidBills" :key="bill.bill_id" :value="bill.bill_id">
                                        {{ bill.bill_number }} - {{ bill.patient?.first_name }} {{ bill.patient?.last_name }} (Due: {{ formatCurrency(bill.due_amount) }})
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3" v-if="selectedBill">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex justify-content-between">
                                        <span>Subtotal:</span>
                                        <strong>{{ formatCurrency(selectedBill.subtotal) }}</strong>
                                    </div>
                                    <div v-if="selectedBill.discount_amount > 0" class="d-flex justify-content-between text-success">
                                        <span>Discount:</span>
                                        <strong>- {{ formatCurrency(selectedBill.discount_amount) }}</strong>
                                    </div>
                                    <div v-if="selectedBill.tax_amount > 0" class="d-flex justify-content-between">
                                        <span>Tax/GST:</span>
                                        <strong>{{ formatCurrency(selectedBill.tax_amount) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-2 mt-1">
                                        <span><strong>Bill Total:</strong></span>
                                        <strong>{{ formatCurrency(selectedBill.total_amount) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Paid:</span>
                                        <strong class="text-success">{{ formatCurrency(selectedBill.paid_amount) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-2 mt-1">
                                        <span><strong>Amount Due:</strong></span>
                                        <strong class="text-danger">{{ formatCurrency(selectedBill.due_amount) }}</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="multiModeCheck" v-model="form.isMultiMode" @change="toggleMultiMode">
                                    <label class="form-check-label" for="multiModeCheck">
                                        <strong>Split Payment (Multiple Modes)</strong>
                                    </label>
                                </div>
                            </div>

                            <!-- Single Mode Payment -->
                            <div v-if="!form.isMultiMode">
                                <div class="mb-3">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" v-model="form.amount" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                    <select class="form-select" v-model="form.payment_mode" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="upi">UPI</option>
                                        <option value="cheque">Cheque</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="insurance">Insurance</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" v-model="form.reference_number" placeholder="Transaction/Cheque number">
                                </div>
                            </div>

                            <!-- Multi Mode Payment -->
                            <div v-else>
                                <div class="mb-3">
                                    <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" v-model="form.totalAmount" step="0.01" required readonly>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">Payment Modes</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="addPaymentMode">
                                            <i class="bi bi-plus-lg"></i> Add Mode
                                        </button>
                                    </div>

                                    <div v-for="(mode, index) in form.paymentModes" :key="index" class="card mb-2">
                                        <div class="card-body p-2">
                                            <div class="row g-2">
                                                <div class="col-md-5">
                                                    <select class="form-select form-select-sm" v-model="mode.payment_mode" required>
                                                        <option value="">Select Mode</option>
                                                        <option value="cash">Cash</option>
                                                        <option value="card">Card</option>
                                                        <option value="upi">UPI</option>
                                                        <option value="cheque">Cheque</option>
                                                        <option value="bank_transfer">Bank Transfer</option>
                                                        <option value="insurance">Insurance</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="number" class="form-control form-control-sm" v-model="mode.amount"
                                                           placeholder="Amount" step="0.01" required @input="calculateMultiModeTotal">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control form-control-sm" v-model="mode.reference_number"
                                                           placeholder="Ref #">
                                                </div>
                                            </div>
                                            <button v-if="form.paymentModes.length > 1" type="button"
                                                    class="btn btn-sm btn-link text-danger p-0 mt-1"
                                                    @click="removePaymentMode(index)">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-2 p-2 small">
                                        <div class="d-flex justify-content-between">
                                            <span>Total Entered:</span>
                                            <strong :class="{'text-danger': multiModeTotal !== parseFloat(form.totalAmount)}">
                                                {{ formatCurrency(multiModeTotal) }}
                                            </strong>
                                        </div>
                                        <div v-if="multiModeTotal !== parseFloat(form.totalAmount)" class="text-danger small mt-1">
                                            <i class="bi bi-exclamation-triangle"></i>
                                            Amount mismatch! Difference: {{ formatCurrency(Math.abs(parseFloat(form.totalAmount) - multiModeTotal)) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" v-model="form.notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                                Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showModal" @click="closeModal"></div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const payments = ref([]);
const unpaidBills = ref([]);
const showModal = ref(false);
const saving = ref(false);
const showFilters = ref(false);
const activeTab = ref('payments');

const filters = reactive({
    from_date: '',
    to_date: '',
    payment_mode: '',
    search: ''
});

const form = reactive({
    bill_id: '',
    amount: '',
    payment_mode: 'cash',
    reference_number: '',
    notes: '',
    opd_id: null, // Track OPD ID if payment is from OPD
    isMultiMode: false,
    totalAmount: '',
    paymentModes: [{ payment_mode: '', amount: '', reference_number: '' }]
});

const summary = reactive({
    total: 0,
    count: 0,
    today: 0
});

const selectedBill = computed(() => unpaidBills.value.find(b => b.bill_id === form.bill_id));

// Computed
const hasActiveFilters = computed(() => {
    return filters.from_date !== '' ||
           filters.to_date !== '' ||
           filters.payment_mode !== '' ||
           filters.search !== '';
});

const multiModeTotal = computed(() => {
    return form.paymentModes.reduce((sum, mode) => sum + (parseFloat(mode.amount) || 0), 0);
});

const toggleMultiMode = () => {
    if (form.isMultiMode) {
        form.totalAmount = form.amount || (selectedBill.value ? selectedBill.value.due_amount : '');
        form.paymentModes = [{ payment_mode: '', amount: '', reference_number: '' }];
    }
};

const addPaymentMode = () => {
    form.paymentModes.push({ payment_mode: '', amount: '', reference_number: '' });
};

const removePaymentMode = (index) => {
    if (form.paymentModes.length > 1) {
        form.paymentModes.splice(index, 1);
    }
};

const calculateMultiModeTotal = () => {
    // Trigger reactivity
};

const fetchPayments = async () => {
    try {
        const params = {};
        if (filters.from_date) params.from_date = filters.from_date;
        if (filters.to_date) params.to_date = filters.to_date;
        if (filters.payment_mode) params.payment_mode = filters.payment_mode;
        if (filters.search) params.search = filters.search;

        const response = await axios.get('/api/payments', { params });
        payments.value = response.data.data || [];

        // Use server-calculated summary if available
        if (response.data.summary) {
            summary.total = response.data.summary.total;
            summary.count = response.data.summary.count;
            summary.today = response.data.summary.today;
        }
    } catch (error) {
        console.error('Error fetching payments:', error);
        alert('Error loading payments');
    }
};

const fetchUnpaidBills = async () => {
    try {
        // Fetch all bills and filter for unpaid ones
        const response = await axios.get('/api/bills', { params: { per_page: 1000 } });
        const bills = response.data.data || response.data;
        // Filter bills that have due amount > 0 (pending or partial)
        unpaidBills.value = Array.isArray(bills)
            ? bills.filter(b => parseFloat(b.due_amount) > 0 && ['pending', 'partial'].includes(b.payment_status))
            : [];
    } catch (error) {
        console.error('Error fetching unpaid bills:', error);
        unpaidBills.value = [];
    }
};

const onBillSelect = () => {
    if (selectedBill.value) {
        form.amount = selectedBill.value.due_amount;
        form.totalAmount = selectedBill.value.due_amount;
    }
};

const closeModal = () => {
    showModal.value = false;
    Object.assign(form, {
        bill_id: '',
        amount: '',
        payment_mode: 'cash',
        reference_number: '',
        notes: '',
        opd_id: null,
        isMultiMode: false,
        totalAmount: '',
        paymentModes: [{ payment_mode: '', amount: '', reference_number: '' }]
    });
};

const savePayment = async () => {
    saving.value = true;
    try {
        const paymentDate = new Date().toISOString().split('T')[0];

        if (form.isMultiMode) {
            // Validate total amounts match
            if (multiModeTotal.value !== parseFloat(form.totalAmount)) {
                alert('Total amount mismatch! Please ensure the sum of all payment modes equals the total amount.');
                saving.value = false;
                return;
            }

            // Validate all modes are selected
            const invalidModes = form.paymentModes.filter(m => !m.payment_mode || !m.amount || parseFloat(m.amount) <= 0);
            if (invalidModes.length > 0) {
                alert('Please fill in all payment modes with valid amounts.');
                saving.value = false;
                return;
            }

            // Create single payment with multiple modes
            await axios.post('/api/payments', {
                bill_id: form.bill_id,
                amount: parseFloat(form.totalAmount),
                payment_mode: 'multi',
                payment_modes: form.paymentModes.map(mode => ({
                    payment_mode: mode.payment_mode,
                    amount: parseFloat(mode.amount),
                    reference_number: mode.reference_number || ''
                })),
                notes: form.notes || 'Multi-mode payment',
                payment_date: paymentDate
            });
            alert('Multi-mode payment recorded successfully!');
        } else {
            // Single mode payment
            await axios.post('/api/payments', {
                bill_id: form.bill_id,
                amount: form.amount,
                payment_mode: form.payment_mode,
                reference_number: form.reference_number,
                notes: form.notes,
                payment_date: paymentDate
            });
            alert('Payment recorded successfully!');
        }

        // Refresh data
        await fetchPayments();
        await fetchUnpaidBills();
        closeModal();
    } catch (error) {
        alert(error.response?.data?.message || 'Error recording payment');
    }
    saving.value = false;
};

const clearFilters = () => {
    filters.from_date = '';
    filters.to_date = '';
    filters.payment_mode = '';
    filters.search = '';
    fetchPayments();
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-IN');
const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
const printReceipt = (payment) => {
    window.open(`/print/payment-receipt/${payment.payment_id}`, '_blank');
};

const recordPaymentForBill = (bill) => {
    form.bill_id = bill.bill_id;
    form.amount = bill.due_amount;
    form.totalAmount = bill.due_amount;
    showModal.value = true;
};

onMounted(async () => {
    await fetchPayments();
    await fetchUnpaidBills();

    // Check for query parameters to auto-open payment modal
    if (route.query.bill_id) {
        // Small delay to ensure DOM is updated with bill options
        setTimeout(() => {
            form.bill_id = parseInt(route.query.bill_id);
            if (route.query.amount) {
                form.amount = parseFloat(route.query.amount);
            } else {
                // Auto-fill amount from selected bill
                onBillSelect();
            }
            // Capture OPD ID if this payment is from OPD
            if (route.query.opd_id) {
                form.opd_id = parseInt(route.query.opd_id);
            }
            showModal.value = true;
        }, 100);
    }
});
</script>

<style scoped>
/* Responsive optimization for 13-14" screens */
@media (max-width: 1600px) {
    h2, h4 {
        font-size: 1.5rem !important;
    }

    .stat-value-compact {
        font-size: 1.1rem !important;
    }

    .stat-card-compact {
        padding: 0.65rem 0.85rem !important;
        min-height: 65px !important;
    }

    .stat-label-compact {
        font-size: 0.65rem !important;
    }

    .modern-table {
        font-size: 0.813rem;
    }

    .modern-table thead th,
    .modern-table tbody td {
        padding: 0.5rem 0.4rem;
    }

    .btn-sm {
        padding: 0.25rem 0.35rem;
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .modern-btn {
        padding: 0.5rem 1rem;
        font-size: 0.813rem;
    }

    .modern-card-header {
        padding: 0.5rem 0.75rem;
    }

    .modern-card-body {
        padding: 0.75rem;
    }
}

/* Modern Dashboard Styles */
.payment-list {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 1.5rem;
}

/* Modern Buttons */
.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    color: white;
}

.modern-btn-outline {
    background: white;
    color: #6c757d;
    border: 1px solid #e0e0e0;
}

.modern-btn-outline:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

/* Compact Stat Cards */
.stat-card-compact {
    border-radius: 12px;
    padding: 0.75rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    border: none;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    height: 100%;
    min-height: 70px;
    position: relative;
    overflow: hidden;
}

.stat-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-label-compact {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    opacity: 0.9;
    color: white;
}

.stat-value-compact {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
    color: white;
}

/* Modern Stat Cards with Gradients */
.stat-card {
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    min-height: 130px;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

/* Gradient Backgrounds */
.stat-card-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card-gradient-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stat-content-full {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-label-top {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.stat-value-large {
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1;
    margin: 0.25rem 0;
}

.stat-description {
    font-size: 0.75rem;
    opacity: 0.85;
    line-height: 1.4;
}

/* Modern Cards */
.modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.modern-card-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}

.modern-card-header h6 {
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.modern-card-body {
    padding: 1rem;
}

/* Modern Table */
.modern-table {
    font-size: 0.875rem;
}

.modern-table thead th {
    background: #fafafa;
    color: #6c757d;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 1.25rem;
    border-bottom: 2px solid #f0f0f0;
}

.modern-table tbody td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    color: #2c3e50;
}

.modern-table tbody tr {
    transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
}

/* Modern Filter Styles */
.modern-card-header.clickable {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
}

.modern-card-header.clickable:hover {
    background: #f5f5f5;
}

.modern-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-select,
.modern-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
    color: #2c3e50;
}

.modern-select:focus,
.modern-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-select:hover,
.modern-input:hover {
    border-color: #b0b0b0;
}

.modern-btn-reset {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #6c757d;
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.modern-btn-reset:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

/* Filter Collapse Animation */
.filter-collapse-enter-active,
.filter-collapse-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.filter-collapse-enter-from,
.filter-collapse-leave-to {
    max-height: 0;
    opacity: 0;
}
</style>
