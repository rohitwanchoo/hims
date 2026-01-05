<template>
    <router-view />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from './stores/auth';

const authStore = useAuthStore();

onMounted(() => {
    authStore.initializeAuth();
});
</script>

<style>
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background-color: #f8f9fa;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.sidebar {
    min-height: calc(100vh - 56px);
}
</style>
