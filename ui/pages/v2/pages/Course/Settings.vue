<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourseStore } from '@/pages/v2/store/modules/course/courseStore';
import CourseHeader from '@/pages/v2/components/course/CourseHeader.vue';
import CourseSidebar from '@/pages/v2/components/course/CourseSidebar.vue';

const props = defineProps({
    courseId: {
        type: Number,
        required: true,
    },
});

// Stores
const courseStore = useCourseStore();

// State
const loading = ref(true);
const error = ref(null);
const saving = ref(false);
const activeTab = ref('general');
const courseSettings = ref({
    // General Settings
    name: '',
    code: '',
    description: '',
    category: '',
    language: 'th',
    timezone: 'Asia/Bangkok',
    
    // Enrollment Settings
    enrollment_type: 'open', // 'open', 'closed', 'approval'
    max_students: 0,
    enrollment_start_date: '',
    enrollment_end_date: '',
    allow_guest_access: false,
    
    // Visibility Settings
    is_public: true,
    is_searchable: true,
    show_in_catalog: true,
    
    // Notification Settings
    enable_notifications: true,
    notify_on_enrollment: true,
    notify_on_submission: true,
    notify_on_completion: true,
    
    // Grading Settings
    grading_system: 'percentage', // 'percentage', 'points', 'letter'
    passing_grade: 60,
    show_grades_to_students: true,
    allow_grade_review: true,
    
    // Attendance Settings
    enable_attendance: true,
    attendance_method: 'manual', // 'manual', 'auto', 'hybrid'
    auto_mark_absent_after: 15, // minutes
    
    // Content Settings
    allow_content_download: true,
    enable_content_versioning: false,
    default_content_visibility: 'published', // 'published', 'draft'
    
    // Security Settings
    require_two_factor: false,
    session_timeout: 120, // minutes
    ip_restriction: '',
    allowed_domains: '',
});

// Computed
const course = computed(() => courseStore.currentCourse);

const hasChanges = computed(() => {
    // This would compare with original settings
    return true; // Simplified for now
});

// Methods
const loadSettings = async () => {
    try {
        loading.value = true;
        
        // Initialize course
        await courseStore.init(props.courseId);
        
        // This would call API
        // courseSettings.value = await courseStore.fetchCourseSettings(props.courseId);
        
        // Mock data for now
        courseSettings.value = {
            name: course.value?.name || 'Introduction to Programming',
            code: course.value?.code || 'CS101',
            description: course.value?.description || 'Basic programming concepts and fundamentals',
            category: 'Computer Science',
            language: 'th',
            timezone: 'Asia/Bangkok',
            
            enrollment_type: 'open',
            max_students: 50,
            enrollment_start_date: '2023-11-01',
            enrollment_end_date: '2023-12-31',
            allow_guest_access: false,
            
            is_public: true,
            is_searchable: true,
            show_in_catalog: true,
            
            enable_notifications: true,
            notify_on_enrollment: true,
            notify_on_submission: true,
            notify_on_completion: true,
            
            grading_system: 'percentage',
            passing_grade: 60,
            show_grades_to_students: true,
            allow_grade_review: true,
            
            enable_attendance: true,
            attendance_method: 'manual',
            auto_mark_absent_after: 15,
            
            allow_content_download: true,
            enable_content_versioning: false,
            default_content_visibility: 'published',
            
            require_two_factor: false,
            session_timeout: 120,
            ip_restriction: '',
            allowed_domains: '',
        };
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load settings:', err);
    } finally {
        loading.value = false;
    }
};

const saveSettings = async () => {
    try {
        saving.value = true;
        
        // This would call API
        // await courseStore.updateCourseSettings(props.courseId, courseSettings.value);
        
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        alert('บันทึกการตั้งค่าเรียบร้อยแล้ว');
    } catch (err) {
        console.error('Failed to save settings:', err);
        alert('เกิดข้อผิดพลาดในการบันทึกการตั้งค่า');
    } finally {
        saving.value = false;
    }
};

