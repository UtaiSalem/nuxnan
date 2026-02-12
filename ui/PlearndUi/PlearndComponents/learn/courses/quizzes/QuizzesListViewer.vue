<script setup>
import { computed, defineAsyncComponent } from 'vue';
// import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

// Lazy load QuizItem for better performance
const QuizItem = defineAsyncComponent(() =>
    import('@/PlearndComponents/learn/courses/quizzes/QuizItem.vue')
);

const props = defineProps({
    quizzes: Array,
    quizzesApiRoute: String
});

const emit = defineEmits([
    'update-quiz',
    'delete-quiz'
]);

// Memoize the quizzes length to avoid unnecessary recalculations
const quizzesCount = computed(() => props.quizzes?.length || 0);
const hasQuizzes = computed(() => quizzesCount.value > 0);

function handleUpdateQuiz(qId, qIndex) {
    emit('update-quiz', qId, qIndex);
}

function handleDeleteQuiz(qId, qIndex) {
    Swal.fire({
        title: 'ยืนยันการลบแบบทดสอบ',
        text: "คุณต้องการลบแบบทดสอบนี้หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ตกลง',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true, // Better UX for Thai language
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom',
            content: 'swal2-content-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            emit('delete-quiz', qId, qIndex);
        }
    });
}

</script>

<template>
    <div class="space-y-4">
        <!-- Enhanced header with better visual design -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-600 rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-center sm:text-left">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">
                            แบบทดสอบ
                            <span class="ml-2 px-3 py-1 bg-blue-600 text-white rounded-full text-sm font-semibold">
                                {{ quizzesCount }} ชุด
                            </span>
                        </h2>
                        <p class="text-gray-600" v-if="!hasQuizzes">
                            ยังไม่มีแบบทดสอบในรายวิชานี้
                        </p>
                    </div>
                    <div v-if="hasQuizzes" class="mt-4 sm:mt-0 flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-gray-600">พร้อมให้ทำแบบทดสอบ</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quiz items with transition group for better animations -->
        <TransitionGroup name="quiz-list" tag="div" class="space-y-4">
            <QuizItem
                v-for="(quiz, index) in props.quizzes"
                :key="quiz.id || index"
                :quiz="quiz"
                :quizzesApiRoute="`${props.quizzesApiRoute}/quizzes/${quiz.id}`"
                :quizIndex="index + 1"
                @update-quiz="handleUpdateQuiz(quiz.id, index)"
                @delete-quiz="handleDeleteQuiz(quiz.id, index)"
            />
        </TransitionGroup>

        <!-- Empty state with better design -->
        <div v-if="!hasQuizzes" class="text-center py-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">ไม่มีแบบทดสอบ</h3>
            <p class="text-gray-500">ยังไม่มีแบบทดสอบในรายวิชานี้</p>
        </div>
    </div>
</template>

<style scoped>
/* List transition animations */
.quiz-list-enter-active,
.quiz-list-leave-active {
    transition: all 0.3s ease;
}

.quiz-list-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.quiz-list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.quiz-list-move {
    transition: transform 0.3s ease;
}

/* Custom SweetAlert styles */
:global(.swal2-popup-custom) {
    border-radius: 12px;
    font-family: 'Sarabun', 'Kanit', sans-serif;
}

:global(.swal2-title-custom) {
    font-size: 1.5rem;
    font-weight: 600;
}

:global(.swal2-content-custom) {
    font-size: 1rem;
}
</style>
