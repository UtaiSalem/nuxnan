<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourseStore } from '@/Pages/v2/store/modules/course/courseStore';
import { useGroupStore } from '@/Pages/v2/store/modules/group/groupStore';
import { useMemberStore } from '@/Pages/v2/store/modules/member/memberStore';
import CourseHeader from '@/Pages/v2/components/course/CourseHeader.vue';
import CourseSidebar from '@/Pages/v2/components/course/CourseSidebar.vue';

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
const exams = ref([]);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const showResultsModal = ref(false);
const editingExam = ref(null);
const deletingExam = ref(null);
const viewingResults = ref(null);
const viewMode = ref('grid'); // 'grid' or 'list'
const selectedStatus = ref('all'); // 'all', 'published', 'draft', 'closed'
const formData = ref({
    title: '',
    description: '',
    instructions: '',
    start_date: '',
    start_time: '09:00',
    end_date: '',
    end_time: '10:00',
    duration_minutes: 60,
    max_score: 100,
    allow_review: true,
    show_results_immediately: false,
    randomize_questions: false,
    is_published: false,
    assigned_groups: [],
    time_limit_enabled: true,
});

// Computed
const course = computed(() => courseStore.currentCourse);
const groups = computed(() => groupStore.groupsArray);
const filteredExams = computed(() => {
    let filtered = exams.value;
    
    if (selectedStatus.value !== 'all') {
        filtered = filtered.filter(e => {
            if (selectedStatus.value === 'published') return e.is_published && !isClosed(e);
            if (selectedStatus.value === 'draft') return !e.is_published;
            if (selectedStatus.value === 'closed') return e.is_published && isClosed(e);
            return true;
        });
    }
    
    return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

const publishedExams = computed(() => 
    exams.value.filter(e => e.is_published)
);

const draftExams = computed(() => 
    exams.value.filter(e => !e.is_published)
);

// Methods
const isClosed = (exam) => {
    if (!exam.end_date) return false;
    return new Date(`${exam.end_date} ${exam.end_time || '23:59'}`) < new Date();
};

const isOngoing = (exam) => {
    if (!exam.start_date || !exam.end_date) return false;
    const now = new Date();
    const start = new Date(`${exam.start_date} ${exam.start_time || '00:00'}`);
    const end = new Date(`${exam.end_date} ${exam.end_time || '23:59'}`);
    return now >= start && now <= end;
};

const isUpcoming = (exam) => {
    if (!exam.start_date) return false;
    return new Date(`${exam.start_date} ${exam.start_time || '00:00'}`) > new Date();
};

const loadExams = async () => {
    try {
        loading.value = true;
        
        // Load groups
        await groupStore.fetchGroups(props.courseId);
        
        // This would call API
        // exams.value = await courseStore.fetchExams(props.courseId);
        
        // Mock data for now
        exams.value = [
            {
                id: 1,
                title: 'ข้อสอบกลางภาค',
                description: 'สอบพื้นฐานการเขียนโปรแกรมและโครงสร้างข้อมูล',
                instructions: '1. อ่านคำสั่งให้ละเอียด\n2. ตรวจสอบคำตอบก่อนส่ง\n3. มีเวลาทำข้อสอบ 60 นาที',
                start_date: '2023-12-10',
                start_time: '09:00',
                end_date: '2023-12-10',
                end_time: '10:00',
                duration_minutes: 60,
                max_score: 100,
                allow_review: true,
                show_results_immediately: false,
                randomize_questions: false,
                is_published: true,
                assigned_groups: [1, 2],
                time_limit_enabled: true,
                questions_count: 20,
                participants_count: 18,
                completed_count: 15,
                created_at: '2023-11-20T10:00:00Z',
                updated_at: '2023-11-20T10:00:00Z',
            },
            {
                id: 2,
                title: 'ข้อสอบปลายภาค',
                description: 'สอบทักษิณาการณ์ทั้งหมดในรายวิชา',
                instructions: '1. อ่านคำสั่งให้ละเอียด\n2. ตรวจสอบคำตอบก่อนส่ง\n3. มีเวลาทำข้อสอบ 120 นาที',
                start_date: '2023-12-20',
                start_time: '13:00',
                end_date: '2023-12-20',
                end_time: '15:00',
                duration_minutes: 120,
                max_score: 100,
                allow_review: true,
                show_results_immediately: false,
                randomize_questions: true,
                is_published: true,
                assigned_groups: [1, 2],
                time_limit_enabled: true,
                questions_count: 40,
                participants_count: 20,
                completed_count: 0,
                created_at: '2023-11-25T14:30:00Z',
                updated_at: '2023-11-25T14:30:00Z',
            },
            {
                id: 3,
                title: 'แบบทดสอบทบทวัดความเข้าใจ',
                description: 'ทบทวัดความเข้าใจเกี่ยวกับฟังก์ชันและโมดูล',
                instructions: 'ทำข้อสอบเพื่อทบทวัดความเข้าใจ',
                start_date: '',
                start_time: '09:00',
                end_date: '',
                end_time: '10:00',
                duration_minutes: 30,
                max_score: 50,
                allow_review: true,
                show_results_immediately: true,
                randomize_questions: false,
                is_published: false,
                assigned_groups: [],
                time_limit_enabled: true,
                questions_count: 10,
                participants_count: 0,
                completed_count: 0,
                created_at: '2023-12-01T09:15:00Z',
                updated_at: '2023-12-01T09:15:00Z',
            }
        ];
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load exams:', err);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingExam.value = null;
    formData.value = {
        title: '',
        description: '',
        instructions: '',
        start_date: '',
        start_time: '09:00',
        end_date: '',
        end_time: '10:00',
        duration_minutes: 60,
        max_score: 100,
        allow_review: true,
        show_results_immediately: false,
        randomize_questions: false,
        is_published: false,
        assigned_groups: [],
        time_limit_enabled: true,
    };
    showCreateModal.value = true;
};

const openEditModal = (exam) => {
    editingExam.value = exam;
    formData.value = {
        title: exam.title,
        description: exam.description,
        instructions: exam.instructions,
        start_date: exam.start_date,
        start_time: exam.start_time || '09:00',
        end_date: exam.end_date,
        end_time: exam.end_time || '10:00',
        duration_minutes: exam.duration_minutes,
        max_score: exam.max_score,
        allow_review: exam.allow_review,
        show_results_immediately: exam.show_results_immediately,
        randomize_questions: exam.randomize_questions,
        is_published: exam.is_published,
        assigned_groups: exam.assigned_groups || [],
        time_limit_enabled: exam.time_limit_enabled,
    };
    showCreateModal.value = true;
};

const openDeleteModal = (exam) => {
    deletingExam.value = exam;
    showDeleteModal.value = true;
};

const openResultsModal = (exam) => {
    viewingResults.value = exam;
    showResultsModal.value = true;
};

const saveExam = async () => {
    try {
        if (editingExam.value) {
            // Update existing exam
            // await courseStore.updateExam(editingExam.value.id, formData.value);
            const index = exams.value.findIndex(e => e.id === editingExam.value.id);
            if (index !== -1) {
                exams.value[index] = {
                    ...exams.value[index],
                    ...formData.value,
                    updated_at: new Date().toISOString()
                };
            }
        } else {
            // Create new exam
            // const newExam = await courseStore.createExam(props.courseId, formData.value);
            const newExam = {
                id: Date.now(),
                ...formData.value,
                questions_count: 0,
                participants_count: 0,
                completed_count: 0,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
            };
            exams.value.unshift(newExam);
        }
        
        showCreateModal.value = false;
    } catch (err) {
        console.error('Failed to save exam:', err);
        alert('เกิดข้อผิดพลาดในการบันทึกข้อสอบ');
    }
};

const deleteExam = async () => {
    try {
        // await courseStore.deleteExam(deletingExam.value.id);
        exams.value = exams.value.filter(e => e.id !== deletingExam.value.id);
        showDeleteModal.value = false;
    } catch (err) {
        console.error('Failed to delete exam:', err);
        alert('เกิดข้อผิดพลาดในการลบข้อสอบ');
    }
};

const togglePublish = async (exam) => {
    try {
        // await courseStore.updateExam(exam.id, { is_published: !exam.is_published });
        exam.is_published = !exam.is_published;
        exam.updated_at = new Date().toISOString();
    } catch (err) {
        console.error('Failed to toggle publish:', err);
    }
};

const getStatusBadge = (exam) => {
    if (!exam.is_published) {
        return { text: 'ฉบับร่าง', class: 'bg-gray-100 text-gray-800' };
    }
    if (isOngoing(exam)) {
        return { text: 'กำลังดำเนินการ', class: 'bg-yellow-100 text-yellow-800' };
    }
    if (isUpcoming(exam)) {
        return { text: 'รอดำเนินการ', class: 'bg-blue-100 text-blue-800' };
    }
    if (isClosed(exam)) {
        return { text: 'สิ้นสุด', class: 'bg-red-100 text-red-800' };
    }
    return { text: 'ไม่ทราบสถานะ', class: 'bg-gray-100 text-gray-800' };
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateString, timeString) => {
    if (!dateString) return '-';
    const date = new Date(`${dateString} ${timeString || '00:00'}`);
    return date.toLocaleString('th-TH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getCompletionRate = (exam) => {
    if (exam.participants_count === 0) return 0;
    return Math.round((exam.completed_count / exam.participants_count) * 100);
};

// Load data on mount
onMounted(async () => {
    await courseStore.init(props.courseId);
    await loadExams();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="ข้อสอบ"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'exams'"
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
                                ข้อสอบ: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                จัดการและตรวจสอบข้อสอบในรายวิชา
                            </p>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            สร้างข้อสอบใหม่
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
                                    <option value="published">เปิดให้ทำ</option>
                                    <option value="closed">สิ้นสุด</option>
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

                    <!-- Exams Grid/List -->
                    <div v-if="filteredExams.length === 0" class="bg-white shadow rounded-lg p-8 text-center text-gray-500">
                        ไม่พบข้อสอบที่ตรงกับเงื่อนไข
                    </div>

                    <!-- Grid View -->
                    <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div 
                            v-for="exam in filteredExams" 
                            :key="exam.id"
                            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <div>
                                        <h4 class="text-lg font-medium text-gray-900">
                                            {{ exam.title }}
                                        </h4>
                                        <span 
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-1"
                                            :class="getStatusBadge(exam).class"
                                        >
                                            {{ getStatusBadge(exam).text }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-3">
                                {{ exam.description }}
                            </p>
                            
                            <div class="space-y-2 text-sm text-gray-500">
                                <div class="flex items-center justify-between">
                                    <span>วันที่เริ่ม:</span>
                                    <span class="font-medium">{{ formatDateTime(exam.start_date, exam.start_time) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>วันที่สิ้นสุด:</span>
                                    <span class="font-medium">{{ formatDateTime(exam.end_date, exam.end_time) }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>ระยะเวลา:</span>
                                    <span class="font-medium">{{ exam.duration_minutes }} นาที</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>คะแนนเต็ม:</span>
                                    <span class="font-medium">{{ exam.max_score }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>จำนวนข้อ:</span>
                                    <span class="font-medium">{{ exam.questions_count }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>ทำแล้ว:</span>
                                    <span class="font-medium">{{ exam.completed_count }}/{{ exam.participants_count }}</span>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                    <span>อัตราการทำ</span>
                                    <span>{{ getCompletionRate(exam) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-blue-600 h-2 rounded-full" 
                                        :style="{ width: getCompletionRate(exam) + '%' }"
                                    ></div>
                                </div>
                            </div>
                            
                            <div class="mt-4 flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-2">
                                    <button
                                        @click="openEditModal(exam)"
                                        class="p-1 text-gray-400 hover:text-blue-500"
                                        title="แก้ไข"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="exam.is_published && exam.completed_count > 0"
                                        @click="openResultsModal(exam)"
                                        class="p-1 text-gray-400 hover:text-green-500"
                                        title="ดูผลลัพธ์"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="togglePublish(exam)"
                                        class="p-1 text-gray-400 hover:text-yellow-500"
                                        :title="exam.is_published ? 'ปิด' : 'เปิด'"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="openDeleteModal(exam)"
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
                                        ข้อสอบ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        วันที่เริ่ม-สิ้นสุด
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        สถานะ
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        การทำ
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        การกระทำ
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="exam in filteredExams" :key="exam.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ exam.title }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    คะแนนเต็ม: {{ exam.max_score }} | จำนวนข้อ: {{ exam.questions_count }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div>
                                            <div>{{ formatDateTime(exam.start_date, exam.start_time) }}</div>
                                            <div>{{ formatDateTime(exam.end_date, exam.end_time) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="getStatusBadge(exam).class"
                                        >
                                            {{ getStatusBadge(exam).text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ exam.completed_count }}/{{ exam.participants_count }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ getCompletionRate(exam) }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button
                                                @click="openEditModal(exam)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                แก้ไข
                                            </button>
                                            <button
                                                v-if="exam.is_published && exam.completed_count > 0"
                                                @click="openResultsModal(exam)"
                                                class="text-green-600 hover:text-green-900"
                                            >
                                                ผลลัพธ์
                                            </button>
                                            <button
                                                @click="togglePublish(exam)"
                                                class="text-yellow-600 hover:text-yellow-900"
                                            >
                                                {{ exam.is_published ? 'ปิด' : 'เปิด' }}
                                            </button>
                                            <button
                                                @click="openDeleteModal(exam)"
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
                    <form @submit.prevent="saveExam">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="space-y-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700">
                                        ชื่อข้อสอบ
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
                                        <label for="start_date" class="block text-sm font-medium text-gray-700">
                                            วันที่เริ่ม
                                        </label>
                                        <input
                                            id="start_date"
                                            v-model="formData.start_date"
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="start_time" class="block text-sm font-medium text-gray-700">
                                            เวลาเริ่ม
                                        </label>
                                        <input
                                            id="start_time"
                                            v-model="formData.start_time"
                                            type="time"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700">
                                            วันที่สิ้นสุด
                                        </label>
                                        <input
                                            id="end_date"
                                            v-model="formData.end_date"
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div>
                                        <label for="end_time" class="block text-sm font-medium text-gray-700">
                                            เวลาสิ้นสุด
                                        </label>
                                        <input
                                            id="end_time"
                                            v-model="formData.end_time"
                                            type="time"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="duration_minutes" class="block text-sm font-medium text-gray-700">
                                            ระยะเวลา (นาที)
                                        </label>
                                        <input
                                            id="duration_minutes"
                                            v-model.number="formData.duration_minutes"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

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
                                            v-model="formData.time_limit_enabled"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">จำกัดเวลาในการทำข้อสอบ</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="formData.allow_review"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">อนุญาตให้ตรวจสอบคำตอบหลังส่ง</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="formData.show_results_immediately"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">แสดงผลลัพธ์ทันทีหลังส่ง</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input
                                            v-model="formData.randomize_questions"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">สุ่มลำดับคำถาม</span>
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
                                {{ editingExam ? 'อัปเดต' : 'สร้าง' }} ข้อสอบ
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
                                    ลบข้อสอบ
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        คุณแน่ใจหรือไม่ที่จะลบข้อสอบ "{{ deletingExam?.title }}"? การกระทำนี้จะลบข้อมูลการทำข้อสอบทั้งหมดและไม่สามารถย้อนกลับได้
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteExam"
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

        <!-- Results Modal -->
        <div v-if="showResultsModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                ผลลัพธ์ข้อสอบ: {{ viewingResults?.title }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                ทำแล้ว: {{ viewingResults?.completed_count }}/{{ viewingResults?.participants_count }} นักเรียน
                            </p>
                        </div>

                        <div class="text-center py-8 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <p class="mt-2">ฟังก์ชันการดูผลลัพธ์และวิเคราะห์จะมาในเวอร์ชันถัดไป</p>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="showResultsModal = false"
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