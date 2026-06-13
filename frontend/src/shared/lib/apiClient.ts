import axios, { AxiosResponse, AxiosError } from 'axios';
import { toast } from 'sonner';

export const TOKEN_KEY = 'worldos_token';

export function unwrapEnvelope(body: unknown): unknown {
  if (body && typeof body === 'object' && !Array.isArray(body) && 'data' in body) {
    const keys = Object.keys(body as object);
    if (keys.length === 1 && keys[0] === 'data') return (body as { data: unknown }).data;
  }
  return body;
}

export const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || '/api',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

apiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token && config.headers) config.headers.set('Authorization', `Bearer ${token}`);
  }
  return config;
});

apiClient.interceptors.response.use(
  (res: AxiosResponse) => { res.data = unwrapEnvelope(res.data); return res; },
  (error: AxiosError<{ message?: string }>) => {
    toast.error(error.response?.data?.message || 'Đã xảy ra lỗi kết nối.');
    return Promise.reject(error);
  },
);
