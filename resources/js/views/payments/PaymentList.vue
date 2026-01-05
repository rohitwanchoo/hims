<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">Payments</h5>
                <p class="text-muted mb-0">Manage bill payments</p>
            </div>
            <button class="btn btn-primary" @click="showModal = true">
                <i class="bi bi-plus-lg me-1"></i> Record Payment
            </button>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" v-model="filters.from_date" @change="fetchPayments">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" v-model="filters.to_date" @change="fetchPayments">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment Mode</label>
                        <select class="form-select" v-model="filters.payment_mode" @change="fetchPayments">
                            <option value="">All Modes</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="insurance">Insurance</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" v-model="filters.search" placeholder="Receipt #, Patient..." @keyup.enter="fetchPayments">
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-light-success">
                    <div class="card-body text-center">
                        <h3 class="text-success mb-1">{{ formatCurrency(summary.total) }}</h3>
                        <small class="text-muted">Total Collections</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-primary">
                    <div class="card-body text-center">
                        <h3 class="text-primary mb-1">{{ summary.count }}</h3>
                        <small class="text-muted">Total Payments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light-info">
                    <div class="card-body text-center">
                        <h3 class="text-info mb-1">{{ formatCurrency(summary.today) }}</h3>
                        <small class="text-muted">Today's Collection</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
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
                            <td class="fw-semibold">{{ payment.receipt_number }}</td>
                            <td>{{ formatDate(payment.payment_date) }}</td>
                            <td>{{ payment.bill?.bill_number }}</td>
                            <td>{{ payment.bill?.patient?.first_name }} {{ payment.bill?.patient?.last_name }}</td>
                            <td class="fw-semibold text-success">{{ formatCurrency(payment.amount) }}</td>
                            <td><span class="badge bg-light-primary text-primary">{{ payment.payment_mode }}</span></td>
                            <td>{{ payment.received_by_user?.full_name || '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-light" @click="printReceipt(payment)">
                                    <i class="bi bi-printer"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="payments.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">No payments found</td>
                        </tr>
                    </tbody>
                </table>
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
                                        {{ bill.bill_number }} - {{ bill.patient?.first_name }} {{ bill.patient?.last_name }} (Balance: {{ formatCurrency(bill.balance_amount) }})
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
                                        <span>Balance:</span>
                                        <strong class="text-danger">{{ formatCurrency(selectedBill.balance_amount) }}</strong>
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
import axios from 'axios';

const payments = ref([]);
const unpaidBills = ref([]);
const showModal = ref(false);
const saving = ref(false);

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
    notes: ''
});

const summary = reactive({
    total: 0,
    count: 0,
    today: 0
});

const selectedBill = computed(() => unpaidBills.value.find(b => b.bill_id === form.bill_id));

const fetchPayments = async () => {
    const params = {};
    if (filters.from_date) params.from_date = filters.from_date;
    if (filters.to_date) params.to_date = filters.to_date;
    if (filters.payment_mode) params.payment_mode = filters.payment_mode;
    if (filters.search) params.search = filters.search;

    const response = await axios.get('/api/payments', { params });
    payments.value = response.data.data || response.data;

    // Calculate summary
    summary.total = payments.value.reduce((sum, p) => sum + parseFloat(p.amount), 0);
    summary.count = payments.value.length;
    const today = new Date().toISOString().split('T')[0];
    summary.today = payments.value.filter(p => p.payment_date?.startsWith(today)).reduce((sum, p) => sum + parseFloat(p.amount), 0);
};

const fetchUnpaidBills = async () => {
    const response = await axios.get('/api/bills', { params: { status: 'unpaid' } });
    const bills = response.data.data || response.data;
    unpaidBills.value = bills.filter(b => parseFloat(b.balance_amount) > 0);
};

const onBillSelect = () => {
    if (selectedBill.value) {
        form.amount = selectedBill.value.balance_amount;
    }
};

const closeModal = () => {
    showModal.value = false;
    Object.assign(form, { bill_id: '', amount: '', payment_mode: 'cash', reference_number: '', notes: '' });
};

const savePayment = async () => {
    saving.value = true;
    try {
        await axios.post('/api/payments', {
            ...form,
            payment_date: new Date().toISOString().split('T')[0]
        });
        await fetchPayments();
        await fetchUnpaidBills();
        closeModal();
    } catch (error) {
        alert(error.response?.data?.message || 'Error recording payment');
    }
    saving.value = false;
};

const formatDate = (date) => new Date(date).toLocaleDateString();
const formatCurrency = (amount) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
const printReceipt = (payment) => alert('Print functionality coming soon');

onMounted(() => {
    fetchPayments();
    fetchUnpaidBills();
});
</script>
