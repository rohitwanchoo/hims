<template>
    <div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Patients</h4>
            <router-link to="/patients/create" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> New Patient
            </router-link>
        </div>

        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input
                            type="text"
                            class="form-control"
                            v-model="search"
                            @input="debouncedSearch"
                            placeholder="Search by name, code or mobile..."
                        >
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" v-model="gender" @change="fetchPatients">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Patient Code</th>
                            <th>Name</th>
                            <th>Age/Gender</th>
                            <th>Mobile</th>
                            <th>City</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="text-center py-4">
                                <span class="spinner-border spinner-border-sm"></span> Loading...
                            </td>
                        </tr>
                        <tr v-else-if="patients.length === 0">
                            <td colspan="6" class="text-center py-4 text-muted">
                                No patients found
                            </td>
                        </tr>
                        <tr v-for="patient in patients" :key="patient.patient_id">
                            <td>
                                <span class="badge bg-secondary">{{ patient.pcd }}</span>
                            </td>
                            <td>
                                <router-link :to="`/patients/${patient.patient_id}`" class="text-decoration-none">
                                    {{ patient.patient_name }}
                                </router-link>
                            </td>
                            <td>
                                {{ patient.age }} {{ patient.age_unit }} /
                                <span :class="genderClass(patient.gender)">{{ patient.gender }}</span>
                            </td>
                            <td>{{ patient.mobile || '-' }}</td>
                            <td>{{ patient.city || '-' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <router-link :to="`/patients/${patient.patient_id}`" class="btn btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </router-link>
                                    <router-link :to="`/patients/${patient.patient_id}/edit`" class="btn btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </router-link>
                                    <router-link :to="`/opd/create?patient=${patient.patient_id}`" class="btn btn-outline-success">
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
                <nav>
                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                        <li class="page-item" :class="{ disabled: !pagination.prev_page_url }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">Previous</a>
                        </li>
                        <li class="page-item" v-for="page in pagination.last_page" :key="page" :class="{ active: page === pagination.current_page }">
                            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: !pagination.next_page_url }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const patients = ref([]);
const loading = ref(false);
const search = ref('');
const gender = ref('');
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    prev_page_url: null,
    next_page_url: null
});

let searchTimeout = null;

const fetchPatients = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/patients', {
            params: {
                page,
                search: search.value,
                gender: gender.value
            }
        });
        patients.value = response.data.data;
        Object.assign(pagination, {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            prev_page_url: response.data.prev_page_url,
            next_page_url: response.data.next_page_url
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

const changePage = (page) => {
    if (page >= 1 && page <= pagination.last_page) {
        fetchPatients(page);
    }
};

const genderClass = (gender) => {
    return {
        'text-primary': gender === 'male',
        'text-danger': gender === 'female',
        'text-secondary': gender === 'other'
    };
};

onMounted(() => {
    fetchPatients();
});
</script>
