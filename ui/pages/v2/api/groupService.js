/**
 * Group Service - API service for course groups
 * 
 * Features:
 * - CRUD operations for groups
 * - Member management within groups
 * - Bulk operations
 * - Statistics and activity
 * 
 * Uses useApi() from composables/useApi.ts (auto-imported by Nuxt)
 */

export const groupService = {
    async getCourseGroups(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/groups`, params)
    },

    async getGroup(groupId) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}`)
    },

    async createGroup(courseId, data) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/groups`, data)
    },

    async updateGroup(groupId, data) {
        const api = useApi()
        return await api.put(`/api/groups/${groupId}`, data)
    },

    async deleteGroup(groupId) {
        const api = useApi()
        return await api.del(`/api/groups/${groupId}`)
    },

    async bulkDeleteGroups(groupIds) {
        const api = useApi()
        return await api.post(`/api/groups/bulk-delete`, { group_ids: groupIds })
    },

    async getGroupMembers(groupId, params = {}) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/members`, params)
    },

    async addMemberToGroup(groupId, memberId) {
        const api = useApi()
        return await api.post(`/api/groups/${groupId}/members`, { member_id: memberId })
    },

    async removeMemberFromGroup(groupId, memberId) {
        const api = useApi()
        return await api.del(`/api/groups/${groupId}/members/${memberId}`)
    },

    async moveMember(memberId, fromGroupId, toGroupId) {
        const api = useApi()
        return await api.post(`/api/members/${memberId}/move`, { 
            from_group_id: fromGroupId, 
            to_group_id: toGroupId 
        })
    },

    async bulkAddMembersToGroup(groupId, memberIds) {
        const api = useApi()
        return await api.post(`/api/groups/${groupId}/members/bulk-add`, { member_ids: memberIds })
    },

    async bulkRemoveMembersFromGroup(groupId, memberIds) {
        const api = useApi()
        return await api.post(`/api/groups/${groupId}/members/bulk-remove`, { member_ids: memberIds })
    },

    async bulkMoveMembersToGroup(memberIds, toGroupId) {
        const api = useApi()
        return await api.post(`/api/groups/${toGroupId}/members/bulk-move`, { member_ids: memberIds })
    },

    async getGroupStats(groupId) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/stats`)
    },

    async getGroupActivity(groupId, params = {}) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/activity`, params)
    },

    async assignGroupLeader(groupId, memberId) {
        const api = useApi()
        return await api.post(`/api/groups/${groupId}/leader`, { member_id: memberId })
    },

    async removeGroupLeader(groupId) {
        const api = useApi()
        return await api.del(`/api/groups/${groupId}/leader`)
    },

    async getGroupProgress(groupId) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/progress`)
    },

    async getGroupGrades(groupId) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/grades`)
    },

    async getGroupAttendance(groupId, params = {}) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/attendance`, params)
    },

    async getGroupSubmissions(groupId, params = {}) {
        const api = useApi()
        return await api.get(`/api/groups/${groupId}/submissions`, params)
    },

    async autoAssignToGroups(courseId, options = {}) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/groups/auto-assign`, options)
    },

    async shuffleGroups(courseId, options = {}) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/groups/shuffle`, options)
    },

    async resetGroups(courseId) {
        const api = useApi()
        return await api.post(`/api/courses/${courseId}/groups/reset`)
    },

    async exportGroups(courseId, format = 'csv', params = {}) {
        const api = useApi()
        return await api.get(`/api/courses/${courseId}/groups/export`, { format, ...params })
    },

    async importGroups(courseId, file) {
        const api = useApi()
        const formData = new FormData()
        formData.append('file', file)
        return await api.post(`/api/courses/${courseId}/groups/import`, formData)
    },

    async duplicateGroup(groupId, newName = null) {
        const api = useApi()
        return await api.post(`/api/groups/${groupId}/duplicate`, { name: newName })
    },

    async mergeGroups(sourceGroupIds, targetGroupId) {
        const api = useApi()
        return await api.post(`/api/groups/${targetGroupId}/merge`, { source_group_ids: sourceGroupIds })
    },

    async splitGroup(groupId, options = {}) {
        const api = useApi()
        return await api.post(`/api/groups/${groupId}/split`, options)
    },

    async setGroupOrder(courseId, groupIds) {
        const api = useApi()
        return await api.put(`/api/courses/${courseId}/groups/order`, { group_ids: groupIds })
    },
}
