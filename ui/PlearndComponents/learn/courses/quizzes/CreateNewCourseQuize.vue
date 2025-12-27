<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { Icon } from '@iconify/vue';
import Textarea from 'primevue/textarea';
import { useDebounceFn } from '@vueuse/core';

import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
    course: Object,
});

const emit = defineEmits(['add-new-quiz']);

// Reactive state with better organization
const isLoading = ref(false);
const isFetchingQuizs = ref(false);
const isCopyingQuizs = ref(false);
const showCreateNewQuizeForm = ref(false);
const errors = ref(null);
const oldQuizs = ref(null);

// Time management with better reactivity
const initTimeLimit = ref(1);
const start_date = ref(new Date());
const end_date = ref(new Date(start_date.value.getTime() + initTimeLimit.value * 60000));

// Optimized form with computed properties
const form = reactive({
  user_id: usePage().props.auth.user.id,
  title: '',
  description: '',
  start_date: computed(() => start_date.value?.toISOString() || ''),
  end_date: computed(() => end_date.value?.toISOString() || ''),
  shuffle_questions: false,
  time_limit: computed(() => initTimeLimit.value),
  is_active: true,
  passing_score: 50
});

// Computed properties for better performance
const isFormValid = computed(() => {
    return form.title.trim() !== '' &&
           start_date.value &&
           end_date.value &&
           end_date.value > start_date.value;
});

const hasOldQuizzes = computed(() => oldQuizs.value && oldQuizs.value.length > 0);

// Optimized date handlers
function handleStartDateSelect(date){
    start_date.value = date;
    updateEndDateBasedOnTimeLimit();
}

function handleEndDateSelect(date){
    end_date.value = date;
}

// Optimized time limit handlers
const handleTimeLimitChange = () => {
    updateEndDateBasedOnTimeLimit();
};

const handleTimeLimitInput = () => {
    if (!initTimeLimit.value || initTimeLimit.value < 0) {
        initTimeLimit.value = 1;
    }
    updateEndDateBasedOnTimeLimit();
};

// Helper function to update end date
function updateEndDateBasedOnTimeLimit() {
    if (start_date.value && initTimeLimit.value > 0) {
        end_date.value = new Date(start_date.value.getTime() + initTimeLimit.value * 60000);
    }
}

// Watch for changes in start_date to update end_date
watch(start_date, () => {
    updateEndDateBasedOnTimeLimit();
});

// const form = useForm({
//   user_id: usePage().props.auth.user.id,
//   title: 'ทดสอบ',
//   description: 'ไม่มีคำอธิบาย',
//   start_date: new Date(start_date),
//   end_date: new Date(end_date),
//   shuffle_questions: false,
//   time_limit: 10,
//   is_active: true,
//   passing_score: 0
// })

