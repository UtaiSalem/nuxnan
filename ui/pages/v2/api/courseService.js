/**
 * Course Service - API service for course management
 * 
 * Features:
 * - CRUD operations for courses
 * - Course metadata management
 * - Statistics and analytics
 * - Settings management
 * 
 * Uses useApi() from composables/useApi.ts (auto-imported by Nuxt)
 */

export const courseService = {
    async getCourse(courseId) {
        const api = useApi()
        return await api.get(`/courses/${courseId}`)
    },

    async getCourses(params = {}) {
        const api = useApi()
        return await api.get('/courses', params)
    },

    async createCourse(data) {
        const api = useApi()
        return await api.post('/courses', data)
    },

    async updateCourse(courseId, data) {
        const api = useApi()
        return await api.put(`/courses/${courseId}`, data)
    },

    async deleteCourse(courseId) {
        const api = useApi()
        return await api.del(`/courses/${courseId}`)
    },

    async getCourseStats(courseId) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/stats`)
    },

    async getCourseAnnouncements(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/announcements`, params)
    },

    async createAnnouncement(courseId, data) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/announcements`, data)
    },

    async updateAnnouncement(announcementId, data) {
        const api = useApi()
        return await api.put(`/announcements/${announcementId}`, data)
    },

    async deleteAnnouncement(announcementId) {
        const api = useApi()
        return await api.del(`/announcements/${announcementId}`)
    },

    async getCourseSettings(courseId) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/settings`)
    },

    async updateCourseSettings(courseId, data) {
        const api = useApi()
        return await api.put(`/courses/${courseId}/settings`, data)
    },

    async getCourseLessons(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/lessons`, params)
    },

    async getCourseAssignments(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/assignments`, params)
    },

    async getCourseQuizzes(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/quizzes`, params)
    },

    async getCourseAttendance(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/attendance`, params)
    },

    async getCourseGrades(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/grades`, params)
    },

    async getCourseActivity(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/activity`, params)
    },

    async searchCourses(query, params = {}) {
        const api = useApi()
        return await api.get('/courses/search', { q: query, ...params })
    },

    async getCoursePermissions(courseId) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/permissions`)
    },

    async updateCoursePermissions(courseId, permissions) {
        const api = useApi()
        return await api.put(`/courses/${courseId}/permissions`, { permissions })
    },

    async exportCourseData(courseId, format = 'csv', params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/export`, { format, ...params })
    },

    async duplicateCourse(courseId, data) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/duplicate`, data)
    },

    async archiveCourse(courseId) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/archive`)
    },

    async restoreCourse(courseId) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/restore`)
    },

    async getEnrollmentRequests(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/enrollment-requests`, params)
    },

    async approveEnrollment(courseId, requestId, data = {}) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/enrollment-requests/${requestId}/approve`, data)
    },

    async rejectEnrollment(courseId, requestId, data = {}) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/enrollment-requests/${requestId}/reject`, data)
    },

    async getCourseCalendar(courseId, params = {}) {
        const api = useApi()
        return await api.get(`/courses/${courseId}/calendar`, params)
    },

    async createCalendarEvent(courseId, data) {
        const api = useApi()
        return await api.post(`/courses/${courseId}/calendar`, data)
    },

    async updateCalendarEvent(eventId, data) {
        const api = useApi()
        return await api.put(`/calendar-events/${eventId}`, data)
    },

    async deleteCalendarEvent(eventId) {
        const api = useApi()
        return await api.del(`/calendar-events/${eventId}`)
    },
}
