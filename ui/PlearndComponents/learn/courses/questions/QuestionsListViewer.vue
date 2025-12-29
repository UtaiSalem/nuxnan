<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Swal from 'sweetalert2';
import QuestionItem from '@/PlearndComponents/learn/courses/questions/QuestionItem.vue';
import { useQuestionAnswersStore } from '@/stores/questionAnswers';

const props = defineProps({
    questionableType: String,
    questionableId: Number,
    questionNameTh: String,
    questionApiRoute: String,
    questions: Object,
    startQuizAt: Date,
    quizId: Number, // Add quizId prop to identify which quiz this is
});

const model = defineModel();

const emit = defineEmits([
    'increase-quiz-points',
    'decrease-quiz-points',
]);

const isLoading = ref(false);

// ใช้ Pinia Store สำหรับจัดการ state ของคำตอบ
const questionAnswersStore = useQuestionAnswersStore();

// Computed properties for better state management
const hasQuestions = computed(() => props.questions && props.questions.length > 0);
const answeredQuestionsCount = computed(() => questionAnswersStore.answeredQuestionsCount(props.quizId));
const submittingQuestionsCount = computed(() => questionAnswersStore.submittingQuestionsCount(props.quizId));

// Calculate progress percentage based on store state
const progressPercentage = computed(() => {
    if (!props.questions || props.questions.length === 0) return 0;
    return Math.round((answeredQuestionsCount.value / props.questions.length) * 100);
});

// Determine progress color based on completion
const progressColor = computed(() => {
    const percentage = progressPercentage.value;
    if (percentage === 0) return 'bg-gray-400';
    if (percentage < 25) return 'bg-red-500';
    if (percentage < 50) return 'bg-orange-500';
    if (percentage < 75) return 'bg-yellow-500';
    if (percentage < 100) return 'bg-blue-500';
    return 'bg-green-500';
});

// Progress status text
const progressStatus = computed(() => {
    const percentage = progressPercentage.value;
    if (percentage === 0) return 'ยังไม่เริ่มทำ';
    if (percentage < 25) return 'เริ่มต้น';
    if (percentage < 50) return 'กำลังดำเนินการ';
    if (percentage < 75) return 'คืบหน้าดี';
    if (percentage < 100) return 'ใกล้เสร็จ';
    return 'ทำเสร็จแล้ว';
});

// Initialize store immediately when component is created (before mount)
// This ensures the state is available as soon as the page loads
if (props.questions) {
    props.questions.forEach(question => {
        // Check if question has been answered by the current user
        if (question.isAnsweredByAuth && question.authAnswerQuestion) {
            questionAnswersStore.setAnsweredQuestion(props.quizId, question.id, question.authAnswerQuestion);
        }
    });
}

// Initialize store with existing answered questions when component mounts
onMounted(() => {
    // Add a small delay to ensure Pinia store is fully initialized
    setTimeout(() => {
        if (props.questions) {
            props.questions.forEach(question => {
                // Check if question has been answered by the current user
                if (question.isAnsweredByAuth && question.authAnswerQuestion) {
                    questionAnswersStore.setAnsweredQuestion(props.quizId, question.id, question.authAnswerQuestion);
                }
            });
        }
    }, 100);
});

// Watch for changes in props.questions to update store when questions are loaded
watch(() => props.questions, (newQuestions) => {
    if (newQuestions) {
        newQuestions.forEach(question => {
            // Check if question has been answered by the current user
            if (question.isAnsweredByAuth && question.authAnswerQuestion) {
                questionAnswersStore.setAnsweredQuestion(props.quizId, question.id, question.authAnswerQuestion);
            }
        });
    }
}, { immediate: true, deep: true });

// Additional watch to ensure progress is always visible
watch(() => questionAnswersStore.answeredQuestionsCount, (newCount) => {
    // console.log('Progress updated:', newCount, '/', props.questions?.length || 0);
}, { immediate: true });

const handleUpdateQuestion = (updatedQuestion, qIdx) => {
    emit('increase-quiz-points', updatedQuestion.points);
}

async function handleDeleteQuestion(qID, qIdx) {
    try {
        isLoading.value = true;
        let response = await axios.delete(`${props.questionApiRoute}/questions/${qID}`);
        if (response.status === 204) {
            // Clear the question from store when deleted
            questionAnswersStore.clearAnsweredQuestion(props.quizId, qID);
            
            emit('decrease-quiz-points', props.questions[qIdx].points);
            props.questions.splice(qIdx, 1);
            Swal.fire({
                title: 'ลบคำถามสำเร็จ',
                icon: 'success',
                timer: 1500,
                showConfirmButton: true,
                timerProgressBar: true,
            });
        }
        
    } catch (error) {
        Swal.fire('ลบคำถามล้มเหลว', 'เกิดข้อผิดพลาดในการลบคำถาม กรุณาลองใหม่', 'error');
    } finally {
        isLoading.value = false;
    }
}

