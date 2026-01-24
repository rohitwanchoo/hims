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
        <div class="modal fade" :class="{ show: showBedMap }" :style="{ display: showBedMap ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable bed-map-modal">
                <div class="modal-content bed-map-content-wrapper">
                    <!-- Header -->
                    <div class="modal-header bed-map-header">
                    <div class="header-left">
                        <div class="header-icon">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                        </div>
                        <div class="header-title">
                            <h3>Hospital Bed Map</h3>
                            <p>Real-time bed availability across all wards</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="legend-pills">
                            <span class="pill pill-available">
                                <span class="pill-dot"></span>
                                Available
                            </span>
                            <span class="pill pill-occupied">
                                <span class="pill-dot"></span>
                                Occupied
                            </span>
                        </div>
                        <button class="btn-close-creative" @click="showBedMap = false">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    </div>

                    <!-- Ward Navigation -->
                    <div class="ward-nav-wrapper">
                    <div class="ward-nav-scroll">
                        <div class="ward-nav">
                            <button
                                v-for="ward in bedAvailability"
                                :key="ward.ward_id"
                                :class="['ward-nav-item', { active: selectedWardTab === ward.ward_id }]"
                                @click="selectedWardTab = ward.ward_id"
                            >
                                <div class="ward-nav-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="ward-nav-content">
                                    <div class="ward-nav-title">{{ ward.ward_name }}</div>
                                    <div class="ward-nav-stats">
                                        <span class="stat-available">{{ ward.available_beds }}</span>
                                        <span class="stat-divider">/</span>
                                        <span class="stat-total">{{ ward.total_beds }}</span>
                                    </div>
                                </div>
                                <div class="ward-nav-indicator"></div>
                            </button>
                        </div>
                    </div>
                    </div>

                    <!-- Bed Content -->
                    <div class="modal-body bed-map-body p-0">
                    <div
                        v-for="ward in bedAvailability"
                        :key="ward.ward_id"
                        v-show="selectedWardTab === ward.ward_id"
                        class="ward-floor-plan"
                    >
                                <!-- Empty State -->
                                <div v-if="ward.beds.length === 0" class="empty-ward">
                                    <div class="empty-ward-icon">
                                        <i class="bi bi-hospital"></i>
                                    </div>
                                    <h4>No Rooms Available</h4>
                                    <p>This ward doesn't have any rooms configured yet.</p>
                                    <router-link to="/masters/bed-allocation" class="btn-create-rooms">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        Create Rooms
                                    </router-link>
                                </div>

                                <!-- Rooms Grid -->
                                <div v-else class="rooms-grid">
                                    <div
                                        v-for="(roomBeds, roomKey) in groupBedsByRoom(ward.beds)"
                                        :key="roomKey"
                                        class="room-panel"
                                    >
                                        <div class="room-panel-header">
                                            <div class="room-icon-wrapper">
                                                <i class="bi bi-door-open-fill"></i>
                                            </div>
                                            <div class="room-info">
                                                <h5>{{ roomKey }}</h5>
                                                <div class="room-capacity">
                                                    <span class="capacity-available">
                                                        {{ roomBeds.filter(b => b.is_available).length }} Available
                                                    </span>
                                                    <span class="capacity-divider">•</span>
                                                    <span class="capacity-total">
                                                        {{ roomBeds.length }} Beds
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="beds-floor">
                                            <div
                                                v-for="bed in roomBeds"
                                                :key="bed.bed_id"
                                                :class="['bed-unit', getBedStatusClass(bed)]"
                                                :title="getBedTooltip(bed)"
                                            >
                                                <div class="bed-visual">
                                                    <div class="bed-frame">
                                                        <i class="bi bi-hospital-fill"></i>
                                                    </div>
                                                    <div class="bed-badges">
                                                        <span v-if="bed.is_isolation" class="bed-badge badge-isolation" title="Isolation">
                                                            <i class="bi bi-shield-fill-exclamation"></i>
                                                        </span>
                                                        <span v-if="bed.is_ventilator" class="bed-badge badge-ventilator" title="Ventilator">
                                                            <i class="bi bi-wind"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="bed-label">{{ getBedDisplayName(bed) }}</div>
                                                <div v-if="!bed.is_available" class="bed-patient">
                                                    <i class="bi bi-person-fill"></i>
                                                    {{ getPatientInitials(bed.current_patient) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" v-if="showBedMap" @click="showBedMap = false"></div>
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
        const selectedWardTab = ref(null);
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
                // Set first ward as selected by default
                if (bedAvailability.value.length > 0) {
                    selectedWardTab.value = bedAvailability.value[0].ward_id;
                }
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

        // Bed map helper functions
        const groupBedsByRoom = (beds) => {
            const grouped = {};
            beds.forEach(bed => {
                const roomName = bed.room?.room_name || `Room ${bed.room_number || 'Unknown'}`;
                if (!grouped[roomName]) {
                    grouped[roomName] = [];
                }
                grouped[roomName].push(bed);
            });

            // Sort beds within each room by bed number
            Object.keys(grouped).forEach(roomKey => {
                grouped[roomKey].sort((a, b) => {
                    const numA = parseInt(a.bed_number) || 0;
                    const numB = parseInt(b.bed_number) || 0;
                    return numA - numB;
                });
            });

            return grouped;
        };

        const getBedStatusClass = (bed) => {
            if (!bed.is_available || bed.status === 'occupied') {
                return 'occupied';
            }
            if (bed.status === 'maintenance') {
                return 'maintenance';
            }
            if (bed.status === 'reserved') {
                return 'reserved';
            }
            return 'available';
        };

        const getBedDisplayName = (bed) => {
            const roomName = bed.room?.room_name || '';
            return `${roomName}${bed.bed_number}`;
        };

        const getBedTooltip = (bed) => {
            if (!bed.is_available) {
                const patientName = bed.current_patient?.full_name || bed.current_patient?.patient_name || 'Unknown Patient';
                return `Occupied - ${patientName}`;
            }
            return 'Available';
        };

        const getPatientInitials = (patient) => {
            if (!patient) return '';
            const firstName = patient.first_name || patient.patient_name?.split(' ')[0] || '';
            const lastName = patient.last_name || patient.patient_name?.split(' ')[1] || '';
            return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
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
            selectedWardTab,
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
            groupBedsByRoom,
            getBedStatusClass,
            getBedDisplayName,
            getBedTooltip,
            getPatientInitials,
        };
    },
};
</script>

