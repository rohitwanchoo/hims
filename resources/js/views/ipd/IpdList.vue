<template>
    <div class="ipd-list">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">IPD Admissions</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" @click="showBedMap = true">
                    <i class="bi bi-grid-3x3"></i> Bed Map
                </button>
                <router-link to="/ipd/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> New Admission
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ summary.total_admitted || 0 }}</h3>
                        <small>Total Admitted</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ summary.today_admissions || 0 }}</h3>
                        <small>Today Admissions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ summary.today_discharges || 0 }}</h3>
                        <small>Today Discharges</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ summary.pending_discharge || 0 }}</h3>
                        <small>Pending Discharge</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ summary.mlc_cases || 0 }}</h3>
                        <small>MLC Cases</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white h-100">
                    <div class="card-body text-center">
                        <h3 class="mb-1">{{ summary.insurance_cases || 0 }}</h3>
                        <small>Insurance</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select v-model="filters.status" class="form-select form-select-sm" @change="loadAdmissions">
                            <option value="all">All Status</option>
                            <option value="admitted">Admitted</option>
                            <option value="discharge_initiated">Discharge Initiated</option>
                            <option value="discharged">Discharged</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Ward</label>
                        <select v-model="filters.ward_id" class="form-select form-select-sm" @change="loadAdmissions">
                            <option value="">All Wards</option>
                            <option v-for="ward in wards" :key="ward.ward_id" :value="ward.ward_id">
                                {{ ward.ward_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Department</label>
                        <select v-model="filters.department_id" class="form-select form-select-sm" @change="loadAdmissions">
                            <option value="">All Departments</option>
                            <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                {{ dept.department_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Doctor</label>
                        <select v-model="filters.doctor_id" class="form-select form-select-sm" @change="loadAdmissions">
                            <option value="">All Doctors</option>
                            <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                {{ doc.doctor_name }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">From Date</label>
                        <input type="date" v-model="filters.from_date" class="form-control form-control-sm" @change="loadAdmissions">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">To Date</label>
                        <input type="date" v-model="filters.to_date" class="form-control form-control-sm" @change="loadAdmissions">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Search</label>
                        <input type="text" v-model="filters.search" class="form-control form-control-sm"
                               placeholder="IPD No, Patient Name, Mobile..." @keyup.enter="loadAdmissions">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" v-model="filters.is_mlc" @change="loadAdmissions">
                            <label class="form-check-label small">MLC</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">&nbsp;</label>
                        <button class="btn btn-outline-secondary btn-sm w-100" @click="resetFilters">
                            <i class="bi bi-x-lg"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admissions Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>IPD No</th>
                                <th>Patient</th>
                                <th>Ward / Bed</th>
                                <th>Doctor</th>
                                <th>Admission Date</th>
                                <th>LOS</th>
                                <th>Status</th>
                                <th>Flags</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="9" class="text-center py-4">
                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                    Loading...
                                </td>
                            </tr>
                            <tr v-else-if="admissions.length === 0">
                                <td colspan="9" class="text-center py-4 text-muted">
                                    No admissions found
                                </td>
                            </tr>
                            <tr v-for="admission in admissions" :key="admission.ipd_id"
                                :class="getRowClass(admission)" @dblclick="viewAdmission(admission)">
                                <td>
                                    <strong>{{ admission.ipd_number }}</strong>
                                </td>
                                <td>
                                    <div>{{ admission.patient?.full_name }}</div>
                                    <small class="text-muted">
                                        {{ admission.patient?.gender }} / {{ admission.patient?.age_display }}
                                    </small>
                                </td>
                                <td>
                                    <div>{{ admission.ward?.ward_name }}</div>
                                    <small class="text-muted">Bed: {{ admission.bed?.bed_number }}</small>
                                </td>
                                <td>
                                    <div>{{ admission.treating_doctor?.doctor_name }}</div>
                                    <small class="text-muted">{{ admission.department?.department_name }}</small>
                                </td>
                                <td>
                                    {{ formatDate(admission.admission_date) }}
                                    <br>
                                    <small class="text-muted">{{ admission.admission_time }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ admission.los_days }} days</span>
                                </td>
                                <td>
                                    <span :class="getStatusBadge(admission.status)">
                                        {{ formatStatus(admission.status) }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="admission.mlc_case" class="badge bg-danger me-1" title="MLC Case">MLC</span>
                                    <span v-if="admission.is_emergency" class="badge bg-warning me-1" title="Emergency">EMR</span>
                                    <span v-if="admission.insurance_applicable" class="badge bg-info" title="Insurance">INS</span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <router-link :to="`/ipd/${admission.ipd_id}`" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </router-link>
                                        <button v-if="admission.status === 'admitted'" class="btn btn-outline-success"
                                                @click="initiateDischarge(admission)" title="Discharge">
                                            <i class="bi bi-box-arrow-right"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" @click="printCaseSheet(admission)" title="Print">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer" v-if="pagination.total > 0">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} admissions
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">Prev</a>
                            </li>
                            <li class="page-item" v-for="page in paginationPages" :key="page"
                                :class="{ active: page === pagination.current_page }">
                                <a class="page-link" href="#" @click.prevent="goToPage(page)">{{ page }}</a>
                            </li>
                            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Bed Map Modal -->
        <div class="modal fade" :class="{ show: showBedMap }" :style="{ display: showBedMap ? 'block' : 'none' }"
             tabindex="-1" @click.self="showBedMap = false">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ward Bed Map</h5>
                        <button type="button" class="btn-close" @click="showBedMap = false"></button>
                    </div>
                    <div class="modal-body">
                        <div v-for="ward in bedAvailability" :key="ward.ward_id" class="mb-4">
                            <h6 class="border-bottom pb-2">
                                {{ ward.ward_name }}
                                <small class="text-muted">({{ ward.available_beds }} / {{ ward.total_beds }} available)</small>
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                <div v-for="bed in ward.beds" :key="bed.bed_id"
                                     :class="['bed-box', bed.is_available ? 'available' : 'occupied']"
                                     :title="bed.is_available ? 'Available' : bed.current_patient?.full_name">
                                    <div class="bed-number">{{ bed.bed_number }}</div>
                                    <div v-if="!bed.is_available" class="patient-name">
                                        {{ bed.current_patient?.first_name?.charAt(0) }}{{ bed.current_patient?.last_name?.charAt(0) }}
                                    </div>
                                    <i v-if="bed.is_isolation" class="bi bi-shield-exclamation isolation-icon" title="Isolation"></i>
                                    <i v-if="bed.is_ventilator" class="bi bi-lungs ventilator-icon" title="Ventilator"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="me-3"><span class="bed-legend available"></span> Available</span>
                            <span class="me-3"><span class="bed-legend occupied"></span> Occupied</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showBedMap" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

export default {
    name: 'IpdList',
    setup() {
        const loading = ref(false);
        const admissions = ref([]);
        const summary = ref({});
        const bedAvailability = ref([]);
        const showBedMap = ref(false);
        const wards = ref([]);
        const departments = ref([]);
        const doctors = ref([]);

        const filters = ref({
            status: 'admitted',
            ward_id: '',
            department_id: '',
            doctor_id: '',
            from_date: '',
            to_date: '',
            search: '',
            is_mlc: false,
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
        });

        const paginationPages = computed(() => {
            const pages = [];
            const current = pagination.value.current_page;
            const last = pagination.value.last_page;

            for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
                pages.push(i);
            }
            return pages;
        });

        const loadAdmissions = async (page = 1) => {
            loading.value = true;
            try {
                const params = { ...filters.value, page, per_page: 15 };
                if (!params.is_mlc) delete params.is_mlc;

                const response = await axios.get('/api/ipd-admissions', { params });
                admissions.value = response.data.data;
                pagination.value = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    total: response.data.total,
                    from: response.data.from,
                    to: response.data.to,
                };
            } catch (error) {
                console.error('Failed to load admissions:', error);
            } finally {
                loading.value = false;
            }
        };

        const loadSummary = async () => {
            try {
                const response = await axios.get('/api/ipd-admissions-summary');
                summary.value = response.data;
            } catch (error) {
                console.error('Failed to load summary:', error);
            }
        };

        const loadBedAvailability = async () => {
            try {
                const response = await axios.get('/api/ipd-admissions-bed-availability');
                bedAvailability.value = response.data;
            } catch (error) {
                console.error('Failed to load bed availability:', error);
            }
        };

        const loadMasterData = async () => {
            try {
                const [wardsRes, deptsRes, docsRes] = await Promise.all([
                    axios.get('/api/wards'),
                    axios.get('/api/departments'),
                    axios.get('/api/doctors'),
                ]);
                wards.value = wardsRes.data.data || wardsRes.data;
                departments.value = deptsRes.data.data || deptsRes.data;
                doctors.value = docsRes.data.data || docsRes.data;
            } catch (error) {
                console.error('Failed to load master data:', error);
            }
        };

        const resetFilters = () => {
            filters.value = {
                status: 'admitted',
                ward_id: '',
                department_id: '',
                doctor_id: '',
                from_date: '',
                to_date: '',
                search: '',
                is_mlc: false,
            };
            loadAdmissions();
        };

        const goToPage = (page) => {
            if (page >= 1 && page <= pagination.value.last_page) {
                loadAdmissions(page);
            }
        };

        const formatDate = (date) => {
            if (!date) return '';
            return new Date(date).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        };

        const formatStatus = (status) => {
            const statusMap = {
                admitted: 'Admitted',
                discharge_initiated: 'Discharge Initiated',
                discharged: 'Discharged',
                cancelled: 'Cancelled',
            };
            return statusMap[status] || status;
        };

        const getStatusBadge = (status) => {
            const badges = {
                admitted: 'badge bg-success',
                discharge_initiated: 'badge bg-warning',
                discharged: 'badge bg-secondary',
                cancelled: 'badge bg-danger',
            };
            return badges[status] || 'badge bg-secondary';
        };

        const getRowClass = (admission) => {
            if (admission.mlc_case) return 'table-danger';
            if (admission.is_emergency) return 'table-warning';
            if (admission.status === 'discharge_initiated') return 'table-info';
            return '';
        };

        const viewAdmission = (admission) => {
            window.location.href = `/ipd/${admission.ipd_id}`;
        };

        const initiateDischarge = async (admission) => {
            if (!confirm('Initiate discharge for this patient?')) return;
            try {
                await axios.post(`/api/ipd-admissions/${admission.ipd_id}/initiate-discharge`);
                loadAdmissions(pagination.value.current_page);
                loadSummary();
            } catch (error) {
                alert('Failed to initiate discharge: ' + (error.response?.data?.message || error.message));
            }
        };

        const printCaseSheet = (admission) => {
            window.open(`/ipd/${admission.ipd_id}/print`, '_blank');
        };

        onMounted(() => {
            loadMasterData();
            loadSummary();
            loadAdmissions();
            loadBedAvailability();
        });

        return {
            loading,
            admissions,
            summary,
            bedAvailability,
            showBedMap,
            wards,
            departments,
            doctors,
            filters,
            pagination,
            paginationPages,
            loadAdmissions,
            resetFilters,
            goToPage,
            formatDate,
            formatStatus,
            getStatusBadge,
            getRowClass,
            viewAdmission,
            initiateDischarge,
            printCaseSheet,
        };
    },
};
</script>

<style scoped>
.bed-box {
    width: 60px;
    height: 50px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    cursor: pointer;
}

.bed-box.available {
    background-color: #d4edda;
    border-color: #28a745;
}

.bed-box.occupied {
    background-color: #f8d7da;
    border-color: #dc3545;
}

.bed-number {
    font-weight: bold;
    font-size: 0.9rem;
}

.patient-name {
    font-size: 0.7rem;
    color: #666;
}

.isolation-icon,
.ventilator-icon {
    position: absolute;
    top: 2px;
    font-size: 0.7rem;
}

.isolation-icon {
    right: 2px;
    color: #ffc107;
}

.ventilator-icon {
    left: 2px;
    color: #17a2b8;
}

.bed-legend {
    display: inline-block;
    width: 16px;
    height: 16px;
    border-radius: 3px;
    margin-right: 4px;
    vertical-align: middle;
}

.bed-legend.available {
    background-color: #d4edda;
    border: 1px solid #28a745;
}

.bed-legend.occupied {
    background-color: #f8d7da;
    border: 1px solid #dc3545;
}
</style>
