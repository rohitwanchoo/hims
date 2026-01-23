<template>
    <div>
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">Patients</h4>
                <p class="text-muted mb-0">Manage your patient records</p>
            </div>
            <router-link to="/patients/create" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>New Patient
            </router-link>
        </div>

        <!-- Search & Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <!-- Quick Search Row -->
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-search me-1"></i>Quick Search
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                v-model="search"
                                @input="debouncedSearch"
                                placeholder="Search by name, patient code, or mobile number..."
                            >
                            <button
                                v-if="search"
                                class="btn btn-outline-secondary"
                                type="button"
                                @click="search = ''; fetchPatients();"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-funnel me-1"></i>Quick Filters
                        </label>
                        <div class="d-flex gap-2">
                            <button
                                class="btn btn-outline-primary flex-fill"
                                :class="{ active: showAdvancedFilters }"
                                @click="showAdvancedFilters = !showAdvancedFilters"
                            >
                                <i class="bi bi-sliders me-1"></i>Advanced Filters
                                <span v-if="activeFiltersCount > 0" class="badge bg-danger ms-1">{{ activeFiltersCount }}</span>
                            </button>
                            <button class="btn btn-outline-secondary" @click="resetFilters">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset All
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters (Collapsible) -->
                <div v-show="showAdvancedFilters" class="border-top pt-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" v-model="filters.gender" @change="applyFilters">
                                <option value="">All Genders</option>
                                <option v-for="g in genders" :key="g.gender_id" :value="g.gender_id">
                                    {{ g.gender_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Blood Group</label>
                            <select class="form-select" v-model="filters.blood_group" @change="applyFilters">
                                <option value="">All Blood Groups</option>
                                <option v-for="bg in bloodGroups" :key="bg.blood_group_id" :value="bg.blood_group_id">
                                    {{ bg.blood_group_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">City</label>
                            <select class="form-select" v-model="filters.city" @change="applyFilters">
                                <option value="">All Cities</option>
                                <option v-for="city in cities" :key="city.city_id" :value="city.city_id">
                                    {{ city.city_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Age Range</label>
                            <select class="form-select" v-model="filters.age_range" @change="applyFilters">
                                <option value="">All Ages</option>
                                <option value="0-10">0-10 years</option>
                                <option value="11-20">11-20 years</option>
                                <option value="21-30">21-30 years</option>
                                <option value="31-40">31-40 years</option>
                                <option value="41-50">41-50 years</option>
                                <option value="51-60">51-60 years</option>
                                <option value="61-100">61+ years</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-3">
                            <label class="form-label">Registration From</label>
                            <input type="date" class="form-control" v-model="filters.date_from" @change="applyFilters">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Registration To</label>
                            <input type="date" class="form-control" v-model="filters.date_to" @change="applyFilters">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" v-model="filters.status" @change="applyFilters">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Active Filter Badges -->
                <div v-if="activeFiltersCount > 0" class="mt-3 pt-3 border-top">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted small fw-semibold">Active Filters:</span>
                        <span v-if="filters.gender" class="badge bg-primary">
                            Gender: {{ getGenderName(filters.gender) }}
                            <i class="bi bi-x-circle ms-1" style="cursor: pointer" @click="filters.gender = ''; applyFilters()"></i>
                        </span>
                        <span v-if="filters.blood_group" class="badge bg-danger">
                            Blood: {{ getBloodGroupName(filters.blood_group) }}
                            <i class="bi bi-x-circle ms-1" style="cursor: pointer" @click="filters.blood_group = ''; applyFilters()"></i>
                        </span>
                        <span v-if="filters.city" class="badge bg-info">
                            City: {{ getCityName(filters.city) }}
                            <i class="bi bi-x-circle ms-1" style="cursor: pointer" @click="filters.city = ''; applyFilters()"></i>
                        </span>
                        <span v-if="filters.age_range" class="badge bg-warning text-dark">
                            Age: {{ filters.age_range }}
                            <i class="bi bi-x-circle ms-1" style="cursor: pointer" @click="filters.age_range = ''; applyFilters()"></i>
                        </span>
                        <span v-if="filters.date_from || filters.date_to" class="badge bg-secondary">
                            Date: {{ filters.date_from || 'Start' }} to {{ filters.date_to || 'End' }}
                            <i class="bi bi-x-circle ms-1" style="cursor: pointer" @click="filters.date_from = ''; filters.date_to = ''; applyFilters()"></i>
                        </span>
                        <span v-if="filters.status !== ''" class="badge bg-success">
                            {{ filters.status === '1' ? 'Active' : 'Inactive' }}
                            <i class="bi bi-x-circle ms-1" style="cursor: pointer" @click="filters.status = ''; applyFilters()"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Table Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-people me-2"></i>Patient List
                </h5>
                <span class="badge badge-soft-primary">
                    {{ pagination.total || patients.length }} patients
                </span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Patient Code</th>
                            <th>Patient Name</th>
                            <th>Age/Gender</th>
                            <th>Mobile</th>
                            <th>City</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2 mb-0">Loading patients...</p>
                            </td>
                        </tr>
                        <tr v-else-if="patients.length === 0">
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2 mb-0">No patients found</p>
                            </td>
                        </tr>
                        <tr v-for="patient in patients" :key="patient.patient_id">
                            <td>
                                <span class="badge badge-soft-secondary">{{ patient.pcd }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-2" :class="getAvatarClass(patient.gender)">
                                        {{ getInitials(patient.patient_name) }}
                                    </div>
                                    <div>
                                        <router-link
                                            :to="`/patients/${patient.patient_id}`"
                                            class="fw-semibold text-decoration-none"
                                        >
                                            {{ patient.patient_name }}
                                        </router-link>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="fw-medium">{{ formatAge(patient) }}</span>
                                <span class="mx-1">/</span>
                                <span :class="getGenderBadgeClass(getGenderValue(patient))">
                                    {{ getGenderDisplay(patient) }}
                                </span>
                            </td>
                            <td>
                                <span v-if="patient.mobile || patient.permanent_mobile">
                                    <i class="bi bi-telephone me-1 text-muted"></i>{{ patient.mobile || patient.permanent_mobile }}
                                </span>
                                <span v-else class="text-muted">-</span>
                            </td>
                            <td>{{ getCityDisplay(patient) }}</td>
                            <td>
                                <div class="table-actions justify-content-center">
                                    <router-link
                                        :to="`/patients/${patient.patient_id}`"
                                        class="btn btn-sm btn-soft-primary"
                                        title="View Patient"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <router-link
                                        :to="`/patients/${patient.patient_id}/edit`"
                                        class="btn btn-sm btn-soft-secondary"
                                        title="Edit Patient"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </router-link>
                                    <router-link
                                        :to="`/opd/create?patient=${patient.patient_id}`"
                                        class="btn btn-sm btn-soft-success"
                                        title="New OPD Visit"
                                    >
                                        <i class="bi bi-clipboard-plus"></i>
                                    </router-link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer" v-if="pagination.last_page > 1">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ ((pagination.current_page - 1) * pagination.per_page) + 1 }}
                        to {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }}
                        of {{ pagination.total }} entries
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <li
                                class="page-item"
                                v-for="page in visiblePages"
                                :key="page"
                                :class="{ active: page === pagination.current_page }"
                            >
                                <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                                <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';

const patients = ref([]);
const loading = ref(false);
const search = ref('');
const showAdvancedFilters = ref(false);

// Master data
const genders = ref([]);
const bloodGroups = ref([]);
const cities = ref([]);

// Filters
const filters = reactive({
    gender: '',
    blood_group: '',
    city: '',
    age_range: '',
    date_from: '',
    date_to: '',
    status: ''
});

const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    prev_page_url: null,
    next_page_url: null
});

let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.gender) count++;
    if (filters.blood_group) count++;
    if (filters.city) count++;
    if (filters.age_range) count++;
    if (filters.date_from || filters.date_to) count++;
    if (filters.status !== '') count++;
    return count;
});

const visiblePages = computed(() => {
    const pages = [];
    const start = Math.max(1, pagination.current_page - 2);
    const end = Math.min(pagination.last_page, pagination.current_page + 2);
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

// Fetch master data
const fetchMasterData = async () => {
    try {
        const [gendersRes, bloodGroupsRes, citiesRes] = await Promise.all([
            axios.get('/api/genders-active'),
            axios.get('/api/blood-groups-active'),
            axios.get('/api/cities-active')
        ]);
        genders.value = gendersRes.data;
        bloodGroups.value = bloodGroupsRes.data;
        cities.value = citiesRes.data;
    } catch (error) {
        console.error('Error fetching master data:', error);
    }
};

const fetchPatients = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            search: search.value
        };

        // Add filters if they have values
        if (filters.gender) params.gender_id = filters.gender;
        if (filters.blood_group) params.blood_group_id = filters.blood_group;
        if (filters.city) params.city_id = filters.city;
        if (filters.age_range) params.age_range = filters.age_range;
        if (filters.date_from) params.date_from = filters.date_from;
        if (filters.date_to) params.date_to = filters.date_to;
        if (filters.status !== '') params.is_active = filters.status;

        const response = await axios.get('/api/patients', { params });
        const paginationData = response.data.data || response.data;
        patients.value = paginationData.data || [];
        Object.assign(pagination, {
            current_page: paginationData.current_page || 1,
            last_page: paginationData.last_page || 1,
            per_page: paginationData.per_page || 15,
            total: paginationData.total || 0,
            prev_page_url: paginationData.prev_page_url,
            next_page_url: paginationData.next_page_url
        });
    } catch (error) {
        console.error('Failed to fetch patients:', error);
    }
    loading.value = false;
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchPatients();
    }, 300);
};

