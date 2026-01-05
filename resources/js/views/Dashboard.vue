<template>
    <div>
        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card primary">
                <i class="bi bi-people stat-icon"></i>
                <div class="stat-value">{{ stats.patients_today }}</div>
                <div class="stat-label">Patients Today</div>
            </div>

            <div class="stat-card success">
                <i class="bi bi-clipboard2-pulse stat-icon"></i>
                <div class="stat-value">{{ stats.opd_visits_today }}</div>
                <div class="stat-label">OPD Visits Today</div>
            </div>

            <div class="stat-card warning">
                <i class="bi bi-hospital stat-icon"></i>
                <div class="stat-value">{{ stats.ipd_admissions_active }}</div>
                <div class="stat-label">Active IPD Admissions</div>
            </div>

            <div class="stat-card danger">
                <i class="bi bi-droplet stat-icon"></i>
                <div class="stat-value">{{ stats.pending_lab_orders }}</div>
                <div class="stat-label">Pending Lab Orders</div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h6>Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/patients/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-person-plus d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>New Patient</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/opd/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-clipboard-plus d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>New OPD Visit</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/ipd/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-building d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>New Admission</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/billing/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-receipt d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>New Bill</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/laboratory/orders/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-droplet d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>Lab Order</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/pharmacy/sales/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-capsule d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>Pharmacy Sale</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/appointments/create" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-calendar-plus d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>Appointment</span>
                                </router-link>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <router-link to="/reports" class="btn btn-light w-100 py-3">
                                    <i class="bi bi-bar-chart d-block mb-1" style="font-size: 1.5rem;"></i>
                                    <span>Reports</span>
                                </router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6>System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="avatar-circle me-3">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ authStore.user?.full_name }}</h6>
                                <span class="badge bg-primary">{{ authStore.user?.role }}</span>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="bi bi-calendar3"></i>
                            <div>
                                <small class="text-muted">Date</small>
                                <div>{{ currentDate }}</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="bi bi-clock"></i>
                            <div>
                                <small class="text-muted">Time</small>
                                <div>{{ currentTime }}</div>
                            </div>
                        </div>

                        <div class="info-item">
                            <i class="bi bi-hdd"></i>
                            <div>
                                <small class="text-muted">System Status</small>
                                <div><span class="badge bg-success">Online</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

const authStore = useAuthStore();

const stats = reactive({
    patients_today: 0,
    opd_visits_today: 0,
    ipd_admissions_active: 0,
    pending_lab_orders: 0
});

const currentDate = ref('');
const currentTime = ref('');
let timeInterval = null;

const updateDateTime = () => {
    const now = new Date();
    currentDate.value = now.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    currentTime.value = now.toLocaleTimeString('en-US');
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
.avatar-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3699ff 0%, #1e86ff 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.5rem;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #ebedf3;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    width: 40px;
    height: 40px;
    border-radius: 0.42rem;
    background: #f3f6f9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: #3699ff;
    font-size: 1.1rem;
}

.btn-light:hover {
    background: #e4e6ef;
    color: #3699ff;
}

.btn-light:hover i {
    color: #3699ff;
}
</style>
