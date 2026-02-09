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
                    <li class="nav-item" v-if="isSuperAdmin() || can('dashboard.view')">
                        <router-link class="nav-link" to="/" exact-active-class="active">
                            <i class="bi bi-grid-1x2"></i>
                            <span>Dashboard</span>
                        </router-link>
                    </li>

                    <!-- Doctor Workbench (No submenu) -->
                    <li class="nav-item" v-if="isSuperAdmin() || can('doctor_workbench.view')">
                        <router-link class="nav-link" to="/doctor-workbench" active-class="active">
                            <i class="bi bi-clipboard2-pulse"></i>
                            <span>Doctor Workbench</span>
                        </router-link>
                    </li>

                    <!-- Menu Sections with Submenus -->
                    <li class="nav-item has-submenu" v-for="section in visibleMenuSections" :key="section.id">
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
                            <li class="nav-item" v-for="item in section.items" :key="item.path || item.id">
                                <!-- Nested submenu item -->
                                <a v-if="item.items"
                                    class="nav-link menu-toggle"
                                    href="#"
                                    @click.prevent="toggleMenu(item.id)"
                                    :class="{ 'expanded': expandedMenus.includes(item.id) }">
                                    <span>{{ item.label }}</span>
                                    <i class="bi bi-chevron-right menu-arrow"></i>
                                </a>
                                <ul v-if="item.items" class="submenu nested-submenu" :class="{ 'show': expandedMenus.includes(item.id) }">
                                    <li class="nav-item" v-for="subItem in item.items" :key="subItem.path">
                                        <router-link class="nav-link" :to="subItem.path" active-class="active">
                                            <span>{{ subItem.label }}</span>
                                        </router-link>
                                    </li>
                                </ul>
                                <!-- Regular menu item -->
                                <router-link v-else class="nav-link" :to="item.path" active-class="active">
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
                                <router-link class="nav-link" to="/subscription-plans" active-class="active">
                                    <span>Subscription Plans</span>
                                </router-link>
                            </li>
                            <li class="nav-item">
                                <router-link class="nav-link" to="/hospitals" active-class="active">
                                    <span>Hospitals</span>
                                </router-link>
                            </li>
                            <!-- Temporarily hidden AI Assistant -->
                            <!-- <li class="nav-item">
                                <router-link class="nav-link" to="/admin/claude-chat" active-class="active">
                                    <span>AI Assistant</span>
                                </router-link>
                            </li> -->
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
import { usePermissions } from '../../composables/usePermissions';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { can, canAny, canAccessModule, isSuperAdmin } = usePermissions();
const sidebarOpen = ref(false);
const expandedMenus = ref([]);

