import axios, { AxiosResponse, AxiosError, InternalAxiosRequestConfig } from 'axios';
import { toast } from 'sonner';

const TOKEN_KEY = 'worldos_token';

const api = axios.create({
    baseURL: process.env.NEXT_PUBLIC_API_URL || '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor: attach Bearer token from localStorage on every request.
// This replaces the fragile manual api.defaults.headers.common pattern.
api.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
        if (typeof window !== 'undefined') {
            const token = localStorage.getItem(TOKEN_KEY);
            if (token && config.headers) {
                config.headers.set('Authorization', `Bearer ${token}`);
            }
        }
        return config;
    },
    (error) => Promise.reject(error),
);

// Auto-unwrap Laravel resource wrapper { data: <payload> } so hooks
// receive the payload directly via res.data without double-nesting.
api.interceptors.response.use(
    (response: AxiosResponse) => {
        const body = response.data;
        if (
            body &&
            typeof body === 'object' &&
            !Array.isArray(body) &&
            'data' in body
        ) {
            const keys = Object.keys(body).filter(
                (k) => k !== 'meta' && k !== 'links',
            );
            if (keys.length === 1 && keys[0] === 'data') {
                response.data = body.data;
            }
        }
        return response;
    },
    (error: AxiosError<{ message?: string }>) => {
        const message =
            error.response?.data?.message || 'Đã xảy ra lỗi kết nối.';
        toast.error(message);
        return Promise.reject(error);
    },
);

export default api;
