/**
 * Member Service - API service for course members
 * 
 * Features:
 * - CRUD operations for members
 * - Role management
 * - Bulk operations
 * - Search and filtering
 */

import { useApi } from '@/Pages/v2/stores/composables/useApi';

const { get, post, put, del, upload } = useApi();

export const memberService = {
    /**
     * Get members for a course
     */
    async getCourseMembers(courseId, params = {}) {
        const response = await get(`/api/courses/${courseId}/members`, params);
        return response;
    },

    /**
     * Get single member
     */
    async getMember(memberId) {
        const response = await get(`/api/members/${memberId}`);
        return response;
    },

    /**
     * Invite single member
     */
    async inviteMember(courseId, data) {
        const response = await post(`/api/courses/${courseId}/members/invite`, data);
        return response;
    },

    /**
     * Bulk invite members
     */
    async bulkInviteMembers(courseId, members) {
        const response = await post(`/api/courses/${courseId}/members/bulk-invite`, {
            members
        });
        return response;
    },

    /**
     * Import members from CSV
     */
    async importMembers(courseId, file) {
        const formData = new FormData();
        formData.append('file', file);
        
        const response = await upload(`/api/courses/${courseId}/members/import`, formData);
        return response;
    },

    /**
     * Update member role
     */
    async updateMemberRole(memberId, role) {
        const response = await put(`/api/members/${memberId}/role`, {
            role
        });
        return response;
    },

    /**
     * Bulk update member roles
     */
    async bulkUpdateRoles(updates) {
        const response = await post(`/api/members/bulk-update-roles`, {
            updates
        });
        return response;
    },

    /**
     * Update member status
     */
    async updateMemberStatus(memberId, status) {
        const response = await put(`/api/members/${memberId}/status`, {
            status
        });
        return response;
    },

    /**
     * Remove member from course
     */
    async removeMember(courseId, memberId) {
        const response = await del(`/api/courses/${courseId}/members/${memberId}`);
        return response;
    },

    /**
     * Bulk remove members
     */
    async bulkRemoveMembers(courseId, memberIds) {
        const response = await post(`/api/courses/${courseId}/members/bulk-remove`, {
            member_ids: memberIds
        });
        return response;
    },

    /**
     * Get member profile
     */
    async getMemberProfile(memberId) {
        const response = await get(`/api/members/${memberId}/profile`);
        return response;
    },

    /**
     * Update member profile
     */
    async updateMemberProfile(memberId, data) {
        const response = await put(`/api/members/${memberId}/profile`, data);
        return response;
    },

    /**
     * Get member activity log
     */
    async getMemberActivity(memberId, params = {}) {
        const response = await get(`/api/members/${memberId}/activity`, params);
        return response;
    },

    /**
     * Get member statistics
     */
    async getMemberStats(memberId) {
        const response = await get(`/api/members/${memberId}/stats`);
        return response;
    },

    /**
     * Search members
     */
    async searchMembers(courseId, query, params = {}) {
        const response = await get(`/api/courses/${courseId}/members/search`, {
            q: query,
            ...params
        });
        return response;
    },

    /**
     * Get member permissions
     */
    async getMemberPermissions(memberId) {
        const response = await get(`/api/members/${memberId}/permissions`);
        return response;
    },

    /**
     * Update member permissions
     */
    async updateMemberPermissions(memberId, permissions) {
        const response = await put(`/api/members/${memberId}/permissions`, {
            permissions
        });
        return response;
    },

    /**
     * Export members
     */
    async exportMembers(courseId, format = 'csv', params = {}) {
        const response = await get(`/api/courses/${courseId}/members/export`, {
            format,
            ...params
        });
        return response;
    },

    /**
     * Get pending member requests
     */
    async getPendingRequests(courseId) {
        const response = await get(`/api/courses/${courseId}/members/pending`);
        return response;
    },

    /**
     * Approve member request
     */
    async approveMemberRequest(courseId, requestId, groupId = null) {
        const response = await post(`/api/courses/${courseId}/members/approve`, {
            request_id: requestId,
            group_id: groupId
        });
        return response;
    },

    /**
     * Reject member request
     */
    async rejectMemberRequest(courseId, requestId, reason = '') {
        const response = await post(`/api/courses/${courseId}/members/reject`, {
            request_id: requestId,
            reason
        });
        return response;
    },

    /**
     * Send reminder to inactive members
     */
    async sendReminder(courseId, memberIds) {
        const response = await post(`/api/courses/${courseId}/members/reminder`, {
            member_ids: memberIds
        });
        return response;
    },

    /**
     * Get member attendance summary
     */
    async getMemberAttendanceSummary(memberId, params = {}) {
        const response = await get(`/api/members/${memberId}/attendance-summary`, params);
        return response;
    },

    /**
     * Get member assignment grades
     */
    async getMemberGrades(memberId, params = {}) {
        const response = await get(`/api/members/${memberId}/grades`, params);
        return response;
    },
};