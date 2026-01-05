<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-balloon-heart me-2"></i>Birth Registrations</h4>
            <router-link to="/birth-registrations/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Registration
            </router-link>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="date" v-model="filters.from_date" class="form-control" @change="loadRegistrations">
                    </div>
                    <div class="col-md-3">
                        <input type="date" v-model="filters.to_date" class="form-control" @change="loadRegistrations">
                    </div>
                    <div class="col-md-3">
                        <select v-model="filters.status" class="form-select" @change="loadRegistrations">
                            <option value="">All Status</option>
                            <option value="registered">Registered</option>
                            <option value="certificate_issued">Certificate Issued</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" v-model="filters.search" class="form-control" placeholder="Search..." @input="loadRegistrations">
                    </div>
                </div>
            </div>
        </div>

        <!-- Registrations Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Reg. No.</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Child Name</th>
                            <th>Mother Name</th>
                            <th>Father Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="reg in registrations" :key="reg.birth_id">
                            <td><strong>{{ reg.registration_number }}</strong></td>
                            <td>{{ formatDate(reg.date_of_birth) }}</td>
                            <td>
                                <i :class="reg.gender === 'male' ? 'bi bi-gender-male text-primary' : 'bi bi-gender-female text-danger'"></i>
                                {{ reg.gender }}
                            </td>
                            <td>{{ reg.child_name || '-' }}</td>
                            <td>{{ reg.mother_name }}</td>
                            <td>{{ reg.father_name }}</td>
                            <td>
                                <span class="badge" :class="reg.certificate_number ? 'bg-success' : 'bg-warning'">
                                    {{ reg.certificate_number ? 'Certificate Issued' : 'Registered' }}
                                </span>
                            </td>
                            <td>
                                <router-link :to="`/birth-registrations/${reg.birth_id}`" class="btn btn-sm btn-outline-primary me-1">
                                    <i class="bi bi-eye"></i>
                                </router-link>
                                <button v-if="!reg.certificate_number" @click="issueCertificate(reg)" class="btn btn-sm btn-success">
                                    <i class="bi bi-file-earmark-text"></i> Issue
                                </button>
                                <button v-else @click="printCertificate(reg)" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-printer"></i>
                                </button>
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

const registrations = ref([]);
const filters = ref({
    from_date: '',
    to_date: '',
    status: '',
    search: ''
});

const loadRegistrations = async () => {
    try {
        const params = new URLSearchParams();
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);
        if (filters.value.status) params.append('status', filters.value.status);
        if (filters.value.search) params.append('search', filters.value.search);

        const response = await axios.get(`/api/birth-registrations?${params}`);
        registrations.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to load registrations:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const issueCertificate = async (reg) => {
    if (confirm('Issue birth certificate for this registration?')) {
        try {
            await axios.post(`/api/birth-registrations/${reg.birth_id}/issue-certificate`);
            loadRegistrations();
        } catch (error) {
            console.error('Failed to issue certificate:', error);
        }
    }
};

const printCertificate = (reg) => {
    window.open(`/prints/birth-certificate/${reg.birth_id}`, '_blank');
};

onMounted(loadRegistrations);
</script>
