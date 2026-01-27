<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourseStore } from '@/Pages/v2/store/modules/course/courseStore';
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

// State
const loading = ref(true);
const error = ref(null);
const lessons = ref([]);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const editingLesson = ref(null);
const deletingLesson = ref(null);
const viewMode = ref('grid'); // 'grid' or 'list'
const formData = ref({
    title: '',
    description: '',
    content: '',
    order: 0,
    is_published: false,
    lesson_type: 'content', // 'content', 'video', 'assignment', 'quiz'
    video_url: '',
    duration: 0,
});

// Computed
const course = computed(() => courseStore.currentCourse);
const publishedLessons = computed(() => 
    lessons.value.filter(l => l.is_published).sort((a, b) => a.order - b.order)
);
const draftLessons = computed(() => 
    lessons.value.filter(l => !l.is_published).sort((a, b) => a.order - b.order)
);

// Methods
const loadLessons = async () => {
    try {
        loading.value = true;
        // This would call API
        // lessons.value = await courseStore.fetchLessons(props.courseId);
        
        // Mock data for now
        lessons.value = [
            {
                id: 1,
                title: 'บทนำ แนะนำรายวิชา',
                description: 'เรียนรู้เกี่ยวกับวัตถุประสงค์และโครงสร้างของรายวิชา',
                content: 'เนื้อหาบทเรียนเกี่ยวกับการแนะนำรายวิชา...',
                order: 1,
                is_published: true,
                lesson_type: 'content',
                video_url: '',
                duration: 30,
                created_at: '2023-11-01T10:00:00Z',
                updated_at: '2023-11-01T10:00:00Z',
            },
            {
                id: 2,
                title: 'บทที่ 1 พื้นฐานการเขียนโปรแกรม',
                description: 'เรียนรู้พื้นฐานการเขียนโปรแกรมเบื้องต้น',
                content: 'เนื้อหาเกี่ยวกับพื้นฐานการเขียนโปรแกรม...',
                order: 2,
                is_published: true,
                lesson_type: 'video',
                video_url: 'https://example.com/video1',
                duration: 45,
                created_at: '2023-11-05T14:30:00Z',
                updated_at: '2023-11-05T14:30:00Z',
            },
            {
                id: 3,
                title: 'บทที่ 2 ตัวแปรและชนิดข้อมูล',
                description: 'ศึกษาเกี่ยวกับตัวแปรและชนิดข้อมูลในการเขียนโปรแกรม',
                content: 'เนื้อหาเกี่ยวกับตัวแปรและชนิดข้อมูล...',
                order: 3,
                is_published: false,
                lesson_type: 'content',
                video_url: '',
                duration: 60,
                created_at: '2023-11-10T09:15:00Z',
                updated_at: '2023-11-10T09:15:00Z',
            }
        ];
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load lessons:', err);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingLesson.value = null;
    formData.value = {
        title: '',
        description: '',
        content: '',
        order: lessons.value.length + 1,
        is_published: false,
        lesson_type: 'content',
        video_url: '',
        duration: 0,
    };
    showCreateModal.value = true;
};

const openEditModal = (lesson) => {
    editingLesson.value = lesson;
    formData.value = {
        title: lesson.title,
        description: lesson.description,
        content: lesson.content,
        order: lesson.order,
        is_published: lesson.is_published,
        lesson_type: lesson.lesson_type,
        video_url: lesson.video_url,
        duration: lesson.duration,
    };
    showCreateModal.value = true;
};

const openDeleteModal = (lesson) => {
    deletingLesson.value = lesson;
    showDeleteModal.value = true;
};

const saveLesson = async () => {
    try {
        if (editingLesson.value) {
            // Update existing lesson
            // await courseStore.updateLesson(editingLesson.value.id, formData.value);
            const index = lessons.value.findIndex(l => l.id === editingLesson.value.id);
            if (index !== -1) {
                lessons.value[index] = {
                    ...lessons.value[index],
                    ...formData.value,
                    updated_at: new Date().toISOString()
                };
            }
        } else {
            // Create new lesson
            // const newLesson = await courseStore.createLesson(props.courseId, formData.value);
            const newLesson = {
                id: Date.now(),
                ...formData.value,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
            };
            lessons.value.push(newLesson);
        }
        
        showCreateModal.value = false;
    } catch (err) {
        console.error('Failed to save lesson:', err);
        alert('เกิดข้อผิดพลาดในการบันทึกบทเรียน');
    }
};

const deleteLesson = async () => {
    try {
        // await courseStore.deleteLesson(deletingLesson.value.id);
        lessons.value = lessons.value.filter(l => l.id !== deletingLesson.value.id);
        showDeleteModal.value = false;
    } catch (err) {
        console.error('Failed to delete lesson:', err);
        alert('เกิดข้อผิดพลาดในการลบบทเรียน');
    }
};

const togglePublish = async (lesson) => {
    try {
        // await courseStore.updateLesson(lesson.id, { is_published: !lesson.is_published });
        lesson.is_published = !lesson.is_published;
        lesson.updated_at = new Date().toISOString();
    } catch (err) {
        console.error('Failed to toggle publish:', err);
    }
};

const reorderLessons = async (draggedLesson, targetLesson) => {
    try {
        const draggedIndex = lessons.value.findIndex(l => l.id === draggedLesson.id);
        const targetIndex = lessons.value.findIndex(l => l.id === targetLesson.id);
        
        if (draggedIndex !== -1 && targetIndex !== -1) {
            const [removed] = lessons.value.splice(draggedIndex, 1);
            lessons.value.splice(targetIndex, 0, removed);
            
            // Update order values
            lessons.value.forEach((lesson, index) => {
                lesson.order = index + 1;
            });
            
            // await courseStore.reorderLessons(props.courseId, lessons.value.map(l => ({ id: l.id, order: l.order })));
        }
    } catch (err) {
        console.error('Failed to reorder lessons:', err);
    }
};

const getLessonIcon = (lessonType) => {
    const icons = {
        content: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        video: 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
        assignment: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        quiz: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
    };
    return icons[lessonType] || icons.content;
};

const formatDuration = (minutes) => {
    if (minutes < 60) {
        return `${minutes} นาที`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours} ชั่วโมง ${mins} นาที`;
};

// Load data on mount
onMounted(async () => {
    await courseStore.init(props.courseId);
    await loadLessons();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="บทเรียน"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'lessons'"
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
                                บทเรียน: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                จัดการเนื้อหาและบทเรียนในรายวิชา
                            </p>
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
                            
                            <button
                                @click="openCreateModal"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                สร้างบทเรียนใหม่
                            </button>
                        </div>
                    </div>

                    <!-- Published Lessons -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                บทเรียนที่เผยแพร่
                            </h3>
                            
                            <div v-if="publishedLessons.length === 0" class="text-center py-8 text-gray-500">
                                ยังไม่มีบทเรียนที่เผยแพร่
                            </div>
                            
                            <!-- Grid View -->
                            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div 
                                    v-for="lesson in publishedLessons" 
                                    :key="lesson.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                    draggable="true"
                                    @dragstart="draggedLesson = lesson"
                                    @dragover.prevent
                                    @drop="reorderLessons(draggedLesson, lesson)"
                                >
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center">
                                            <svg class="w-8 h-8 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getLessonIcon(lesson.lesson_type)" />
                                            </svg>
                                            <div>
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ lesson.title }}
                                                </h4>
                                                <p class="text-sm text-gray-500">
                                                    บทที่ {{ lesson.order }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-3">
                                        {{ lesson.description }}
                                    </p>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <span>{{ formatDuration(lesson.duration) }}</span>
                                        <div class="flex items-center space-x-2">
                                            <button
                                                @click.stop="openEditModal(lesson)"
                                                class="p-1 text-gray-400 hover:text-blue-500"
                                                title="แก้ไข"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click.stop="togglePublish(lesson)"
                                                class="p-1 text-gray-400 hover:text-yellow-500"
                                                title="ซ่อน"
                                            >
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click.stop="openDeleteModal(lesson)"
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
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                บทเรียน
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ประเภท
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ระยะเวลา
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                อัปเดตล่าสุด
                                            </th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                การกระทำ
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="lesson in publishedLessons" :key="lesson.id">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getLessonIcon(lesson.lesson_type)" />
                                                    </svg>
                                                    <div>
                                                        <div class="text-sm font-medium text-gray-900">
                                                            {{ lesson.title }}
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            บทที่ {{ lesson.order }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    {{ lesson.lesson_type === 'content' ? 'เนื้อหา' : lesson.lesson_type === 'video' ? 'วิดีโอ' : lesson.lesson_type === 'assignment' ? 'การบ้าน' : 'แบบทดสอบ' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ formatDuration(lesson.duration) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ new Date(lesson.updated_at).toLocaleDateString('th-TH') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <button
                                                        @click="openEditModal(lesson)"
                                                        class="text-blue-600 hover:text-blue-900"
                                                    >
                                                        แก้ไข
                                                    </button>
                                                    <button
                                                        @click="togglePublish(lesson)"
                                                        class="text-yellow-600 hover:text-yellow-900"
                                                    >
                                                        ซ่อน
                                                    </button>
                                                    <button
                                                        @click="openDeleteModal(lesson)"
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
                    </div>

                    <!-- Draft Lessons -->
                    <div v-if="draftLessons.length > 0" class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                ฉบับร่าง
                            </h3>
                            
                            <div class="space-y-4">
                                <div 
                                    v-for="lesson in draftLessons" 
                                    :key="lesson.id"
                                    class="border border-gray-200 rounded-lg p-4 opacity-75"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-medium text-gray-900">
                                                {{ lesson.title }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                บทที่ {{ lesson.order }}
                                            </p>
                                            <p class="mt-2 text-sm text-gray-600">
                                                {{ lesson.description }}
                                            </p>
                                        </div>
                                        
                                        <div class="ml-4 flex items-center space-x-2">
                                            <button
                                                @click="openEditModal(lesson)"
                                                class="p-1 text-gray-400 hover:text-blue-500"
                                                title="แก้ไข"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="openDeleteModal(lesson)"
                                                class="p-1 text-gray-400 hover:text-red-500"
                                                title="ลบ"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
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

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form @submit.prevent="saveLesson">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="space-y-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700">
                                        ชื่อบทเรียน
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

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="lesson_type" class="block text-sm font-medium text-gray-700">
                                            ประเภทบทเรียน
                                        </label>
                                        <select
                                            id="lesson_type"
                                            v-model="formData.lesson_type"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                            <option value="content">เนื้อหา</option>
                                            <option value="video">วิดีโอ</option>
                                            <option value="assignment">การบ้าน</option>
                                            <option value="quiz">แบบทดสอบ</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="duration" class="block text-sm font-medium text-gray-700">
                                            ระยะเวลา (นาที)
                                        </label>
                                        <input
                                            id="duration"
                                            v-model.number="formData.duration"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>
                                </div>

                                <div v-if="formData.lesson_type === 'video'">
                                    <label for="video_url" class="block text-sm font-medium text-gray-700">
                                        ลิงก์วิดีโอ
                                    </label>
                                    <input
                                        id="video_url"
                                        v-model="formData.video_url"
                                        type="url"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                </div>

                                <div>
                                    <label for="content" class="block text-sm font-medium text-gray-700">
                                        เนื้อหาบทเรียน
                                    </label>
                                    <textarea
                                        id="content"
                                        v-model="formData.content"
                                        rows="6"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    ></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="order" class="block text-sm font-medium text-gray-700">
                                            ลำดับที่
                                        </label>
                                        <input
                                            id="order"
                                            v-model.number="formData.order"
                                            type="number"
                                            min="1"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                        >
                                    </div>

                                    <div class="flex items-center pt-6">
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
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ editingLesson ? 'อัปเดต' : 'สร้าง' }} บทเรียน
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
                                    ลบบทเรียน
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        คุณแน่ใจหรือไม่ที่จะลบบทเรียน "{{ deletingLesson?.title }}"? การกระทำนี้ไม่สามารถย้อนกลับได้
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteLesson"
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
    </div>
</template>