const applyFilters = () => {
    fetchPatients();
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
        fetchPatients(page);
    }
};

const resetFilters = () => {
    search.value = '';
    filters.gender = '';
    filters.blood_group = '';
    filters.city = '';
    filters.age_range = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.status = '';
    showAdvancedFilters.value = false;
    fetchPatients();
};

// Helper functions for filter badges
const getGenderName = (genderId) => {
    const gender = genders.value.find(g => g.gender_id == genderId);
    return gender ? gender.gender_name : '';
};

const getBloodGroupName = (bloodGroupId) => {
    const bloodGroup = bloodGroups.value.find(bg => bg.blood_group_id == bloodGroupId);
    return bloodGroup ? bloodGroup.blood_group_name : '';
};

const getCityName = (cityId) => {
    const city = cities.value.find(c => c.city_id == cityId);
    return city ? city.city_name : '';
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const capitalizeFirst = (str) => {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
};

const getAvatarClass = (gender) => {
    const classes = {
        'male': 'avatar-soft-primary',
        'female': 'avatar-soft-danger',
        'other': 'avatar-soft-secondary'
    };
    return classes[gender] || 'avatar-soft-primary';
};

const getGenderBadgeClass = (gender) => {
    const classes = {
        'male': 'badge badge-soft-primary',
        'female': 'badge badge-soft-danger',
        'other': 'badge badge-soft-secondary'
    };
    return classes[gender?.toLowerCase()] || 'badge badge-soft-secondary';
};

// Format age display - handles multiple age field formats
const formatAge = (patient) => {
    // Check for age_years, age_months, age_days
    if (patient.age_years || patient.age_months || patient.age_days) {
        const parts = [];
        if (patient.age_years) parts.push(`${patient.age_years}Y`);
        if (patient.age_months) parts.push(`${patient.age_months}M`);
        if (patient.age_days) parts.push(`${patient.age_days}D`);
        return parts.join(' ') || '-';
    }
    // Fallback to age + age_unit
    if (patient.age) {
        const unit = patient.age_unit || 'years';
        return `${patient.age} ${unit}`;
    }
    // Calculate from DOB if available
    if (patient.dob) {
        const birthDate = new Date(patient.dob);
        const today = new Date();
        let years = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            years--;
        }
        return `${years} years`;
    }
    return '-';
};

// Get gender value for styling
const getGenderValue = (patient) => {
    // Check gender_relation first (from eager loading)
    if (patient.gender_relation?.gender_name) {
        return patient.gender_relation.gender_name.toLowerCase();
    }
    // Then check gender field
    if (patient.gender) {
        return patient.gender.toLowerCase();
    }
    return '';
};

// Get gender display text
const getGenderDisplay = (patient) => {
    // Check gender_relation first (from eager loading)
    if (patient.gender_relation?.gender_name) {
        return patient.gender_relation.gender_name;
    }
    // Then check gender field
    if (patient.gender) {
        return capitalizeFirst(patient.gender);
    }
    return '-';
};

// Get city display
const getCityDisplay = (patient) => {
    // Check permanent_city relation first
    if (patient.permanent_city?.city_name) {
        return patient.permanent_city.city_name;
    }
    // Then check legacy city field
    if (patient.city) {
        return patient.city;
    }
    // Check permanent_city string field
    if (patient.permanent_city && typeof patient.permanent_city === 'string') {
        return patient.permanent_city;
    }
    return '-';
};

onMounted(() => {
    fetchMasterData();
    fetchPatients();
});
</script>

<style scoped>
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}
</style>
