/**
 * Group Service - API service for course groups
 * 
 * Features:
 * - CRUD operations for groups
 * - Member management within groups
 * - Bulk operations
 * - Search and filtering
 */

import { useApi } from '@/Pages/v2/stores/composables/useApi';

const { get, post, put, del } = useApi();

export const groupService = {
    /**
     * Get groups for a course
     */
    async getCourseGroups(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/groups`, params);
        return response;
    },

    /**
     * Get single group
     */
    async getGroup(groupId) {
        const response = await get(`/groups/${groupId}`);
        return response;
    },

    /**
     * Create a new group
     */
    async createGroup(courseId, data) {
        const response = await post(`/courses/${courseId}/groups`, data);
        return response;
    },

    /**
     * Update an existing group
     */
    async updateGroup(groupId, data) {
        const response = await put(`/groups/${groupId}`, data);
        return response;
    },

    /**
     * Delete a group
     */
    async deleteGroup(groupId) {
        const response = await del(`/groups/${groupId}`);
        return response;
    },

    /**
     * Get members of a group
     */
    async getGroupMembers(groupId, params = {}) {
        const response = await get(`/groups/${groupId}/members`, params);
        return response;
    },

    /**
     * Add member to group
     */
    async addMemberToGroup(groupId, memberId) {
        const response = await post(`/groups/${groupId}/members`, {
            member_id: memberId
        });
        return response;
    },

    /**
     * Remove member from group
     */
    async removeMemberFromGroup(groupId, memberId) {
        const response = await del(`/groups/${groupId}/members/${memberId}`);
        return response;
    },

    /**
     * Move member between groups
     */
    async moveMember(fromGroupId, toGroupId, memberId) {
        const response = await post(`/groups/move-member`, {
            from_group_id: fromGroupId,
            to_group_id: toGroupId,
            member_id: memberId,
        });
        return response;
    },

    /**
     * Bulk add members to group
     */
    async bulkAddMembersToGroup(groupId, memberIds) {
        const response = await post(`/groups/${groupId}/members/bulk-add`, {
            member_ids: memberIds
        });
        return response;
    },

    /**
     * Bulk remove members from group
     */
    async bulkRemoveMembersFromGroup(groupId, memberIds) {
        const response = await post(`/groups/${groupId}/members/bulk-remove`, {
            member_ids: memberIds
        });
        return response;
    },

    /**
     * Get group statistics
     */
    async getGroupStats(groupId) {
        const response = await get(`/groups/${groupId}/stats`);
        return response;
    },

    /**
     * Get group activity log
     */
    async getGroupActivity(groupId, params = {}) {
        const response = await get(`/groups/${groupId}/activity`, params);
        return response;
    },

    /**
     * Assign group leader
     */
    async assignGroupLeader(groupId, memberId) {
        const response = await put(`/groups/${groupId}/leader`, {
            member_id: memberId
        });
        return response;
    },

    /**
     * Remove group leader
     */
    async removeGroupLeader(groupId) {
        const response = await del(`/groups/${groupId}/leader`);
        return response;
    },

    /**
     * Get group assignments
     */
    async getGroupAssignments(groupId, params = {}) {
        const response = await get(`/groups/${groupId}/assignments`, params);
        return response;
    },

    /**
     * Export group data
     */
    async exportGroupData(groupId, format = 'csv', params = {}) {
        const response = await get(`/groups/${groupId}/export`, {
            format,
            ...params
        });
        return response;
    },

    /**
     * Search groups
     */
    async searchGroups(courseId, query, params = {}) {
        const response = await get(`/courses/${courseId}/groups/search`, {
            q: query,
            ...params
        });
        return response;
    },

    /**
     * Get group permissions
     */
    async getGroupPermissions(groupId) {
        const response = await get(`/groups/${groupId}/permissions`);
        return response;
    },

    /**
     * Update group permissions
     */
    async updateGroupPermissions(groupId, permissions) {
        const response = await put(`/groups/${groupId}/permissions`, {
            permissions
        });
        return response;
    },

    /**
     * Get group attendance summary
     */
    async getGroupAttendanceSummary(groupId, params = {}) {
        const response = await get(`/groups/${groupId}/attendance-summary`, params);
        return response;
    },

    /**
     * Get group grades summary
     */
    async getGroupGradesSummary(groupId, params = {}) {
        const response = await get(`/groups/${groupId}/grades-summary`, params);
        return response;
    },
};