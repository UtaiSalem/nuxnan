/**
 * Member Store - Pinia store for managing course members
 * 
 * Features:
 * - Member CRUD operations
 * - Role management
 * - Search and filtering
 * - Pagination support
 * - Bulk operations
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useCache } from '@/Pages/v2/stores/composables/useCache';
import { useLoading } from '@/Pages/v2/stores/composables/useLoading';
import { memberService } from '@/Pages/v2/api/memberService';

export const useMemberStore = defineStore('member', () => {
    // ============= STATE =============
    const members = ref(new Map()); // Using Map for better performance
    const loading = ref(false);
    const error = ref(null);
    const filters = ref({
        role: '',
        q: '', // search query
    });
    const pagination = ref({
        page: 1,
        perPage: 50,
        total: 0,
    });

    // ============= COMPOSABLES =============
    const { setCache, getCache, isCacheValid, clearCache } = useCache('member', {
        ttl: 5 * 60 * 1000, // 5 minutes
        maxSize: 500, // More members expected
    });

    const { setLoading, isLoading, clearLoading } = useLoading();

    // ============= GETTERS =============
    const memberById = computed(() => (memberId) => {
        return members.value.get(memberId);
    });

    const membersArray = computed(() => {
        return Array.from(members.value.values());
    });

    const filteredMembers = computed(() => {
        let filtered = membersArray.value;

        // Filter by role
        if (filters.value.role) {
            filtered = filtered.filter(member => member.role === filters.value.role);
        }

        // Filter by search query
        if (filters.value.q) {
            const query = filters.value.q.toLowerCase();
            filtered = filtered.filter(member => 
                member.name.toLowerCase().includes(query) ||
                member.email.toLowerCase().includes(query) ||
                (member.student_id && member.student_id.toLowerCase().includes(query))
            );
        }

        return filtered;
    });

    const paginatedMembers = computed(() => {
        const start = (pagination.value.page - 1) * pagination.value.perPage;
        const end = start + pagination.value.perPage;
        return filteredMembers.value.slice(start, end);
    });

    const students = computed(() => {
        return membersArray.value.filter(member => member.role === 'student');
    });

    const instructors = computed(() => {
        return membersArray.value.filter(member => member.role === 'teacher' || member.role === 'instructor');
    });

    const tas = computed(() => {
        return membersArray.value.filter(member => member.role === 'ta' || member.role === 'teaching_assistant');
    });

    const activeMembers = computed(() => {
        return membersArray.value.filter(member => member.status === 'active');
    });

    const pendingMembers = computed(() => {
        return membersArray.value.filter(member => member.status === 'pending');
    });

    // ============= ACTIONS =============

    /**
     * Fetch members for a course
     */
    const fetchMembers = async (courseId, params = {}) => {
        const {
            page = 1,
            perPage = 50,
            role = '',
            q = '',
            forceRefresh = false,
        } = params;

        const cacheKey = `course_members_${courseId}_${page}_${perPage}_${role}_${q}`;
        
        if (!forceRefresh && isCacheValid(cacheKey)) {
            const cachedData = getCache(cacheKey);
            pagination.value = cachedData.pagination;
            return cachedData.members;
        }

        setLoading('fetch_members', true);

        try {
            const data = await memberService.getCourseMembers(courseId, {
                page, perPage, role, q
            });

            // Update members Map
            data.data.forEach(member => {
                members.value.set(member.id, member);
            });

            // Update pagination
            pagination.value = {
                page: data.current_page,
                perPage: data.per_page,
                total: data.total,
            };

            // Update filters
            filters.value = { role, q };

            // Cache the result
            setCache(cacheKey, {
                members: data.data,
                pagination: pagination.value,
            });

            return data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('fetch_members', false);
        }
    };

    /**
     * Invite a single member
     */
    const inviteMember = async (courseId, payload) => {
        setLoading('invite_member', true);

        try {
            const newMember = await memberService.inviteMember(courseId, payload);

            // Add to local state
            members.value.set(newMember.id, newMember);

            // Clear cache
            clearCache(`course_members_${courseId}`);

            return newMember;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('invite_member', false);
        }
    };

    /**
     * Bulk import members from CSV
     */
    const bulkImport = async (courseId, membersArray) => {
        setLoading('bulk_import', true);

        try {
            const result = await memberService.bulkInviteMembers(courseId, membersArray);

            // Add new members to local state
            result.imported.forEach(member => {
                members.value.set(member.id, member);
            });

            // Clear cache
            clearCache(`course_members_${courseId}`);

            return result;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('bulk_import', false);
        }
    };

    /**
     * Update member role
     */
    const updateMemberRole = async (memberId, role) => {
        setLoading(`update_role_${memberId}`, true);

        try {
            const updatedMember = await memberService.updateMemberRole(memberId, role);

            // Update local state
            members.value.set(memberId, updatedMember);

            return updatedMember;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`update_role_${memberId}`, false);
        }
    };

    /**
     * Remove member from course
     */
    const removeMember = async (courseId, memberId) => {
        setLoading(`remove_member_${memberId}`, true);

        try {
            await memberService.removeMember(courseId, memberId);

            // Remove from local state
            members.value.delete(memberId);

            // Clear cache
            clearCache(`course_members_${courseId}`);

            return true;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`remove_member_${memberId}`, false);
        }
    };

    /**
     * Update member status (active, suspended, etc.)
     */
    const updateMemberStatus = async (memberId, status) => {
        setLoading(`update_status_${memberId}`, true);

        try {
            const updatedMember = await memberService.updateMemberStatus(memberId, status);

            // Update local state
            members.value.set(memberId, updatedMember);

            return updatedMember;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`update_status_${memberId}`, false);
        }
    };

    /**
     * Bulk update member roles
     */
    const bulkUpdateRoles = async (updates) => {
        setLoading('bulk_update_roles', true);

        try {
            const result = await memberService.bulkUpdateRoles(updates);

            // Update local state
            result.updated.forEach(member => {
                members.value.set(member.id, member);
            });

            return result;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('bulk_update_roles', false);
        }
    };

    /**
     * Update filters
     */
    const updateFilters = (newFilters) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    /**
     * Update pagination
     */
    const updatePagination = (newPagination) => {
        pagination.value = { ...pagination.value, ...newPagination };
    };

    /**
     * Export members
     */
    const exportMembers = async (courseId, format = 'csv') => {
        setLoading('export_members', true);

        try {
            const data = await memberService.exportMembers(courseId, format);
            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('export_members', false);
        }
    };

    /**
     * Bulk remove members
     */
    const bulkRemoveMembers = async (courseId, memberIds) => {
        setLoading('bulk_remove_members', true);

        try {
            const result = await memberService.bulkRemoveMembers(courseId, memberIds);
            
            // Remove from local state
            memberIds.forEach(id => {
                members.value.delete(id);
            });

            // Clear cache
            clearCache(`course_members_${courseId}`);

            return result;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('bulk_remove_members', false);
        }
    };

    /**
     * Clear all members
     */
    const clearAll = () => {
        members.value.clear();
        filters.value = { role: '', q: '' };
        pagination.value = { page: 1, perPage: 50, total: 0 };
        clearCache();
        clearLoading();
    };

    return {
        // State
        members,
        loading,
        error,
        filters,
        pagination,

        // Getters
        memberById,
        membersArray,
        filteredMembers,
        paginatedMembers,
        students,
        instructors,
        tas,
        activeMembers,
        pendingMembers,

        // Loading states
        isLoading,

        // Actions
        fetchMembers,
        inviteMember,
        bulkImport,
        updateMemberRole,
        removeMember,
        updateMemberStatus,
        bulkUpdateRoles,
        updateFilters,
        updatePagination,
        clearAll,
    };
});