/**
 * useCache - Composable สำหรับจัดการ Cache
 * 
 * ฟีเจอร์:
 * - TTL (Time To Live) - กำหนดอายุของ cache
 * - Max Size - จำกัดขนาด cache
 * - Namespace - แยก cache ตาม context
 */

import { ref } from 'vue';

export function useCache(namespace, options = {}) {
    const { 
        ttl = 5 * 60 * 1000, // 5 นาที (default)
        maxSize = 100 
    } = options;
    
    const cache = ref(new Map());
    const timestamps = ref(new Map());
    
    /**
     * บันทึกข้อมูลลง cache
     */
    const setCache = (key, data) => {
        const fullKey = `${namespace}:${key}`;
        
        // ลบข้อมูลเก่าถ้าเกิน maxSize
        if (cache.value.size >= maxSize) {
            const oldestKey = Array.from(timestamps.value.entries())
                .sort(([, a], [, b]) => a - b)[0][0];
            cache.value.delete(oldestKey);
            timestamps.value.delete(oldestKey);
        }
        
        cache.value.set(fullKey, data);
        timestamps.value.set(fullKey, Date.now());
    };
    
    /**
     * ดึงข้อมูลจาก cache
     */
    const getCache = (key) => {
        const fullKey = `${namespace}:${key}`;
        return cache.value.get(fullKey);
    };
    
    /**
     * ตรวจสอบว่า cache ยังใช้ได้อยู่หรือไม่
     */
    const isCacheValid = (key) => {
        const fullKey = `${namespace}:${key}`;
        const timestamp = timestamps.value.get(fullKey);
        
        if (!timestamp) return false;
        return (Date.now() - timestamp) < ttl;
    };
    
    /**
     * ลบ cache
     * @param {string|null} key - ถ้าไม่ระบุจะลบทั้งหมดใน namespace
     */
    const clearCache = (key = null) => {
        if (key) {
            const fullKey = `${namespace}:${key}`;
            cache.value.delete(fullKey);
            timestamps.value.delete(fullKey);
        } else {
            // Clear all cache for this namespace
            Array.from(cache.value.keys())
                .filter(k => k.startsWith(`${namespace}:`))
                .forEach(k => {
                    cache.value.delete(k);
                    timestamps.value.delete(k);
                });
        }
    };
    
    /**
     * ลบ cache ทั้งหมดใน namespace
     */
    const invalidateCache = () => {
        clearCache();
    };
    
    /**
     * ดึงข้อมูลสถิติ cache
     */
    const getCacheStats = () => {
        const namespaceCaches = Array.from(cache.value.keys())
            .filter(k => k.startsWith(`${namespace}:`));
        
        return {
            size: namespaceCaches.length,
            maxSize,
            ttl,
            namespace,
        };
    };
    
    return {
        cache,
        setCache,
        getCache,
        isCacheValid,
        clearCache,
        invalidateCache,
        getCacheStats,
    };
}
