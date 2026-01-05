<template>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar" :class="{ 'show': sidebarOpen }">
            <div class="sidebar-brand">
                <i class="bi bi-heart-pulse"></i>
                <h4>HIMS</h4>
            </div>

            <div class="sidebar-menu">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <router-link class="nav-link" to="/" exact-active-class="active">
                            <i class="bi bi-grid"></i> Dashboard
                        </router-link>
                    </li>

                    <li class="nav-section">Patient Management</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/patients" active-class="active">
                            <i class="bi bi-people"></i> Patients
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/appointments" active-class="active">
                            <i class="bi bi-calendar-check"></i> Appointments
                        </router-link>
                    </li>

                    <li class="nav-section">Clinical</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/doctors" active-class="active">
                            <i class="bi bi-person-badge"></i> Doctors
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/departments" active-class="active">
                            <i class="bi bi-building"></i> Departments
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/opd" active-class="active">
                            <i class="bi bi-clipboard2-pulse"></i> OPD Visits
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/ipd" active-class="active">
                            <i class="bi bi-hospital"></i> IPD Admissions
                        </router-link>
                    </li>

                    <li class="nav-section">Laboratory</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/laboratory/tests" active-class="active">
                            <i class="bi bi-droplet"></i> Lab Tests
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/laboratory/orders" active-class="active">
                            <i class="bi bi-file-earmark-medical"></i> Lab Orders
                        </router-link>
                    </li>

                    <li class="nav-section">Radiology</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/radiology/orders" active-class="active">
                            <i class="bi bi-radioactive"></i> Radiology Orders
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/radiology/worklist" active-class="active">
                            <i class="bi bi-list-check"></i> Worklist
                        </router-link>
                    </li>

                    <li class="nav-section">Operation Theater</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/ot/schedules" active-class="active">
                            <i class="bi bi-calendar2-week"></i> OT Schedule
                        </router-link>
                    </li>

                    <li class="nav-section">Pharmacy</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/pharmacy/drugs" active-class="active">
                            <i class="bi bi-capsule"></i> Drugs Inventory
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/pharmacy/sales" active-class="active">
                            <i class="bi bi-cart3"></i> Pharmacy Sales
                        </router-link>
                    </li>

                    <li class="nav-section">Inventory</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/inventory" active-class="active">
                            <i class="bi bi-box-seam"></i> Dashboard
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/inventory/items" active-class="active">
                            <i class="bi bi-grid-3x3"></i> Items Master
                        </router-link>
                    </li>

                    <li class="nav-section">Billing</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/billing" active-class="active">
                            <i class="bi bi-receipt"></i> Bills
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/payments" active-class="active">
                            <i class="bi bi-credit-card"></i> Payments
                        </router-link>
                    </li>

                    <li class="nav-section">Reports</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/reports" active-class="active">
                            <i class="bi bi-bar-chart"></i> Reports
                        </router-link>
                    </li>

                    <li class="nav-section">Medical Records</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/mrd" active-class="active">
                            <i class="bi bi-folder2-open"></i> MRD Dashboard
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/birth-registrations" active-class="active">
                            <i class="bi bi-person-hearts"></i> Birth Registrations
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/death-registrations" active-class="active">
                            <i class="bi bi-file-medical"></i> Death Registrations
                        </router-link>
                    </li>

                    <li class="nav-section">ABHA Integration</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/abha" active-class="active">
                            <i class="bi bi-shield-check"></i> ABHA Management
                        </router-link>
                    </li>

                    <li class="nav-section">Configuration</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/settings/opd-configuration" active-class="active">
                            <i class="bi bi-sliders"></i> OPD Configuration
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/settings/opd-time-slots" active-class="active">
                            <i class="bi bi-calendar3"></i> OPD Time Slots
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/settings/rate-requests" active-class="active">
                            <i class="bi bi-cash-stack"></i> Rate Requests
                        </router-link>
                    </li>

                    <li class="nav-section">System</li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/users" active-class="active">
                            <i class="bi bi-person-gear"></i> Users
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/roles" active-class="active">
                            <i class="bi bi-shield-lock"></i> Roles & Permissions
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/notifications/settings" active-class="active">
                            <i class="bi bi-bell"></i> Notifications
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link" to="/settings" active-class="active">
                            <i class="bi bi-gear"></i> Settings
                        </router-link>
                    </li>

                    <!-- Super Admin Only -->
                    <template v-if="authStore.isSuperAdmin">
                        <li class="nav-section">Super Admin</li>
                        <li class="nav-item">
                            <router-link class="nav-link" to="/hospitals" active-class="active">
                                <i class="bi bi-hospital"></i> Hospitals
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link class="nav-link" to="/admin/claude-chat" active-class="active">
                                <i class="bi bi-robot"></i> AI Assistant
                            </router-link>
                        </li>
                    </template>
                </ul>
            </div>
        </nav>

        <!-- Main Content Wrapper -->
        <div class="main-wrapper">
            <!-- Top Navbar -->
            <nav class="top-navbar d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="btn btn-light btn-icon d-lg-none me-2" @click="toggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>
                    <h5 class="mb-0 fw-semibold">{{ pageTitle }}</h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Hospital Switcher (Super Admin) -->
                    <div class="dropdown" v-if="authStore.isSuperAdmin && authStore.availableHospitals.length > 0">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-hospital me-1"></i>
                            {{ authStore.currentHospital?.name || 'All Hospitals' }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" @click.prevent="clearHospitalContext">
                                    <i class="bi bi-grid me-2"></i> All Hospitals
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li v-for="hospital in authStore.availableHospitals" :key="hospital.hospital_id">
                                <a class="dropdown-item" href="#" @click.prevent="switchHospital(hospital)"
                                   :class="{ 'active': authStore.currentHospital?.hospital_id === hospital.hospital_id }">
                                    {{ hospital.name }}
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Hospital Name (Regular Users) -->
                    <div v-else-if="authStore.currentHospital" class="hospital-badge d-none d-md-block">
                        <i class="bi bi-hospital me-1"></i>
                        {{ authStore.currentHospital.name }}
                    </div>

                    <div class="dropdown">
                        <button class="btn dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <div class="avatar">
                                <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                            </div>
                            <div class="text-start d-none d-md-block">
                                <div class="fw-semibold">{{ authStore.user?.full_name }}</div>
                                <small class="text-muted">{{ authStore.user?.role }}</small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-person me-2"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-key me-2"></i> Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" @click.prevent="logout">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <main class="main-content">
                <router-view />
            </main>
        </div>

        <!-- Sidebar Overlay (Mobile) -->
        <div class="sidebar-overlay" :class="{ 'show': sidebarOpen }" @click="sidebarOpen = false"></div>

        <!-- Claude AI Assistant Widget -->
        <ClaudeAssistant />
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import ClaudeAssistant from '../ClaudeAssistant.vue';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const sidebarOpen = ref(false);

const pageTitle = computed(() => {
    const titles = {
        '/': 'Dashboard',
        '/patients': 'Patients',
        '/doctors': 'Doctors',
        '/departments': 'Departments',
        '/appointments': 'Appointments',
        '/opd': 'OPD Visits',
        '/ipd': 'IPD Admissions',
        '/laboratory/tests': 'Lab Tests',
        '/laboratory/orders': 'Lab Orders',
        '/radiology/orders': 'Radiology Orders',
        '/radiology/worklist': 'Radiology Worklist',
        '/ot/schedules': 'OT Schedule',
        '/pharmacy/drugs': 'Drug Inventory',
        '/pharmacy/sales': 'Pharmacy Sales',
        '/inventory': 'Inventory Dashboard',
        '/inventory/items': 'Items Master',
        '/billing': 'Bills',
        '/payments': 'Payments',
        '/reports': 'Reports',
        '/mrd': 'Medical Records',
        '/birth-registrations': 'Birth Registrations',
        '/death-registrations': 'Death Registrations',
        '/abha': 'ABHA Management',
        '/users': 'Users',
        '/roles': 'Roles & Permissions',
        '/notifications/settings': 'Notification Settings',
        '/settings': 'Settings',
        '/settings/opd-configuration': 'OPD Configuration',
        '/settings/opd-time-slots': 'OPD Time Slots',
        '/settings/rate-requests': 'Rate Change Requests',
        '/hospitals': 'Hospitals',
        '/admin/claude-chat': 'AI Assistant',
    };
    return titles[route.path] || 'HIMS';
});

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

watch(route, () => {
    sidebarOpen.value = false;
});

const logout = async () => {
    await authStore.logout();
    router.push('/login');
};

const switchHospital = async (hospital) => {
    await authStore.switchHospital(hospital);
    router.push('/');
};

const clearHospitalContext = () => {
    authStore.clearHospitalContext();
    router.push('/hospitals');
};
</script>

<style scoped>
.main-wrapper {
    flex: 1;
    margin-left: 220px;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    width: calc(100% - 220px);
    max-width: calc(100vw - 220px);
    overflow-x: hidden;
}

.sidebar {
    width: 220px;
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-menu {
    padding: 0.5rem 0;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.sidebar-overlay.show {
    display: block;
}

@media (max-width: 991.98px) {
    .main-wrapper {
        margin-left: 0;
        width: 100%;
        max-width: 100vw;
    }

    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .sidebar.show {
        transform: translateX(0);
    }
}
</style>