// Menu structure with permissions
const menuSections = [
    {
        id: 'patients',
        title: 'Patient Management',
        icon: 'bi bi-people',
        module: 'patient',
        items: [
            { path: '/patients', label: 'Patients', permission: 'patient.view' },
            { path: '/appointments', label: 'Appointments', permission: 'appointment.view' },
            { path: '/calendar', label: 'Calendar', permission: 'calendar.view' }
        ]
    },
    {
        id: 'clinical',
        title: 'Clinical',
        icon: 'bi bi-clipboard2-pulse',
        items: [
            { path: '/opd', label: 'OPD Visits', permission: 'opd.view' },
            { path: '/ipd', label: 'IPD Admissions', permission: 'ipd.view' },
            { path: '/discharge-summary', label: 'Discharge Summary', permission: 'discharge_summary.view' }
        ]
    },
    {
        id: 'billing',
        title: 'Billing',
        icon: 'bi bi-receipt',
        module: 'billing',
        items: [
            { path: '/billing', label: 'Bills', permission: 'billing.view' },
            { path: '/payments', label: 'Payments', permission: 'payment.view' }
        ]
    },
    /* Temporarily hidden sections
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
    */
    {
        id: 'reports',
        title: 'Reports',
        icon: 'bi bi-bar-chart-line',
        module: 'reports',
        items: [
            { path: '/reports', label: 'Reports', permission: 'reports.view' }
        ]
    },
    /*
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
    */
    {
        id: 'abha',
        title: 'ABHA Integration',
        icon: 'bi bi-shield-check',
        module: 'abha',
        items: [
            { path: '/abha', label: 'ABHA Management', permission: 'abha.link_patient' }
        ]
    },
    {
        id: 'config',
        title: 'Configuration',
        icon: 'bi bi-sliders',
        module: 'settings',
        items: [
            { path: '/settings/opd-configuration', label: 'OPD Configuration', permission: 'settings.manage' },
            { path: '/settings/opd-time-slots', label: 'OPD Time Slots', permission: 'settings.manage' },
            { path: '/settings/rate-requests', label: 'Rate Requests', permission: 'settings.manage' },
            { path: '/discharge-summary-custom-fields', label: 'Discharge Summary Fields', permission: 'settings.manage' },
            { path: '/bed-transfers', label: 'Bed Transfer', permission: 'ipd.view' },
            { path: '/consultation-forms', label: 'Consultation Forms', permission: 'settings.manage' }
        ]
    },
    {
        id: 'masters',
        title: 'Masters',
        icon: 'bi bi-database',
        module: 'masters',
        items: [
            {
                id: 'common-master',
                label: 'Common Master',
                items: [
                    { path: '/doctors', label: 'Doctors', permission: 'doctor.view' },
                    { path: '/departments', label: 'Departments', permission: 'department.view' },
                    { path: '/masters/reception/prefix', label: 'Prefix', permission: 'prefix.view' },
                    { path: '/masters/reception/gender', label: 'Gender', permission: 'gender.view' },
                    { path: '/masters/reception/age-group', label: 'Age Group', permission: 'age_group.view' },
                    { path: '/masters/reception/blood-group', label: 'Blood Group', permission: 'blood_group.view' },
                    { path: '/masters/reception/patient-type', label: 'Patient Type', permission: 'patient_type.view' },
                    { path: '/masters/reception/marital-status', label: 'Marital Status', permission: 'marital_status.view' },
                    { path: '/masters/reception/reference-doctor', label: 'Reference Master', permission: 'reference_doctor.view' },
                    { path: '/masters/reception/insurance-company', label: 'Insurance Company', permission: 'insurance_company.view' },
                    { path: '/masters/reception/qualification', label: 'Qualification', permission: 'qualification.view' },
                    { path: '/masters/reception/consult-master', label: 'Consult Master', permission: 'consult_master.view' },
                    { path: '/masters/prescription', label: 'Prescription Master', permission: 'prescription_master.view' },
                    { path: '/masters/bed-allocation', label: 'Bed Allocation', permission: 'bed_allocation.view' },
                    { path: '/masters/hospital-services', label: 'Hospital Services', permission: 'hospital_services.view' },
                    { path: '/masters/gst-plan', label: 'GST Plan Master', permission: 'gst_plan.view' }
                ]
            },
            {
                id: 'address-masters',
                label: 'Address Masters',
                items: [
                    { path: '/masters/address/country', label: 'Country', permission: 'country.view' },
                    { path: '/masters/address/state', label: 'State', permission: 'state.view' },
                    { path: '/masters/address/district', label: 'District', permission: 'district.view' },
                    { path: '/masters/address/city', label: 'City/Taluka', permission: 'city.view' },
                    { path: '/masters/address/area', label: 'Area/Village', permission: 'area.view' }
                ]
            },
            {
                id: 'pathology-masters',
                label: 'Pathology Masters',
                items: [
                    { path: '/masters/pathology/analyzer', label: 'Analyzer Master', permission: 'analyzer.view' },
                    { path: '/masters/pathology/external-lab', label: 'External Lab Center', permission: 'external_lab.view' },
                    { path: '/masters/pathology/test-method', label: 'Test Method Master', permission: 'test_method.view' },
                    { path: '/masters/pathology/test-unit', label: 'Test Unit Master', permission: 'test_unit.view' },
                    { path: '/masters/pathology/container', label: 'Container Master', permission: 'container.view' },
                    { path: '/masters/pathology/faculty', label: 'Faculty Master', permission: 'faculty.view' },
                    { path: '/masters/pathology/sample-type', label: 'Sample Type', permission: 'sample_type.view' },
                    { path: '/masters/pathology/race', label: 'Race Master', permission: 'race.view' },
                    { path: '/masters/pathology/sensitivity', label: 'Sensitivity Master', permission: 'sensitivity.view' },
                    { path: '/masters/pathology/pathologist-map', label: 'Pathologist Doctor Map', permission: 'pathologist_map.view' },
                    { path: '/masters/pathology/test', label: 'Test Master', permission: 'test.view' },
                    { path: '/masters/pathology/test-note', label: 'Test Note Master', permission: 'test_note.view' },
                    { path: '/masters/pathology/test-category', label: 'Test Category', permission: 'test_category.view' },
                    { path: '/masters/pathology/test-group', label: 'Test Group', permission: 'test_group.view' },
                    { path: '/masters/pathology/skill-test-map', label: 'Skill Test Map', permission: 'skill_test_map.view' },
                    { path: '/masters/pathology/test-report', label: 'Test Report Master', permission: 'test_report.view' },
                    { path: '/masters/pathology/instruction', label: 'Instruction Master', permission: 'instruction.view' }
                ]
            }
        ]
    },
    {
        id: 'system',
        title: 'System',
        icon: 'bi bi-gear',
        module: 'admin',
        items: [
            { path: '/users', label: 'Users', permission: 'admin.manage_users' },
            { path: '/roles', label: 'Roles & Permissions', permission: 'admin.manage_roles' },
            { path: '/notifications/settings', label: 'Notifications', permission: 'admin.manage_settings' },
            { path: '/settings', label: 'Settings', permission: 'admin.manage_settings' }
        ]
    }
];

