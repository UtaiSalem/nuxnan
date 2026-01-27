/**
 * Course Routes - Vue Router configuration for v2 course pages
 * 
 * Features:
 * - Dynamic nested routes for courses
 * - Lazy loading for better performance
 * - Route guards for permissions
 */

import { createRouter, createWebHistory } from 'vue-router';

// Lazy load components for better performance
const Dashboard = () => import('@/v2/pages/Course/Dashboard.vue');
const Announcement = () => import('@/v2/pages/Course/Announcement.vue');
const Attendance = () => import('@/v2/pages/Course/Attendance.vue');
const Lessons = () => import('@/v2/pages/Course/Lessons.vue');
const Assignments = () => import('@/v2/pages/Course/Assignments.vue');
const Exams = () => import('@/v2/pages/Course/Exams.vue');
const Groups = () => import('@/v2/pages/Course/Groups.vue');
const Members = () => import('@/v2/pages/Course/Members.vue');
const Settings = () => import('@/v2/pages/Course/Settings.vue');
const Summary = () => import('@/v2/pages/Course/Summary.vue');

const CourseLayout = () => import('@/v2/layouts/CourseLayout.vue');

// Route guard for course access
const requireCourseAccess = (to, from, next) => {
    // This would check if user has access to the course
    // For now, we'll just pass through
    next();
};

const routes = [
    {
        path: '/course/:courseId',
        component: CourseLayout,
        beforeEnter: requireCourseAccess,
        children: [
            {
                path: '',
                name: 'Course.Dashboard',
                component: Dashboard,
                meta: {
                    title: 'แดชบอร์ด',
                    icon: 'dashboard',
                    requiresAuth: true,
                },
            },
            {
                path: 'announcements',
                name: 'Course.Announcements',
                component: Announcement,
                meta: {
                    title: 'ประกาศ',
                    icon: 'announcement',
                    requiresAuth: true,
                },
            },
            {
                path: 'attendance',
                name: 'Course.Attendance',
                component: Attendance,
                meta: {
                    title: 'การเข้าเรียน',
                    icon: 'attendance',
                    requiresAuth: true,
                },
            },
            {
                path: 'lessons',
                name: 'Course.Lessons',
                component: Lessons,
                meta: {
                    title: 'บทเรียน',
                    icon: 'lessons',
                    requiresAuth: true,
                },
            },
            {
                path: 'assignments',
                name: 'Course.Assignments',
                component: Assignments,
                meta: {
                    title: 'การบ้าน',
                    icon: 'assignments',
                    requiresAuth: true,
                },
            },
            {
                path: 'exams',
                name: 'Course.Exams',
                component: Exams,
                meta: {
                    title: 'ข้อสอบ',
                    icon: 'exams',
                    requiresAuth: true,
                },
            },
            {
                path: 'groups',
                name: 'Course.Groups',
                component: Groups,
                meta: {
                    title: 'กลุ่มเรียน',
                    icon: 'groups',
                    requiresAuth: true,
                },
            },
            {
                path: 'members',
                name: 'Course.Members',
                component: Members,
                meta: {
                    title: 'สมาชิก',
                    icon: 'members',
                    requiresAuth: true,
                },
            },
            {
                path: 'settings',
                name: 'Course.Settings',
                component: Settings,
                meta: {
                    title: 'ตั้งค่า',
                    icon: 'settings',
                    requiresAuth: true,
                    requiresAdmin: true,
                },
            },
            {
                path: 'summary',
                name: 'Course.Summary',
                component: Summary,
                meta: {
                    title: 'สรุป',
                    icon: 'summary',
                    requiresAuth: true,
                },
            },
        ],
    },
];

// Create router instance
const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    },
});

// Navigation guards
router.beforeEach((to, from, next) => {
    // Set page title
    if (to.meta.title) {
        document.title = `${to.meta.title} - Plearnd`;
    }

    // Check authentication
    if (to.meta.requiresAuth) {
        // This would check if user is authenticated
        // For now, we'll just pass through
    }

    // Check admin permissions
    if (to.meta.requiresAdmin) {
        // This would check if user is admin
        // For now, we'll just pass through
    }

    next();
});

export default router;