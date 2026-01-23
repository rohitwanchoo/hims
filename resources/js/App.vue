<template>
    <router-view />
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';
import axios from 'axios';

const router = useRouter();
const authStore = useAuthStore();

onMounted(() => {
    authStore.initializeAuth();

    // Setup axios 401 interceptor with router access
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response?.status === 401) {
                // Clear auth state
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('hospital');
                localStorage.removeItem('hospitals');
                localStorage.removeItem('currentHospitalId');

                // Update store
                authStore.token = null;
                authStore.user = null;
                authStore.hospital = null;
                authStore.hospitals = [];

                // Use Vue Router for navigation (SPA-friendly)
                router.push('/login');
            }
            return Promise.reject(error);
        }
    );
});
</script>

<style>
/* Global styles are now handled by dreams-emr.css */
/* Only add truly global overrides here if needed */

/* Inter font import */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
</style>