// Function to reset all answers (useful for quiz reset functionality)
const resetAllAnswers = () => {
    if (props.questions && props.quizId) {
        props.questions.forEach(question => {
            questionAnswersStore.clearAnsweredQuestion(props.quizId, question.id);
        });
    }
};

// Expose functions for parent components
defineExpose({
    resetAllAnswers,
    answeredQuestionsCount,
    submittingQuestionsCount
});

</script>

<template>
    <div class="w-full">
        <div class="tabs flex flex-col justify-center w-full">
            <div class="tabs-header px-2 w-full flex flex-col items-center justify-center">
                <!-- Quiz progress indicator (always visible for students, hidden for course owners) -->
                <div v-if="props.questions && props.questions.length > 0 && !$page.props.isCourseAdmin" class="w-full mb-4 p-4 rounded-lg shadow-sm"
                     :class="progressPercentage === 100 ? 'bg-green-50 border border-green-200' : 'bg-blue-50 border border-blue-200'">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center">
                            <div class="w-3 h-3 rounded-full mr-2" :class="progressColor"></div>
                            <span class="text-sm font-medium text-gray-700">ความคืบหน้า:</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-lg" :class="progressPercentage === 100 ? 'text-green-600' : 'text-blue-600'">
                                {{ answeredQuestionsCount }}/{{ props.questions.length }}
                            </span>
                            <span class="text-sm text-gray-600 ml-1">ข้อ</span>
                        </div>
                    </div>
                    
                    <!-- Progress bar with color -->
                    <div class="w-full bg-gray-200 rounded-full h-3 mb-2 overflow-hidden">
                        <div class="h-3 rounded-full transition-all duration-500 ease-out flex items-center justify-end pr-1"
                             :class="progressColor"
                             :style="{ width: `${progressPercentage}%` }">
                            <span v-if="progressPercentage > 10" class="text-xs text-white font-semibold">
                                {{ progressPercentage }}%
                            </span>
                        </div>
                    </div>
                    
                    <!-- Progress status -->
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium" :class="progressPercentage === 100 ? 'text-green-600' : 'text-gray-600'">
                            {{ progressStatus }}
                        </span>
                        <span class="text-xs text-gray-500">
                            เหลืออีก {{ props.questions.length - answeredQuestionsCount }} ข้อ
                        </span>
                    </div>
                </div>

                <!-- No questions message -->
                <div class="text-base font-normal" v-if="!hasQuestions">
                    (ไม่มีคำถาม)
                </div>
                
                <!-- Questions list for students (only show when quiz is started) -->
                <div v-else-if="model && !$page.props.isCourseAdmin" class="w-full">
                    <div v-for="(question, idx) in props.questions" :key="question.id" class="w-full border rounded-md p-1 my-2">
                        <QuestionItem
                            :questionApiRoute
                            :question="question"
                            :indexNumber="idx"
                            :isCourseOwner="$page.props.isCourseAdmin"
                            :startQuizAt="startQuizAt"
                            :quizId="props.quizId"

                            @update-question="(updatedQuestion) => handleUpdateQuestion(updatedQuestion, idx)"
                            @delete-question="handleDeleteQuestion(question.id, idx)"
                        />
                    </div>
                </div>
                
                <!-- Questions list for course admins -->
                <div v-if="$page.props.isCourseAdmin" class="w-full">
                    <div v-for="(question, idx) in props.questions" :key="question.id" class="w-full border rounded-md my-2">
                        <QuestionItem
                            :questionApiRoute
                            :question="question"
                            :indexNumber="idx"
                            :isCourseOwner="$page.props.isCourseAdmin"
                            :startQuizAt="startQuizAt"
                            :quizId="props.quizId"

                            @update-question="(updatedQuestion) => handleUpdateQuestion(updatedQuestion, idx)"
                            @delete-question="handleDeleteQuestion(question.id, idx)"
                        />
                    </div>
                </div>

                <hr>
            </div>
        </div>
        
        <!-- Loading overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-white bg-opacity-80 z-20 h-screen w-screen flex items-center justify-center">
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-20 w-20 text-gray-800" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <p class="mt-4 text-gray-600">กำลังดำเนินการ...</p>
            </div>
        </div>
    </div>
</template>
