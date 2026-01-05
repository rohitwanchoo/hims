<template>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="text-center mb-4">
                <div class="login-logo">
                    <i class="bi bi-heart-pulse"></i>
                </div>
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to HIMS Dashboard</p>
            </div>

            <form @submit.prevent="handleLogin">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input
                            type="text"
                            class="form-control"
                            v-model="credentials.username"
                            :class="{ 'is-invalid': errors.username }"
                            placeholder="Enter your username"
                            required
                        >
                    </div>
                    <div class="text-danger small mt-1" v-if="errors.username">
                        {{ errors.username }}
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input
                            type="password"
                            class="form-control"
                            v-model="credentials.password"
                            :class="{ 'is-invalid': errors.password }"
                            placeholder="Enter your password"
                            required
                        >
                    </div>
                    <div class="text-danger small mt-1" v-if="errors.password">
                        {{ errors.password }}
                    </div>
                </div>

                <div class="alert alert-danger d-flex align-items-center" v-if="error">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ error }}
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                    <span v-if="!loading">Sign In</span>
                    <span v-else>Signing in...</span>
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center">
                <small class="text-muted">
                    Demo: <strong>admin</strong> / <strong>admin123</strong>
                </small>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
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
const errors = reactive({
    username: '',
    password: ''
});

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
.login-logo {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #3699ff 0%, #1e86ff 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.login-logo i {
    font-size: 2rem;
    color: #fff;
}

.login-card .input-group-text {
    background: #f3f6f9;
    border-right: none;
}

.login-card .form-control {
    border-left: none;
}

.login-card .form-control:focus {
    box-shadow: none;
    border-color: #ebedf3;
}

.login-card .input-group:focus-within .input-group-text,
.login-card .input-group:focus-within .form-control {
    border-color: #3699ff;
}
</style>
