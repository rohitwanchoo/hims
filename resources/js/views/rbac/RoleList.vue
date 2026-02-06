<template>
    <div>
        <div class="d-flex justify-content-between mb-4">
            <h4><i class="bi bi-shield-lock me-2"></i>Roles & Permissions</h4>
            <button class="btn btn-primary" @click="showRoleForm = true" :disabled="loading">
                <i class="bi bi-plus-lg"></i> Create Role
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading roles...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ error }}
            <button @click="loadRoles" class="btn btn-sm btn-outline-danger ms-2">
                <i class="bi bi-arrow-clockwise"></i> Retry
            </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="roles.length === 0" class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No roles found. Click "Create Role" to add a new role.
        </div>

        <!-- Roles Grid -->
        <div v-else class="row">
            <div v-for="role in roles" :key="role.role_id" class="col-md-4 mb-4">
                <div class="card h-100" :class="{ 'border-primary': role.is_system_role }">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ role.role_name }}</strong>
                            <span v-if="role.is_system_role" class="badge bg-primary ms-2">System</span>
                        </div>
                        <div v-if="!role.is_system_role" class="dropdown">
                            <button class="btn btn-sm btn-link" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" @click="editRole(role)">Edit</a></li>
                                <li><a class="dropdown-item text-danger" href="#" @click="deleteRole(role)">Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">{{ role.description || 'No description' }}</p>
                        <div class="mb-3">
                            <strong class="small">Permissions:</strong>
                            <span class="badge bg-secondary ms-2">{{ role.permissions?.length || 0 }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <span v-for="perm in role.permissions?.slice(0, 5)" :key="perm.permission_id"
                                class="badge bg-light text-dark small">
                                {{ perm.permission_code }}
                            </span>
                            <span v-if="role.permissions?.length > 5" class="badge bg-light text-muted">
                                +{{ role.permissions.length - 5 }} more
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <button @click="managePermissions(role)" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-key me-1"></i>Manage Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Form Modal -->
        <div v-if="showRoleForm" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ editingRole ? 'Edit Role' : 'Create Role' }}</h5>
                        <button type="button" class="btn-close" @click="closeRoleForm"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role Code</label>
                            <input type="text" v-model="roleForm.role_code" class="form-control" :disabled="editingRole">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" v-model="roleForm.role_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea v-model="roleForm.description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeRoleForm">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="saveRole">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Modal -->
        <div v-if="showPermissionsModal" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Permissions - {{ selectedRole?.role_name }}</h5>
                        <button type="button" class="btn-close" @click="showPermissionsModal = false"></button>
                    </div>
                    <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
                        <!-- Section-based Permission Organization -->
                        <div v-for="section in permissionSections" :key="section.name" class="mb-4">
                            <!-- Section Header -->
                            <div class="d-flex align-items-center mb-3">
                                <i :class="section.icon" class="me-2 text-primary"></i>
                                <h5 class="mb-0">{{ section.name }}</h5>
                                <button
                                    @click="toggleSection(section.name, true)"
                                    class="btn btn-sm btn-link ms-auto text-decoration-none"
                                    type="button">
                                    Select All
                                </button>
                                <button
                                    @click="toggleSection(section.name, false)"
                                    class="btn btn-sm btn-link text-decoration-none"
                                    type="button">
                                    Deselect All
                                </button>
                            </div>

                            <!-- Modules within Section -->
                            <div v-for="module in section.modules" :key="module.name" class="ms-3 mb-3">
                                <h6 class="text-uppercase text-muted border-bottom pb-2 mb-2">
                                    <i class="bi bi-circle-fill me-2" style="font-size: 8px;"></i>
                                    {{ module.name }}
                                </h6>
                                <div class="row">
                                    <div v-for="perm in module.permissions" :key="perm.permission_id" class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input"
                                                :id="`perm-${perm.permission_id}`"
                                                :value="perm.permission_id"
                                                v-model="selectedPermissions">
                                            <label class="form-check-label" :for="`perm-${perm.permission_id}`">
                                                {{ perm.permission_name }}
                                                <br><small class="text-muted">{{ perm.permission_code }}</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showPermissionsModal = false">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="savePermissions">Save Permissions</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const roles = ref([]);
const allPermissions = ref({});
const loading = ref(false);
const error = ref(null);
const showRoleForm = ref(false);
const showPermissionsModal = ref(false);
const editingRole = ref(null);
const selectedRole = ref(null);
const selectedPermissions = ref([]);
const roleForm = ref({
    role_code: '',
    role_name: '',
    description: ''
});

// Define sidebar sections with their modules
const sectionDefinitions = [
    {
        name: 'Patient Management',
        icon: 'bi bi-people',
        modules: ['patient', 'appointment', 'calendar']
    },
    {
        name: 'Clinical',
        icon: 'bi bi-clipboard2-pulse',
        modules: ['opd', 'ipd', 'discharge_summary']
    },
    {
        name: 'Billing',
        icon: 'bi bi-receipt',
        modules: ['billing', 'payment']
    },
    {
        name: 'Laboratory',
        icon: 'bi bi-droplet',
        modules: ['lab']
    },
    {
        name: 'Radiology',
        icon: 'bi bi-x-ray',
        modules: ['radiology']
    },
    {
        name: 'Operation Theater',
        icon: 'bi bi-heart-pulse',
        modules: ['ot', 'surgery']
    },
    {
        name: 'Pharmacy',
        icon: 'bi bi-capsule',
        modules: ['pharmacy', 'drug']
    },
    {
        name: 'Inventory',
        icon: 'bi bi-box-seam',
        modules: ['inventory', 'indent', 'purchase_order', 'supplier', 'store']
    },
    {
        name: 'Medical Records',
        icon: 'bi bi-folder2-open',
        modules: ['mrd', 'birth', 'death']
    },
    {
        name: 'Reports',
        icon: 'bi bi-bar-chart-line',
        modules: ['reports']
    },
    {
        name: 'ABHA Integration',
        icon: 'bi bi-shield-check',
        modules: ['abha']
    },
    {
        name: 'Configuration',
        icon: 'bi bi-sliders',
        modules: ['settings', 'consultation_form']
    },
    {
        name: 'Masters',
        icon: 'bi bi-database',
        modules: ['masters', 'doctor', 'department', 'ward', 'room', 'bed', 'service', 'package']
    },
    {
        name: 'System',
        icon: 'bi bi-gear',
        modules: ['admin', 'user', 'role', 'notification']
    }
];

