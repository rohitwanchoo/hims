<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Doctors</h4>
            <router-link to="/doctors/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Doctor
            </router-link>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Department</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="7" class="text-center py-4">
                                <span class="spinner-border spinner-border-sm"></span> Loading...
                            </td>
                        </tr>
                        <tr v-else-if="doctors.length === 0">
                            <td colspan="7" class="text-center py-4 text-muted">
                                No doctors found
                            </td>
                        </tr>
                        <tr v-for="doctor in doctors" :key="doctor.doctor_id">
                            <td>{{ doctor.doctor_code }}</td>
                            <td>{{ doctor.full_name }}</td>
                            <td>{{ doctor.specialization }}</td>
                            <td>{{ doctor.department?.department_name }}</td>
                            <td>{{ doctor.mobile }}</td>
                            <td><span class="badge" :class="doctor.is_active ? 'bg-success' : 'bg-secondary'">{{ doctor.is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <router-link :to="`/doctors/${doctor.doctor_id}/edit`" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </router-link>
                                </div>
                            </td>
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

const doctors = ref([]);
const loading = ref(false);

const fetchDoctors = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/doctors');
        doctors.value = response.data;
    } catch (error) {
        console.error('Failed to fetch doctors:', error);
    }
    loading.value = false;
};

onMounted(fetchDoctors);
</script>
