/**
 * Course Service - จัดการ API calls สำหรับ Course
 * 
 * แยก API logic ออกจาก Store เพื่อ:
 * - ง่ายต่อการ test และ mock
 * - Reusable
 * - Centralized API endpoints
 */

import axios from 'axios';

export const courseService = {
    /**
     * ดึงข้อมูลรายวิชาเดียว
     */
    async getCourse(courseId) {
        const response = await axios.get(`/api/courses/${courseId}`);
        return response.data;
    },
    
    /**
     * ดึงรายการรายวิชา
     */
    async getCourses(params = {}) {
        const response = await axios.get('/api/courses', { params });
        return response.data;
    },
    
    /**
     * สร้างรายวิชาใหม่
     */
    async createCourse(data) {
        const response = await axios.post('/api/courses', data);
        return response.data;
    },
    
    /**
     * อัพเดทข้อมูลรายวิชา
     */
    async updateCourse(courseId, data) {
        const response = await axios.patch(`/api/courses/${courseId}`, data);
        return response.data;
    },
    
    /**
     * ลบรายวิชา
     */
    async deleteCourse(courseId) {
        const response = await axios.delete(`/api/courses/${courseId}`);
        return response.data;
    },
    
    /**
     * อัพเดทรูปปก
     */
    async updateCourseCover(courseId, file) {
        const formData = new FormData();
        formData.append('cover', file);
        formData.append('_method', 'PATCH');
        
        const response = await axios.post(
            `/courses/${courseId}`, 
            formData,
            { 
                headers: { 
                    'Content-Type': 'multipart/form-data' 
                } 
            }
        );
        return response.data;
    },
    
    /**
     * อัพเดทโลโก้
     */
    async updateCourseLogo(courseId, file) {
        const formData = new FormData();
        formData.append('logo', file);
        formData.append('_method', 'PATCH');
        
        const response = await axios.post(
            `/courses/${courseId}`, 
            formData,
            { 
                headers: { 
                    'Content-Type': 'multipart/form-data' 
                } 
            }
        );
        return response.data;
    },
    
    /**
     * อัพเดทหัวข้อรายวิชา
     */
    async updateCourseHeader(courseId, header) {
        const response = await axios.patch(`/courses/${courseId}`, { 
            name: header 
        });
        return response.data;
    },
    
    /**
     * อัพเดทรหัสวิชา
     */
    async updateCourseSubheader(courseId, subheader) {
        const response = await axios.patch(`/courses/${courseId}`, { 
            code: subheader 
        });
        return response.data;
    },
    
    // ============= Member Management =============
    
    /**
     * ขอเป็นสมาชิกรายวิชา
     */
    async requestMembership(courseId, groupId = null) {
        const response = await axios.post(`/courses/${courseId}/members`, {
            group_id: groupId
        });
        return response.data;
    },
    
    /**
     * ยกเลิกสมาชิก / ออกจากรายวิชา
     */
    async cancelMembership(courseId, memberId) {
        const response = await axios.delete(`/courses/${courseId}/members/${memberId}`);
        return response.data;
    },
    
    /**
     * ดึงรายชื่อสมาชิกในรายวิชา
     */
    async getCourseMembers(courseId, params = {}) {
        const response = await axios.get(`/courses/${courseId}/members`, { params });
        return response.data;
    },
    
    /**
     * อัพเดทสถานะสมาชิก (สำหรับ Admin)
     */
    async updateMemberStatus(courseId, memberId, status) {
        const response = await axios.patch(
            `/courses/${courseId}/members/${memberId}`, 
            { status }
        );
        return response.data;
    },
    
    // ============= Groups =============
    
    /**
     * ดึงกลุ่มในรายวิชา
     */
    async getCourseGroups(courseId) {
        const response = await axios.get(`/courses/${courseId}/groups`);
        return response.data;
    },
    
    /**
     * สร้างกลุ่มใหม่
     */
    async createCourseGroup(courseId, data) {
        const response = await axios.post(`/courses/${courseId}/groups`, data);
        return response.data;
    },
    
    /**
     * อัพเดทกลุ่ม
     */
    async updateCourseGroup(courseId, groupId, data) {
        const response = await axios.patch(
            `/courses/${courseId}/groups/${groupId}`, 
            data
        );
        return response.data;
    },
    
    /**
     * ลบกลุ่ม
     */
    async deleteCourseGroup(courseId, groupId) {
        const response = await axios.delete(`/courses/${courseId}/groups/${groupId}`);
        return response.data;
    },
};
