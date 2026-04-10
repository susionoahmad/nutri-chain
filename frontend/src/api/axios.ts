import axios from 'axios';

let baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
// Auto-append /api jika user lupa memasukkannya di environment variable
if (baseUrl && !baseUrl.endsWith('/api')) {
    // Remove trailing slash if exists before appending /api
    baseUrl = baseUrl.replace(/\/$/, '') + '/api';
}

const api = axios.create({
    baseURL: baseUrl,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }

        if (error.response?.status === 403 && error.response?.data?.code === 'SUBSCRIPTION_EXPIRED') {
            // Redirect to billing page if not already there
            if (!window.location.pathname.startsWith('/billing')) {
                window.location.href = '/billing';
            }
        }
        
        return Promise.reject(error.response?.data?.message || 'Something went wrong');
    }
);

export default api;