<style scoped>
/* Bootstrap Modal Adjustments */
.bed-map-modal {
    max-width: 95%;
    margin: 1.75rem auto;
}

.bed-map-content-wrapper {
    height: 85vh;
    display: flex;
    flex-direction: column;
}

/* Header */
.modal-header.bed-map-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-bottom: none;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.header-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

.header-title {
    min-width: 0;
    flex: 1;
}

.header-title h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: -0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-title p {
    font-size: 0.8rem;
    margin: 0.25rem 0 0;
    opacity: 0.9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}

.legend-pills {
    display: flex;
    gap: 0.5rem;
}

.pill {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.8rem;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
    white-space: nowrap;
}

.pill-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.pill-available .pill-dot {
    background: #4ade80;
}

.pill-occupied .pill-dot {
    background: #f87171;
}

.btn-close-creative {
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    color: white;
    font-size: 1.1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    flex-shrink: 0;
}

.btn-close-creative:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Ward Navigation */
.ward-nav-wrapper {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 0 1rem;
    flex-shrink: 0;
}

.ward-nav-scroll {
    overflow-x: auto;
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

.ward-nav-scroll::-webkit-scrollbar {
    height: 6px;
}

.ward-nav-scroll::-webkit-scrollbar-track {
    background: #f3f4f6;
}

.ward-nav-scroll::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

.ward-nav {
    display: flex;
    gap: 0.5rem;
    padding: 0.75rem 0;
    min-width: max-content;
}

.ward-nav-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    min-width: 160px;
    flex-shrink: 0;
}

.ward-nav-item:hover {
    background: #f3f4f6;
}

.ward-nav-item.active {
    background: white;
    border-color: #667eea;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
}

.ward-nav-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: #667eea;
    flex-shrink: 0;
}

.ward-nav-item.active .ward-nav-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.ward-nav-content {
    flex: 1;
    min-width: 0;
}

.ward-nav-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ward-nav-stats {
    font-size: 0.75rem;
    color: #6b7280;
}

.stat-available {
    font-weight: 700;
    color: #10b981;
}

