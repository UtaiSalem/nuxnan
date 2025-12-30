<script setup>
import { ref } from 'vue';
import { usePage } from "@inertiajs/vue3";

// Layout & Components
import CourseLayout from '@/Layouts/CourseLayout.vue';
import LoadingPage from '@/PlearndComponents/accessories/LoadingPage.vue';
import CreateNewLesson from '@/PlearndComponents/learn/courses/lessons/CreateNewLesson.vue';

// Modern Lesson Post Component
import LessonPost from '~/components/course/lesson/LessonPost.vue';

const props = defineProps({
    course: Object,
    lessons: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
    groups: Object,
});

// State
const isLoadingPage = ref(false);

// Methods
const handleEditLesson = (lesson) => {
    isLoadingPage.value = true;
    window.location.href = `/courses/${props.course.data.id}/lessons/${lesson.id}/edit`;
};

const handleDeleteLesson = async (lessonId) => {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะลบบทเรียนนี้?')) return;
    
    try {
        isLoadingPage.value = true;
        const response = await axios.delete(`/courses/${props.course.data.id}/lessons/${lessonId}`);
        if (response.status === 200) {
            // Remove from list
            const index = props.lessons.data.findIndex(l => l.id === lessonId);
            if (index > -1) {
                props.lessons.data.splice(index, 1);
            }
        }
    } catch (error) {
        console.error('Error deleting lesson:', error);
        alert('เกิดข้อผิดพลาดในการลบบทเรียน');
    } finally {
        isLoadingPage.value = false;
    }
};

const handleLikeLesson = async (lessonId) => {
    try {
        await axios.post(`/courses/${props.course.data.id}/lessons/${lessonId}/like`);
        // Update lesson in list
        const lesson = props.lessons.data.find(l => l.id === lessonId);
        if (lesson) {
            lesson.is_liked_by_auth = !lesson.is_liked_by_auth;
            lesson.like_count = (lesson.like_count || 0) + (lesson.is_liked_by_auth ? 1 : -1);
        }
    } catch (error) {
        console.error('Error liking lesson:', error);
    }
};

const handleBookmarkLesson = async (lessonId) => {
    try {
        await axios.post(`/courses/${props.course.data.id}/lessons/${lessonId}/bookmark`);
        // Update lesson in list
        const lesson = props.lessons.data.find(l => l.id === lessonId);
        if (lesson) {
            lesson.is_bookmarked_by_auth = !lesson.is_bookmarked_by_auth;
            lesson.bookmarks_count = (lesson.bookmarks_count || 0) + (lesson.is_bookmarked_by_auth ? 1 : -1);
        }
    } catch (error) {
        console.error('Error bookmarking lesson:', error);
    }
};

const handleShareLesson = (lesson) => {
    // TODO: Implement share modal
    const url = window.location.origin + `/courses/${props.course.data.id}/lessons/${lesson.id}`;
    navigator.clipboard.writeText(url);
    alert('คัดลอกลิงก์แล้ว!');
};

const handleCommentLesson = (lessonId) => {
    // Navigate to lesson detail to comment
    window.location.href = `/courses/${props.course.data.id}/lessons/${lessonId}#comments`;
};

const handleAddNewLesson = (newLesson) => {
    props.lessons.data.unshift(newLesson);
    isLoadingPage.value = false;
};
</script>

<template>
    <div>
        <CourseLayout 
            :course 
            :isCourseAdmin
            :courseMemberOfAuth
            :activeTab="1"
        >
            <template #courseContent>
                <LoadingPage v-if="isLoadingPage" />

                <div class="mt-4">
                    <!-- Create New Lesson (Admin Only) -->
                    <CreateNewLesson 
                        v-if="isCourseAdmin"
                        @creating-new-lesson="isLoadingPage = true"
                        @add-new-lesson="handleAddNewLesson" 
                    />

                    <!-- Empty State -->
                    <div 
                        v-if="!lessons?.data?.length" 
                        class="p-4 my-4 text-base text-gray-600 bg-white border-t-4 border-blue-500 rounded-lg shadow-lg"
                    >
                        <div class="text-center">
                            <p>ยังไม่มีบทเรียนในรายวิชานี้</p>
                        </div>
                    </div>

                    <!-- Lessons Feed (แสดงทีละบทแบบโพสต์) -->
                    <div v-for="(lesson, index) in lessons.data" :key="lesson.id">
                        <LessonPost
                            :lesson="lesson"
                            :is-admin="isCourseAdmin"
                            @edit="handleEditLesson"
                            @delete="handleDeleteLesson"
                            @like="handleLikeLesson"
                            @bookmark="handleBookmarkLesson"
                            @share="handleShareLesson"
                            @comment="handleCommentLesson"
                        />
                    </div>
                </div>
            </template>
        </CourseLayout>
    </div>
</template>
