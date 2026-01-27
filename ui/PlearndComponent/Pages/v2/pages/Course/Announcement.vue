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
const announcements = ref([]);
const showCreateModal = ref(false);
const editingAnnouncement = ref(null);
const formData = ref({
    title: '',
    content: '',
    is_pinned: false,
    is_published: true,
});

// Computed
const course = computed(() => courseStore.currentCourse);
const publishedAnnouncements = computed(() => 
    announcements.value.filter(a => a.is_published).sort((a, b) => {
        // Pinned announcements first
        if (a.is_pinned && !b.is_pinned) return -1;
        if (!a.is_pinned && b.is_pinned) return 1;
        // Then by date (newest first)
        return new Date(b.created_at) - new Date(a.created_at);
    })
);
const draftAnnouncements = computed(() => 
    announcements.value.filter(a => !a.is_published)
);

// Methods
const loadAnnouncements = async () => {
    try {
        loading.value = true;
        // This would call the API
        // announcements.value = await courseStore.fetchAnnouncements(props.courseId);
        
        // Mock data for now
        announcements.value = [
            {
                id: 1,
                title: 'ประกาศสำคัญ: กำหนดการสอบปลายภาค',
                content: 'สอบปลายภาคจะจัดขึ้นในวันที่ 15-20 ธันวาคม 2566 กรุณาเตรียมตัวให้พร้อม',
                is_pinned: true,
                is_published: true,
                created_at: '2023-12-01T10:00:00Z',
                updated_at: '2023-12-01T10:00:00Z',
                author: {
                    name: 'อาจารย์สมชาย ใจดี',
                    avatar: 'https://images.unsplash.com/photo-1517365830460-955ce321267c?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
                }
            },
            {
                id: 2,
                title: 'เปิดรับสมัครผู้ช่วยสอน (TA)',
                content: 'รายวิชานี้กำลังมองหาผู้ช่วยสอน 2 ตำแหน่ง สนใจติดต่อภาควิชา',
                is_pinned: false,
                is_published: true,
                created_at: '2023-11-28T14:30:00Z',
                updated_at: '2023-11-28T14:30:00Z',
                author: {
                    name: 'อาจารย์สมชาย ใจดี',
                    avatar: 'https://images.unsplash.com/photo-1517365830460-955ce321267c?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
                }
            }
        ];
    } catch (err) {
        error.value = err.message;
        console.error('Failed to load announcements:', err);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingAnnouncement.value = null;
    formData.value = {
        title: '',
        content: '',
        is_pinned: false,
        is_published: true,
    };
    showCreateModal.value = true;
};

const openEditModal = (announcement) => {
    editingAnnouncement.value = announcement;
    formData.value = {
        title: announcement.title,
        content: announcement.content,
        is_pinned: announcement.is_pinned,
        is_published: announcement.is_published,
    };
    showCreateModal.value = true;
};

const saveAnnouncement = async () => {
    try {
        if (editingAnnouncement.value) {
            // Update existing announcement
            // await courseStore.updateAnnouncement(editingAnnouncement.value.id, formData.value);
            const index = announcements.value.findIndex(a => a.id === editingAnnouncement.value.id);
            if (index !== -1) {
                announcements.value[index] = {
                    ...announcements.value[index],
                    ...formData.value,
                    updated_at: new Date().toISOString()
                };
            }
        } else {
            // Create new announcement
            // const newAnnouncement = await courseStore.createAnnouncement(props.courseId, formData.value);
            const newAnnouncement = {
                id: Date.now(),
                ...formData.value,
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
                author: {
                    name: 'ผู้ใช้ปัจจุบัน',
                    avatar: 'https://images.unsplash.com/photo-1517365830460-955ce321267c?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
                }
            };
            announcements.value.unshift(newAnnouncement);
        }
        
        showCreateModal.value = false;
    } catch (err) {
        console.error('Failed to save announcement:', err);
    }
};

const deleteAnnouncement = async (announcementId) => {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะลบประกาศนี้?')) return;
    
    try {
        // await courseStore.deleteAnnouncement(announcementId);
        announcements.value = announcements.value.filter(a => a.id !== announcementId);
    } catch (err) {
        console.error('Failed to delete announcement:', err);
    }
};

const togglePin = async (announcement) => {
    try {
        // await courseStore.updateAnnouncement(announcement.id, { is_pinned: !announcement.is_pinned });
        announcement.is_pinned = !announcement.is_pinned;
        announcement.updated_at = new Date().toISOString();
    } catch (err) {
        console.error('Failed to toggle pin:', err);
    }
};

// Load data on mount
onMounted(async () => {
    await courseStore.init(props.courseId);
    await loadAnnouncements();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <CourseHeader 
            :course="course"
            :course-id="courseId"
            title="ประกาศ"
        />

        <div class="flex">
            <!-- Sidebar -->
            <CourseSidebar 
                :course-id="courseId"
                :active-tab="'announcements'"
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
                                ประกาศรายวิชา: {{ course?.name }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600">
                                ประกาศล่าสุดและข้อมูลสำคัญของรายวิชา
                            </p>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            สร้างประกาศใหม่
                        </button>
                    </div>

                    <!-- Published Announcements -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                ประกาศที่เผยแพร่
                            </h3>
                            
                            <div v-if="publishedAnnouncements.length === 0" class="text-center py-8 text-gray-500">
                                ยังไม่มีประกาศที่เผยแพร่
                            </div>
                            
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="announcement in publishedAnnouncements" 
                                    :key="announcement.id"
                                    class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ announcement.title }}
                                                </h4>
                                                <span 
                                                    v-if="announcement.is_pinned"
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                                >
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                    </svg>
                                                    ปักหมุด
                                                </span>
                                            </div>
                                            
                                            <div class="mt-2 prose prose-sm max-w-none text-gray-600">
                                                {{ announcement.content }}
                                            </div>
                                            
                                            <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500">
                                                <div class="flex items-center space-x-2">
                                                    <img 
                                                        class="h-6 w-6 rounded-full" 
                                                        :src="announcement.author.avatar" 
                                                        :alt="announcement.author.name"
                                                    >
                                                    <span>{{ announcement.author.name }}</span>
                                                </div>
                                                <span>{{ new Date(announcement.created_at).toLocaleDateString('th-TH') }}</span>
                                            </div>
                                        </div>
                                        
                                        <div class="ml-4 flex items-center space-x-2">
                                            <button
                                                @click="togglePin(announcement)"
                                                class="p-1 text-gray-400 hover:text-yellow-500"
                                                :title="announcement.is_pinned ? 'เลิกปักหมุด' : 'ปักหมุด'"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="openEditModal(announcement)"
                                                class="p-1 text-gray-400 hover:text-blue-500"
                                                title="แก้ไข"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteAnnouncement(announcement.id)"
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

                    <!-- Draft Announcements -->
                    <div v-if="draftAnnouncements.length > 0" class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                ฉบับร่าง
                            </h3>
                            
                            <div class="space-y-4">
                                <div 
                                    v-for="announcement in draftAnnouncements" 
                                    :key="announcement.id"
                                    class="border border-gray-200 rounded-lg p-4 opacity-75"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-medium text-gray-900">
                                                {{ announcement.title }}
                                            </h4>
                                            <div class="mt-2 prose prose-sm max-w-none text-gray-600">
                                                {{ announcement.content }}
                                            </div>
                                        </div>
                                        
                                        <div class="ml-4 flex items-center space-x-2">
                                            <button
                                                @click="openEditModal(announcement)"
                                                class="p-1 text-gray-400 hover:text-blue-500"
                                                title="แก้ไข"
                                            >
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteAnnouncement(announcement.id)"
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

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="saveAnnouncement">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700">
                                    หัวข้อประกาศ
                                </label>
                                <input
                                    id="title"
                                    v-model="formData.title"
                                    type="text"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                            </div>

                            <div class="mb-4">
                                <label for="content" class="block text-sm font-medium text-gray-700">
                                    เนื้อหาประกาศ
                                </label>
                                <textarea
                                    id="content"
                                    v-model="formData.content"
                                    rows="6"
                                    required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                ></textarea>
                            </div>

                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        v-model="formData.is_pinned"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                    >
                                    <span class="ml-2 text-sm text-gray-700">ปักหมุดประกาศนี้</span>
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

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ editingAnnouncement ? 'อัปเดต' : 'สร้าง' }} ประกาศ
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
    </div>
</template>