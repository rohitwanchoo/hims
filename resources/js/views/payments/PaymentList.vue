<template>
    <div class="payment-list">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1 fw-bold">Payments Dashboard</h2>
                <p class="text-muted mb-0 small">Manage and track all payment transactions</p>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="fetchPayments">
                    <i class="bi bi-arrow-clockwise"></i>
                    <span>Refresh</span>
                </button>
                <button class="modern-btn modern-btn-primary" @click="showModal = true">
                    <i class="bi bi-plus-lg"></i>
                    <span>Record Payment</span>
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-success">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total Collections</div>
                        <div class="stat-value-large">{{ formatCurrency(summary.total) }}</div>
                        <div class="stat-description">All payment collections</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-primary">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Total Payments</div>
                        <div class="stat-value-large">{{ summary.count }}</div>
                        <div class="stat-description">Payment transactions</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-4 col-md-6">
                <div class="stat-card stat-card-gradient-info">
                    <div class="stat-content-full">
                        <div class="stat-label-top">Today's Collection</div>
                        <div class="stat-value-large">{{ formatCurrency(summary.today) }}</div>
                        <div class="stat-description">Collected today</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="modern-card mb-4">
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

        <!-- Payments Table -->
        <div class="modern-card">
            <div class="modern-card-header">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Payment Records</h6>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 modern-table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Bill #</th>
                                <th>Patient</th>
                                <th>Amount</th>
                                <th>Mode</th>
                                <th>Received By</th>
                                <th width="80">Actions</th>
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
                                        <span>Bill Total:</span>
                                        <strong>{{ formatCurrency(selectedBill.total_amount) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Paid:</span>
                                        <strong>{{ formatCurrency(selectedBill.paid_amount) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Due:</span>
                                        <strong class="text-danger">{{ formatCurrency(selectedBill.due_amount) }}</strong>
                                    </div>
                                </div>
                            </div>
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
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="insurance">Insurance</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" v-model="form.reference_number" placeholder="Transaction/Cheque number">
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
    opd_id: null // Track OPD ID if payment is from OPD
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
    }
};

const closeModal = () => {
    showModal.value = false;
    Object.assign(form, { bill_id: '', amount: '', payment_mode: 'cash', reference_number: '', notes: '', opd_id: null });
};

const savePayment = async () => {
    saving.value = true;
    try {
        const paymentResponse = await axios.post('/api/payments', {
            ...form,
            payment_date: new Date().toISOString().split('T')[0]
        });

        // If payment is from OPD bill, update OPD payment status
        if (form.opd_id) {
            try {
                await axios.post(`/api/opd-visits/${form.opd_id}/payment`, {
                    amount: form.amount,
                    payment_mode: form.payment_mode,
                    reference_number: paymentResponse.data.receipt_number || form.reference_number
                });
                console.log('OPD payment status updated successfully');
            } catch (opdError) {
                console.error('Error updating OPD payment status:', opdError);
                // Don't fail the whole operation if OPD update fails
            }
        }

        alert('Payment recorded successfully!');

        // If this was from OPD, redirect back to OPD list with refresh
        if (form.opd_id) {
            closeModal();
            router.push(`/opd?refresh=${Date.now()}`);
        } else {
            await fetchPayments();
            await fetchUnpaidBills();
            closeModal();
        }
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
    padding: 1.25rem 1.5rem;
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
    padding: 1.5rem;
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