.stat-divider {
    margin: 0 0.25rem;
}

.ward-nav-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: transparent;
    flex-shrink: 0;
}

.ward-nav-item.active .ward-nav-indicator {
    background: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

/* Content Area */
.modal-body.bed-map-body {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    background: #f8f9fa;
    padding: 1.5rem;
    -webkit-overflow-scrolling: touch;
}

.ward-floor-plan {
    width: 100%;
}

/* Empty State */
.empty-ward {
    text-align: center;
    padding: 3rem 1.5rem;
}

.empty-ward-icon {
    width: 100px;
    height: 100px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #9ca3af;
}

.empty-ward h4 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.empty-ward p {
    font-size: 0.9rem;
    color: #6b7280;
    margin-bottom: 1.25rem;
}

.btn-create-rooms {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
}

.btn-create-rooms:hover {
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    color: white;
}

/* Rooms Grid */
.rooms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.25rem;
    width: 100%;
}

/* Room Panel */
.room-panel {
    background: white;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid #e5e7eb;
    width: 100%;
    max-width: 100%;
}

.room-panel:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.room-panel-header {
    background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.875rem;
    border-bottom: 2px solid #e5e7eb;
}

.room-icon-wrapper {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.375rem;
    color: #3b82f6;
    flex-shrink: 0;
}

.room-info {
    flex: 1;
    min-width: 0;
}

.room-info h5 {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.room-capacity {
    font-size: 0.8rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.capacity-available {
    color: #10b981;
    font-weight: 600;
}

.capacity-divider {
    color: #d1d5db;
}

/* Beds Floor */
.beds-floor {
    padding: 1.25rem;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(85px, 1fr));
    gap: 0.875rem;
    justify-content: center;
    width: 100%;
}

/* Bed Unit */
.bed-unit {
    position: relative;
    aspect-ratio: 0.8;
    border-radius: 10px;
    padding: 0.625rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    border: 2px solid;
}

.bed-unit:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Bed Status Colors */
.bed-unit.available {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-color: #10b981;
}

.bed-unit.occupied {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-color: #ef4444;
}

.bed-unit.maintenance {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-color: #f59e0b;
}

.bed-unit.reserved {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-color: #3b82f6;
}

.bed-visual {
    position: relative;
    width: 100%;
}

.bed-frame {
    font-size: 2rem;
    text-align: center;
    margin-bottom: 0.5rem;
}

.bed-unit.available .bed-frame {
    color: #10b981;
}

.bed-unit.occupied .bed-frame {
    color: #ef4444;
}

.bed-unit.maintenance .bed-frame {
    color: #f59e0b;
}

.bed-unit.reserved .bed-frame {
    color: #3b82f6;
}

.bed-badges {
    position: absolute;
    top: -8px;
    right: -8px;
    display: flex;
    gap: 0.25rem;
}

.bed-badge {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    color: white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.badge-isolation {
    background: #f59e0b;
}

.badge-ventilator {
    background: #06b6d4;
}

.bed-label {
    font-size: 0.875rem;
    font-weight: 700;
    text-align: center;
    color: #1f2937;
    letter-spacing: 0.3px;
}

.bed-patient {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(0, 0, 0, 0.1);
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    margin-top: 0.25rem;
}

.bed-unit.occupied .bed-patient {
    background: rgba(239, 68, 68, 0.2);
    color: #991b1b;
}

/* Responsive Design */

/* Large Screens */
@media (min-width: 1400px) {
    .bed-map-modal {
        max-width: 1400px;
    }

    .rooms-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    }

    .beds-floor {
        grid-template-columns: repeat(auto-fill, minmax(95px, 1fr));
        gap: 1rem;
    }
}

/* Medium Screens */
@media (min-width: 992px) and (max-width: 1399px) {
    .bed-map-modal {
        max-width: 90%;
    }

    .rooms-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }

    .beds-floor {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 0.875rem;
    }
}

