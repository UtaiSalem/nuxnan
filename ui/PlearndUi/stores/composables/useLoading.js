/**
 * useLoading - Composable สำหรับจัดการ Loading States
 * 
 * ฟีเจอร์:
 * - จัดการ loading state แบบ centralized
 * - รองรับหลาย loading states พร้อมกัน
 * - ตรวจสอบว่ามี loading อยู่หรือไม่
 */

import { ref, computed } from 'vue';

export function useLoading() {
    const loadingStates = ref(new Map());
    
    /**
     * ตั้งค่า loading state
     * @param {string} key - ชื่อ key ของ loading state
     * @param {boolean} status - สถานะ loading (true/false)
     */
    const setLoading = (key, status) => {
        if (status) {
            loadingStates.value.set(key, status);
        } else {
            // ลบ key ออกเมื่อ loading เสร็จ เพื่อประหยัด memory
            loadingStates.value.delete(key);
        }
    };
    
    /**
     * ตรวจสอบว่า key นี้กำลัง loading อยู่หรือไม่
     */
    const isLoading = computed(() => (key) => {
        return loadingStates.value.get(key) || false;
    });
    
    /**
     * ตรวจสอบว่ามี loading state ใดๆ อยู่หรือไม่
     */
    const isAnyLoading = computed(() => {
        return Array.from(loadingStates.value.values()).some(v => v === true);
    });
    
    /**
     * นับจำนวน loading states ที่กำลังทำงาน
     */
    const loadingCount = computed(() => {
        return Array.from(loadingStates.value.values()).filter(v => v === true).length;
    });
    
    /**
     * ลบ loading state
     * @param {string|null} key - ถ้าไม่ระบุจะลบทั้งหมด
     */
    const clearLoading = (key = null) => {
        if (key) {
            loadingStates.value.delete(key);
        } else {
            loadingStates.value.clear();
        }
    };
    
    /**
     * ดึงรายการ loading states ทั้งหมด
     */
    const getAllLoadingStates = () => {
        return Array.from(loadingStates.value.entries()).map(([key, value]) => ({
            key,
            isLoading: value,
        }));
    };
    
    /**
     * Wrapper function สำหรับ async operation พร้อม loading state
     * @param {string} key - ชื่อ key ของ loading state
     * @param {Function} asyncFn - async function ที่จะรัน
     */
    const withLoading = async (key, asyncFn) => {
        setLoading(key, true);
        try {
            return await asyncFn();
        } finally {
            setLoading(key, false);
        }
    };
    
    return {
        loadingStates,
        setLoading,
        isLoading,
        isAnyLoading,
        loadingCount,
        clearLoading,
        getAllLoadingStates,
        withLoading,
    };
}
