<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>
                <i class="bi bi-file-earmark-arrow-up me-2"></i>
                {{ isView ? 'View Indent' : 'New Indent' }}
            </h4>
            <router-link to="/inventory/indents" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </router-link>
        </div>

        <div class="card">
            <div class="card-body">
                <form @submit.prevent="submitForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">From Store (Requesting) *</label>
                            <select v-model="form.from_store_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Store</option>
                                <option v-for="store in stores" :key="store.store_id" :value="store.store_id">
                                    {{ store.store_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">To Store (Issuing) *</label>
                            <select v-model="form.to_store_id" class="form-select" :disabled="isView" required>
                                <option value="">Select Store</option>
                                <option v-for="store in stores" :key="store.store_id" :value="store.store_id">
                                    {{ store.store_name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Indent Date *</label>
                            <input type="date" v-model="form.indent_date" class="form-control" :disabled="isView" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Required By</label>
                            <input type="date" v-model="form.required_date" class="form-control" :disabled="isView">
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
                                        <th width="120">Quantity</th>
                                        <th>Unit</th>
                                        <th>Remarks</th>
                                        <th width="80" v-if="!isView">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td>
                                            <select v-model="item.item_id" class="form-select form-select-sm" :disabled="isView" @change="onItemSelect(item)">
                                                <option value="">Select Item</option>
                                                <option v-for="i in inventoryItems" :key="i.item_id" :value="i.item_id">
                                                    {{ i.item_name }} ({{ i.item_code }})
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" v-model="item.quantity" class="form-control form-control-sm" min="1" :disabled="isView">
                                        </td>
                                        <td>{{ item.unit || '-' }}</td>
                                        <td>
                                            <input type="text" v-model="item.remarks" class="form-control form-control-sm" :disabled="isView">
                                        </td>
                                        <td v-if="!isView">
                                            <button type="button" @click="removeItem(index)" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button v-if="!isView" type="button" @click="addItem" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Item
                        </button>
                    </div>

                    <div class="d-flex gap-2" v-if="!isView">
                        <button type="submit" class="btn btn-primary" :disabled="loading">
                            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                            Submit Indent
                        </button>
                        <router-link to="/inventory/indents" class="btn btn-outline-secondary">Cancel</router-link>
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

const stores = ref([]);
const inventoryItems = ref([]);
const loading = ref(false);

const form = ref({
    from_store_id: '',
    to_store_id: '',
    indent_date: new Date().toISOString().split('T')[0],
    required_date: '',
    remarks: '',
    items: [{ item_id: '', quantity: 1, unit: '', remarks: '' }]
});

const isView = computed(() => route.name === 'inventory.indents.view');

const loadData = async () => {
    try {
        const [storesRes, itemsRes] = await Promise.all([
            axios.get('/api/inventory/stores'),
            axios.get('/api/inventory/items')
        ]);
        stores.value = storesRes.data.stores || storesRes.data.data || [];
        inventoryItems.value = itemsRes.data.items || itemsRes.data.data || [];
    } catch (error) {
        console.error('Failed to load data:', error);
    }
};

const loadIndent = async () => {
    if (!route.params.id) return;
    try {
        const response = await axios.get(`/api/inventory/indents/${route.params.id}`);
        const indent = response.data.indent || response.data;
        form.value = {
            from_store_id: indent.from_store_id,
            to_store_id: indent.to_store_id,
            indent_date: indent.indent_date,
            required_date: indent.required_date || '',
            remarks: indent.remarks || '',
            items: indent.indent_items?.map(i => ({
                item_id: i.item_id,
                quantity: i.quantity,
                unit: i.item?.unit || '',
                remarks: i.remarks || ''
            })) || []
        };
    } catch (error) {
        console.error('Failed to load indent:', error);
    }
};

const addItem = () => {
    form.value.items.push({ item_id: '', quantity: 1, unit: '', remarks: '' });
};

const removeItem = (index) => {
    form.value.items.splice(index, 1);
};

const onItemSelect = (item) => {
    const selectedItem = inventoryItems.value.find(i => i.item_id === item.item_id);
    if (selectedItem) {
        item.unit = selectedItem.unit || '';
    }
};

const submitForm = async () => {
    loading.value = true;
    try {
        const payload = {
            ...form.value,
            items: form.value.items.filter(i => i.item_id)
        };
        await axios.post('/api/inventory/indents', payload);
        router.push('/inventory/indents');
    } catch (error) {
        console.error('Failed to submit indent:', error);
        alert(error.response?.data?.message || 'Failed to submit indent');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadData();
    if (route.params.id) {
        loadIndent();
    }
});
</script>
