/**
 * Loading Composable
 * 
 * Provides a centralized loading state management
 * for use with Pinia stores
 */
import { ref } from 'vue';

export function useLoading() {
    // Loading states storage
    const loadingStates = ref(new Map());
    
    // Set loading state for a specific operation
    const setLoading = (key, value) => {
        loadingStates.value.set(key, value);
    };
    
    // Check if any operation is loading
    const isLoading = (key) => {
        if (key) {
            return loadingStates.value.get(key) || false;
        }
        
        // Check if any operation is loading
        return Array.from(loadingStates.value.values()).some(state => state === true);
    };
    
    // Clear loading state for a specific operation
    const clearLoading = (key) => {
        if (key) {
            loadingStates.value.delete(key);
        } else {
            // Clear all loading states
            loadingStates.value.clear();
        }
    };
    
    return {
        setLoading,
        isLoading,
        clearLoading,
    };
}