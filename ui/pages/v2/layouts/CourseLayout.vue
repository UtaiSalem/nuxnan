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

// Computed
const course = computed(() => courseStore.currentCourse);
const isCourseAdmin = computed(() => {
    // This would come from auth or permissions
    return true; // Placeholder
});

// Initialize course data
onMounted(async () => {
    try {
        loading.value = true;
        
        // Initialize course
        await courseStore.init(props.courseId);
        
        // Load basic data
        await Promise.all([
            groupStore.fetchGroups(props.courseId),
            memberStore.fetchMembers(props.courseId),
        ]);
        
    } catch (err) {
        error.value = err.message;
        console.error('Failed to initialize course:', err);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center h-screen">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="min-h-screen flex items-center justify-center">
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md max-w-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 00016zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-2.293 2.293a1 1 0 101.414 1.414L10 11.414l2.293 2.293a1 1 0 001.414-1.414L11.414 10l2.293-2.293a1 1 0 00-1.414-1.414L10 8.586 7.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            เกิดข้อผิดพลาดในการโหลดข้อมูล
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            {{ error }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Content -->
        <div v-else-if="course" class="flex h-screen">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md h-full">
                <CourseSidebar 
                    :course-id="courseId"
                    :course="course"
                    :is-admin="isCourseAdmin"
                />
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col overflow-hidden">
                <!-- Page Content -->
                <main class="flex-1 overflow-y-auto bg-gray-50">
                    <div class="py-6">
                        <router-view />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

