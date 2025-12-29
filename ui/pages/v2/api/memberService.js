/**
 * Member Service - API service for course members
 * 
 * Features:
 * - CRUD operations for members
 * - Role management
 * - Bulk operations
 * - Search and filtering
 * 
 * Uses useApi() from composables/useApi.ts (auto-imported by Nuxt)
 */

export const memberService = {
    async getCourseMembers(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/members`, params)
    },

    async getMember(memberId) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}`)
    },

    async inviteMember(courseId, data) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/members/invite`, data)
    },

    async bulkInviteMembers(courseId, members) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/members/bulk-invite`, { members })
    },

    async importMembers(courseId, file) {
        const api = useApi()
        const formData = new FormData()
        formData.append('file', file)
        return await api.post(`/api/courses/${courseId}/members/import`, formData)
    },

    async updateMemberRole(memberId, role) {
        const api = useApi()
        return await api.put(`/api/members/${memberId}/role`, { role })
    },

    async bulkUpdateRoles(updates) {
        const api = useApi()
        return await api.post(`/api/members/bulk-update-roles`, { updates })
    },

    async updateMemberStatus(memberId, status) {
        const api = useApi()
        return await api.put(`/api/members/${memberId}/status`, { status })
    },

    async removeMember(courseId, memberId) {
        const api = useApi()
        return await api.del(`/api/courses/${courseId}/members/${memberId}`)
    },

    async bulkRemoveMembers(courseId, memberIds) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/members/bulk-remove`, { member_ids: memberIds })
    },

    async getMemberProfile(memberId) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/profile`)
    },

    async updateMemberProfile(memberId, data) {
        const api = useApi()
        return await api.put(`/api/members/${memberId}/profile`, data)
    },

    async getMemberActivity(memberId, params = {}) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/activity`, params)
    },

    async getMemberStats(memberId) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/stats`)
    },

    async searchMembers(courseId, query, params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/members/search`, { q: query, ...params })
    },

    async getMembersByGroup(courseId, groupId, params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/groups/${groupId}/members`, params)
    },

    async getMembersWithoutGroup(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/members/ungrouped`, params)
    },

    async getMemberProgress(memberId) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/progress`)
    },

    async updateMemberProgress(memberId, data) {
        const api = useApi()
        return await api.put(`/api/members/${memberId}/progress`, data)
    },

    async getMemberGrades(memberId) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/grades`)
    },

    async getMemberAttendance(memberId, params = {}) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/attendance`, params)
    },

    async getMemberSubmissions(memberId, params = {}) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/submissions`, params)
    },

    async getMemberBadges(memberId) {
        const api = useApi()
        return await api.get(`/api/members/${memberId}/badges`)
    },

    async awardBadge(memberId, badgeId) {
        const api = useApi()
        return await api.post(`/api/members/${memberId}/badges`, { badge_id: badgeId })
    },

    async revokeBadge(memberId, badgeId) {
        const api = useApi()
        return await api.del(`/api/members/${memberId}/badges/${badgeId}`)
    },

    async sendNotification(memberIds, notification) {
        const api = useApi()
        return await api.post(`/api/members/notify`, { member_ids: memberIds, ...notification })
    },

    async exportMembers(courseId, format = 'csv', params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/members/export`, { format, ...params })
    },
}
