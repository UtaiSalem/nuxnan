/**
 * useApi - Composable สำหรับจัดการ API calls
 * 
 * ฟีเจอร์:
 * - Wrapper สำหรับ axios
 * - จัดการ error และ loading state
 * - รองรับ callbacks (onSuccess, onError)
 * - Retry mechanism (optional)
 */

import axios from 'axios';
import { ref } from 'vue';

export function useApi(options = {}) {
    const {
        baseURL = '',
        timeout = 30000,
        defaultHeaders = {},
    } = options;
    
    const error = ref(null);
    const isLoading = ref(false);
    const lastResponse = ref(null);
    
    /**
     * สร้าง axios instance
     */
    const createAxiosInstance = () => {
        return axios.create({
            baseURL,
            timeout,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...defaultHeaders,
            },
        });
    };
    
    /**
     * ฟังก์ชันหลักสำหรับ request
     */
    const request = async (config, requestOptions = {}) => {
        const {
            showLoading = true,
            throwError = true,
            onSuccess = null,
            onError = null,
            retries = 0,
            retryDelay = 1000,
        } = requestOptions;
        
        if (showLoading) isLoading.value = true;
        error.value = null;
        
        let lastError = null;
        
        // Retry logic
        for (let attempt = 0; attempt <= retries; attempt++) {
            try {
                const axiosInstance = createAxiosInstance();
                const response = await axiosInstance(config);
                
                lastResponse.value = response;
                
                if (onSuccess) {
                    onSuccess(response.data);
                }
                
                if (showLoading) isLoading.value = false;
                return response.data;
                
            } catch (err) {
                lastError = err;
                
                // ถ้ายังมี retries เหลือ และไม่ใช่ error 4xx (client error)
                const shouldRetry = attempt < retries && 
                                   (!err.response || err.response.status >= 500);
                
                if (shouldRetry) {
                    // รอก่อน retry
                    await new Promise(resolve => setTimeout(resolve, retryDelay * (attempt + 1)));
                    continue;
                }
                
                // Handle error
                error.value = err.response?.data?.message || err.message || 'An error occurred';
                
                if (onError) {
                    onError(err);
                }
                
                if (throwError) {
                    if (showLoading) isLoading.value = false;
                    throw err;
                }
                
                if (showLoading) isLoading.value = false;
                return null;
            }
        }
        
        // ถ้า retry หมดแล้วยัง fail
        if (lastError) {
            error.value = lastError.response?.data?.message || lastError.message || 'An error occurred';
            
            if (onError) {
                onError(lastError);
            }
            
            if (throwError) {
                if (showLoading) isLoading.value = false;
                throw lastError;
            }
        }
        
        if (showLoading) isLoading.value = false;
        return null;
    };
    
    /**
     * GET request
     */
    const get = (url, params = {}, requestOptions = {}) => {
        return request({ 
            method: 'GET', 
            url, 
            params 
        }, requestOptions);
    };
    
    /**
     * POST request
     */
    const post = (url, data = {}, requestOptions = {}) => {
        return request({ 
            method: 'POST', 
            url, 
            data 
        }, requestOptions);
    };
    
    /**
     * PUT request
     */
    const put = (url, data = {}, requestOptions = {}) => {
        return request({ 
            method: 'PUT', 
            url, 
            data 
        }, requestOptions);
    };
    
    /**
     * PATCH request
     */
    const patch = (url, data = {}, requestOptions = {}) => {
        return request({ 
            method: 'PATCH', 
            url, 
            data 
        }, requestOptions);
    };
    
    /**
     * DELETE request
     */
    const del = (url, requestOptions = {}) => {
        return request({ 
            method: 'DELETE', 
            url 
        }, requestOptions);
    };
    
    /**
     * Upload file (multipart/form-data)
     */
    const upload = (url, formData, requestOptions = {}) => {
        return request({
            method: 'POST',
            url,
            data: formData,
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        }, requestOptions);
    };
    
    /**
     * ล้าง error state
     */
    const clearError = () => {
        error.value = null;
    };
    
    /**
     * ล้าง response state
     */
    const clearResponse = () => {
        lastResponse.value = null;
    };
    
    /**
     * Reset ทั้งหมด
     */
    const reset = () => {
        error.value = null;
        isLoading.value = false;
        lastResponse.value = null;
    };
    
    return {
        // State
        error,
        isLoading,
        lastResponse,
        
        // Methods
        request,
        get,
        post,
        put,
        patch,
        del,
        upload,
        
        // Utilities
        clearError,
        clearResponse,
        reset,
    };
}
