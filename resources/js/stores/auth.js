import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
        hospital: JSON.parse(localStorage.getItem('hospital')) || null,
        hospitals: JSON.parse(localStorage.getItem('hospitals')) || [],
        currentHospitalId: localStorage.getItem('currentHospitalId') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        currentUser: (state) => state.user,
        userRole: (state) => state.user?.role || null,
        isSuperAdmin: (state) => state.user?.is_super_admin === true,
        currentHospital: (state) => state.hospital,
        availableHospitals: (state) => state.hospitals,
    },

    actions: {
        async login(credentials) {
            try {
                const response = await axios.post('/api/login', credentials);
                this.token = response.data.token;
                this.user = response.data.user;
                this.hospital = response.data.hospital || null;
                this.hospitals = response.data.hospitals || [];

                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
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
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('hospital');
                localStorage.removeItem('hospitals');
                localStorage.removeItem('currentHospitalId');
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
                localStorage.setItem('user', JSON.stringify(this.user));
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
