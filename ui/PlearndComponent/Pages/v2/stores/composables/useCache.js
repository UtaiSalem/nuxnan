/**
 * Cache Composable
 * 
 * Provides a simple in-memory cache with TTL support
 * for use with Pinia stores
 */
import { ref } from 'vue';

export function useCache(namespace, options = {}) {
    const {
        ttl = 5 * 60 * 1000, // 5 minutes default
        maxSize = 100,
    } = options;
    
    // Cache storage
    const cache = ref(new Map());
    
    // Get cache key with namespace
    const getNamespacedKey = (key) => `${namespace}_${key}`;
    
    // Check if cache entry is still valid
    const isCacheValid = (key) => {
        const entry = cache.value.get(getNamespacedKey(key));
        if (!entry) return false;
        
        return Date.now() - entry.timestamp < ttl;
    };
    
    // Get value from cache
    const getCache = (key) => {
        const entry = cache.value.get(getNamespacedKey(key));
        if (!entry) return null;
        
        // Check if expired
        if (Date.now() - entry.timestamp >= ttl) {
            cache.value.delete(getNamespacedKey(key));
            return null;
        }
        
        return entry.value;
    };
    
    // Set value in cache
    const setCache = (key, value) => {
        // Implement LRU if cache is full
        if (cache.value.size >= maxSize) {
            // Remove oldest entry
            const firstKey = cache.value.keys().next().value;
            if (firstKey) {
                cache.value.delete(firstKey);
            }
        }
        
        cache.value.set(getNamespacedKey(key), {
            value,
            timestamp: Date.now(),
        });
    };
    
    // Clear specific cache entry
    const clearCache = (key) => {
        if (key) {
            cache.value.delete(getNamespacedKey(key));
        } else {
            // Clear all entries with this namespace
            const keysToDelete = [];
            for (const cacheKey of cache.value.keys()) {
                if (cacheKey.startsWith(`${namespace}_`)) {
                    keysToDelete.push(cacheKey);
                }
            }
            
            keysToDelete.forEach(key => {
                cache.value.delete(key);
            });
        }
    };
    
    return {
        isCacheValid,
        getCache,
        setCache,
        clearCache,
    };
}