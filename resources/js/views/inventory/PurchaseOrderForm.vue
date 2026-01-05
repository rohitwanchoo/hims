<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>
                <i class="bi bi-cart-check me-2"></i>
                {{ isView ? 'View Purchase Order' : 'New Purchase Order' }}
            </h4>
            <router-link to="/inventory/purchase-orders" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </router-link>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Vendor *</label>
                            <select v-model="form.vendor_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Vendor</option>
                                <option v-for="vendor in vendors" :key="vendor.vendor_id" :value="vendor.vendor_id">
                                    {{ vendor.vendor_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Store *</label>
                            <select v-model="form.store_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Store</option>
                                <option v-for="store in stores" :key="store.store_id" :value="store.store_id">
                                    {{ store.store_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">PO Date *</label>
                            <input type="date" v-model="form.po_date" class="form-control" :disabled="isView" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expected Delivery</label>
                            <input type="date" v-model="form.expected_delivery_date" class="form-control" :disabled="isView">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Terms</label>
                            <select v-model="form.payment_terms" class="form-select" :disabled="isView">
                                <option value="advance">Advance</option>
                                <option value="cod">Cash on Delivery</option>
                                <option value="credit_30">Credit 30 Days</option>
                                <option value="credit_60">Credit 60 Days</option>
                                <option value="credit_90">Credit 90 Days</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea v-model="form.remarks" class="form-control" rows="2" :disabled="isView"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th width="100">Qty</th>
                                        <th>Unit</th>
                                        <th width="120">Rate</th>
                                        <th width="100">Tax %</th>
                                        <th width="120">Amount</th>
                                        <th width="80" v-if="!isView">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td>
                                            <select v-model="item.item_id" class="form-select form-select-sm" :disabled="isView" @change="onItemSelect(item)">
                                                <option value="">Select Item</option>
                                                <option v-for="i in inventoryItems" :key="i.item_id" :value="i.item_id">
                                                    {{ i.item_name }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" v-model="item.quantity" class="form-control form-control-sm" min="1" :disabled="isView" @input="calculateAmount(item)">
                                        </td>
                                        <td>{{ item.unit || '-' }}</td>
                                        <td>
                                            <input type="number" v-model="item.rate" class="form-control form-control-sm" min="0" step="0.01" :disabled="isView" @input="calculateAmount(item)">
                                        </td>
                                        <td>
                                            <input type="number" v-model="item.tax_percent" class="form-control form-control-sm" min="0" max="100" :disabled="isView" @input="calculateAmount(item)">
                                        </td>
                                        <td class="text-end">{{ formatCurrency(item.amount) }}</td>
                                        <td v-if="!isView">
                                            <button type="button" @click="removeItem(index)" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="5" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end"><strong>{{ formatCurrency(totalAmount) }}</strong></td>
                                        <td v-if="!isView"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <button v-if="!isView" type="button" @click="addItem" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>

                    <div class="d-flex gap-2" v-if="!isView">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            Create Purchase Order
                        </button>
                        <router-link to="/inventory/purchase-orders" class="btn btn-outline-secondary">Cancel</router-link>
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

const vendors = ref([]);
const stores = ref([]);
const inventoryItems = ref([]);
const loading = ref(false);

const form = ref({
    vendor_id: '',
    store_id: '',
    po_date: new Date().toISOString().split('T')[0],
    expected_delivery_date: '',
    payment_terms: 'credit_30',
    remarks: '',
    items: [{ item_id: '', quantity: 1, unit: '', rate: 0, tax_percent: 0, amount: 0 }]
});

const isView = computed(() => route.name === 'inventory.purchase-orders.view');

const totalAmount = computed(() => {
    return form.value.items.reduce((sum, item) => sum + (item.amount || 0), 0);
});

const loadData = async () => {
    try {
        const [vendorsRes, storesRes, itemsRes] = await Promise.all([
            axios.get('/api/inventory/vendors'),
            axios.get('/api/inventory/stores'),
            axios.get('/api/inventory/items')
        ]);
        vendors.value = vendorsRes.data.vendors || vendorsRes.data.data || [];
        stores.value = storesRes.data.stores || storesRes.data.data || [];
        inventoryItems.value = itemsRes.data.items || itemsRes.data.data || [];
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const loadPO = async () => {
    if (!route.params.id) return;
    try {
        const response = await axios.get(`/api/inventory/purchase-orders/${route.params.id}`);
        const po = response.data.purchase_order || response.data;
        form.value = {
            vendor_id: po.vendor_id,
            store_id: po.store_id,
            po_date: po.po_date,
            expected_delivery_date: po.expected_delivery_date || '',
            payment_terms: po.payment_terms || 'credit_30',
            remarks: po.remarks || '',
            items: po.po_items?.map(i => ({
                item_id: i.item_id,
                quantity: i.quantity,
                unit: i.item?.unit || '',
                rate: i.rate,
                tax_percent: i.tax_percent || 0,
                amount: i.amount
            })) || []
        };
    } catch (error) {
        console.error('Failed to load PO:', error);
    }
};

const addItem = () => {
    form.value.items.push({ item_id: '', quantity: 1, unit: '', rate: 0, tax_percent: 0, amount: 0 });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

const onItemSelect = (item) => {
    const selectedItem = inventoryItems.value.find(i => i.item_id === item.item_id);
    if (selectedItem) {
        item.unit = selectedItem.unit || '';
        item.rate = selectedItem.purchase_rate || 0;
        calculateAmount(item);
    }
};

const calculateAmount = (item) => {
    const subtotal = (item.quantity || 0) * (item.rate || 0);
    const tax = subtotal * ((item.tax_percent || 0) / 100);
    item.amount = subtotal + tax;
};

const formatCurrency = (amount) => new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);

const submitForm = async () => {
    loading.value = true;
    try {
        const payload = {
            ...form.value,
            total_amount: totalAmount.value,
            items: form.value.items.filter(i => i.item_id)
        };
        await axios.post('/api/inventory/purchase-orders', payload);
        router.push('/inventory/purchase-orders');
    } catch (error) {
        console.error('Failed to create PO:', error);
        alert(error.response?.data?.message || 'Failed to create purchase order');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadData();
    if (route.params.id) {
        loadPO();
    }
});
</script>