// Organize permissions by sections
const permissionSections = computed(() => {
    if (!allPermissions.value || Object.keys(allPermissions.value).length === 0) {
        return [];
    }

    const sections = [];

    sectionDefinitions.forEach(sectionDef => {
        const section = {
            name: sectionDef.name,
            icon: sectionDef.icon,
            modules: []
        };

        sectionDef.modules.forEach(moduleName => {
            const permissions = allPermissions.value[moduleName] || [];
            if (permissions.length > 0) {
                section.modules.push({
                    name: formatModuleName(moduleName),
                    permissions: permissions
                });
            }
        });

        // Only add section if it has modules with permissions
        if (section.modules.length > 0) {
            sections.push(section);
        }
    });

    return sections;
});

const formatModuleName = (module) => {
    // Convert module code to display name
    const names = {
        'patient': 'Patients',
        'appointment': 'Appointments',
        'calendar': 'Calendar',
        'opd': 'OPD Visits',
        'ipd': 'IPD Admissions',
        'discharge_summary': 'Discharge Summary',
        'billing': 'Billing',
        'payment': 'Payments',
        'lab': 'Laboratory',
        'radiology': 'Radiology',
        'ot': 'Operation Theater',
        'surgery': 'Surgery Types',
        'pharmacy': 'Pharmacy',
        'drug': 'Drug Management',
        'inventory': 'Inventory',
        'indent': 'Indents',
        'purchase_order': 'Purchase Orders',
        'supplier': 'Suppliers',
        'store': 'Stores',
        'mrd': 'Medical Records',
        'birth': 'Birth Registration',
        'death': 'Death Registration',
        'reports': 'Reports',
        'abha': 'ABHA',
        'settings': 'Settings',
        'consultation_form': 'Consultation Forms',
        'masters': 'Master Data',
        'doctor': 'Doctors',
        'department': 'Departments',
        'ward': 'Wards',
        'room': 'Rooms',
        'bed': 'Beds',
        'service': 'Services',
        'package': 'Health Packages',
        'admin': 'Administration',
        'user': 'User Management',
        'role': 'Role Management',
        'notification': 'Notifications'
    };
    return names[module] || module.charAt(0).toUpperCase() + module.slice(1).replace(/_/g, ' ');
};

const toggleSection = (sectionName, selectAll) => {
    const section = permissionSections.value.find(s => s.name === sectionName);
    if (!section) return;

    section.modules.forEach(module => {
        module.permissions.forEach(perm => {
            const index = selectedPermissions.value.indexOf(perm.permission_id);
            if (selectAll && index === -1) {
                selectedPermissions.value.push(perm.permission_id);
            } else if (!selectAll && index > -1) {
                selectedPermissions.value.splice(index, 1);
            }
        });
    });
};

const groupedPermissions = computed(() => allPermissions.value);

const loadRoles = async () => {
    loading.value = true;
    error.value = null;
    try {
        const response = await axios.get('/api/roles');
        roles.value = response.data.roles || [];
        console.log('Loaded roles:', roles.value.length);
    } catch (err) {
        console.error('Failed to load roles:', err);
        error.value = err.response?.data?.message || 'Failed to load roles. Please check your permissions.';
    } finally {
        loading.value = false;
    }
};

const loadPermissions = async () => {
    try {
        const response = await axios.get('/api/roles/permissions');
        allPermissions.value = response.data.permissions || {};
    } catch (error) {
        console.error('Failed to load permissions:', error);
    }
};

const editRole = (role) => {
    editingRole.value = role;
    roleForm.value = { ...role };
    showRoleForm.value = true;
};

const closeRoleForm = () => {
    showRoleForm.value = false;
    editingRole.value = null;
    roleForm.value = { role_code: '', role_name: '', description: '' };
};

const saveRole = async () => {
    try {
        if (editingRole.value) {
            await axios.put(`/api/roles/${editingRole.value.role_id}`, roleForm.value);
        } else {
            await axios.post('/api/roles', roleForm.value);
        }
        closeRoleForm();
        loadRoles();
    } catch (error) {
        console.error('Failed to save role:', error);
    }
};

const deleteRole = async (role) => {
    if (confirm(`Delete role "${role.role_name}"?`)) {
        try {
            await axios.delete(`/api/roles/${role.role_id}`);
            loadRoles();
        } catch (error) {
            console.error('Failed to delete role:', error);
        }
    }
};

const managePermissions = (role) => {
    selectedRole.value = role;
    selectedPermissions.value = role.permissions?.map(p => p.permission_id) || [];
    showPermissionsModal.value = true;
};

const savePermissions = async () => {
    try {
        await axios.put(`/api/roles/${selectedRole.value.role_id}`, {
            permissions: selectedPermissions.value
        });
        showPermissionsModal.value = false;
        loadRoles();
    } catch (error) {
        console.error('Failed to save permissions:', error);
    }
};

onMounted(() => {
    loadRoles();
    loadPermissions();
});
</script>
