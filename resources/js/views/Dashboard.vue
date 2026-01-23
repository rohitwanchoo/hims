<template>
    <div>
        <!-- Stats Cards Row -->
        <div class="stats-row">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ stats.patients_today }}</div>
                    <div class="stat-label">Patients Today</div>
                    <div class="stat-change positive" v-if="stats.patients_change > 0">
                        <i class="bi bi-arrow-up"></i> {{ stats.patients_change }}% from yesterday
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-clipboard2-pulse"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ stats.opd_visits_today }}</div>
                    <div class="stat-label">OPD Visits Today</div>
                    <div class="stat-change positive" v-if="stats.opd_change > 0">
                        <i class="bi bi-arrow-up"></i> {{ stats.opd_change }}% from yesterday
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-hospital"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ stats.ipd_admissions_active }}</div>
                    <div class="stat-label">Active IPD Admissions</div>
                    <div class="stat-change" v-if="stats.bed_occupancy">
                        {{ stats.bed_occupancy }}% bed occupancy
                    </div>
                </div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-droplet"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ stats.pending_lab_orders }}</div>
                    <div class="stat-label">Pending Lab Orders</div>
                    <div class="stat-change" v-if="stats.urgent_labs > 0">
                        {{ stats.urgent_labs }} urgent
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Actions Card -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/patients/create" class="quick-action-card">
                                    <i class="bi bi-person-plus"></i>
                                    <span>New Patient</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/opd/create" class="quick-action-card">
                                    <i class="bi bi-clipboard-plus"></i>
                                    <span>New OPD Visit</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/ipd/create" class="quick-action-card">
                                    <i class="bi bi-building"></i>
                                    <span>New Admission</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/billing/create" class="quick-action-card">
                                    <i class="bi bi-receipt"></i>
                                    <span>New Bill</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/laboratory/orders/create" class="quick-action-card">
                                    <i class="bi bi-droplet"></i>
                                    <span>Lab Order</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/pharmacy/sales/create" class="quick-action-card">
                                    <i class="bi bi-capsule"></i>
                                    <span>Pharmacy Sale</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/appointments/create" class="quick-action-card">
                                    <i class="bi bi-calendar-plus"></i>
                                    <span>Appointment</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/reports" class="quick-action-card">
                                    <i class="bi bi-bar-chart-line"></i>
                                    <span>Reports</span>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Patients Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-people me-2"></i>Recent Patients</h5>
                        <router-link to="/patients" class="btn btn-sm btn-soft-primary">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </router-link>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Phone</th>
                                        <th>Last Visit</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="patient in recentPatients" :key="patient.id">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm avatar-soft-primary me-2">
                                                    {{ getInitials(patient.name) }}
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ patient.name }}</div>
                                                    <small class="text-muted">{{ patient.patient_id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ patient.phone }}</td>
                                        <td>{{ patient.last_visit }}</td>
                                        <td>
                                            <span :class="getStatusBadgeClass(patient.status)">
                                                {{ patient.status }}
                                            </span>
                                        </td>
                                        <td>
                                            <router-link
                                                :to="`/patients/${patient.id}`"
                                                class="btn btn-sm btn-soft-primary"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </router-link>
                                        </td>
                                    </tr>
                                    <tr v-if="recentPatients.length === 0">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            No recent patients found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- System Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle me-2"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- User Info -->
                        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                            <div class="avatar avatar-lg avatar-soft-primary me-3">
                                {{ userInitials }}
                            </div>
                            <div>
                                <h6 class="mb-1">{{ authStore.user?.full_name }}</h6>
                                <span class="badge badge-soft-primary">{{ authStore.user?.role }}</span>
                            </div>
                        </div>

                        <!-- Info Items -->
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-calendar3"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Date</div>
                                <div class="info-value">{{ currentDate }}</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Time</div>
                                <div class="info-value">{{ currentTime }}</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-hdd"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">System Status</div>
                                <div class="info-value">
                                    <span class="badge badge-soft-success">
                                        <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>
                                        Online
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="info-item" v-if="authStore.currentHospital">
                            <div class="info-icon">
                                <i class="bi bi-hospital"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Hospital</div>
                                <div class="info-value">{{ authStore.currentHospital.name }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-calendar-check me-2"></i>Today's Appointments</h5>
                        <router-link to="/appointments" class="btn btn-sm btn-soft-primary">
                            View All
                        </router-link>
                    </div>
                    <div class="card-body">
                        <div v-if="todayAppointments.length > 0">
                            <div
                                v-for="apt in todayAppointments"
                                :key="apt.id"
                                class="appointment-item"
                            >
                                <div class="appointment-time">
                                    {{ apt.time }}
                                </div>
                                <div class="appointment-details">
                                    <div class="fw-semibold">{{ apt.patient_name }}</div>
                                    <small class="text-muted">{{ apt.doctor_name }}</small>
                                </div>
                                <span :class="getAppointmentBadgeClass(apt.status)">
                                    {{ apt.status }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-muted">
                            <i class="bi bi-calendar-x d-block mb-2" style="font-size: 2rem;"></i>
                            No appointments today
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const authStore = useAuthStore();

const stats = reactive({
    patients_today: 0,
    opd_visits_today: 0,
    ipd_admissions_active: 0,
    pending_lab_orders: 0,
    patients_change: 12,
    opd_change: 8,
    bed_occupancy: 75,
    urgent_labs: 2
});

const recentPatients = ref([
    { id: 1, patient_id: 'PT001', name: 'John Doe', phone: '9876543210', last_visit: 'Today', status: 'Active' },
    { id: 2, patient_id: 'PT002', name: 'Jane Smith', phone: '9876543211', last_visit: 'Yesterday', status: 'Active' },
    { id: 3, patient_id: 'PT003', name: 'Robert Wilson', phone: '9876543212', last_visit: '2 days ago', status: 'Inactive' },
]);

const todayAppointments = ref([
    { id: 1, time: '09:00 AM', patient_name: 'John Doe', doctor_name: 'Dr. Smith', status: 'Scheduled' },
    { id: 2, time: '10:30 AM', patient_name: 'Jane Smith', doctor_name: 'Dr. Johnson', status: 'Completed' },
    { id: 3, time: '02:00 PM', patient_name: 'Mike Brown', doctor_name: 'Dr. Smith', status: 'Pending' },
]);

const currentDate = ref('');
const currentTime = ref('');
let timeInterval = null;

const userInitials = computed(() => {
    const name = authStore.user?.full_name || 'User';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'Active': 'badge badge-soft-success',
        'Inactive': 'badge badge-soft-danger',
        'Pending': 'badge badge-soft-warning'
    };
    return classes[status] || 'badge badge-soft-secondary';
};

const getAppointmentBadgeClass = (status) => {
    const classes = {
        'Scheduled': 'badge badge-soft-info',
        'Completed': 'badge badge-soft-success',
        'Pending': 'badge badge-soft-warning',
        'Cancelled': 'badge badge-soft-danger'
    };
    return classes[status] || 'badge badge-soft-secondary';
};

const updateDateTime = () => {
    const now = new Date();
    currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/dashboard');
        Object.assign(stats, response.data);
    } catch (error) {
        console.error('Failed to fetch dashboard stats:', error);
    }
};

onMounted(() => {
    fetchStats();
    updateDateTime();
    timeInterval = setInterval(updateDateTime, 1000);
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }
});
</script>

<style scoped>
/* Appointment Item */
.appointment-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-color);
}

.appointment-item:last-child {
    border-bottom: none;
}

.appointment-time {
    font-size: 13px;
    font-weight: 600;
    color: var(--primary);
    min-width: 80px;
}

.appointment-details {
    flex: 1;
    margin: 0 12px;
}

/* Avatar Sizes */
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.avatar-lg {
    width: 56px;
    height: 56px;
    font-size: 18px;
}
</style>