const resetSettings = () => {
    if (confirm('คุณแน่ใจหรือไม่ที่จะรีเซ็ตการตั้งค่าเป็นค่าเริ่มต้น?')) {
        loadSettings();
    }
};

const exportCourse = async () => {
    try {
        // This would call API
        // await courseStore.exportCourse(props.courseId);
        alert('ระบบจะเริ่มการส่งออกข้อมูลรายวิชา');
    } catch (err) {
        console.error('Failed to export course:', err);
    }
};

const archiveCourse = async () => {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะเก็บถาวัตัวรายวิชานี้? รายวิชาจะไม่สามารถเข้าถึงได้ชั่วคราวง')) {
        return;
    }
    
    try {
        // This would call API
        // await courseStore.archiveCourse(props.courseId);
        alert('เก็บถาวัตัวรายวิชาเรียบร้อยแล้ว');
    } catch (err) {
        console.error('Failed to archive course:', err);
    }
};

const deleteCourse = async () => {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะลบรายวิชานี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        return;
    }
    
    if (!confirm('โปรดพิมพ์ "DELETE" เพื่อยืนยันการลบรายวิชา')) {
        return;
    }
    
    try {
        // This would call API
        // await courseStore.deleteCourse(props.courseId);
        alert('ลบรายวิชาเรียบร้อยแล้ว');
        // Redirect to courses list
        window.location.href = '/courses';
    } catch (err) {
        console.error('Failed to delete course:', err);
    }
};