/* Tablets */
@media (min-width: 768px) and (max-width: 991px) {
    .bed-map-modal {
        max-width: 95%;
    }

    .bed-map-content-wrapper {
        height: 80vh;
    }

    .modal-header.bed-map-header {
        padding: 0.875rem 1rem;
    }

    .header-icon {
        width: 42px;
        height: 42px;
        font-size: 1.35rem;
    }

    .header-title h3 {
        font-size: 1.1rem;
    }

    .header-title p {
        display: none;
    }

    .legend-pills {
        gap: 0.35rem;
    }

    .pill {
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
    }

    .btn-close-creative {
        width: 36px;
        height: 36px;
        font-size: 1rem;
    }

    .ward-nav-item {
        min-width: 130px;
        padding: 0.625rem 0.75rem;
    }

    .ward-nav-icon {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }

    .modal-body.bed-map-body {
        padding: 1rem;
    }

    .rooms-grid {
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 0.875rem;
    }

    .beds-floor {
        grid-template-columns: repeat(auto-fill, minmax(70px, 1fr));
        gap: 0.675rem;
        padding: 0.875rem;
    }
}

/* Mobile Landscape & Small Tablets */
@media (max-width: 767px) {
    .bed-map-modal {
        max-width: 98%;
        margin: 0.5rem auto;
    }

    .bed-map-content-wrapper {
        height: 90vh;
    }

    .modal-header.bed-map-header {
        padding: 0.75rem;
    }

    .header-left {
        gap: 0.625rem;
    }

    .header-icon {
        width: 36px;
        height: 36px;
        font-size: 1.15rem;
    }

    .header-title h3 {
        font-size: 0.95rem;
    }

    .header-title p {
        display: none;
    }

    .legend-pills {
        display: none;
    }

    .btn-close-creative {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }

    .ward-nav-wrapper {
        padding: 0 0.75rem;
    }

    .ward-nav {
        padding: 0.5rem 0;
        gap: 0.375rem;
    }

    .ward-nav-item {
        min-width: 110px;
        padding: 0.5rem;
        gap: 0.45rem;
    }

    .ward-nav-icon {
        width: 28px;
        height: 28px;
        font-size: 0.9rem;
    }

    .ward-nav-title {
        font-size: 0.725rem;
    }

    .ward-nav-stats {
        font-size: 0.65rem;
    }

    .modal-body.bed-map-body {
        padding: 0.75rem;
    }

    .rooms-grid {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }

    .room-panel-header {
        padding: 0.675rem 0.75rem;
    }

    .room-icon-wrapper {
        width: 34px;
        height: 34px;
        font-size: 1.1rem;
    }

    .room-info h5 {
        font-size: 0.875rem;
    }

    .beds-floor {
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 0.5rem;
        padding: 0.675rem;
    }

    .bed-unit {
        padding: 0.5rem;
    }

    .bed-frame {
        font-size: 1.5rem;
    }

    .bed-label {
        font-size: 0.75rem;
    }
}

