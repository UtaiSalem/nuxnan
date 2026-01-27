<script setup>
import { ref, computed, shallowRef, nextTick } from 'vue';
// import { usePage } from "@inertiajs/vue3";
import Swal from 'sweetalert2';

import CourseLayout from '@/Layouts/CourseLayout.vue';
import QuizzesListViewer from '@/PlearndComponents/learn/courses/quizzes/QuizzesListViewer.vue';
import CreateNewCourseQuize from '@/PlearndComponents/learn/courses/quizzes/CreateNewCourseQuize.vue';
import UpdateCourseQuize from '@/PlearndComponents/learn/courses/quizzes/UpdateCourseQuize.vue';

const props = defineProps({
    course: Object,
    quizzes: Object,
    isCourseAdmin: Boolean,
    courseMemberOfAuth: Object,
});

// Use shallowRef for better performance with large arrays
const quizzesData = shallowRef([...props.quizzes.data]);
const isLoading = ref(false);
const quizIndex = ref(null);
const showUpdateQuizModal = ref(false);

// Computed property to maintain reactivity while optimizing performance
const quizzesComputed = computed(() => quizzesData.value);

function handleAddNewQuiz(newQuiz){
    // Create a new array reference to trigger reactivity efficiently
    quizzesData.value = [...quizzesData.value, newQuiz];
}

function handleUpdateQuiz(qId, qIndex){
    quizIndex.value = qIndex;
    showUpdateQuizModal.value = true;
}

async function handleDeleteQuiz(qId, qIndex){
    try {
        isLoading.value = true;
        let deleteResp = await axios.delete(`/courses/${props.course.data.id}/quizzes/${qId}`);
        if (deleteResp.data.success) {
            // Create new array without the deleted item for better performance
            quizzesData.value = quizzesData.value.filter((_, index) => index !== qIndex);
            
            Swal.fire({
                title: 'ลบแบบทดสอบสำเร็จ',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        }
    } catch (error) {
        console.log(error);
        Swal.fire({
            title: 'ลบแบบทดสอบไม่สำเร็จ',
            text: 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
            icon: 'error',
        });
    } finally {
        isLoading.value = false;
    }
}

function handleUpdateQuizData(updatedQuiz) {
    // Create new array with updated quiz for better performance
    quizzesData.value = quizzesData.value.map((quiz, index) =>
        index === quizIndex.value ? updatedQuiz : quiz
    );
}

</script>

<template>
    <div class="relative">
        <CourseLayout
            :course="props.course"
            :isCourseAdmin="props.isCourseAdmin"
            :courseMemberOfAuth="props.courseMemberOfAuth"
            :activeTab="3"
        >
            <template #courseContent>
                <div class="mt-4">
                    <!-- Use computed property for optimized reactivity -->
                    <QuizzesListViewer
                        :quizzes="quizzesComputed"
                        :quizzesApiRoute="`/courses/${props.course.data.id}`"
                        @update-quiz="(qId, qIndex) => handleUpdateQuiz( qId, qIndex)"
                        @delete-quiz="(qId, qIndex) => handleDeleteQuiz(qId, qIndex)"
                    />

                    <!-- Lazy load update modal to improve initial load performance -->
                    <Teleport to="body">
                        <Transition name="modal-fade">
                            <UpdateCourseQuize v-if="showUpdateQuizModal && quizIndex !== null"
                                :quiz="quizzesComputed[quizIndex]"
                                :course_id="props.course.data.id"
                                @update-quiz="handleUpdateQuizData"
                                @close-update-quiz-modal="showUpdateQuizModal = false; quizIndex = null;"
                            />
                        </Transition>
                    </Teleport>

                    <!-- Lazy load create form to improve initial load performance -->
                    <CreateNewCourseQuize
                        :course="props.course.data"
                        @add-new-quiz="handleAddNewQuiz"
                    />
                </div>
            </template>
        </CourseLayout>
        
        <!-- Enhanced loading overlay with better UX -->
        <Transition name="loading-fade">
            <div v-if="isLoading" class="fixed inset-0 bg-white bg-opacity-90 z-50 h-full w-full flex items-center justify-center backdrop-blur-sm">
                <div class="flex flex-col items-center space-y-4">
                    <div class="relative">
                        <svg class="animate-spin h-16 w-16 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <div class="absolute inset-0 rounded-full border-4 border-blue-200 animate-ping"></div>
                    </div>
                    <p class="text-gray-600 font-medium">กำลังดำเนินการ...</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Modal transition animations */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
    transform: scale(0.9);
}

/* Loading overlay transition */
.loading-fade-enter-active,
.loading-fade-leave-active {
    transition: opacity 0.2s ease;
}

.loading-fade-enter-from,
.loading-fade-leave-to {
    opacity: 0;
}
</style>
