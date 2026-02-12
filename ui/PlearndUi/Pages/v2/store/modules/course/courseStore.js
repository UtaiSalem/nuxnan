/**
 * Course Store - Pinia store for managing course data
 * 
 * Features:
 * - Centralized course state management
 * - Cache integration
 * - Loading states
 * - Optimistic updates
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { courseService } from '@/Pages/v2/api/courseService';
import { useCache } from '@/Pages/v2/stores/composables/useCache';
import { useLoading } from '@/Pages/v2/stores/composables/useLoading';

export const useCourseStore = defineStore('course', () => {
    // ============= STATE =============
    const courses = ref(new Map()); // Using Map for better performance
    const currentCourseId = ref(null);
    const courseMeta = ref({}); // title, code, term, instructors
    const loading = ref(false);
    const error = ref(null);

    // ============= COMPOSABLES =============
    const { setCache, getCache, isCacheValid, clearCache } = useCache('course', {
        ttl: 5 * 60 * 1000, // 5 minutes
        maxSize: 100,
    });

    const { setLoading, isLoading, clearLoading } = useLoading();

    // ============= GETTERS =============
    const currentCourse = computed(() => {
        return currentCourseId.value 
            ? courses.value.get(currentCourseId.value) 
            : null;
    });

    const getCourseById = computed(() => (courseId) => {
        return courses.value.get(courseId);
    });

    const coursesArray = computed(() => {
        return Array.from(courses.value.values());
    });

    const instructors = computed(() => {
        return courseMeta.value.instructors || [];
    });

    const isTeacher = computed(() => (userId) => {
        return instructors.value.some(instructor => instructor.id === userId);
    });

    // ============= ACTIONS =============

    /**
     * Initialize course with basic metadata
     */
    const init = async (courseId) => {
        currentCourseId.value = courseId;
        
        // Check cache first
        const cacheKey = `course_meta_${courseId}`;
        if (isCacheValid(cacheKey)) {
            courseMeta.value = getCache(cacheKey);
            return courseMeta.value;
        }

        setLoading('init_course', true);
        
        try {
            const data = await courseService.getCourse(courseId);
            courses.value.set(courseId, data);
            courseMeta.value = {
                title: data.name,
                code: data.code,
                term: data.term,
                instructors: data.instructors || [],
            };
            
            setCache(cacheKey, courseMeta.value);
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('init_course', false);
        }
    };

    /**
     * Fetch single course
     */
    const fetchCourse = async (courseId, options = {}) => {
        const { forceRefresh = false } = options;
        const cacheKey = `course_${courseId}`;

        // Check cache
        if (!forceRefresh && isCacheValid(cacheKey)) {
            return getCache(cacheKey);
        }

        setLoading(cacheKey, true);

        try {
            const data = await courseService.getCourse(courseId);
            
            // Save to Map
            courses.value.set(courseId, data);
            
            // Save cache
            setCache(cacheKey, data);
            
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    /**
     * Fetch multiple courses
     */
    const fetchCourses = async (params = {}) => {
        const cacheKey = `courses_${JSON.stringify(params)}`;
        
        if (isCacheValid(cacheKey)) {
            return getCache(cacheKey);
        }

        setLoading('courses_list', true);

        try {
            const data = await courseService.getCourses(params);
            
            // Save each course to Map
            data.data.forEach(course => {
                courses.value.set(course.id, course);
            });
            
            setCache(cacheKey, data);
            
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('courses_list', false);
        }
    };

    /**
     * Update course
     */
    const updateCourse = async (courseId, updates) => {
        const cacheKey = `update_course_${courseId}`;
        
        setLoading(cacheKey, true);

        try {
            const data = await courseService.updateCourse(courseId, updates);
            
            // Update local state
            const existingCourse = courses.value.get(courseId);
            if (existingCourse) {
                courses.value.set(courseId, { ...existingCourse, ...data });
            }
            
            // Clear related cache
            clearCache(`course_${courseId}`);
            clearCache(`course_meta_${courseId}`);
            
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(cacheKey, false);
        }
    };

    /**
     * Optimistic update for better UX
     */
    const optimisticUpdate = (courseId, updates) => {
        const existingCourse = courses.value.get(courseId);
        if (existingCourse) {
            courses.value.set(courseId, { ...existingCourse, ...updates });
        }
    };

    /**
     * Revert optimistic update
     */
    const revertUpdate = (courseId, previousData) => {
        courses.value.set(courseId, previousData);
    };

    /**
     * Fetch quick stats for dashboard
     */
    const fetchQuickStats = async (courseId) => {
        const cacheKey = `course_stats_${courseId}`;
        
        if (isCacheValid(cacheKey)) {
            return getCache(cacheKey);
        }

        setLoading('fetch_stats', true);

        try {
            const data = await courseService.getCourseStats(courseId);
            setCache(cacheKey, data);
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('fetch_stats', false);
        }
    };

    /**
     * Set current course
     */
    const setCurrentCourse = (courseId) => {
        currentCourseId.value = courseId;
    };

    /**
     * Clear course data
     */
    const clearCourse = (courseId) => {
        courses.value.delete(courseId);
        clearCache(`course_${courseId}`);
        clearCache(`course_meta_${courseId}`);
    };

    /**
     * Clear all data
     */
    const clearAll = () => {
        courses.value.clear();
        currentCourseId.value = null;
        courseMeta.value = {};
        clearCache();
        clearLoading();
    };

    return {
        // State
        courses,
        currentCourseId,
        courseMeta,
        loading,
        error,

        // Getters
        currentCourse,
        getCourseById,
        coursesArray,
        instructors,
        isTeacher,

        // Loading states
        isLoading,

        // Actions
        init,
        fetchCourse,
        fetchCourses,
        updateCourse,
        optimisticUpdate,
        revertUpdate,
        fetchQuickStats,
        setCurrentCourse,
        clearCourse,
        clearAll,
    };
});