// Filter menu sections based on permissions
const visibleMenuSections = computed(() => {
    if (isSuperAdmin()) {
        return menuSections;
    }

    return menuSections.filter(section => {
        // If section has a module, check if user can access it
        if (section.module && !canAccessModule(section.module)) {
            return false;
        }

        // Filter items within the section
        // Pass flag indicating if section has module (to skip sub-module checks)
        const visibleItems = filterMenuItems(section.items, !!section.module);

        // Only show section if it has visible items
        return visibleItems.length > 0;
    }).map(section => ({
        ...section,
        items: filterMenuItems(section.items, !!section.module)
    }));
});

// Recursively filter menu items based on permissions
// skipModuleCheck: if true, only check permissions (parent section already checked module)
function filterMenuItems(items, skipModuleCheck = false) {
    if (!items) return [];

    return items.filter(item => {
        // If item has nested items, filter them recursively
        if (item.items) {
            const visibleSubItems = filterMenuItems(item.items, skipModuleCheck);
            return visibleSubItems.length > 0;
        }

        // If item has permission requirement, check it
        if (item.permission) {
            // Only check module if parent section didn't have module check
            if (!skipModuleCheck) {
                // Extract module name from permission (e.g., "ipd.view" -> "ipd")
                const moduleName = item.permission.split('.')[0];

                // Check if module is enabled in subscription plan
                if (!canAccessModule(moduleName)) {
                    return false; // Module not enabled in subscription
                }
            }

            // Check user permission
            return can(item.permission);
        }

        // Show items without permission requirements
        return true;
    }).map(item => {
        // If item has nested items, return filtered version
        if (item.items) {
            return {
                ...item,
                items: filterMenuItems(item.items, skipModuleCheck)
            };
        }
        return item;
    });
}

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
        '/masters/reception/age-group': 'Age Group Master',
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
        '/masters/pathology/analyzer': 'Analyzer Master',
        '/masters/pathology/external-lab': 'External Lab Center',
        '/masters/pathology/test-method': 'Test Method Master',
        '/masters/pathology/test-unit': 'Test Unit Master',
        '/masters/pathology/container': 'Container Master',
        '/masters/pathology/faculty': 'Faculty Master',
        '/masters/pathology/sample-type': 'Sample Type Master',
        '/masters/pathology/race': 'Race Master',
        '/masters/pathology/sensitivity': 'Sensitivity Master',
        '/masters/pathology/pathologist-map': 'Pathologist Doctor Map',
        '/masters/pathology/test': 'Test Master',
        '/masters/pathology/test-note': 'Test Note Master',
        '/masters/pathology/test-category': 'Test Category Master',
        '/masters/pathology/test-group': 'Test Group Master',
        '/masters/pathology/skill-test-map': 'Skill Test Map',
        '/masters/pathology/test-report': 'Test Report Master',
        '/masters/pathology/instruction': 'Instruction Master',
        '/patients': 'Patients',
        '/doctors': 'Doctors',
        '/departments': 'Departments',
        '/appointments': 'Appointments',
        '/opd': 'OPD Visits',
        '/ipd': 'IPD Admissions',
        '/discharge-summary': 'Discharge Summary',
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
        // '/admin/claude-chat': 'AI Assistant', // Temporarily hidden
    };
    return titles[route.path] || 'HIMS';
});

const toggleMenu = (menuId) => {
    const index = expandedMenus.value.indexOf(menuId);

    if (index > -1) {
        // Collapse this menu and its children
        expandedMenus.value.splice(index, 1);
    } else {
        // Check if this is a nested menu (contains a dash like 'common-master')
        const isNestedMenu = menuId.includes('-');

        if (isNestedMenu) {
            // For nested menus, just add to the array (keep parent open)
            expandedMenus.value.push(menuId);
        } else {
            // For top-level sections, use accordion style (close others)
            const otherTopLevel = expandedMenus.value.filter(id => id.includes('-'));
            expandedMenus.value = [menuId, ...otherTopLevel];
        }
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
    max-height: 1000px;
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

/* Nested submenu styling */
.nested-submenu {
    padding-left: 0;
    list-style: none;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.nested-submenu.show {
    max-height: 2000px;
    transition: max-height 0.4s ease-in;
}

.nested-submenu .nav-link {
    padding-left: 68px;
    font-size: 13px;
}

.submenu .menu-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px 10px 52px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.2s ease;
}

.submenu .menu-toggle:hover {
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.05);
}

.submenu .menu-toggle .menu-arrow {
    font-size: 12px;
    transition: transform 0.3s ease;
}

.submenu .menu-toggle.expanded .menu-arrow {
    transform: rotate(90deg);
}
</style>