const handleCreateNewQuizFormSubmit = async () => {
    if (isLoading.value || !isFormValid.value) return;

    try {
        isLoading.value = true;
        errors.value = null;
        
        // Create a clean form object for submission
        const formData = {
            user_id: form.user_id,
            title: form.title.trim(),
            description: form.description.trim(),
            start_date: form.start_date,
            end_date: form.end_date,
            shuffle_questions: form.shuffle_questions,
            time_limit: form.time_limit,
            is_active: form.is_active,
            passing_score: form.passing_score
        };

        const resp = await axios.post(`/courses/${props.course.id}/quizzes`, formData);
        
        if (resp.data?.success) {
            emit('add-new-quiz', resp.data.quiz);
            SwalAlert('สำเร็จ', 'เพิ่มแบบทดสอบเรียบร้อย', 'success');
            await nextTick();
            handleCancleCreateNewQuizForm();
        } else {
            console.error('Server response:', resp.data);
            SwalAlert('ล้มเหลว', resp.data?.message || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (error) {
        console.error('Error creating quiz:', error);
        errors.value = error.response?.data?.errors || ['เกิดข้อผิดพลาดในการเชื่อมต่อ'];
        SwalAlert('ล้มเหลว', errors.value[0] || 'เกิดข้อผิดพลาด', 'error');
    } finally {
        isLoading.value = false;
    }
}

const handleCancleCreateNewQuizForm = () => {
    showCreateNewQuizeForm.value = false;
    errors.value = null;
    
    // Reset form values
    form.title = '';
    form.description = '';
    form.shuffle_questions = false;
    form.is_active = true;
    form.passing_score = 50;
    
    // Reset time values
    initTimeLimit.value = 1;
    start_date.value = new Date();
    end_date.value = new Date(start_date.value.getTime() + initTimeLimit.value * 60000);
    
    // Clear old quizzes
    oldQuizs.value = null;
    
    isLoading.value = false;
}

// Optimized quiz title input handler
const handleQuizTitleInput = () => {
    if (isFetchingQuizs.value || !form.title.trim()) return;
    isFetchingQuizs.value = true;
    fetchOldQuizzes();
};

// Debounced fetch function with better error handling
const fetchOldQuizzes = useDebounceFn(async () => {
    if (!form.title.trim()) {
        oldQuizs.value = null;
        isFetchingQuizs.value = false;
        return;
    }
    
    try {
        const resp = await axios.get(`/quizzes/get-quizzes`, {
            params: {
                title: form.title.trim(),
                limit: 5 // Limit results for better performance
            }
        });
        
        if (resp.data?.quizs) {
            oldQuizs.value = resp.data.quizs;
        } else {
            oldQuizs.value = null;
        }
    } catch (error) {
        console.error('Error fetching old quizzes:', error);
        oldQuizs.value = null;
    } finally {
        isFetchingQuizs.value = false;
    }
}, 800); // Reduced debounce time for better UX

// Optimized old quiz selection handler
const handleClickOldQuizs = (quiz) => {
    form.title = quiz.title || '';
    form.description = quiz.description || '';
    form.shuffle_questions = Boolean(quiz.shuffle_questions);
    form.is_active = Boolean(quiz.is_active);
    form.passing_score = quiz.passing_score || 50;
    
    // Handle dates safely
    if (quiz.start_date) {
        start_date.value = new Date(quiz.start_date);
    }
    if (quiz.end_date) {
        end_date.value = new Date(quiz.end_date);
    }
    
    initTimeLimit.value = quiz.time_limit || 1;
    
    // Clear old quizzes
    oldQuizs.value = null;
};

// Optimized quiz copying with better error handling
const handleCopyOldQuizs = async (quiz) => {
    if (isCopyingQuizs.value) return;
    
    isCopyingQuizs.value = true;
    try {
        const resp = await axios.post(`/quizzes/${quiz.id}/duplicate`, {
            course_id: props.course.id
        });
        
        if (resp.data?.success) {
            emit('add-new-quiz', resp.data.quiz);
            SwalAlert('สำเร็จ', 'คัดลอกแบบทดสอบเรียบร้อย', 'success');
            await nextTick();
            handleCancleCreateNewQuizForm();
        } else {
            console.error('Copy response:', resp.data);
            SwalAlert('ล้มเหลว', resp.data?.message || 'ไม่สามารถคัดลอกแบบทดสอบได้', 'error');
        }
    } catch (error) {
        console.error('Error copying quiz:', error);
        SwalAlert('ล้มเหลว', 'เกิดข้อผิดพลาดในการคัดลอกแบบทดสอบ', 'error');
    } finally {
        isCopyingQuizs.value = false;
    }
};

// Optimized old quiz removal
const handleSpliceOldQuizs = (old_q_index) => {
    if (oldQuizs.value && oldQuizs.value.length > old_q_index) {
        oldQuizs.value = oldQuizs.value.filter((_, index) => index !== old_q_index);
    }
};

function SwalAlert(title, text, icon) {
    Swal.fire({
        title: title,
        text: text,
        icon: icon,
    });
}

</script>

<template>
    <div class="mt-4">
        <!-- Enhanced add quiz button with better design -->
        <Transition name="fade-slide" mode="out-in">
            <div v-if="$page.props.isCourseAdmin && !showCreateNewQuizeForm"
                class="bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-300 p-6 text-center">
                <button
                    type="button"
                    @click="showCreateNewQuizeForm = true"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-violet-500 to-violet-600 text-white font-semibold rounded-lg hover:from-violet-600 hover:to-violet-700 transform hover:scale-105 transition-all duration-200 shadow-md"
                >
                    <Icon icon="heroicons:plus-circle" class="w-5 h-5 mr-2" />
                    เพิ่มแบบทดสอบ
                </button>
            </div>
        </Transition>

        <!-- Enhanced form with better visual design -->
        <Transition name="fade-slide" mode="out-in">
            <div v-if="$page.props.isCourseAdmin && showCreateNewQuizeForm" class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Form header -->
                <div class="bg-gradient-to-r from-violet-500 to-violet-600 p-4 text-white">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold">สร้างแบบทดสอบใหม่</h2>
                        <button
                            @click="handleCancleCreateNewQuizForm"
                            class="text-white hover:text-violet-200 transition-colors"
                        >
                            <Icon icon="heroicons:x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <form @submit.prevent="handleCreateNewQuizFormSubmit" class="p-6 space-y-6">
                    <!-- Quiz title with enhanced design -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="course-quiz-name-input-form">
                            ชื่อแบบทดสอบ <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <Textarea
                                v-model="form.title"
                                @input="handleQuizTitleInput"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                                id="course-quiz-name-input-form"
                                rows="2"
                                required
                                placeholder="กรอกชื่อแบบทดสอบ..."
                            />
                            <div class="absolute right-2 top-2">
                                <Icon v-if="isFetchingQuizs" icon="svg-spinners:6-dots-rotate" class="w-5 h-5 text-violet-500 animate-spin" />
                            </div>
                        </div>
                    </div>

                    <!-- Old quizzes suggestions with better design -->
                    <Transition name="slide-down">
                        <div v-if="hasOldQuizzes" class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">แบบทดสอบที่คล้ายกัน:</h3>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <div v-for="(quiz, index) in oldQuizs" :key="quiz.id"
                                    class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 hover:border-violet-300 transition-all duration-200">
                                    <button
                                        type="button"
                                        @click.prevent="handleClickOldQuizs(quiz)"
                                        class="flex items-center flex-1 text-left text-sm text-gray-700 hover:text-violet-600 transition-colors"
                                    >
                                        <Icon icon="heroicons:document-text" class="w-4 h-4 mr-2 text-violet-500" />
                                        <span class="truncate">{{ quiz.title }}</span>
                                    </button>
                                    <div class="flex items-center space-x-1">
                                        <button
                                            type="button"
                                            @click.prevent="handleCopyOldQuizs(quiz)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="คัดลอกแบบทดสอบ"
                                        >
                                            <Icon icon="heroicons:document-duplicate" class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click.prevent="handleSpliceOldQuizs(index)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="ลบ"
                                        >
                                            <Icon icon="heroicons:trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Description with enhanced design -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="course-quiz-description-input-form">
                            คำอธิบาย
                        </label>
                        <textarea
                            v-model="form.description"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 placeholder-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                            id="course-quiz-description-input-form"
                            rows="3"
                            placeholder="เพิ่มคำอธิบายเพิ่มเติม (ไม่จำเป็น)">
                        </textarea>
                    </div>

                    <!-- Time limit with enhanced design -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            เวลาที่ใช้ทำแบบทดสอบ
                        </label>
                        <div class="flex items-center space-x-3">
                            <input
                                type="number"
                                id="course-quiz-time_limit-input"
                                min="1"
                                v-model="initTimeLimit"
                                @change="handleTimeLimitChange"
                                @input="handleTimeLimitInput"
                                class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                            />
                            <span class="text-gray-600">นาที</span>
                        </div>
                    </div>

                    <!-- Date range with enhanced design -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            วันที่เริ่ม - สิ้นสุด
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">วันที่เริ่ม</label>
                                <VueDatePicker
                                    v-model="start_date"
                                    name="form-start_date"
                                    time-picker-inline
                                    :format="'dd/MM/yyyy HH:mm'"
                                    @update:model-value="handleStartDateSelect"
                                    class="w-full"
                                    :class="{ 'border-red-300': end_date.value <= start_date.value }"
                                />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">วันที่สิ้นสุด</label>
                                <VueDatePicker
                                    v-model="end_date"
                                    name="form-end_date"
                                    time-picker-inline
                                    :format="'dd/MM/yyyy HH:mm'"
                                    @update:model-value="handleEndDateSelect"
                                    class="w-full"
                                    :class="{ 'border-red-300': end_date.value <= start_date.value }"
                                />
                            </div>
                        </div>
                        <div v-if="end_date.value <= start_date.value" class="mt-1 text-sm text-red-600">
                            วันที่สิ้นสุดต้องมากกว่าวันที่เริ่ม
                        </div>
                    </div>

                    <!-- Passing score with enhanced design -->
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2">
                            คะแนนผ่านขั้นต่ำ
                        </label>
                        <div class="flex items-center space-x-3">
                            <input
                                id="course-quiz-passing_score-input"
                                type="number"
                                min="0"
                                max="100"
                                v-model="form.passing_score"
                                class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-all duration-200"
                            />
                            <span class="text-gray-600">%</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">*คะแนนรวมจะถูกคำนวนจากแต่ละคำถาม</p>
                    </div>

                    <!-- Options with enhanced design -->
                    <div class="space-y-4">
                        <!-- Shuffle questions -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <input
                                    class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500"
                                    type="checkbox"
                                    id="course-quiz-shuffle-questions-form-input"
                                    v-model="form.shuffle_questions"
                                />
                                <label
                                    class="ml-3 text-sm font-medium text-gray-700 cursor-pointer"
                                    for="course-quiz-shuffle-questions-form-input">
                                    สลับข้อสำหรับผู้สอบแต่ละคน
                                </label>
                            </div>
                        </div>

                        <!-- Active status -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-700 mr-3">สถานะ:</span>
                                <span class="text-sm text-gray-600">{{ form.is_active ? 'เปิดสอบ' : 'ร่าง' }}</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-violet-600"></div>
                            </label>
                        </div>
                    </div>

                    <!-- Error messages -->
                    <div v-if="errors && errors.length" class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex">
                            <Icon icon="heroicons:exclamation-triangle" class="w-5 h-5 text-red-400 mr-2" />
                            <div>
                                <h3 class="text-sm font-medium text-red-800">เกิดข้อผิดพลาด:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li v-for="(err, index) in errors" :key="index">{{ err }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form actions with enhanced design -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            @click.prevent="handleCancleCreateNewQuizForm"
                            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200"
                            type="button"
                        >
                            ยกเลิก
                        </button>
                        <button
                            :disabled="isLoading || !isFormValid"
                            class="px-6 py-2.5 bg-gradient-to-r from-violet-500 to-violet-600 text-white rounded-lg hover:from-violet-600 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center"
                            type="submit"
                        >
                            <Icon v-if="isLoading" icon="svg-spinners:6-dots-rotate" class="w-4 h-4 mr-2 animate-spin" />
                            {{ isLoading ? 'กำลังบันทึก...' : 'บันทึก' }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <!-- Enhanced loading overlay for copying -->
        <Transition name="fade">
            <div v-if="isCopyingQuizs" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center backdrop-blur-sm">
                <div class="bg-white rounded-lg p-6 flex flex-col items-center space-y-4">
                    <div class="relative">
                        <Icon icon="svg-spinners:6-dots-rotate" class="w-12 h-12 text-violet-600 animate-spin" />
                        <div class="absolute inset-0 rounded-full border-4 border-violet-200 animate-ping"></div>
                    </div>
                    <p class="text-gray-700 font-medium">กำลังคัดลอกแบบทดสอบ...</p>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Fade and slide transitions */
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

/* Slide down transition */
.slide-down-enter-active,
.slide-down-leave-active {
    transition: all 0.3s ease;
}

.slide-down-enter-from {
    opacity: 0;
    max-height: 0;
    transform: translateY(-10px);
}

.slide-down-leave-to {
    opacity: 0;
    max-height: 0;
    transform: translateY(-10px);
}

.slide-down-enter-to,
.slide-down-leave-from {
    opacity: 1;
    max-height: 300px;
    transform: translateY(0);
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Custom styles for date picker */
:deep(.dp__main) {
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

:deep(.dp__active_date) {
    background-color: #8b5cf6;
    color: white;
}

:deep(.dp__today) {
    border: 1px solid #8b5cf6;
}

:deep(.dp__range_end, .dp__range_start) {
    background-color: #8b5cf6;
}
</style>
