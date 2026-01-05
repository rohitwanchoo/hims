<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4>Lab Tests</h4>
            <button class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Test</button>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Test Name</th>
                            <th>Category</th>
                            <th>Rate</th>
                            <th>Sample Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="test in tests" :key="test.test_id">
                            <td>{{ test.test_code }}</td>
                            <td>{{ test.test_name }}</td>
                            <td>{{ test.category?.category_name }}</td>
                            <td>{{ formatCurrency(test.rate) }}</td>
                            <td>{{ test.sample_type }}</td>
                            <td><span class="badge" :class="test.is_active ? 'bg-success' : 'bg-secondary'">{{ test.is_active ? 'Active' : 'Inactive' }}</span></td>
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
const tests = ref([]);
onMounted(async () => {
    const response = await axios.get('/api/lab-tests');
    tests.value = response.data;
});
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
};
</script>
