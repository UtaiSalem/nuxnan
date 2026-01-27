/**
 * Course Service - API service for course management
 * 
 * Features:
 * - CRUD operations for courses
 * - Course metadata management
 * - Statistics and analytics
 * - Settings management
 */

import { useApi } from '@/Pages/v2/stores/composables/useApi';

const { get, post, put, del } = useApi();

export const courseService = {
    /**
     * Get single course
     */
    async getCourse(courseId) {
        const response = await get(`/courses/${courseId}`);
        return response;
    },

    /**
     * Get multiple courses
     */
    async getCourses(params = {}) {
        const response = await get('/courses', params);
        return response;
    },

    /**
     * Create a new course
     */
    async createCourse(data) {
        const response = await post('/courses', data);
        return response;
    },

    /**
     * Update an existing course
     */
    async updateCourse(courseId, data) {
        const response = await put(`/courses/${courseId}`, data);
        return response;
    },

    /**
     * Delete a course
     */
    async deleteCourse(courseId) {
        const response = await del(`/courses/${courseId}`);
        return response;
    },

    /**
     * Get course statistics
     */
    async getCourseStats(courseId) {
        const response = await get(`/courses/${courseId}/stats`);
        return response;
    },

    /**
     * Get course announcements
     */
    async getCourseAnnouncements(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/announcements`, params);
        return response;
    },

    /**
     * Create course announcement
     */
    async createAnnouncement(courseId, data) {
        const response = await post(`/courses/${courseId}/announcements`, data);
        return response;
    },

    /**
     * Update course announcement
     */
    async updateAnnouncement(announcementId, data) {
        const response = await put(`/announcements/${announcementId}`, data);
        return response;
    },

    /**
     * Delete course announcement
     */
    async deleteAnnouncement(announcementId) {
        const response = await del(`/announcements/${announcementId}`);
        return response;
    },

    /**
     * Get course settings
     */
    async getCourseSettings(courseId) {
        const response = await get(`/courses/${courseId}/settings`);
        return response;
    },

    /**
     * Update course settings
     */
    async updateCourseSettings(courseId, data) {
        const response = await put(`/courses/${courseId}/settings`, data);
        return response;
    },

    /**
     * Get course lessons
     */
    async getCourseLessons(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/lessons`, params);
        return response;
    },

    /**
     * Get course assignments
     */
    async getCourseAssignments(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/assignments`, params);
        return response;
    },

    /**
     * Get course quizzes
     */
    async getCourseQuizzes(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/quizzes`, params);
        return response;
    },

    /**
     * Get course attendance data
     */
    async getCourseAttendance(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/attendance`, params);
        return response;
    },

    /**
     * Get course grades summary
     */
    async getCourseGrades(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/grades`, params);
        return response;
    },

    /**
     * Get course activity log
     */
    async getCourseActivity(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/activity`, params);
        return response;
    },

    /**
     * Search courses
     */
    async searchCourses(query, params = {}) {
        const response = await get('/courses/search', {
            q: query,
            ...params
        });
        return response;
    },

    /**
     * Get course permissions
     */
    async getCoursePermissions(courseId) {
        const response = await get(`/courses/${courseId}/permissions`);
        return response;
    },

    /**
     * Update course permissions
     */
    async updateCoursePermissions(courseId, permissions) {
        const response = await put(`/courses/${courseId}/permissions`, {
            permissions
        });
        return response;
    },

    /**
     * Export course data
     */
    async exportCourseData(courseId, format = 'csv', params = {}) {
        const response = await get(`/courses/${courseId}/export`, {
            format,
            ...params
        });
        return response;
    },

    /**
     * Duplicate course
     */
    async duplicateCourse(courseId, data) {
        const response = await post(`/courses/${courseId}/duplicate`, data);
        return response;
    },

    /**
     * Archive course
     */
    async archiveCourse(courseId) {
        const response = await post(`/courses/${courseId}/archive`);
        return response;
    },

    /**
     * Restore archived course
     */
    async restoreCourse(courseId) {
        const response = await post(`/courses/${courseId}/restore`);
        return response;
    },

    /**
     * Get course enrollment requests
     */
    async getEnrollmentRequests(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/enrollment-requests`, params);
        return response;
    },

    /**
     * Approve enrollment request
     */
    async approveEnrollment(courseId, requestId, data = {}) {
        const response = await post(`/courses/${courseId}/enrollment-requests/${requestId}/approve`, data);
        return response;
    },

    /**
     * Reject enrollment request
     */
    async rejectEnrollment(courseId, requestId, data = {}) {
        const response = await post(`/courses/${courseId}/enrollment-requests/${requestId}/reject`, data);
        return response;
    },

    /**
     * Get course calendar events
     */
    async getCourseCalendar(courseId, params = {}) {
        const response = await get(`/courses/${courseId}/calendar`, params);
        return response;
    },

    /**
     * Create course calendar event
     */
    async createCalendarEvent(courseId, data) {
        const response = await post(`/courses/${courseId}/calendar`, data);
        return response;
    },

    /**
     * Update course calendar event
     */
    async updateCalendarEvent(eventId, data) {
        const response = await put(`/calendar-events/${eventId}`, data);
        return response;
    },

    /**
     * Delete course calendar event
     */
    async deleteCalendarEvent(eventId) {
        const response = await del(`/calendar-events/${eventId}`);
        return response;
    },
};