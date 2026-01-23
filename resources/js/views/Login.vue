<template>
    <div class="login-wrapper">
        <div class="login-card">
            <!-- Logo -->
            <div class="text-center mb-4">
                <div class="login-logo">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to HIMS Dashboard</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="handleLogin">
                <div class="mb-4">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-person"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            v-model="credentials.username"
                            :class="{ 'is-invalid': errors.username }"
                            placeholder="Enter your username"
                            required
                            autocomplete="username"
                        >
                    </div>
                    <div class="text-danger small mt-1" v-if="errors.username">
                        {{ errors.username }}
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Password <span class="text-danger">*</span></label>
                        <a href="#" class="text-primary small">Forgot Password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            v-model="credentials.password"
                            :class="{ 'is-invalid': errors.password }"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            class="input-group-text cursor-pointer"
                            @click="showPassword = !showPassword"
                        >
                            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                    </div>
                    <div class="text-danger small mt-1" v-if="errors.password">
                        {{ errors.password }}
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe" v-model="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                </div>

                <!-- Error Alert -->
                <div class="alert alert-danger d-flex align-items-center" v-if="error">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span>{{ error }}</span>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100 btn-lg" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    <span v-if="!loading"><i class="bi bi-box-arrow-in-right me-2"></i>Sign In</span>
                    <span v-else>Signing in...</span>
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-4 pt-4 border-top text-center">
                <p class="text-muted small mb-2">Demo Credentials</p>
                <div class="d-flex gap-2 justify-content-center">
                    <span class="badge badge-soft-primary">Username: admin</span>
                    <span class="badge badge-soft-primary">Password: admin123</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-4 text-center">
                <p class="text-muted small mb-0">
                    &copy; {{ currentYear }} HIMS. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const credentials = reactive({
    username: '',
    password: ''
});

const loading = ref(false);
const error = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);

const errors = reactive({
    username: '',
    password: ''
});

const currentYear = computed(() => new Date().getFullYear());

const handleLogin = async () => {
    loading.value = true;
    error.value = '';
    errors.username = '';
    errors.password = '';

    const result = await authStore.login(credentials);

    if (result.success) {
        router.push('/');
    } else {
        error.value = result.message;
    }

    loading.value = false;
};
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}

.input-group-text {
    border-left: none;
}

.input-group .form-control {
    border-right: none;
}

.input-group .form-control:focus {
    border-color: var(--border-color);
}

.input-group:focus-within .input-group-text,
.input-group:focus-within .form-control {
    border-color: var(--primary);
}

.btn-lg {
    padding: 14px 28px;
    font-size: 15px;
    font-weight: 600;
}
</style>
