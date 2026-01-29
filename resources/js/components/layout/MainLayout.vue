<template>
    <div class="app-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar" :class="{ 'show': sidebarOpen }">
            <!-- Brand Logo -->
            <div class="sidebar-brand">
                <div class="brand-logo">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <div>
                    <div class="brand-name">HIMS</div>
                    <div class="brand-subtitle">Healthcare System</div>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar-menu">
                <ul class="nav">
                    <!-- Dashboard (No submenu) -->
                    <li class="nav-item">
                        <router-link class="nav-link" to="/" exact-active-class="active">
                            <i class="bi bi-grid-1x2"></i>
                            <span>Dashboard</span>
                        </router-link>
                    </li>

                    <!-- Doctor Workbench (No submenu) -->
                    <li class="nav-item">
                        <router-link class="nav-link" to="/doctor-workbench" active-class="active">
                            <i class="bi bi-clipboard2-pulse"></i>
                            <span>Doctor Workbench</span>
                        </router-link>
                    </li>

                    <!-- Menu Sections with Submenus -->
                    <li class="nav-item has-submenu" v-for="section in menuSections" :key="section.id">
                        <a
                            class="nav-link menu-toggle"
                            href="#"
                            @click.prevent="toggleMenu(section.id)"
                            :class="{ 'expanded': expandedMenus.includes(section.id), 'has-active': isSectionActive(section) }"
                        >
                            <i :class="section.icon"></i>
                            <span>{{ section.title }}</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <ul class="submenu" :class="{ 'show': expandedMenus.includes(section.id) }">
                            <li class="nav-item" v-for="item in section.items" :key="item.path">
                                <router-link class="nav-link" :to="item.path" active-class="active">
                                    <span>{{ item.label }}</span>
                                </router-link>
                            </li>
                        </ul>
                    </li>

                    <!-- Super Admin Only -->
                    <li class="nav-item has-submenu" v-if="authStore.isSuperAdmin">
                        <a
                            class="nav-link menu-toggle"
                            href="#"
                            @click.prevent="toggleMenu('superadmin')"
                            :class="{ 'expanded': expandedMenus.includes('superadmin') }"
                        >
                            <i class="bi bi-shield-lock"></i>
                            <span>Super Admin</span>
                            <i class="bi bi-chevron-right menu-arrow"></i>
                        </a>
                        <ul class="submenu" :class="{ 'show': expandedMenus.includes('superadmin') }">
                            <li class="nav-item">
                                <router-link class="nav-link" to="/hospitals" active-class="active">
                                    <span>Hospitals</span>
                                </router-link>
                            </li>
                            <li class="nav-item">
                                <router-link class="nav-link" to="/admin/claude-chat" active-class="active">
                                    <span>AI Assistant</span>
                                </router-link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content Wrapper -->
        <div class="main-wrapper">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <div class="d-flex align-items-center gap-3">
                    <!-- Mobile Menu Toggle -->
                    <button class="btn btn-icon btn-light d-lg-none" @click="toggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>

                    <!-- Page Title & Breadcrumb -->
                    <div>
                        <h1 class="page-title">{{ pageTitle }}</h1>
                    </div>
                </div>

                <div class="navbar-actions">
                    <!-- Search -->
                    <button class="nav-btn d-none d-md-flex">
                        <i class="bi bi-search"></i>
                    </button>

                    <!-- Notifications -->
                    <button class="nav-btn">
                        <i class="bi bi-bell"></i>
                        <span class="badge">3</span>
                    </button>

                    <!-- Hospital Switcher (Super Admin) -->
                    <div class="dropdown" v-if="authStore.isSuperAdmin && authStore.availableHospitals.length > 0">
                        <button class="hospital-badge dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-hospital"></i>
                            <span>{{ authStore.currentHospital?.name || 'All Hospitals' }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#" @click.prevent="clearHospitalContext">
                                    <i class="bi bi-grid"></i> All Hospitals
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

                    <!-- Hospital Name Badge (Regular Users) -->
                    <div v-else-if="authStore.currentHospital" class="hospital-badge d-none d-md-flex">
                        <i class="bi bi-hospital"></i>
                        <span>{{ authStore.currentHospital.name }}</span>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <div class="user-profile dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="avatar avatar-soft-primary">
                                {{ userInitials }}
                            </div>
                            <div class="user-info d-none d-md-block">
                                <div class="user-name">{{ authStore.user?.full_name }}</div>
                                <div class="user-role">{{ authStore.user?.role }}</div>
                            </div>
                            <i class="bi bi-chevron-down d-none d-md-block"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-person"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-key"></i> Change Password
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" @click.prevent="logout">
                                    <i class="bi bi-box-arrow-right"></i> Logout
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
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const sidebarOpen = ref(false);
const expandedMenus = ref([]);

// Menu structure
const menuSections = [
    {
        id: 'masters',
        title: 'Masters',
        icon: 'bi bi-database',
        items: [
            { path: '/masters/reception/prefix', label: 'Prefix' },
            { path: '/masters/reception/gender', label: 'Gender' },
            { path: '/masters/reception/blood-group', label: 'Blood Group' },
            { path: '/masters/reception/patient-type', label: 'Patient Type' },
            { path: '/masters/reception/marital-status', label: 'Marital Status' },
            { path: '/masters/reception/reference-doctor', label: 'Reference Master' },
            { path: '/masters/reception/insurance-company', label: 'Insurance Company' },
            { path: '/masters/reception/qualification', label: 'Qualification' },
            { path: '/masters/reception/consult-master', label: 'Consult Master' },
            { path: '/masters/prescription', label: 'Prescription Master' },
            { path: '/masters/bed-allocation', label: 'Bed Allocation' },
            { path: '/masters/hospital-services', label: 'Hospital Services' },
            { path: '/masters/gst-plan', label: 'GST Plan Master' }
        ]
    },
    {
        id: 'address',
        title: 'Address Masters',
        icon: 'bi bi-geo-alt',
        items: [
            { path: '/masters/address/country', label: 'Country' },
            { path: '/masters/address/state', label: 'State' },
            { path: '/masters/address/district', label: 'District' },
            { path: '/masters/address/city', label: 'City/Taluka' },
            { path: '/masters/address/area', label: 'Area/Village' }
        ]
    },
    {
        id: 'patients',
        title: 'Patient Management',
        icon: 'bi bi-people',
        items: [
            { path: '/patients', label: 'Patients' },
            { path: '/appointments', label: 'Appointments' },
            { path: '/calendar', label: 'Calendar' }
        ]
    },
    {
        id: 'clinical',
        title: 'Clinical',
        icon: 'bi bi-clipboard2-pulse',
        items: [
            { path: '/doctors', label: 'Doctors' },
            { path: '/departments', label: 'Departments' },
            { path: '/opd', label: 'OPD Visits' },
            { path: '/ipd', label: 'IPD Admissions' },
            { path: '/discharge-summary', label: 'Discharge Summaries' },
            { path: '/bed-transfers', label: 'Bed Transfer' },
            { path: '/consultation-forms', label: 'Consultation Forms' }
        ]
    },
    {
        id: 'laboratory',
        title: 'Laboratory',
        icon: 'bi bi-droplet',
        items: [
            { path: '/laboratory/tests', label: 'Lab Tests' },
            { path: '/laboratory/orders', label: 'Lab Orders' }
        ]
    },
    {
        id: 'radiology',
        title: 'Radiology',
        icon: 'bi bi-x-ray',
        items: [
            { path: '/radiology/orders', label: 'Radiology Orders' },
            { path: '/radiology/worklist', label: 'Worklist' }
        ]
    },
    {
        id: 'ot',
        title: 'Operation Theater',
        icon: 'bi bi-heart-pulse',
        items: [
            { path: '/ot/schedules', label: 'OT Schedule' }
        ]
    },
    {
        id: 'pharmacy',
        title: 'Pharmacy',
        icon: 'bi bi-capsule',
        items: [
            { path: '/pharmacy/drugs', label: 'Drugs Inventory' },
            { path: '/pharmacy/sales', label: 'Pharmacy Sales' }
        ]
    },
    {
        id: 'inventory',
        title: 'Inventory',
        icon: 'bi bi-box-seam',
        items: [
            { path: '/inventory', label: 'Dashboard' },
            { path: '/inventory/items', label: 'Items Master' }
        ]
    },
    {
        id: 'billing',
        title: 'Billing',
        icon: 'bi bi-receipt',
        items: [
            { path: '/billing', label: 'Bills' },
            { path: '/payments', label: 'Payments' }
        ]
    },
    {
        id: 'reports',
        title: 'Reports',
        icon: 'bi bi-bar-chart-line',
        items: [
            { path: '/reports', label: 'Reports' }
        ]
    },
    {
        id: 'mrd',
        title: 'Medical Records',
        icon: 'bi bi-folder2-open',
        items: [
            { path: '/mrd', label: 'MRD Dashboard' },
            { path: '/birth-registrations', label: 'Birth Registrations' },
            { path: '/death-registrations', label: 'Death Registrations' }
        ]
    },
    {
        id: 'abha',
        title: 'ABHA Integration',
        icon: 'bi bi-shield-check',
        items: [
            { path: '/abha', label: 'ABHA Management' }
        ]
    },
    {
        id: 'config',
        title: 'Configuration',
        icon: 'bi bi-sliders',
        items: [
            { path: '/settings/opd-configuration', label: 'OPD Configuration' },
            { path: '/settings/opd-time-slots', label: 'OPD Time Slots' },
            { path: '/settings/rate-requests', label: 'Rate Requests' },
            { path: '/discharge-summary-custom-fields', label: 'Discharge Summary Fields' }
        ]
    },
    {
        id: 'system',
        title: 'System',
        icon: 'bi bi-gear',
        items: [
            { path: '/users', label: 'Users' },
            { path: '/roles', label: 'Roles & Permissions' },
            { path: '/notifications/settings', label: 'Notifications' },
            { path: '/settings', label: 'Settings' }
        ]
    }
];

const userInitials = computed(() => {
    const name = authStore.user?.full_name || 'User';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const pageTitle = computed(() => {
    const titles = {
        '/': 'Dashboard',
        '/doctor-workbench': 'Doctor Workbench',
        '/masters/reception/prefix': 'Prefix Master',
        '/masters/reception/gender': 'Gender Master',
        '/masters/reception/blood-group': 'Blood Group Master',
        '/masters/reception/patient-type': 'Patient Type Master',
        '/masters/reception/marital-status': 'Marital Status Master',
        '/masters/reception/reference-doctor': 'Reference Doctor Master',
        '/masters/reception/insurance-company': 'Insurance Company Master',
        '/masters/reception/qualification': 'Qualification Master',
        '/masters/reception/consult-master': 'Consult Master',
        '/masters/prescription': 'Prescription Master',
        '/masters/bed-allocation': 'Bed Allocation Master',
        '/masters/address/country': 'Country Master',
        '/masters/address/state': 'State Master',
        '/masters/address/district': 'District Master',
        '/masters/address/city': 'City/Taluka Master',
        '/masters/address/area': 'Area/Village Master',
        '/patients': 'Patients',
        '/doctors': 'Doctors',
        '/departments': 'Departments',
        '/appointments': 'Appointments',
        '/opd': 'OPD Visits',
        '/ipd': 'IPD Admissions',
        '/discharge-summary': 'Discharge Summaries',
        '/bed-transfers': 'Bed Transfer',
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
        '/discharge-summary-custom-fields': 'Discharge Summary Custom Fields',
        '/hospitals': 'Hospitals',
        '/admin/claude-chat': 'AI Assistant',
    };
    return titles[route.path] || 'HIMS';
});

const toggleMenu = (menuId) => {
    if (expandedMenus.value.includes(menuId)) {
        // Collapse if already expanded
        expandedMenus.value = [];
    } else {
        // Accordion style: only one open at a time
        expandedMenus.value = [menuId];
    }
};

const isSectionActive = (section) => {
    return section.items.some(item => route.path === item.path || route.path.startsWith(item.path + '/'));
};

const expandActiveMenus = () => {
    // Find the first active section and expand only that one (accordion style)
    const activeSection = menuSections.find(section => isSectionActive(section));
    if (activeSection) {
        expandedMenus.value = [activeSection.id];
    }
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

watch(route, () => {
    sidebarOpen.value = false;
    expandActiveMenus();
});

onMounted(() => {
    expandActiveMenus();
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
/* Component-specific styles */
.nav-link span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dropdown-toggle::after {
    display: none;
}

.user-profile {
    cursor: pointer;
}

.user-profile .bi-chevron-down {
    font-size: 12px;
    color: var(--gray-500);
    margin-left: 4px;
}

/* Submenu Toggle */
.has-submenu > .nav-link {
    position: relative;
}

.menu-toggle {
    cursor: pointer;
}

.menu-arrow {
    margin-left: auto;
    font-size: 12px;
    transition: transform 0.3s ease;
}

.menu-toggle.expanded .menu-arrow {
    transform: rotate(90deg);
}

.menu-toggle.has-active {
    color: rgba(255, 255, 255, 0.9) !important;
}

.menu-toggle.has-active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 20px;
    background: var(--primary);
    border-radius: 0 3px 3px 0;
}

/* Submenu Styling */
.submenu {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
    background: rgba(0, 0, 0, 0.15);
    border-radius: 0 0 8px 8px;
}

.submenu.show {
    max-height: 500px;
    transition: max-height 0.4s ease-in;
}

.submenu .nav-item {
    margin: 0;
}

.submenu .nav-link {
    padding: 10px 16px 10px 52px;
    font-size: 14px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.7);
    display: flex;
    align-items: center;
    transition: color 0.2s ease, background 0.2s ease;
    position: relative;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
    letter-spacing: 0.2px;
}

.submenu .nav-link::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.4);
    position: absolute;
    left: 32px;
    transition: background 0.2s ease;
}

.submenu .nav-link:hover {
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.05);
}

.submenu .nav-link:hover::before {
    background: rgba(255, 255, 255, 0.6);
}

.submenu .nav-link.active {
    color: #fff;
    background: var(--primary);
}

.submenu .nav-link.active::before {
    background: #fff;
}
</style>
