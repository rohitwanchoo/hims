<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-file-medical me-2"></i>Death Registrations</h4>
            <router-link to="/death-registrations/create" class="btn btn-primary">
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
                    <div class="col-md-2">
                        <div class="form-check mt-2">
                            <input type="checkbox" v-model="filters.is_mlc_case" class="form-check-input" id="mlcCase" @change="loadRegistrations">
                            <label class="form-check-label" for="mlcCase">MLC Cases Only</label>
                        </div>
                    </div>
                    <div class="col-md-4">
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
                            <th>Date of Death</th>
                            <th>Deceased Name</th>
                            <th>Age/Gender</th>
                            <th>Cause of Death</th>
                            <th>MLC</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="reg in registrations" :key="reg.death_id" :class="{ 'table-danger': reg.is_mlc_case }">
                            <td><strong>{{ reg.registration_number }}</strong></td>
                            <td>{{ formatDate(reg.date_of_death) }}</td>
                            <td>{{ reg.deceased_name }}</td>
                            <td>{{ reg.deceased_age }} yrs / {{ reg.deceased_gender }}</td>
                            <td>
                                <span :title="reg.cause_of_death_immediate">
                                    {{ truncate(reg.cause_of_death_immediate, 30) }}
                                </span>
                            </td>
                            <td>
                                <span v-if="reg.is_mlc_case" class="badge bg-danger">Yes</span>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>
                                <span class="badge" :class="reg.certificate_number ? 'bg-success' : 'bg-warning'">
                                    {{ reg.certificate_number ? 'Certificate Issued' : 'Registered' }}
                                </span>
                            </td>
                            <td>
                                <router-link :to="`/death-registrations/${reg.death_id}`" class="btn btn-sm btn-outline-primary me-1">
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
    is_mlc_case: false,
    search: ''
});

const loadRegistrations = async () => {
    try {
        const params = new URLSearchParams();
        if (filters.value.from_date) params.append('from_date', filters.value.from_date);
        if (filters.value.to_date) params.append('to_date', filters.value.to_date);
        if (filters.value.is_mlc_case) params.append('is_mlc_case', '1');
        if (filters.value.search) params.append('search', filters.value.search);

        const response = await axios.get(`/api/death-registrations?${params}`);
        registrations.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to load registrations:', error);
    }
};

const formatDate = (date) => new Date(date).toLocaleDateString();
const truncate = (text, length) => text?.length > length ? text.substring(0, length) + '...' : text;

const issueCertificate = async (reg) => {
    if (confirm('Issue death certificate for this registration?')) {
        try {
            await axios.post(`/api/death-registrations/${reg.death_id}/issue-certificate`);
            loadRegistrations();
        } catch (error) {
            console.error('Failed to issue certificate:', error);
        }
    }
};

const printCertificate = (reg) => {
    window.open(`/prints/death-certificate/${reg.death_id}`, '_blank');
};

onMounted(loadRegistrations);
</script>