// Load data on mount
onMounted(async () => {
    await loadSettings();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="ตั้งค่า"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'settings'"
            />

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 00016zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293a1 1 0 00-1.414-1.414L10 8.586 7.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                {{ error }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div v-else class="space-y-6">
                    <!-- Page Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900">
                                ตั้งค่ารายวิชา: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                จัดการการตั้งค่าและการกำหนดค่ารายวิชา
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button
                                @click="resetSettings"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                รีเซ็ต
                            </button>
                            <button
                                @click="saveSettings"
                                :disabled="saving"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                            >
                                <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ saving ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
                            </button>
                        </div>
                    </div>

                    <!-- Settings Tabs -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                                <button
                                    v-for="tab in [
                                        { key: 'general', name: 'ทั่วไป' },
                                        { key: 'enrollment', name: 'การลงทะเบียน' },
                                        { key: 'visibility', name: 'การมองเห็น' },
                                        { key: 'notifications', name: 'การแจ้งเตือน' },
                                        { key: 'grading', name: 'การให้คะแนน' },
                                        { key: 'attendance', name: 'การเข้าเรียน' },
                                        { key: 'content', name: 'เนื้อหา' },
                                        { key: 'security', name: 'ความปลอดภัย' },
                                        { key: 'danger', name: 'อันตราย' }
                                    ]"
                                    :key="tab.key"
                                    @click="activeTab = tab.key"
                                    class="py-4 px-1 border-b-2 font-medium text-sm"
                                    :class="activeTab === tab.key
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                >
                                    {{ tab.name }}
                                </button>
                            </nav>
                        </div>

                        <div class="p-6">
                            <!-- General Settings -->
                            <div v-if="activeTab === 'general'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700">
                                            ชื่อรายวิชา
                                        </label>
                                        <input
                                            id="name"
                                            v-model="courseSettings.name"
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="code" class="block text-sm font-medium text-gray-700">
                                            รหัสรายวิชา
                                        </label>
                                        <input
                                            id="code"
                                            v-model="courseSettings.code"
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        คำอธิบายรายวิชา
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="courseSettings.description"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700">
                                            หมวดหมู่
                                        </label>
                                        <select
                                            id="category"
                                            v-model="courseSettings.category"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="Computer Science">Computer Science</option>
                                            <option value="Mathematics">Mathematics</option>
                                            <option value="Science">Science</option>
                                            <option value="Language">Language</option>
                                            <option value="Arts">Arts</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="language" class="block text-sm font-medium text-gray-700">
                                            ภาษา
                                        </label>
                                        <select
                                            id="language"
                                            v-model="courseSettings.language"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="th">ไทย</option>
                                            <option value="en">English</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="timezone" class="block text-sm font-medium text-gray-700">
                                            เขตเวลา
                                        </label>
                                        <select
                                            id="timezone"
                                            v-model="courseSettings.timezone"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="Asia/Bangkok">Asia/Bangkok (GMT+7)</option>
                                            <option value="UTC">UTC</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Enrollment Settings -->
                            <div v-if="activeTab === 'enrollment'" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        ประเภทการลงทะเบียน
                                    </label>
                                    <div class="mt-2 space-y-2">
                                        <label class="flex items-center">
                                            <input
                                                v-model="courseSettings.enrollment_type"
                                                value="open"
                                                type="radio"
                                                class="border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">เปิด - ใครก็สามารถลงทะเบียนได้</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                v-model="courseSettings.enrollment_type"
                                                value="closed"
                                                type="radio"
                                                class="border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">ปิด - ต้องได้รับเชิญเท่านั้น</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input
                                                v-model="courseSettings.enrollment_type"
                                                value="approval"
                                                type="radio"
                                                class="border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">ต้องการอนุมัติ - ต้องได้รับการอนุมัติจากผู้สอน</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="max_students" class="block text-sm font-medium text-gray-700">
                                            จำนวนนักเรียนสูงสุด (0 = ไม่จำกัด)
                                        </label>
                                        <input
                                            id="max_students"
                                            v-model.number="courseSettings.max_students"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label class="flex items-center pt-6">
                                            <input
                                                v-model="courseSettings.allow_guest_access"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">อนุญาตให้ผู้เยี่ยมชมเข้าถึง</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="enrollment_start_date" class="block text-sm font-medium text-gray-700">
                                            วันที่เริ่มลงทะเบียน
                                        </label>
                                        <input
                                            id="enrollment_start_date"
                                            v-model="courseSettings.enrollment_start_date"
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="enrollment_end_date" class="block text-sm font-medium text-gray-700">
                                            วันที่สิ้นสุดการลงทะเบียน
                                        </label>
                                        <input
                                            id="enrollment_end_date"
                                            v-model="courseSettings.enrollment_end_date"
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Visibility Settings -->
                            <div v-if="activeTab === 'visibility'" class="space-y-6">
                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.is_public"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">ทำให้รายวิชาเป็นสาธารณะ</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.is_searchable"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">อนุญาตให้ค้นหารายวิชานี้</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.show_in_catalog"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">แสดงในรายการรายวิชา</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Notification Settings -->
                            <div v-if="activeTab === 'notifications'" class="space-y-6">
                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.enable_notifications"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานการแจ้งเตือน</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.notify_on_enrollment"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">แจ้งเตือนเมื่อมีนักเรียนลงทะเบียน</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.notify_on_submission"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">แจ้งเตือนเมื่อมีการส่งงาน</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.notify_on_completion"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">แจ้งเตือนเมื่อนักเรียนเรียนจบรายวิชา</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Grading Settings -->
                            <div v-if="activeTab === 'grading'" class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="grading_system" class="block text-sm font-medium text-gray-700">
                                            ระบบการให้คะแนน
                                        </label>
                                        <select
                                            id="grading_system"
                                            v-model="courseSettings.grading_system"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="percentage">เปอร์เซ็นต์</option>
                                            <option value="points">คะแนน</option>
                                            <option value="letter">เกรด (A, B, C, ...)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="passing_grade" class="block text-sm font-medium text-gray-700">
                                            เกรดที่ผ่านขั้นต่ำ
                                        </label>
                                        <input
                                            id="passing_grade"
                                            v-model.number="courseSettings.passing_grade"
                                            type="number"
                                            min="0"
                                            max="100"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.show_grades_to_students"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">แสดงเกรดให้นักเรียนเห็น</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.allow_grade_review"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">อนุญาตให้นักเรียนขอทบทวนคะแนน</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Attendance Settings -->
                            <div v-if="activeTab === 'attendance'" class="space-y-6">
                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.enable_attendance"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานเช็คชื่อ</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="attendance_method" class="block text-sm font-medium text-gray-700">
                                            วิธีการเช็คชื่อ
                                        </label>
                                        <select
                                            id="attendance_method"
                                            v-model="courseSettings.attendance_method"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="manual">เช็คชื่อด้วยตนเอง</option>
                                            <option value="auto">อัตโนมัติ</option>
                                            <option value="hybrid">ผสม</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="auto_mark_absent_after" class="block text-sm font-medium text-gray-700">
                                            ทำเครื่องขาดหลัง (นาที)
                                        </label>
                                        <input
                                            id="auto_mark_absent_after"
                                            v-model.number="courseSettings.auto_mark_absent_after"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Content Settings -->
                            <div v-if="activeTab === 'content'" class="space-y-6">
                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.allow_content_download"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">อนุญาตให้ดาวน์โหลดเนื้อหา</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.enable_content_versioning"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">เปิดใช้งานจัดการเวอร์ชันเนื้อหา</span>
                                    </label>
                                </div>

                                <div>
                                    <label for="default_content_visibility" class="block text-sm font-medium text-gray-700">
                                        การมองเห็นเนื้อหาเริ่มต้น
                                    </label>
                                    <select
                                        id="default_content_visibility"
                                        v-model="courseSettings.default_content_visibility"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                        <option value="published">เผยแพร่</option>
                                        <option value="draft">ฉบับร่าง</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Security Settings -->
                            <div v-if="activeTab === 'security'" class="space-y-6">
                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input
                                            v-model="courseSettings.require_two_factor"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">ต้องการยืนยันตัวตนสองปัจจัย (2FA)</span>
                                    </label>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="session_timeout" class="block text-sm font-medium text-gray-700">
                                            หมดเวลาเซสชัน (นาที)
                                        </label>
                                        <input
                                            id="session_timeout"
                                            v-model.number="courseSettings.session_timeout"
                                            type="number"
                                            min="5"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label for="ip_restriction" class="block text-sm font-medium text-gray-700">
                                            จำกัดการเข้าถึงจาก IP (คั่นด้วย ,)
                                        </label>
                                        <input
                                            id="ip_restriction"
                                            v-model="courseSettings.ip_restriction"
                                            type="text"
                                            placeholder="192.168.1.1, 10.0.0.0/8"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="allowed_domains" class="block text-sm font-medium text-gray-700">
                                            โดเมนที่อนุญาติ (คั่นด้วย ,)
                                        </label>
                                        <input
                                            id="allowed_domains"
                                            v-model="courseSettings.allowed_domains"
                                            type="text"
                                            placeholder="university.edu, ac.th"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Danger Zone -->
                            <div v-if="activeTab === 'danger'" class="space-y-6">
                                <div class="border border-red-200 rounded-md p-4">
                                    <h3 class="text-lg font-medium text-red-800 mb-4">
                                        อันตราย - การกระทำเหล่านี้ไม่สามารถย้อนกลับได้
                                    </h3>
                                    
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900">ส่งออกข้อมูลรายวิชา</h4>
                                                <p class="text-sm text-gray-500">ดาวน์โหลดข้อมูลทั้งหมดของรายวิชา</p>
                                            </div>
                                            <button
                                                @click="exportCourse"
                                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                                ส่งออก
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-sm font-medium text-gray-900">เก็บถาวัตัวรายวิชา</h4>
                                                <p class="text-sm text-gray-500">ซ่อนรายวิชาแต่ยังคงข้อมูลไว้</p>
                                            </div>
                                            <button
                                                @click="archiveCourse"
                                                class="inline-flex items-center px-4 py-2 border border-yellow-300 text-sm font-medium rounded-md shadow-sm text-yellow-700 bg-yellow-50 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                            >
                                                เก็บถาวัตัว
                                            </button>
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h4 class="text-sm font-medium text-red-900">ลบรายวิชา</h4>
                                                <p class="text-sm text-red-500">ลบข้อมูลทั้งหมดของรายวิชาถาวัตัว</p>
                                            </div>
                                            <button
                                                @click="deleteCourse"
                                                class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md shadow-sm text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                            >
                                                ลบรายวิชา
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

