import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

// Add auth token to all requests
axios.interceptors.request.use(
    config => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    error => Promise.reject(error)
);

// Handle 401 responses
// Note: Router instance not available here, will be handled in App.vue
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            // Don't auto-redirect here - let the application handle it
            // This prevents page flashing issues
            console.error('Unauthorized request:', error.config?.url);
        }
        return Promise.reject(error);
    }
);
