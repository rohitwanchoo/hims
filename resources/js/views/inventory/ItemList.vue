<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-box me-2"></i>Items Master</h4>
            <button class="btn btn-primary" @click="showForm = true">
                <i class="bi bi-plus-lg"></i> Add Item
            </button>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <select v-model="filters.category_id" class="form-select" @change="loadItems">
                            <option value="">All Categories</option>
                            <option v-for="cat in categories" :key="cat.category_id" :value="cat.category_id">
                                {{ cat.category_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select v-model="filters.item_type" class="form-select" @change="loadItems">
                            <option value="">All Types</option>
                            <option value="consumable">Consumable</option>
                            <option value="equipment">Equipment</option>
                            <option value="implant">Implant</option>
                            <option value="reagent">Reagent</option>
                            <option value="drug">Drug</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" v-model="filters.search" class="form-control" placeholder="Search items..." @input="loadItems">
                    </div>
                    <div class="col-md-2">
                        <div class="form-check mt-2">
                            <input type="checkbox" v-model="filters.low_stock" class="form-check-input" id="lowStock" @change="loadItems">
                            <label class="form-check-label" for="lowStock">Low Stock Only</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th>Reorder Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in items" :key="item.item_id" :class="{ 'table-danger': item.total_stock <= item.reorder_level }">
                            <td><code>{{ item.item_code }}</code></td>
                            <td>
                                <strong>{{ item.item_name }}</strong>
                                <br v-if="item.generic_name"><small class="text-muted">{{ item.generic_name }}</small>
                            </td>
                            <td>{{ item.category?.category_name }}</td>
                            <td><span class="badge bg-secondary">{{ item.item_type }}</span></td>
                            <td>{{ item.unit_of_measure }}</td>
                            <td>{{ item.total_stock || 0 }}</td>
                            <td>{{ item.reorder_level }}</td>
                            <td>
                                <button @click="viewStock(item)" class="btn btn-sm btn-outline-info me-1">
                                    <i class="bi bi-boxes"></i>
                                </button>
                                <button @click="editItem(item)" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Item Form Modal -->
        <div v-if="showForm" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingItem ? 'Edit Item' : 'Add Item' }}</h5>
                        <button type="button" class="btn-close" @click="closeForm"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="saveItem">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Item Code</label>
                                    <input type="text" v-model="form.item_code" class="form-control" required>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Item Name</label>
                                    <input type="text" v-model="form.item_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Generic Name</label>
                                    <input type="text" v-model="form.generic_name" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select v-model="form.category_id" class="form-select" required>
                                        <option v-for="cat in categories" :key="cat.category_id" :value="cat.category_id">
                                            {{ cat.category_name }}
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Item Type</label>
                                    <select v-model="form.item_type" class="form-select" required>
                                        <option value="consumable">Consumable</option>
                                        <option value="equipment">Equipment</option>
                                        <option value="implant">Implant</option>
                                        <option value="reagent">Reagent</option>
                                        <option value="drug">Drug</option>
                                        <option value="general">General</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Unit of Measure</label>
                                    <input type="text" v-model="form.unit_of_measure" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Reorder Level</label>
                                    <input type="number" v-model="form.reorder_level" class="form-control" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">HSN Code</label>
                                    <input type="text" v-model="form.hsn_code" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">GST %</label>
                                    <input type="number" v-model="form.gst_percent" class="form-control" min="0" max="100">
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeForm">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveItem">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const items = ref([]);
const categories = ref([]);
const showForm = ref(false);
const editingItem = ref(null);
const filters = ref({
    category_id: '',
    item_type: '',
    search: '',
    low_stock: false
});
const form = ref({
    item_code: '',
    item_name: '',
    generic_name: '',
    category_id: '',
    item_type: 'consumable',
    unit_of_measure: '',
    reorder_level: 10,
    hsn_code: '',
    gst_percent: 0
});

const loadItems = async () => {
    try {
        const params = new URLSearchParams();
        if (filters.value.category_id) params.append('category_id', filters.value.category_id);
        if (filters.value.item_type) params.append('item_type', filters.value.item_type);
        if (filters.value.search) params.append('search', filters.value.search);
        if (filters.value.low_stock) params.append('low_stock', '1');

        const response = await axios.get(`/api/inventory/items?${params}`);
        items.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to load items:', error);
    }
};

const loadCategories = async () => {
    try {
        const response = await axios.get('/api/inventory/item-categories');
        categories.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to load categories:', error);
    }
};

const editItem = (item) => {
    editingItem.value = item;
    form.value = { ...item };
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingItem.value = null;
    form.value = {
        item_code: '',
        item_name: '',
        generic_name: '',
        category_id: '',
        item_type: 'consumable',
        unit_of_measure: '',
        reorder_level: 10,
        hsn_code: '',
        gst_percent: 0
    };
};

const saveItem = async () => {
    try {
        if (editingItem.value) {
            await axios.put(`/api/inventory/items/${editingItem.value.item_id}`, form.value);
        } else {
            await axios.post('/api/inventory/items', form.value);
        }
        closeForm();
        loadItems();
    } catch (error) {
        console.error('Failed to save item:', error);
    }
};

const viewStock = (item) => {
    // Navigate to stock view
};

onMounted(() => {
    loadItems();
    loadCategories();
});
</script>
