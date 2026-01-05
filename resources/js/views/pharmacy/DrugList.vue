<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>Drugs / Medicines</h4>
            <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Drug</button>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Drug Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Unit Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="drug in drugs" :key="drug.drug_id">
                            <td>{{ drug.drug_code }}</td>
                            <td>{{ drug.drug_name }}</td>
                            <td>{{ drug.generic_name }}</td>
                            <td>{{ drug.category?.category_name }}</td>
                            <td>{{ formatCurrency(drug.unit_price) }}</td>
                            <td>
                                <span :class="drug.current_stock < drug.reorder_level ? 'text-danger fw-bold' : ''">
                                    {{ drug.current_stock }}
                                </span>
                            </td>
                            <td><span class="badge" :class="drug.is_active ? 'bg-success' : 'bg-secondary'">{{ drug.is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
const drugs = ref([]);
onMounted(async () => {
    const response = await axios.get('/api/drugs');
    drugs.value = response.data;
});
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};
</script>
