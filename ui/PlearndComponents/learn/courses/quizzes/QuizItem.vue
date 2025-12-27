<script setup>
import { ref, computed, onMounted, onUnmounted, shallowRef, nextTick } from 'vue';
import DotsDropdownMenu from '@/PlearndComponents/accessories/DotsDropdownMenu.vue';
import { defineAsyncComponent } from 'vue';
import { useQuestionAnswersStore } from '@/stores/questionAnswers';

import Swal from 'sweetalert2';
import { Icon } from '@iconify/vue';

// Lazy load components for better performance
const QuestionsListViewer = defineAsyncComponent(() =>
    import('@/PlearndComponents/learn/courses/questions/QuestionsListViewer.vue')
);
const CreateNewQuestion = defineAsyncComponent(() =>
    import('@/PlearndComponents/learn/courses/questions/CreateNewQuestion.vue')
);

const props = defineProps({
    quiz: Object,
    quizIndex: Number,
    quizzesApiRoute: String
});

const emit = defineEmits([
    'update-quiz',
    'delete-quiz'
]);

const showQuestions = ref(false);

// Use Pinia store for quiz-specific state management
const questionAnswersStore = useQuestionAnswersStore();

// Use shallowRef for better performance with large objects
const quizData = shallowRef(props.quiz);

// Memoize computed properties for better performance
const questionsLength = computed(() => quizData.value.questions?.length || 0);
const totalScore = computed(() => quizData.value.total_score || 0);
const timeLimit = computed(() => quizData.value.time_limit || 0);

const startQuiz = ref(false);
const startQuizAt = ref(null);
const endQuiz = ref(false);
const timeCountdown = ref(null);
const limitTimeOut = shallowRef(null);

// Optimized progress calculation
const progressPercentage = computed(() => {
    if (!timeCountdown.value || !timeLimit.value) return 0;
    return Math.max(0, Math.min(100, (timeCountdown.value / (timeLimit.value * 60)) * 100));
});

// Initialize quiz state when component mounts
onMounted(() => {
    // Clear any existing state for this quiz to ensure fresh start
    questionAnswersStore.clearQuizAnswers(quizData.value.id);
});

// Optimized progress color calculation
const progressColor = computed(() => {
    const percentage = progressPercentage.value;
    if (percentage > 50) return '#10b981'; // Green-500
    if (percentage > 20) return '#f59e0b'; // Amber-500
    return '#ef4444'; // Red-500
});

// Optimized radial progress style
const radialProgressStyle = computed(() => ({
    background: `radial-gradient(closest-side, white 79%, transparent 80% 100%),
                 conic-gradient(${progressColor.value} ${progressPercentage.value}%, #e5e7eb 0)`,
    width: '100px',
    height: '100px',
    borderRadius: '50%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    transition: 'background 0.3s ease'
}));

