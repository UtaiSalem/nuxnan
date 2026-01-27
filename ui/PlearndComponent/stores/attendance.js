import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export const useAttendanceStore = defineStore('attendance', () => {
    // State
    const attendances = ref({});
    const attendanceDetails = ref({});
    const loadingStates = ref({});
    const errorStates = ref({});
    const cacheTimestamps = ref({});
    
    // Cache duration in milliseconds (5 minutes)
    const CACHE_DURATION = 5 * 60 * 1000;
    
    // Getters
    const getGroupAttendances = computed(() => (groupId) => {
        return attendances.value[groupId] || [];
    });
    
    const getAttendanceDetails = computed(() => (attendanceId) => {
        return attendanceDetails.value[attendanceId] || null;
    });
    
    const isLoading = computed(() => (key) => {
        return loadingStates.value[key] || false;
    });
    
    const getError = computed(() => (key) => {
        return errorStates.value[key] || null;
    });
    
    const isCacheValid = computed(() => (key) => {
        const timestamp = cacheTimestamps.value[key];
        if (!timestamp) return false;
        return Date.now() - timestamp < CACHE_DURATION;
    });
    
    // Actions
    const setLoading = (key, status) => {
        loadingStates.value[key] = status;
    };
    
    const setError = (key, error) => {
        errorStates.value[key] = error;
    };
    
    const clearError = (key) => {
        delete errorStates.value[key];
    };
    
    const setCacheTimestamp = (key) => {
        cacheTimestamps.value[key] = Date.now();
    };
    
    const setGroupAttendances = (groupId, data) => {
        attendances.value[groupId] = data;
        setCacheTimestamp(`group_${groupId}`);
    };
    
    const addAttendance = (groupId, attendance) => {
        if (!attendances.value[groupId]) {
            attendances.value[groupId] = [];
        }
        attendances.value[groupId].push(attendance);
        setCacheTimestamp(`group_${groupId}`);
    };
    
    const updateAttendanceInGroup = (groupId, attendanceId, updatedData) => {
        if (attendances.value[groupId]) {
            const index = attendances.value[groupId].findIndex(a => a.id === attendanceId);
            if (index !== -1) {
                attendances.value[groupId][index] = { ...attendances.value[groupId][index], ...updatedData };
                setCacheTimestamp(`group_${groupId}`);
            }
        }
    };
    
    const deleteAttendanceFromGroup = (groupId, attendanceId) => {
        if (attendances.value[groupId]) {
            attendances.value[groupId] = attendances.value[groupId].filter(a => a.id !== attendanceId);
            setCacheTimestamp(`group_${groupId}`);
        }
    };
    
    const setAttendanceDetails = (attendanceId, data) => {
        attendanceDetails.value[attendanceId] = data;
        setCacheTimestamp(`attendance_${attendanceId}`);
    };
    
    const updateMemberAttendanceStatus = (attendanceId, memberId, status) => {
        if (attendances.value) {
            // Update in all groups that might contain this attendance
            Object.keys(attendances.value).forEach(groupId => {
                const attendance = attendances.value[groupId].find(a => a.id === attendanceId);
                if (attendance && attendance.details) {
                    const detailIndex = attendance.details.findIndex(d => d.course_member_id === memberId);
                    if (detailIndex !== -1) {
                        attendance.details[detailIndex].status = status;
                    }
                }
            });
        }
    };
    
    const clearGroupCache = (groupId) => {
        delete attendances.value[groupId];
        delete cacheTimestamps.value[`group_${groupId}`];
    };
    
    const clearAttendanceCache = (attendanceId) => {
        delete attendanceDetails.value[attendanceId];
        delete cacheTimestamps.value[`attendance_${attendanceId}`];
    };
    
    const clearAllCache = () => {
        attendances.value = {};
        attendanceDetails.value = {};
        cacheTimestamps.value = {};
        loadingStates.value = {};
        errorStates.value = {};
    };
    
    // API Actions
    const fetchGroupAttendances = async (courseId, groupId, isCourseAdmin = false, forceRefresh = false) => {
        const cacheKey = `group_${groupId}`;
        
        // Return cached data if valid and not forcing refresh
        if (!forceRefresh && isCacheValid.value(cacheKey)) {
            return attendances.value[groupId] || [];
        }
        
        setLoading(cacheKey, true);
        clearError(cacheKey);
        
        try {
            const response = await axios.get(`/courses/${courseId}/groups/${groupId}/attendances`, {
                params: {
                    isCourseAdmin: isCourseAdmin
                }
            });
            
            if (response.data && response.data.success) {
                setGroupAttendances(groupId, response.data.attendances);
                return response.data.attendances;
            } else {
                throw new Error(response.data.message || 'Failed to fetch attendances');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to fetch attendances');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    const fetchAttendanceDetails = async (attendanceId, forceRefresh = false) => {
        const cacheKey = `attendance_${attendanceId}`;
        
        // Return cached data if valid and not forcing refresh
        if (!forceRefresh && isCacheValid.value(cacheKey)) {
            return attendanceDetails.value[attendanceId];
        }
        
        setLoading(cacheKey, true);
        clearError(cacheKey);
        
        try {
            const response = await axios.get(`/attendances/${attendanceId}/details`);
            
            if (response.data && response.data.success) {
                setAttendanceDetails(attendanceId, response.data.details);
                return response.data.details;
            } else {
                throw new Error(response.data.message || 'Failed to fetch attendance details');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to fetch attendance details');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    const createAttendance = async (courseId, groupId, attendanceData) => {
        const cacheKey = `create_${groupId}`;
        
        setLoading(cacheKey, true);
        clearError(cacheKey);
        
        try {
            const response = await axios.post(`/courses/${courseId}/groups/${groupId}/attendances`, attendanceData);
            
            if (response.data && response.data.success) {
                addAttendance(groupId, response.data.attendance);
                return response.data.attendance;
            } else {
                throw new Error(response.data.message || 'Failed to create attendance');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to create attendance');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    const updateAttendance = async (attendanceId, updateData) => {
        const cacheKey = `update_${attendanceId}`;
        
        setLoading(cacheKey, true);
        clearError(cacheKey);
        
        try {
            const response = await axios.patch(`/attendances/${attendanceId}`, updateData);
            
            if (response.data && response.data.success) {
                // Find which group this attendance belongs to and update it
                Object.keys(attendances.value).forEach(groupId => {
                    updateAttendanceInGroup(groupId, attendanceId, response.data.attendance);
                });
                return response.data.attendance;
            } else {
                throw new Error(response.data.message || 'Failed to update attendance');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to update attendance');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    const deleteAttendance = async (attendanceId) => {
        const cacheKey = `delete_${attendanceId}`;
        
        setLoading(cacheKey, true);
        clearError(cacheKey);
        
        try {
            const response = await axios.delete(`/attendances/${attendanceId}`);
            
            if (response.data && response.data.success) {
                // Find and remove from all groups
                Object.keys(attendances.value).forEach(groupId => {
                    deleteAttendanceFromGroup(groupId, attendanceId);
                });
                clearAttendanceCache(attendanceId);
                return true;
            } else {
                throw new Error(response.data.message || 'Failed to delete attendance');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to delete attendance');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    const submitMemberAttendance = async (attendanceId, memberId, status, comments = null) => {
        const cacheKey = `submit_${attendanceId}_${memberId}`;
        
        setLoading(cacheKey, true);
        clearError(cacheKey);
        
        try {
            const response = await axios.post(`/attendances/${attendanceId}/details`, {
                course_attendance_id: attendanceId,
                course_member_id: memberId,
                status,
                comments
            });
            
            if (response.data && response.data.success) {
                updateMemberAttendanceStatus(attendanceId, memberId, response.data.attendance_detail.status);
                return response.data.attendance_detail;
            } else {
                throw new Error(response.data.message || 'Failed to submit attendance');
            }
        } catch (error) {
            setError(cacheKey, error.message || 'Failed to submit attendance');
            throw error;
        } finally {
            setLoading(cacheKey, false);
        }
    };
    
    const fetchMemberJoinStatus = async (attendanceId, memberId) => {
        const cacheKey = `member_status_${attendanceId}_${memberId}`;
        
        try {
            const response = await axios.get(`/attendances/${attendanceId}/member/${memberId}/join-status`);
            
            if (response.data && response.data.success) {
                return response.data.status;
            } else {
                throw new Error(response.data.message || 'Failed to fetch member join status');
            }
        } catch (error) {
            // Only log errors that are not 404 (to avoid console spam when attendance is deleted)
            if (error.response?.status !== 404) {
                console.error('Failed to fetch member join status:', error);
            }
            throw error;
        }
    };
    
    const updateMemberStatusInAttendance = (attendanceId, memberId, status) => {
        // Update in all groups that might contain this attendance
        Object.keys(attendances.value).forEach(groupId => {
            const attendance = attendances.value[groupId].find(a => a.id === attendanceId);
            if (attendance && attendance.details) {
                const detailIndex = attendance.details.findIndex(d => d.course_member_id === memberId);
                if (detailIndex !== -1) {
                    attendance.details[detailIndex].status = status;
                }
            }
        });
    };
    
    return {
        // State
        attendances,
        attendanceDetails,
        loadingStates,
        errorStates,
        
        // Getters
        getGroupAttendances,
        getAttendanceDetails,
        isLoading,
        getError,
        isCacheValid,
        
        // Actions
        setLoading,
        setError,
        clearError,
        setGroupAttendances,
        addAttendance,
        updateAttendance,
        deleteAttendance,
        setAttendanceDetails,
        updateMemberAttendanceStatus,
        clearGroupCache,
        clearAttendanceCache,
        clearAllCache,
        
        // API Actions
        fetchGroupAttendances,
        fetchAttendanceDetails,
        createAttendance,
        updateAttendance,
        deleteAttendance,
        submitMemberAttendance,
        fetchMemberJoinStatus,
        updateMemberStatusInAttendance,
    };
});