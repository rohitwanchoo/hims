<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-1">Hospitals</h5>
                <p class="text-muted mb-0">Manage hospitals and clinics</p>
            </div>
            <router-link to="/hospitals/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Hospital
            </router-link>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card bg-light-primary">
                    <div class="card-body text-center">
                        <h3 class="text-primary mb-1">{{ stats.total_hospitals }}</h3>
                        <small class="text-muted">Total Hospitals</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light-success">
                    <div class="card-body text-center">
                        <h3 class="text-success mb-1">{{ stats.active_hospitals }}</h3>
                        <small class="text-muted">Active</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning mb-1">{{ stats.expiring_soon }}</h3>
                        <small class="text-muted">Expiring Soon</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light-info">
                    <div class="card-body text-center">
                        <h3 class="text-info mb-1">{{ stats.total_hospitals - stats.active_hospitals }}</h3>
                        <small class="text-muted">Inactive</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" v-model="filters.search" placeholder="Search hospitals..." @keyup.enter="fetchHospitals">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filters.type" @change="fetchHospitals">
                            <option value="">All Types</option>
                            <option value="general">General Hospital</option>
                            <option value="clinic">Clinic</option>
                            <option value="opd_center">OPD Center</option>
                            <option value="ipd_center">IPD Center</option>
                            <option value="diagnostic_center">Diagnostic Center</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="filters.is_active" @change="fetchHospitals">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-light w-100" @click="resetFilters">
                            <i class="bi bi-x-circle me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hospital List -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>City</th>
                            <th>Plan</th>
                            <th>Subscription</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="hospital in hospitals" :key="hospital.hospital_id">
                            <td class="fw-semibold">{{ hospital.code }}</td>
                            <td>{{ hospital.name }}</td>
                            <td>
                                <span class="badge bg-light-info text-info">{{ formatType(hospital.type) }}</span>
                            </td>
                            <td>{{ hospital.city || '-' }}</td>
                            <td>
                                <span class="badge" :class="planBadgeClass(hospital.subscription_plan)">
                                    {{ hospital.subscription_plan }}
                                </span>
                            </td>
                            <td>
                                <span v-if="hospital.subscription_end" :class="{ 'text-danger': isExpiringSoon(hospital.subscription_end) }">
                                    {{ formatDate(hospital.subscription_end) }}
                                </span>
                                <span v-else class="text-muted">Unlimited</span>
                            </td>
                            <td>
                                <span class="badge" :class="hospital.is_active ? 'bg-success' : 'bg-secondary'">
                                    {{ hospital.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-light me-1" @click="switchToHospital(hospital)" title="Switch to this hospital">
                                    <i class="bi bi-box-arrow-in-right"></i>
                                </button>
                                <router-link :to="`/hospitals/${hospital.hospital_id}/edit`" class="btn btn-sm btn-light me-1">
                                    <i class="bi bi-pencil"></i>
                                </router-link>
                                <button class="btn btn-sm btn-light text-danger" @click="toggleStatus(hospital)">
                                    <i :class="hospital.is_active ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="hospitals.length === 0">
                            <td colspan="8" class="text-center py-4 text-muted">No hospitals found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3" v-if="pagination.total > pagination.per_page">
            <small class="text-muted">
                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} hospitals
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">Previous</a>
                    </li>
                    <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                        <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();

const hospitals = ref([]);
const stats = ref({
    total_hospitals: 0,
    active_hospitals: 0,
    expiring_soon: 0,
});

const filters = reactive({
    search: '',
    type: '',
    is_active: '',
});

const pagination = reactive({
    current_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
    prev_page_url: null,
    next_page_url: null,
});

const fetchHospitals = async (page = 1) => {
    const params = { page, per_page: pagination.per_page };
    if (filters.search) params.search = filters.search;
    if (filters.type) params.type = filters.type;
    if (filters.is_active !== '') params.is_active = filters.is_active;

    const response = await axios.get('/api/hospitals', { params });
    hospitals.value = response.data.data;
    Object.assign(pagination, {
        current_page: response.data.current_page,
        total: response.data.total,
        from: response.data.from,
        to: response.data.to,
        prev_page_url: response.data.prev_page_url,
        next_page_url: response.data.next_page_url,
    });
};

const fetchStats = async () => {
    const response = await axios.get('/api/hospitals/stats');
    stats.value = response.data;
};

const resetFilters = () => {
    filters.search = '';
    filters.type = '';
    filters.is_active = '';
    fetchHospitals();
};

const changePage = (page) => {
    fetchHospitals(page);
};

const switchToHospital = async (hospital) => {
    try {
        await axios.post('/api/switch-hospital', { hospital_id: hospital.hospital_id });
        authStore.setCurrentHospital(hospital);
        router.push('/');
    } catch (error) {
        alert('Error switching hospital');
    }
};

const toggleStatus = async (hospital) => {
    const action = hospital.is_active ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} ${hospital.name}?`)) {
        await axios.put(`/api/hospitals/${hospital.hospital_id}`, {
            is_active: !hospital.is_active,
        });
        fetchHospitals(pagination.current_page);
        fetchStats();
    }
};

const formatType = (type) => {
    const types = {
        general: 'General Hospital',
        clinic: 'Clinic',
        opd_center: 'OPD Center',
        ipd_center: 'IPD Center',
        diagnostic_center: 'Diagnostic',
    };
    return types[type] || type;
};

const planBadgeClass = (plan) => {
    const classes = {
        basic: 'bg-secondary',
        standard: 'bg-primary',
        premium: 'bg-warning text-dark',
    };
    return classes[plan] || 'bg-secondary';
};

const formatDate = (date) => new Date(date).toLocaleDateString();

const isExpiringSoon = (date) => {
    const expiryDate = new Date(date);
    const now = new Date();
    const diffDays = Math.ceil((expiryDate - now) / (1000 * 60 * 60 * 24));
    return diffDays <= 30 && diffDays > 0;
};

onMounted(() => {
    fetchHospitals();
    fetchStats();
});
</script>
