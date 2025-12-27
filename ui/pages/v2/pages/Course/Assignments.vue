<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourseStore } from '@/pages/v2/store/modules/course/courseStore';
import { useGroupStore } from '@/pages/v2/store/modules/group/groupStore';
import { useMemberStore } from '@/pages/v2/store/modules/member/memberStore';
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
const groupStore = useGroupStore();
const memberStore = useMemberStore();

// State
const loading = ref(true);
const error = ref(null);
const assignments = ref([]);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const showGradeModal = ref(false);
const editingAssignment = ref(null);
const deletingAssignment = ref(null);
const gradingAssignment = ref(null);
const viewMode = ref('grid'); // 'grid' or 'list'
const selectedStatus = ref('all'); // 'all', 'published', 'draft', 'closed'
const formData = ref({
    title: '',
    description: '',
    instructions: '',
    due_date: '',
    due_time: '23:59',
    max_score: 100,
    allow_late_submission: false,
    is_published: false,
    assigned_groups: [],
    file_types: ['pdf', 'doc', 'docx', 'txt'],
    max_file_size: 10, // MB
});

// Computed
const course = computed(() => courseStore.currentCourse);
const groups = computed(() => groupStore.groupsArray);
const filteredAssignments = computed(() => {
    let filtered = assignments.value;
    
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(a => {
            if (selectedStatus.value === 'published') return a.is_published && !isClosed(a);
            if (selectedStatus.value === 'draft') return !a.is_published;
            if (selectedStatus.value === 'closed') return a.is_published && isClosed(a);
            return true;
        });
    }
    
    return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const publishedAssignments = computed(() => 
    assignments.value.filter(a => a.is_published)
);

const draftAssignments = computed(() => 
    assignments.value.filter(a => !a.is_published)
);

// Methods
const isClosed = (assignment) => {
    if (!assignment.due_date) return false;
    return new Date(`${assignment.due_date} ${assignment.due_time || '23:59'}`) < new Date();
};

const loadAssignments = async () => {
    try {
        loading.value = true;
        
        // Load groups
        await groupStore.fetchGroups(props.courseId);
        
        // This would call API
        // assignments.value = await courseStore.fetchAssignments(props.courseId);
        
        // Mock data for now
        assignments.value = [
            {
                id: 1,
                title: 'การบ้านที่ 1: พื้นฐานการเขียนโปรแกรม',
                description: 'สร้างโปรแกรมง่ายๆ เพื่อทดสอบความเข้าใจพื้นฐาน',
                instructions: '1. สร้างโปรแกรมที่รับข้อมูลจากผู้ใช้\n2. ประมวลผลและแสดงผลลัพธ์\n3. ส่งไฟล์โค้ดและผลลัพธ์',
                due_date: '2023-12-15',
                due_time: '23:59',
                max_score: 100,
                allow_late_submission: true,
                is_published: true,
                assigned_groups: [1, 2],
                file_types: ['pdf', 'doc', 'docx', 'zip'],
                max_file_size: 10,
                submissions_count: 15,
                total_students: 20,
                created_at: '2023-12-01T10:00:00Z',
                updated_at: '2023-12-01T10:00:00Z',
            },
            {
                id: 2,
                title: 'การบ้านที่ 2: โครงสร้างข้อมูล',
                description: 'ฝึกใช้โครงสร้างข้อมูลต่างๆ ในการแก้ปัญหา',
                instructions: 'เขียนโปรแกรมที่ใช้ array, list, หรือ dictionary',
                due_date: '2023-12-20',
                due_time: '23:59',
                max_score: 100,
                allow_late_submission: false,
                is_published: true,
                assigned_groups: [1],
                file_types: ['pdf', 'doc', 'docx', 'zip'],
                max_file_size: 15,
                submissions_count: 8,
                total_students: 10,
                created_at: '2023-12-05T14:30:00Z',
                updated_at: '2023-12-05T14:30:00Z',
            },
            {
                id: 3,
                title: 'การบ้านที่ 3: ฟังก์ชันและโมดูล',
                description: 'ฝึกสร้างและใช้ฟังก์ชันในการเขียนโปรแกรม',
                instructions: 'สร้างฟังก์ชันที่มีพารามิเตอร์และคืนค่า',
                due_date: '',
                due_time: '23:59',
                max_score: 100,
                allow_late_submission: true,
                is_published: false,
                assigned_groups: [],
                file_types: ['pdf', 'doc', 'docx', 'zip'],
                max_file_size: 10,
                submissions_count: 0,
                total_students: 0,
                created_at: '2023-12-10T09:15:00Z',
                updated_at: '2023-12-10T09:15:00Z',
            }
        ];
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load assignments:', err);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingAssignment.value = null;
    formData.value = {
        title: '',
        description: '',
        instructions: '',
        due_date: '',
        due_time: '23:59',
        max_score: 100,
        allow_late_submission: false,
        is_published: false,
        assigned_groups: [],
        file_types: ['pdf', 'doc', 'docx', 'txt'],
        max_file_size: 10,
    };
    showCreateModal.value = true;
};

const openEditModal = (assignment) => {
    editingAssignment.value = assignment;
    formData.value = {
        title: assignment.title,
        description: assignment.description,
        instructions: assignment.instructions,
        due_date: assignment.due_date,
        due_time: assignment.due_time || '23:59',
        max_score: assignment.max_score,
        allow_late_submission: assignment.allow_late_submission,
        is_published: assignment.is_published,
        assigned_groups: assignment.assigned_groups || [],
        file_types: assignment.file_types || ['pdf', 'doc', 'docx', 'txt'],
        max_file_size: assignment.max_file_size || 10,
    };
    showCreateModal.value = true;
};

const openDeleteModal = (assignment) => {
    deletingAssignment.value = assignment;
    showDeleteModal.value = true;
};

const openGradeModal = (assignment) => {
    gradingAssignment.value = assignment;
    showGradeModal.value = true;
};

const saveAssignment = async () => {
    try {
        if (editingAssignment.value) {
            // Update existing assignment
            // await courseStore.updateAssignment(editingAssignment.value.id, formData.value);
            const index = assignments.value.findIndex(a => a.id === editingAssignment.value.id);
            if (index !== -1) {
                assignments.value[index] = {
                    ...assignments.value[index],
                    ...formData.value,
                    updated_at: new Date().toISOString()
                };
            }
        } else {
            // Create new assignment
            // const newAssignment = await courseStore.createAssignment(props.courseId, formData.value);
            const newAssignment = {
                id: Date.now(),
                ...formData.value,
                submissions_count: 0,
                total_students: 0,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
            };
            assignments.value.unshift(newAssignment);
        }
        
        showCreateModal.value = false;
    } catch (err) {
        console.error('Failed to save assignment:', err);
        alert('เกิดข้อผิดพลาดในการบันทึกการบ้าน');
    }
};

const deleteAssignment = async () => {
    try {
        // await courseStore.deleteAssignment(deletingAssignment.value.id);
        assignments.value = assignments.value.filter(a => a.id !== deletingAssignment.value.id);
        showDeleteModal.value = false;
    } catch (err) {
        console.error('Failed to delete assignment:', err);
        alert('เกิดข้อผิดพลาดในการลบการบ้าน');
    }
};

const togglePublish = async (assignment) => {
    try {
        // await courseStore.updateAssignment(assignment.id, { is_published: !assignment.is_published });
        assignment.is_published = !assignment.is_published;
        assignment.updated_at = new Date().toISOString();
    } catch (err) {
        console.error('Failed to toggle publish:', err);
    }
};

const getStatusBadge = (assignment) => {
    if (!assignment.is_published) {
        return { text: 'ฉบับร่าง', class: 'bg-gray-100 text-gray-800' };
    }
    if (isClosed(assignment)) {
        return { text: 'ปิดรับส่ง', class: 'bg-red-100 text-red-800' };
    }
    return { text: 'เปิดรับส่ง', class: 'bg-green-100 text-green-800' };
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getSubmissionRate = (assignment) => {
    if (assignment.total_students === 0) return 0;
    return Math.round((assignment.submissions_count / assignment.total_students) * 100);
};

// Load data on mount
onMounted(async () => {
    await courseStore.init(props.courseId);
    await loadAssignments();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="การบ้าน"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'assignments'"
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
                                การบ้าน: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                จัดการและตรวจสอบการส่งการบ้านของนักเรียน
                            </p>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            สร้างการบ้านใหม่
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white shadow rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <label class="block text-sm font-medium text-gray-700">
                                    สถานะ:
                                </label>
                                <select
                                    v-model="selectedStatus"
                                    class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="all">ทั้งหมด</option>
                                    <option value="published">เปิดรับส่ง</option>
                                    <option value="closed">ปิดรับส่ง</option>
                                    <option value="draft">ฉบับร่าง</option>
                                </select>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <!-- View Mode Toggle -->
                                <div class="flex items-center bg-white rounded-md shadow-sm border border-gray-300">
                                    <button
                                        @click="viewMode = 'grid'"
                                        class="px-3 py-2 text-sm font-medium rounded-l-md"
                                        :class="viewMode === 'grid' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-50'"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="viewMode = 'list'"
                                        class="px-3 py-2 text-sm font-medium rounded-r-md -ml-px"
                                        :class="viewMode === 'list' ? 'bg-blue-500 text-white' : 'text-gray-700 hover:bg-gray-50'"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignments Grid/List -->
                    <div v-if="filteredAssignments.length === 0" class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
                        ไม่พบการบ้านที่ตรงกับเงื่อนไข
                    </div>

                    <!-- Grid View -->
                    <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="assignment in filteredAssignments" 
                            :key="assignment.id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900">
                                            {{ assignment.title }}
                                        </h4>
                                        <span 
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1"
                                            :class="getStatusBadge(assignment).class"
                                        >
                                            {{ getStatusBadge(assignment).text }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-3">
                                {{ assignment.description }}
                            </p>
                            
                            <div class="space-y-2 text-sm text-gray-500">
                                <div class="flex items-center justify-between">
                                    <span>กำหนดส่ง:</span>
                                    <span class="font-medium">{{ formatDate(assignment.due_date) }} {{ assignment.due_time }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>คะแนนเต็ม:</span>
                                    <span class="font-medium">{{ assignment.max_score }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>ส่งแล้ว:</span>
                                    <span class="font-medium">{{ assignment.submissions_count }}/{{ assignment.total_students }}</span>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                    <span>อัตราการส่ง</span>
                                    <span>{{ getSubmissionRate(assignment) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full" 
                                        :style="{ width: getSubmissionRate(assignment) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="openEditModal(assignment)"
                                        class="p-1 text-gray-400 hover:text-blue-500"
                                        title="แก้ไข"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="assignment.is_published"
                                        @click="openGradeModal(assignment)"
                                        class="p-1 text-gray-400 hover:text-green-500"
                                        title="ตรวจและให้คะแนน"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="togglePublish(assignment)"
                                        class="p-1 text-gray-400 hover:text-yellow-500"
                                        :title="assignment.is_published ? 'ซ่อน' : 'เผยแพร่'"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openDeleteModal(assignment)"
                                        class="p-1 text-gray-400 hover:text-red-500"
                                        title="ลบ"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List View -->
                    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        การบ้าน
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        กำหนดส่ง
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        สถานะ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        การส่ง
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        การกระทำ
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="assignment in filteredAssignments" :key="assignment.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ assignment.title }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    คะแนนเต็ม: {{ assignment.max_score }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(assignment.due_date) }} {{ assignment.due_time }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadge(assignment).class"
                                        >
                                            {{ getStatusBadge(assignment).text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ assignment.submissions_count }}/{{ assignment.total_students }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ getSubmissionRate(assignment) }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button
                                                @click="openEditModal(assignment)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                แก้ไข
                                            </button>
                                            <button
                                                v-if="assignment.is_published"
                                                @click="openGradeModal(assignment)"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                ตรวจ
                                            </button>
                                            <button
                                                @click="togglePublish(assignment)"
                                                class="text-yellow-600 hover:text-yellow-900"
                                            >
                                                {{ assignment.is_published ? 'ซ่อน' : 'เผยแพร่' }}
                                            </button>
                                            <button
                                                @click="openDeleteModal(assignment)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                ลบ
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    <form @submit.prevent="saveAssignment">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="space-y-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700">
                                        ชื่อการบ้าน
                                    </label>
                                    <input
                                        id="title"
                                        v-model="formData.title"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">
                                        คำอธิบาย
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="formData.description"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <div>
                                    <label for="instructions" class="block text-sm font-medium text-gray-700">
                                        คำแนะนำ
                                    </label>
                                    <textarea
                                        id="instructions"
                                        v-model="formData.instructions"
                                        rows="4"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="due_date" class="block text-sm font-medium text-gray-700">
                                            วันที่กำหนดส่ง
                                        </label>
                                        <input
                                            id="due_date"
                                            v-model="formData.due_date"
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="due_time" class="block text-sm font-medium text-gray-700">
                                            เวลากำหนดส่ง
                                        </label>
                                        <input
                                            id="due_time"
                                            v-model="formData.due_time"
                                            type="time"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="max_score" class="block text-sm font-medium text-gray-700">
                                            คะแนนเต็ม
                                        </label>
                                        <input
                                            id="max_score"
                                            v-model.number="formData.max_score"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="max_file_size" class="block text-sm font-medium text-gray-700">
                                            ขนาดไฟล์สูงสุด (MB)
                                        </label>
                                        <input
                                            id="max_file_size"
                                            v-model.number="formData.max_file_size"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        มอบหมายให้กลุ่ม
                                    </label>
                                    <div class="mt-2 space-y-2">
                                        <label v-for="group in groups" :key="group.id" class="flex items-center">
                                            <input
                                                v-model="formData.assigned_groups"
                                                :value="group.id"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">{{ group.name }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input
                                            v-model="formData.allow_late_submission"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">อนุญาตให้ส่งล่าช้า</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="formData.is_published"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">เผยแพร่ทันที</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ editingAssignment ? 'อัปเดต' : 'สร้าง' }} การบ้าน
                            </button>
                            <button
                                type="button"
                                @click="showCreateModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    ลบการบ้าน
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        คุณแน่ใจหรือไม่ที่จะลบการบ้าน "{{ deletingAssignment?.title }}"? การกระทำนี้จะลบข้อมูลการส่งงานทั้งหมดและไม่สามารถย้อนกลับได้
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteAssignment"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            ลบ
                        </button>
                        <button
                            @click="showDeleteModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grading Modal -->
        <div v-if="showGradeModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                ตรวจและให้คะแนน: {{ gradingAssignment?.title }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                ส่งแล้ว: {{ gradingAssignment?.submissions_count }}/{{ gradingAssignment?.total_students }} นักเรียน
                            </p>
                        </div>

                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-2">ฟังก์ชันการตรวจและให้คะแนนจะมาในเวอร์ชันถัดไป</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="showGradeModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            ปิด
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