/* Extra Small Screens (480px - 575px) - Medium Phones */
@media (min-width: 480px) and (max-width: 575px) {
    .bed-map-overlay {
        padding: 0;
    }

    .bed-map-container {
        border-radius: 0;
        height: 100vh;
    }

    .bed-map-header {
        padding: 0.75rem;
        border-radius: 0;
    }

    .header-left {
        gap: 0.625rem;
    }

    .header-icon {
        width: 36px;
        height: 36px;
        font-size: 1.15rem;
    }

    .header-title h3 {
        font-size: 0.95rem;
    }

    .header-title p {
        display: none;
    }

    .legend-pills {
        display: none;
    }

    .btn-close-creative {
        width: 32px;
        height: 32px;
        font-size: 0.9rem;
    }

    .ward-nav-wrapper {
        padding: 0 0.75rem;
    }

    .ward-nav {
        padding: 0.5rem 0;
        gap: 0.375rem;
    }

    .ward-nav-item {
        min-width: 110px;
        padding: 0.5rem;
        gap: 0.45rem;
    }

    .ward-nav-icon {
        width: 28px;
        height: 28px;
        font-size: 0.9rem;
    }

    .ward-nav-title {
        font-size: 0.725rem;
    }

    .ward-nav-stats {
        font-size: 0.65rem;
    }

    .ward-nav-indicator {
        width: 5px;
        height: 5px;
    }

    .bed-map-content {
        padding: 0.75rem;
        width: 100%;
    }

    .ward-floor-plan {
        width: 100%;
    }

    .empty-ward {
        padding: 2rem 1rem;
        width: 100%;
    }

    .empty-ward-icon {
        width: 80px;
        height: 80px;
        font-size: 2.5rem;
    }

    .empty-ward h4 {
        font-size: 1.1rem;
    }

    .empty-ward p {
        font-size: 0.85rem;
    }

    .rooms-grid {
        grid-template-columns: 1fr;
        gap: 0.675rem;
        max-width: 100%;
        width: 100%;
    }

    .room-panel {
        width: 100%;
    }

    .room-panel-header {
        padding: 0.675rem 0.75rem;
        gap: 0.625rem;
    }

    .room-icon-wrapper {
        width: 34px;
        height: 34px;
        font-size: 1.1rem;
    }

    .room-info h5 {
        font-size: 0.875rem;
    }

    .room-capacity {
        font-size: 0.7rem;
    }

    .beds-floor {
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 0.5rem;
        padding: 0.675rem;
    }

    .bed-unit {
        padding: 0.5rem;
    }

    .bed-frame {
        font-size: 1.5rem;
    }

    .bed-label {
        font-size: 0.75rem;
    }

    .bed-patient {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}

/* Very Small Screens (below 480px) - Small Phones */
@media (max-width: 479px) {
    .bed-map-overlay {
        padding: 0;
    }

    .bed-map-container {
        border-radius: 0;
        height: 100vh;
    }

    .bed-map-header {
        padding: 0.625rem;
        border-radius: 0;
        flex-wrap: nowrap;
    }

    .header-left {
        gap: 0.5rem;
        min-width: 0;
    }

    .header-icon {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }

    .header-title {
        min-width: 0;
    }

    .header-title h3 {
        font-size: 0.875rem;
    }

    .header-title p {
        display: none;
    }

    .legend-pills {
        display: none;
    }

    .btn-close-creative {
        width: 30px;
        height: 30px;
        font-size: 0.85rem;
    }

    .ward-nav-wrapper {
        padding: 0 0.625rem;
    }

    .ward-nav {
        padding: 0.5rem 0;
        gap: 0.35rem;
    }

    .ward-nav-item {
        min-width: 100px;
        padding: 0.45rem 0.5rem;
        gap: 0.4rem;
    }

    .ward-nav-icon {
        width: 26px;
        height: 26px;
        font-size: 0.85rem;
    }

    .ward-nav-content {
        min-width: 0;
    }

    .ward-nav-title {
        font-size: 0.7rem;
    }

    .ward-nav-stats {
        font-size: 0.625rem;
    }

    .ward-nav-indicator {
        width: 4px;
        height: 4px;
    }

    .bed-map-content {
        padding: 0.625rem;
        width: 100%;
    }

    .ward-floor-plan {
        width: 100%;
    }

    .empty-ward {
        padding: 1.5rem 0.75rem;
        width: 100%;
    }

    .empty-ward-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }

    .empty-ward h4 {
        font-size: 1rem;
    }

    .empty-ward p {
        font-size: 0.8rem;
    }

    .btn-create-rooms {
        padding: 0.625rem 1.25rem;
        font-size: 0.875rem;
    }

    .rooms-grid {
        grid-template-columns: 1fr;
        gap: 0.625rem;
        max-width: 100%;
        width: 100%;
    }

    .room-panel {
        border-radius: 10px;
        width: 100%;
    }

    .room-panel-header {
        padding: 0.625rem;
        gap: 0.5rem;
    }

    .room-icon-wrapper {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }

    .room-info h5 {
        font-size: 0.8rem;
    }

    .room-capacity {
        font-size: 0.675rem;
        gap: 0.3rem;
    }

    .beds-floor {
        grid-template-columns: repeat(auto-fill, minmax(55px, 1fr));
        gap: 0.45rem;
        padding: 0.625rem;
    }

    .bed-unit {
        padding: 0.45rem;
        border-radius: 8px;
    }

    .bed-frame {
        font-size: 1.35rem;
        margin-bottom: 0.35rem;
    }

    .bed-label {
        font-size: 0.7rem;
    }

    .bed-patient {
        font-size: 0.65rem;
        padding: 0.15rem 0.35rem;
        gap: 0.25rem;
    }

    .bed-badges {
        top: -6px;
        right: -6px;
    }

    .bed-badge {
        width: 16px;
        height: 16px;
        font-size: 0.55rem;
    }
}

</style>
