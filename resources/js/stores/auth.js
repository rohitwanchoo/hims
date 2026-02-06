import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
        hospital: JSON.parse(localStorage.getItem('hospital')) || null,
        hospitals: JSON.parse(localStorage.getItem('hospitals')) || [],
        currentHospitalId: localStorage.getItem('currentHospitalId') || null,
        permissions: JSON.parse(localStorage.getItem('permissions')) || [],
        roles: JSON.parse(localStorage.getItem('roles')) || [],
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        currentUser: (state) => state.user,
        userRole: (state) => state.user?.role || null,
        isSuperAdmin: (state) => state.user?.is_super_admin === true,
        currentHospital: (state) => state.hospital,
        availableHospitals: (state) => state.hospitals,

        // Permission getters
        userPermissions: (state) => state.permissions,
        userRoles: (state) => state.roles,

        hasPermission: (state) => (permission) => {
            if (state.user?.is_super_admin) return true;
            return state.permissions.includes(permission);
        },

        hasAnyPermission: (state) => (permissions) => {
            if (state.user?.is_super_admin) return true;
            if (!Array.isArray(permissions)) return false;
            return permissions.some(permission => state.permissions.includes(permission));
        },

        hasAllPermissions: (state) => (permissions) => {
            if (state.user?.is_super_admin) return true;
            if (!Array.isArray(permissions)) return false;
            return permissions.every(permission => state.permissions.includes(permission));
        },

        hasRole: (state) => (role) => {
            if (state.user?.is_super_admin) return true;
            return state.roles.includes(role);
        },

        canAccessModule: (state) => (module) => {
            if (state.user?.is_super_admin) return true;
            // Check if user has any permission for this module
            return state.permissions.some(permission => permission.startsWith(`${module}.`));
        },
    },

    actions: {
        async login(credentials) {
            try {
                const response = await axios.post('/api/login', credentials);
                this.token = response.data.token;
                this.user = response.data.user;
                this.hospital = response.data.hospital || null;
                this.hospitals = response.data.hospitals || [];
                this.permissions = response.data.permissions || [];
                this.roles = response.data.roles || [];

                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                localStorage.setItem('permissions', JSON.stringify(this.permissions));
                localStorage.setItem('roles', JSON.stringify(this.roles));
                if (this.hospital) {
                    localStorage.setItem('hospital', JSON.stringify(this.hospital));
                }
                if (this.hospitals.length > 0) {
                    localStorage.setItem('hospitals', JSON.stringify(this.hospitals));
                }

                // Set default axios header
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;

                // Set hospital header if available
                if (this.hospital?.hospital_id) {
                    this.currentHospitalId = this.hospital.hospital_id;
                    localStorage.setItem('currentHospitalId', this.currentHospitalId);
                    axios.defaults.headers.common['X-Hospital-Id'] = this.currentHospitalId;
                }

                return { success: true };
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.message || 'Login failed'
                };
            }
        },

        async logout() {
            try {
                await axios.post('/api/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.token = null;
                this.user = null;
                this.hospital = null;
                this.hospitals = [];
                this.currentHospitalId = null;
                this.permissions = [];
                this.roles = [];
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('hospital');
                localStorage.removeItem('hospitals');
                localStorage.removeItem('currentHospitalId');
                localStorage.removeItem('permissions');
                localStorage.removeItem('roles');
                delete axios.defaults.headers.common['Authorization'];
                delete axios.defaults.headers.common['X-Hospital-Id'];
            }
        },

        async fetchUser() {
            try {
                const response = await axios.get('/api/user');
                this.user = response.data;
                this.hospital = response.data.hospital || null;
                this.hospitals = response.data.hospitals || [];
                this.permissions = response.data.permissions || [];
                this.roles = response.data.roles || [];
                localStorage.setItem('user', JSON.stringify(this.user));
                localStorage.setItem('permissions', JSON.stringify(this.permissions));
                localStorage.setItem('roles', JSON.stringify(this.roles));
                if (this.hospital) {
                    localStorage.setItem('hospital', JSON.stringify(this.hospital));
                }
                if (this.hospitals.length > 0) {
                    localStorage.setItem('hospitals', JSON.stringify(this.hospitals));
                }
            } catch (error) {
                this.logout();
            }
        },

        async switchHospital(hospital) {
            try {
                await axios.post('/api/switch-hospital', { hospital_id: hospital.hospital_id });
                this.hospital = hospital;
                this.currentHospitalId = hospital.hospital_id;
                localStorage.setItem('hospital', JSON.stringify(hospital));
                localStorage.setItem('currentHospitalId', hospital.hospital_id);
                axios.defaults.headers.common['X-Hospital-Id'] = hospital.hospital_id;
                return { success: true };
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.message || 'Failed to switch hospital'
                };
            }
        },

        setCurrentHospital(hospital) {
            this.hospital = hospital;
            this.currentHospitalId = hospital.hospital_id;
            localStorage.setItem('hospital', JSON.stringify(hospital));
            localStorage.setItem('currentHospitalId', hospital.hospital_id);
            axios.defaults.headers.common['X-Hospital-Id'] = hospital.hospital_id;
        },

        clearHospitalContext() {
            this.hospital = null;
            this.currentHospitalId = null;
            localStorage.removeItem('hospital');
            localStorage.removeItem('currentHospitalId');
            delete axios.defaults.headers.common['X-Hospital-Id'];
        },

        initializeAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            }
            if (this.currentHospitalId) {
                axios.defaults.headers.common['X-Hospital-Id'] = this.currentHospitalId;
            }
        }
    }
});
