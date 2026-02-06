<template>
    <div class="ipd-list">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-1">
            <div>
                <h4 class="mb-0 fw-bold">IPD Dashboard</h4>
            </div>
            <div class="d-flex gap-2">
                <button class="modern-btn modern-btn-outline" @click="showBedMap = true">
                    <i class="bi bi-grid-3x3"></i>
                </button>
                <router-link to="/ipd/create" class="modern-btn modern-btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    <span>New Admission</span>
                </router-link>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-2 mb-1">
            <div class="col-2">
                <div class="stat-card-compact stat-card-gradient-primary">
                    <div class="stat-label-compact">Total Admitted</div>
                    <div class="stat-value-compact">{{ summary.total_admitted || 0 }}</div>
                </div>
            </div>
            <div class="col-2">
                <div class="stat-card-compact stat-card-gradient-success">
                    <div class="stat-label-compact">Today Admissions</div>
                    <div class="stat-value-compact">{{ summary.today_admissions || 0 }}</div>
                </div>
            </div>
            <div class="col-2">
                <div class="stat-card-compact stat-card-gradient-info">
                    <div class="stat-label-compact">Today Discharges</div>
                    <div class="stat-value-compact">{{ summary.today_discharges || 0 }}</div>
                </div>
            </div>
            <div class="col-2">
                <div class="stat-card-compact stat-card-gradient-warning">
                    <div class="stat-label-compact">Pending Discharge</div>
                    <div class="stat-value-compact">{{ summary.pending_discharge || 0 }}</div>
                </div>
            </div>
            <div class="col-2">
                <div class="stat-card-compact stat-card-gradient-danger">
                    <div class="stat-label-compact">MLC Cases</div>
                    <div class="stat-value-compact">{{ summary.mlc_cases || 0 }}</div>
                </div>
            </div>
            <div class="col-2">
                <div class="stat-card-compact stat-card-gradient-secondary">
                    <div class="stat-label-compact">Insurance</div>
                    <div class="stat-value-compact">{{ summary.insurance_cases || 0 }}</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="modern-card mb-1">
            <div class="modern-card-header clickable" @click="showFilters = !showFilters">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <h6 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>Filters
                        <span v-if="hasActiveFilters" class="badge bg-primary ms-2" style="font-size: 0.7rem;">Active</span>
                    </h6>
                    <button class="btn btn-sm btn-link text-decoration-none p-0">
                        <i class="bi" :class="showFilters ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                </div>
            </div>
            <transition name="filter-collapse">
                <div v-show="showFilters" class="modern-card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="modern-label">Status</label>
                            <select v-model="filters.status" class="modern-select" @change="loadAdmissions">
                                <option value="all">All Status</option>
                                <option value="admitted">Admitted</option>
                                <option value="discharge_initiated">Discharge Initiated</option>
                                <option value="discharged">Discharged</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Ward</label>
                            <select v-model="filters.ward_id" class="modern-select" @change="loadAdmissions">
                                <option value="">All Wards</option>
                                <option v-for="ward in wards" :key="ward.ward_id" :value="ward.ward_id">
                                    {{ ward.ward_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Department</label>
                            <select v-model="filters.department_id" class="modern-select" @change="loadAdmissions">
                                <option value="">All Departments</option>
                                <option v-for="dept in departments" :key="dept.department_id" :value="dept.department_id">
                                    {{ dept.department_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">Doctor</label>
                            <select v-model="filters.doctor_id" class="modern-select" @change="loadAdmissions">
                                <option value="">All Doctors</option>
                                <option v-for="doc in doctors" :key="doc.doctor_id" :value="doc.doctor_id">
                                    {{ doc.doctor_name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">From Date</label>
                            <input type="date" v-model="filters.from_date" class="modern-input" @change="loadAdmissions">
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">To Date</label>
                            <input type="date" v-model="filters.to_date" class="modern-input" @change="loadAdmissions">
                        </div>
                        <div class="col-md-3">
                            <label class="modern-label">Search</label>
                            <input type="text" v-model="filters.search" class="modern-input"
                                   placeholder="IPD No, Patient Name, Mobile..." @keyup.enter="loadAdmissions">
                        </div>
                        <div class="col-md-1">
                            <label class="modern-label">&nbsp;</label>
                            <div class="modern-checkbox">
                                <input class="form-check-input" type="checkbox" id="mlc-filter" v-model="filters.is_mlc" @change="loadAdmissions">
                                <label class="form-check-label" for="mlc-filter">MLC</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="modern-label">&nbsp;</label>
                            <button class="modern-btn-reset w-100" @click="resetFilters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <!-- Admissions Table -->
        <div class="modern-card">
            <div class="modern-card-header">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i>Patient Admissions</h6>
            </div>
            <div class="modern-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 modern-table">
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
                                <th>Actions</th>
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
                                    <div class="btn-group" role="group">
                                        <router-link :to="`/ipd/${admission.ipd_id}`" class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </router-link>
                                        <button v-if="admission.status === 'admitted'" class="btn btn-outline-success"
                                                @click="initiateDischarge(admission)" title="Discharge & Print Receipt">
                                            <i class="bi bi-box-arrow-right"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" @click="printCaseSheet(admission)" title="Print Discharge Receipt">
                                            <i class="bi bi-receipt"></i>
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

        const showFilters = ref(false);

        const hasActiveFilters = computed(() => {
            return filters.value.status !== 'admitted' ||
                   filters.value.ward_id !== '' ||
                   filters.value.department_id !== '' ||
                   filters.value.doctor_id !== '' ||
                   filters.value.from_date !== '' ||
                   filters.value.to_date !== '' ||
                   filters.value.search !== '' ||
                   filters.value.is_mlc === true;
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
            if (!confirm('Discharge this patient?\n\nThis will:\n- Mark patient as discharged\n- Release the bed\n- Finalize the IPD bill\n- Open discharge receipt for printing')) return;
            try {
                const response = await axios.post(`/api/ipd-admissions/${admission.ipd_id}/initiate-discharge`, {
                    discharge_type: 'normal',
                    condition_at_discharge: 'Stable'
                });

                // Open discharge receipt automatically
                const url = `/print/ipd-discharge-receipt/${admission.ipd_id}`;
                window.open(url, '_blank');

                // Reload the list to show updated status
                await loadAdmissions(pagination.value.current_page);
                await loadSummary();

                alert('Patient discharged successfully!\n\nStatus: Discharged\nBed: Released\n\nThe discharge receipt has been opened.\nYou can reprint it anytime from the print button.');
            } catch (error) {
                console.error('Discharge error:', error);
                alert('Failed to discharge patient: ' + (error.response?.data?.message || error.message));
            }
        };

        const printCaseSheet = (admission) => {
            // Open discharge receipt (billing receipt)
            const url = `/print/ipd-discharge-receipt/${admission.ipd_id}`;
            window.open(url, '_blank');
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
            if (!bed.is_available && bed.current_patient) {
                const patient = bed.current_patient;
                const patientName = patient.full_name || `${patient.first_name || ""} ${patient.last_name || ""}`.trim() || "Unknown Patient";
                const mobile = patient.mobile || "N/A";

                // Build address string - prioritize permanent address
                const parts = [];

                // Use permanent address if available
                if (patient.permanent_address) {
                    parts.push(patient.permanent_address);
                    if (patient.permanent_city?.city_name) parts.push(patient.permanent_city.city_name);
                    if (patient.permanent_state?.state_name) parts.push(patient.permanent_state.state_name);
                    if (patient.permanent_pincode) parts.push(patient.permanent_pincode);
                }
                // Fall back to current address
                else if (patient.current_address) {
                    parts.push(patient.current_address);
                    if (patient.current_city?.city_name) parts.push(patient.current_city.city_name);
                    if (patient.current_state?.state_name) parts.push(patient.current_state.state_name);
                    if (patient.current_pincode) parts.push(patient.current_pincode);
                }
                // Fall back to legacy address fields
                else {
                    if (patient.address) parts.push(patient.address);
                    if (patient.city) parts.push(patient.city);
                    if (patient.state) parts.push(patient.state);
                    if (patient.pincode) parts.push(patient.pincode);
                }

                const address = parts.length > 0 ? parts.join(", ") : "Address not available";

                return `Patient: ${patientName}\nMobile: ${mobile}\nAddress: ${address}`;
            }
            return "Available";
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
            showFilters,
            hasActiveFilters,
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
/* Responsive optimization for 13-14" screens */
@media (max-width: 1600px) {
    h2, h4 {
        font-size: 1.5rem !important;
    }

    .stat-value-compact {
        font-size: 1.1rem !important;
    }

    .stat-card-compact {
        padding: 0.65rem 0.85rem !important;
        min-height: 65px !important;
    }

    .stat-label-compact {
        font-size: 0.65rem !important;
    }

    .modern-table {
        font-size: 0.813rem;
    }

    .modern-table thead th,
    .modern-table tbody td {
        padding: 0.5rem 0.4rem;
    }

    .btn-sm {
        padding: 0.25rem 0.35rem;
        font-size: 0.75rem;
    }

    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .modern-btn {
        padding: 0.5rem 1rem;
        font-size: 0.813rem;
    }

    .modern-card-header {
        padding: 0.5rem 0.75rem;
    }

    .modern-card-body {
        padding: 0.75rem;
    }
}

/* Modern Dashboard Styles */
.ipd-list {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 1.5rem;
}

/* Modern Buttons */
.modern-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
}

.modern-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

.modern-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.35);
    color: white;
}

.modern-btn-outline {
    background: white;
    color: #6c757d;
    border: 1px solid #e0e0e0;
}

.modern-btn-outline:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

/* Compact Stat Cards */
.stat-card-compact {
    border-radius: 12px;
    padding: 0.75rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    border: none;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    height: 100%;
    min-height: 70px;
    position: relative;
    overflow: hidden;
}

.stat-card-compact:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-label-compact {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    opacity: 0.9;
    color: white;
}

.stat-value-compact {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1;
    color: white;
}

/* Modern Stat Cards with Gradients */
.stat-card {
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    min-height: 130px;
    position: relative;
    overflow: hidden;
}

.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

/* Gradient Backgrounds */
.stat-card-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.stat-card-gradient-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.stat-card-gradient-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.stat-card-gradient-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
}

.stat-card-gradient-danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.stat-card-gradient-secondary {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #2c3e50;
}

.stat-content-full {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-label-top {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.stat-value-large {
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1;
    margin: 0.25rem 0;
}

.stat-description {
    font-size: 0.75rem;
    opacity: 0.85;
    line-height: 1.4;
}

/* Modern Cards */
.modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.modern-card-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f0f0f0;
    background: #fafafa;
}

.modern-card-header h6 {
    font-weight: 600;
    color: #2c3e50;
    display: flex;
    align-items: center;
}

.modern-card-body {
    padding: 1rem;
}

/* Modern Table */
.modern-table {
    font-size: 0.875rem;
}

.modern-table thead th {
    background: #fafafa;
    color: #6c757d;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 1.25rem;
    border-bottom: 2px solid #f0f0f0;
}

.modern-table tbody td {
    padding: 1rem 1.25rem;
    vertical-align: middle;
    color: #2c3e50;
}

.modern-table tbody tr {
    transition: all 0.2s ease;
}

.modern-table tbody tr:hover {
    background: #f8f9fa;
}

/* Modern Filter Styles */
.modern-card-header.clickable {
    cursor: pointer;
    user-select: none;
    transition: background 0.2s ease;
}

.modern-card-header.clickable:hover {
    background: #f5f5f5;
}

.modern-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6c757d;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modern-select,
.modern-input {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: white;
    color: #2c3e50;
}

.modern-select:focus,
.modern-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.modern-select:hover,
.modern-input:hover {
    border-color: #b0b0b0;
}

.modern-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0;
}

.modern-checkbox .form-check-input {
    width: 1.125rem;
    height: 1.125rem;
    border: 2px solid #e0e0e0;
    border-radius: 4px;
    cursor: pointer;
}

.modern-checkbox .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.modern-checkbox .form-check-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2c3e50;
    cursor: pointer;
    margin: 0;
}

.modern-btn-reset {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    color: #6c757d;
    transition: all 0.2s ease;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.modern-btn-reset:hover {
    background: #f8f9fa;
    border-color: #667eea;
    color: #667eea;
}

/* Filter Collapse Animation */
.filter-collapse-enter-active,
.filter-collapse-leave-active {
    transition: all 0.3s ease;
    max-height: 500px;
    overflow: hidden;
}

.filter-collapse-enter-from,
.filter-collapse-leave-to {
    max-height: 0;
    opacity: 0;
}

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


/* IPD List Table Fixes */
.ipd-list .table {
    table-layout: auto;
    width: 100%;
}

.ipd-list .table thead th {
    white-space: nowrap;
    vertical-align: middle;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 0.75rem 0.5rem;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.ipd-list .table tbody td {
    vertical-align: middle;
    padding: 0.75rem 0.5rem;
    font-size: 0.875rem;
}

/* Column widths */
.ipd-list .table th:nth-child(1), .ipd-list .table td:nth-child(1) { width: 10%; }
.ipd-list .table th:nth-child(2), .ipd-list .table td:nth-child(2) { width: 15%; }
.ipd-list .table th:nth-child(3), .ipd-list .table td:nth-child(3) { width: 12%; }
.ipd-list .table th:nth-child(4), .ipd-list .table td:nth-child(4) { width: 15%; }
.ipd-list .table th:nth-child(5), .ipd-list .table td:nth-child(5) { width: 10%; }
.ipd-list .table th:nth-child(6), .ipd-list .table td:nth-child(6) { width: 8%; text-align: center; }
.ipd-list .table th:nth-child(7), .ipd-list .table td:nth-child(7) { width: 10%; text-align: center; }
.ipd-list .table th:nth-child(8), .ipd-list .table td:nth-child(8) { width: 10%; text-align: center; }
.ipd-list .table th:nth-child(9), .ipd-list .table td:nth-child(9) { width: 10%; text-align: center; }

/* Text wrapping */
.ipd-list .table td > div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ipd-list .table td small { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Responsive table - no horizontal scroll */
.ipd-list .table-responsive {
    overflow-x: hidden;
}

.ipd-list .table {
    width: 100%;
    table-layout: auto;
}
</style>
