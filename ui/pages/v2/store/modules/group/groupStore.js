/**
 * Group Store - Pinia store for managing course groups
 * 
 * Features:
 * - Group CRUD operations
 * - Member management within groups
 * - Pagination support
 * - Cache integration
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { useCache } from '@/Pages/v2/stores/composables/useCache';
import { useLoading } from '@/Pages/v2/stores/composables/useLoading';
import { groupService } from '@/Pages/v2/api/groupService';

export const useGroupStore = defineStore('group', () => {
    // ============= STATE =============
    const groups = ref(new Map()); // Using Map for better performance
    const activeGroupId = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        page: 1,
        perPage: 20,
        total: 0,
    });

    // ============= COMPOSABLES =============
    const { setCache, getCache, isCacheValid, clearCache } = useCache('group', {
        ttl: 5 * 60 * 1000, // 5 minutes
        maxSize: 100,
    });

    const { setLoading, isLoading, clearLoading } = useLoading();

    // ============= GETTERS =============
    const activeGroup = computed(() => {
        return activeGroupId.value 
            ? groups.value.get(activeGroupId.value) 
            : null;
    });

    const groupById = computed(() => (groupId) => {
        return groups.value.get(groupId);
    });

    const groupsArray = computed(() => {
        return Array.from(groups.value.values());
    });

    const groupsForSelect = computed(() => {
        return groupsArray.value.map(group => ({
            value: group.id,
            label: group.name,
            capacity: group.capacity,
            membersCount: group.membersCount,
        }));
    });

    const paginatedGroups = computed(() => {
        const start = (pagination.value.page - 1) * pagination.value.perPage;
        const end = start + pagination.value.perPage;
        return groupsArray.value.slice(start, end);
    });

    // ============= ACTIONS =============

    /**
     * Fetch groups for a course
     */
    const fetchGroups = async (courseId, params = {}) => {
        const {
            page = 1,
            perPage = 20,
            forceRefresh = false,
        } = params;

        const cacheKey = `course_groups_${courseId}_${page}_${perPage}`;
        
        if (!forceRefresh && isCacheValid(cacheKey)) {
            const cachedData = getCache(cacheKey);
            pagination.value = cachedData.pagination;
            return cachedData.groups;
        }

        setLoading('fetch_groups', true);

        try {
            const data = await groupService.getCourseGroups(courseId, { page, perPage });

            // Update groups Map
            data.data.forEach(group => {
                groups.value.set(group.id, group);
            });

            // Update pagination
            pagination.value = {
                page: data.current_page,
                perPage: data.per_page,
                total: data.total,
            };

            // Cache the result
            setCache(cacheKey, {
                groups: data.data,
                pagination: pagination.value,
            });

            return data.data;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('fetch_groups', false);
        }
    };

    /**
     * Create a new group
     */
    const createGroup = async (courseId, payload) => {
        setLoading('create_group', true);

        try {
            const newGroup = await groupService.createGroup(courseId, payload);

            // Add to local state
            groups.value.set(newGroup.id, newGroup);

            // Clear cache
            clearCache(`course_groups_${courseId}`);

            return newGroup;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading('create_group', false);
        }
    };

    /**
     * Update an existing group
     */
    const updateGroup = async (groupId, payload) => {
        setLoading(`update_group_${groupId}`, true);

        try {
            const updatedGroup = await groupService.updateGroup(groupId, payload);

            // Update local state
            groups.value.set(groupId, updatedGroup);

            // Clear related cache
            clearCache('group_'); // Clear all group cache

            return updatedGroup;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`update_group_${groupId}`, false);
        }
    };

    /**
     * Delete a group
     */
    const deleteGroup = async (groupId) => {
        setLoading(`delete_group_${groupId}`, true);

        try {
            await groupService.deleteGroup(groupId);

            // Remove from local state
            groups.value.delete(groupId);

            // Clear cache
            clearCache('group_');

            return true;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`delete_group_${groupId}`, false);
        }
    };

    /**
     * Add member to group
     */
    const addMemberToGroup = async (groupId, memberId) => {
        setLoading(`add_member_${groupId}`, true);

        try {
            const result = await groupService.addMemberToGroup(groupId, memberId);

            // Update group member count
            const group = groups.value.get(groupId);
            if (group) {
                groups.value.set(groupId, {
                    ...group,
                    membersCount: (group.membersCount || 0) + 1,
                    memberIds: [...(group.memberIds || []), memberId],
                });
            }

            return result;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`add_member_${groupId}`, false);
        }
    };

    /**
     * Remove member from group
     */
    const removeMemberFromGroup = async (groupId, memberId) => {
        setLoading(`remove_member_${groupId}`, true);

        try {
            await groupService.removeMemberFromGroup(groupId, memberId);

            // Update group member count
            const group = groups.value.get(groupId);
            if (group) {
                groups.value.set(groupId, {
                    ...group,
                    membersCount: Math.max((group.membersCount || 0) - 1, 0),
                    memberIds: (group.memberIds || []).filter(id => id !== memberId),
                });
            }

            return true;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`remove_member_${groupId}`, false);
        }
    };

    /**
     * Move member between groups
     */
    const moveMember = async (fromGroupId, toGroupId, memberId) => {
        setLoading(`move_member_${memberId}`, true);

        try {
            const result = await groupService.moveMember(fromGroupId, toGroupId, memberId);

            // Update both groups
            const fromGroup = groups.value.get(fromGroupId);
            const toGroup = groups.value.get(toGroupId);

            if (fromGroup) {
                groups.value.set(fromGroupId, {
                    ...fromGroup,
                    membersCount: Math.max((fromGroup.membersCount || 0) - 1, 0),
                    memberIds: (fromGroup.memberIds || []).filter(id => id !== memberId),
                });
            }

            if (toGroup) {
                groups.value.set(toGroupId, {
                    ...toGroup,
                    membersCount: (toGroup.membersCount || 0) + 1,
                    memberIds: [...(toGroup.memberIds || []), memberId],
                });
            }

            return result;
        } catch (err) {
            error.value = err.message;
            throw err;
        } finally {
            setLoading(`move_member_${memberId}`, false);
        }
    };

    /**
     * Set active group
     */
    const setActiveGroup = (groupId) => {
        activeGroupId.value = groupId;
    };

    /**
     * Update pagination
     */
    const updatePagination = (newPagination) => {
        pagination.value = { ...pagination.value, ...newPagination };
    };

    /**
     * Clear all groups
     */
    const clearAll = () => {
        groups.value.clear();
        activeGroupId.value = null;
        pagination.value = { page: 1, perPage: 20, total: 0 };
        clearCache();
        clearLoading();
    };

    return {
        // State
        groups,
        activeGroupId,
        loading,
        error,
        pagination,

        // Getters
        activeGroup,
        groupById,
        groupsArray,
        groupsForSelect,
        paginatedGroups,

        // Loading states
        isLoading,

        // Actions
        fetchGroups,
        createGroup,
        updateGroup,
        deleteGroup,
        addMemberToGroup,
        removeMemberFromGroup,
        moveMember,
        setActiveGroup,
        updatePagination,
        clearAll,
    };
});