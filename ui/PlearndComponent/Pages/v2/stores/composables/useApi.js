/**
 * API Composable
 * 
 * Provides a centralized API client with authentication,
 * error handling, and response interceptors
 */
import axios from 'axios';

// Base API configuration
const API_BASE_URL = '/api/v2';

// Create axios instance with default configuration
const apiClient = axios.create({
    baseURL: API_BASE_URL,
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor to handle common errors
apiClient.interceptors.response.use(
    (response) => {
        return response.data;
    },
    (error) => {
        if (error.response?.status === 401) {
            // Handle unauthorized access
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }
        return Promise.reject(error.response?.data || error.message);
    }
);

export function useApi() {
    // GET request
    const get = async (url, params = {}) => {
        try {
            const response = await apiClient.get(url, { params });
            return response;
        } catch (error) {
            console.error('API GET Error:', error);
            throw error;
        }
    };

    // POST request
    const post = async (url, data = {}) => {
        try {
            const response = await apiClient.post(url, data);
            return response;
        } catch (error) {
            console.error('API POST Error:', error);
            throw error;
        }
    };

    // PUT request
    const put = async (url, data = {}) => {
        try {
            const response = await apiClient.put(url, data);
            return response;
        } catch (error) {
            console.error('API PUT Error:', error);
            throw error;
        }
    };

    // DELETE request
    const del = async (url) => {
        try {
            const response = await apiClient.delete(url);
            return response;
        } catch (error) {
            console.error('API DELETE Error:', error);
            throw error;
        }
    };

    // File upload
    const upload = async (url, formData) => {
        try {
            const response = await apiClient.post(url, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });
            return response;
        } catch (error) {
            console.error('API Upload Error:', error);
            throw error;
        }
    };

    // Download file
    const download = async (url, params = {}, filename = 'download') => {
        try {
            const response = await apiClient.get(url, {
                params,
                responseType: 'blob',
            });
            
            // Create download link
            const blob = new Blob([response]);
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            
            // Clean up
            document.body.removeChild(link);
            window.URL.revokeObjectURL(downloadUrl);
            
            return response;
        } catch (error) {
            console.error('API Download Error:', error);
            throw error;
        }
    };

    return {
        get,
        post,
        put,
        del,
        upload,
        download,
        apiClient,
    };
}