// Format time display
const formattedTime = computed(() => {
    if (!timeCountdown.value) return '00:00';
    const minutes = Math.floor(timeCountdown.value / 60);
    const seconds = timeCountdown.value % 60;
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

// Optimized quiz points handling
const handleAddQuizPoints = (points) => {
    quizData.value.total_score = (quizData.value.total_score || 0) + points;
}

const handleDeleteQuizPoints = (points) => {
    quizData.value.total_score = Math.max(0, (quizData.value.total_score || 0) - points);
}

function handleStartQuiz() {
    try {
        showQuestions.value = true;
        startQuizAt.value = new Date();
        timeCountdown.value = timeLimit.value * 60;
        startQuiz.value = true;
        
        // Use setInterval for accurate timing
        limitTimeOut.value = setInterval(() => {
            timeCountdown.value -= 1;
            if (timeCountdown.value <= 0) {
                handleEndQuiz();
            }
        }, 1000); // Update every second
    } catch (error) {
        console.error('Error starting quiz:', error);
        Swal.fire({
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถเริ่มทำแบบทดสอบได้ กรุณาลองใหม่',
            icon: 'error'
        });
    }
}

async function handleEndQuiz() {
    endQuiz.value = true;
    startQuiz.value = false;
    
    // Clean up timer properly
    if (limitTimeOut.value) {
        clearInterval(limitTimeOut.value);
        limitTimeOut.value = null;
    }
    
    try {
        await axios.post(`${props.quizzesApiRoute}/efficiency`);
    } catch (error) {
        console.error('Error ending quiz:', error);
    }
}

// Clean up quiz state when component unmounts
onUnmounted(() => {
    if (limitTimeOut.value) {
        clearInterval(limitTimeOut.value);
    }
});

async function addNewQuestion(question){
    // Create new array for better reactivity
    quizData.value.questions = [...(quizData.value.questions || []), question];
    quizData.value.total_score = (quizData.value.total_score || 0) + parseInt(question.points);
    
    await nextTick(); // Wait for DOM update
    
    Swal.fire({
        title: 'บันทึกสำเร็จ',
        text: 'เพิ่มคำถามเสร็จเรียบร้อย.',
        icon: 'success',
        timerProgressBar: true,
        timer: 1000,
        showConfirmButton: false
    });
}

function toggleShowQuestions(){
    showQuestions.value = !showQuestions.value;
}



</script>

<template>
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 mt-4 relative overflow-hidden border-l-4 border-blue-500">
        
        <!-- Enhanced header with gradient background -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 relative">
            <!-- Admin menu with better positioning -->
            <div class="absolute top-2 right-2 z-10" v-if="$page.props.isCourseAdmin">
                <DotsDropdownMenu
                    @edit-model="emit('update-quiz')"
                    @delete-model="emit('delete-quiz')"
                    class="opacity-75 hover:opacity-100 transition-opacity"
                >
                    <template #editModel>
                        <div class="flex items-center space-x-2">
                            <Icon icon="heroicons:pencil" class="w-4 h-4" />
                            <span>แก้ไข</span>
                        </div>
                    </template>
                    <template #deleteModel>
                        <div class="flex items-center space-x-2 text-red-600">
                            <Icon icon="heroicons:trash" class="w-4 h-4" />
                            <span>ลบแบบทดสอบ</span>
                        </div>
                    </template>
                </DotsDropdownMenu>
            </div>

            <!-- Quiz information with better layout -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-800 mb-2 flex items-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-bold mr-3">
                            {{ quizIndex }}
                        </span>
                        {{ quizData.title }}
                    </h3>
                    
                    <!-- Quiz stats with better visual design -->
                    <div class="flex flex-wrap gap-3 text-sm">
                        <div class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full">
                            <Icon icon="heroicons:question-mark-circle" class="w-4 h-4 mr-1" />
                            {{ questionsLength }} ข้อ
                        </div>
                        <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-full">
                            <Icon icon="heroicons:star" class="w-4 h-4 mr-1" />
                            {{ totalScore }} คะแนน
                        </div>
                        <div class="inline-flex items-center px-3 py-1 bg-amber-100 text-amber-700 rounded-full">
                            <Icon icon="heroicons:clock" class="w-4 h-4 mr-1" />
                            {{ timeLimit }} นาที
                        </div>
                    </div>
                </div>

                <!-- Quiz controls for students -->
                <div v-if="!$page.props.isCourseAdmin && questionsLength" class="flex items-center justify-center">
                    <Transition name="quiz-controls" mode="out-in">
                        <div v-if="startQuiz" class="flex flex-col items-center space-y-3">
                            <!-- Enhanced timer with better visual feedback -->
                            <div class="relative">
                                <div class="radial-progress shadow-lg" :style="radialProgressStyle">
                                    <div class="text-center">
                                        <div class="text-lg font-bold" :style="{ color: progressColor }">
                                            {{ formattedTime }}
                                        </div>
                                        <div class="text-xs text-gray-500">เหลือเวลา</div>
                                    </div>
                                </div>
                                <!-- Progress indicator -->
                                <div class="absolute -bottom-2 left-0 right-0">
                                    <div class="h-1 bg-gray-200 rounded-full overflow-hidden">
                                        <div
                                            class="h-full transition-all duration-1000 ease-linear"
                                            :style="{
                                                width: `${progressPercentage}%`,
                                                backgroundColor: progressColor
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            
                            <button
                                @click.prevent="handleEndQuiz"
                                class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transform hover:scale-105 transition-all duration-200 shadow-md"
                            >
                                <Icon icon="heroicons:check-circle" class="w-5 h-5 inline mr-2" />
                                หยุดเวลา
                            </button>
                        </div>
                        
                        <button
                            v-else
                            @click.prevent="handleStartQuiz"
                            class="px-8 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg flex items-center"
                        >
                            <Icon icon="heroicons:play-circle" class="w-5 h-5 mr-2" />
                            เริ่มทดสอบ
                        </button>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Admin controls with better design -->
        <div v-if="$page.props.isCourseAdmin" class="px-4 py-3 bg-gray-50 border-t">
            <div class="flex justify-center">
                <button
                    @click.prevent="toggleShowQuestions"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-medium text-white transition-all duration-300 rounded-full focus-visible:outline-none whitespace-nowrap bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 focus:from-emerald-700 focus:to-emerald-800 transform hover:scale-105 shadow-md"
                >
                    <span>{{ showQuestions ? 'ซ่อนคำถาม' : 'แสดงคำถาม' }}</span>
                    <Icon icon="heroicons:chevron-down"
                        class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': showQuestions }"
                    />
                </button>
            </div>
        </div>
                
        <!-- Questions section with smooth transitions -->
        <Transition name="questions-section" mode="out-in">
            <div v-if="$page.props.isCourseAdmin ? showQuestions : true" class="border-t border-gray-200">
                <Suspense>
                    <QuestionsListViewer
                        :questionableType="'courses'"
                        :questionableId="$page.props.course.data.id"
                        :questionNameTh="'รายวิชา'"
                        :questionApiRoute="`/courses/${$page.props.course.data.id}/quizzes/${quizData.id}`"
                        :questions="quizData.questions"
                        v-model="startQuiz"
                        :startQuizAt="startQuizAt"
                        :quizId="quizData.id"
                        @increase-quiz-points="handleAddQuizPoints"
                        @decrease-quiz-points="handleDeleteQuizPoints"
                    />
                    <template #fallback>
                        <div class="p-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full mb-4">
                                <Icon icon="heroicons:arrow-path" class="w-6 h-6 text-blue-600 animate-spin" />
                            </div>
                            <p class="text-gray-600">กำลังโหลดคำถาม...</p>
                        </div>
                    </template>
                </Suspense>
            </div>
        </Transition>

        <!-- Create question section for admins -->
        <div v-if="$page.props.isCourseAdmin" class="border-t border-gray-200 bg-gray-50">
            <Suspense>
                <CreateNewQuestion
                    :questionApiRoute="`/courses/${$page.props.course.data.id}/quizzes/${quizData.id}`"
                    :quizId="quizData.id"
                    @add-new-question="addNewQuestion"
                />
                <template #fallback>
                    <div class="p-4 text-center">
                        <div class="inline-flex items-center justify-center w-8 h-8 bg-gray-200 rounded-full">
                            <Icon icon="heroicons:arrow-path" class="w-4 h-4 text-gray-600 animate-spin" />
                        </div>
                    </div>
                </template>
            </Suspense>
        </div>
    </div>
</template>

<style scoped>
/* Enhanced radial progress styles */
.radial-progress {
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.radial-progress:hover {
    transform: scale(1.05);
}

/* Quiz controls transition */
.quiz-controls-enter-active,
.quiz-controls-leave-active {
    transition: all 0.3s ease;
}

.quiz-controls-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.quiz-controls-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Questions section transition */
.questions-section-enter-active,
.questions-section-leave-active {
    transition: all 0.4s ease;
}

.questions-section-enter-from {
    opacity: 0;
    max-height: 0;
}

.questions-section-leave-to {
    opacity: 0;
    max-height: 0;
}

.questions-section-enter-to,
.questions-section-leave-from {
    opacity: 1;
    max-height: 2000px;
}

/* Hover effects */
.hover\:shadow-xl:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Gradient animations */
@keyframes gradient-shift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

.bg-gradient-to-r {
    background-size: 200% 200%;
    animation: gradient-shift 3s ease infinite;
}
</style>
