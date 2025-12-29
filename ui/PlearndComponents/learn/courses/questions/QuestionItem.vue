<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import Swal from 'sweetalert2';
import QuestionImagesViewer from "@/PlearndComponents/learn/courses/questions/QuestionImagesViewer.vue";
import QuestionOptionForm from '@/PlearndComponents/learn/courses/questions/QuestionOptionForm.vue';
import VerticalDotsDropdownMenu from '@/PlearndComponents/accessories/VerticalDotsDropdownMenu.vue';
import EditQuestionModal from '@/PlearndComponents/learn/courses/questions/EditQuestionModal.vue';
import { useQuestionAnswersStore } from '@/stores/questionAnswers';

// Debounce function สำหรับป้องกันการคลิกซ้ำ
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const props = defineProps({
    questionApiRoute: String,
    question: Object,
    indexNumber: Number,
    isCourseOwner: Boolean,
    startQuizAt: Date,
    quizId: Number, // Add quizId prop to identify which quiz this is
});

const emit = defineEmits([
    'delete-question', 
    'update-question',
]);

const selectedOption = ref(props.question.isAnsweredByAuth && props.question.authAnswerQuestion ? props.question.authAnswerQuestion.question_option_id : null);
const correctAnswer = ref(props.question.correct_option_id ?? null);
const showConfirmAnswerButton = ref(props.question.isAnsweredByAuth ? false : true);
const canEditAnswer = ref(props.question.isAnsweredByAuth ? true : false);

const isLoading = ref(false);
const isSubmitting = ref(false);
const optionActionIndex = ref(null);
const isOptionImageLoading = ref(false);
const deletingOptionImageIndex = ref(null);

const showEditModal = ref(false);
const isOpen = ref(!props.isCourseOwner);
const showEditAnswerButton = computed(() => canEditAnswer.value && !showConfirmAnswerButton.value);

// ใช้ Pinia Store สำหรับจัดการ state ของคำตอบ
const questionAnswersStore = useQuestionAnswersStore();

// Initialize store with current question state after component is mounted
onMounted(() => {
    if (props.question.isAnsweredByAuth && props.question.authAnswerQuestion && props.quizId) {
        questionAnswersStore.setAnsweredQuestion(props.quizId, props.question.id, props.question.authAnswerQuestion);
    }
});

function setCanEditAnswer(){   
    if (usePage().props.auth.user.pp < props.question.pp_fine) {
        Swal.fire({
            title: 'ขออภัย! คุณมีแต้มสะสมไม่เพียงพอ',
            text: "ต้องใช้แต้มสะสม " + props.question.pp_fine + " แต้ม",
            icon: 'warning',
        });
        canEditAnswer.value = false;
        showConfirmAnswerButton.value = true;
        return;
    }

    Swal.fire({
        title: 'แก้ไขคำตอบ',
        text: "ต้องใช้แต้มสะสม " + props.question.pp_fine + " แต้ม",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#f87171',
        confirmButtonText: 'ยืนยันการแก้ไข'
    }).then((result) => {
        if (result.isConfirmed) {
            canEditAnswer.value = false;
            showConfirmAnswerButton.value = true;
        }
    })
}

// สร้าง debounced version ของ handleConfirmAnswer
const debouncedHandleConfirmAnswer = debounce(async function() {
    // ป้องกันการเรียกซ้ำหลายชั้นโดยใช้ store
    if (isLoading.value || isSubmitting.value || questionAnswersStore.isQuestionSubmitting(props.quizId, props.question.id)) {
        return;
    }
    
    try {
        let resultResp = null;
        isSubmitting.value = true;
        isLoading.value = true;
        
        // Set submitting state in store
        questionAnswersStore.setQuestionSubmitting(props.quizId, props.question.id, true);
        
        // Check if this is a first-time answer or an edit
        if (!props.question.isAnsweredByAuth) {
            // First-time answer
            resultResp = await axios.post(`/quizs/${props.question.questionable_id}/questions/${props.question.id}/answers`,
                {
                    course_id: usePage().props.course.data.id,
                    answer_id: selectedOption.value,
                    started_at: props.startQuizAt
                });
        } else {
            // Edit existing answer
            if (usePage().props.auth.user.pp < props.question.pp_fine) {
                Swal.fire('ขออภัย! แต้มสะสมของคุณไม่เพียงพอ',
                'ไม่สามารถแก้ไขคำตอบได้ <br /> ต้องสะสมแต้มเพิ่มเติม <br /> ก่อนทำการแก้ไขคำตอบ',
                'error'
                );
                showConfirmAnswerButton.value = false;
                canEditAnswer.value = false;
                return;
            }
            
            // Get the existing answer ID
            const existingAnswer = questionAnswersStore.getAnswerForQuestion(props.quizId, props.question.id);
            const userAnswerQuestionId = existingAnswer ? existingAnswer.id : (props.question.authAnswerQuestion ? props.question.authAnswerQuestion.id : null);

            if (!userAnswerQuestionId) {
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถแก้ไขคำตอบได้เนื่องจากไม่พบข้อมูลคำตอบเดิม', 'error');
                return;
            }

            resultResp = await axios.patch(`/quizs/${props.question.questionable_id}/questions/${props.question.id}/answers/${userAnswerQuestionId}`,
            {
                course_id: usePage().props.course.data.id,
                answer_id: selectedOption.value,
            });
        }

        if (resultResp.data.success) {
            props.question.authAnswerQuestion = resultResp.data.authAnswerQuestion
            props.question.isAnsweredByAuth = true;
            showConfirmAnswerButton.value = false;
            canEditAnswer.value = true;
            
            // Update store with the answer
            questionAnswersStore.setAnsweredQuestion(props.quizId, props.question.id, resultResp.data.authAnswerQuestion);
            
            Swal.fire('สำเร็จ', resultResp.data.message, 'success' );
        } else if (resultResp.status === 422) {
            // Handle all 422 errors more gracefully
            const errorMessage = resultResp.data.message || 'เกิดข้อผิดพลาด';
            
            if (resultResp.data.message === 'คุณได้ตอบคำถามนี้ไปแล้ว') {
                // กรณีมีคำตอบซ้ำ อัพเดท state ให้ถูกต้อง
                props.question.authAnswerQuestion = resultResp.data.existing_answer_id;
                props.question.isAnsweredByAuth = true;
                showConfirmAnswerButton.value = false;
                canEditAnswer.value = true;
                
                // Update store with the existing answer
                questionAnswersStore.setAnsweredQuestion(props.quizId, props.question.id, resultResp.data.existing_answer_id);
                
                Swal.fire('แจ้งเตือน', resultResp.data.message, 'warning' );
            } else {
                // Store error in store for potential display
                questionAnswersStore.setQuestionError(props.quizId, props.question.id, errorMessage);
                Swal.fire('บันทึกล้มเหลว', errorMessage, 'error' );
            }
        } else {
            const errorMessage = resultResp.data.message || 'เกิดข้อผิดพลาดที่ไม่คาดคิด';
            questionAnswersStore.setQuestionError(props.quizId, props.question.id, errorMessage);
            Swal.fire('บันทึกล้มเหลว', errorMessage, 'error' );
        }
        
        if (resultResp.data.message === 'แก้ไข') {
            usePage().props.auth.user.pp -= props.question.pp_fine
        }

    } catch (error) {
        console.error('Error submitting answer:', error);
        const errorMessage = error.response?.data?.message || error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่';
        
        // Store error in store
        questionAnswersStore.setQuestionError(props.quizId, props.question.id, errorMessage);
        
        Swal.fire('ล้มเหลว', errorMessage, 'error' );
    } finally {
        isLoading.value = false;
        isSubmitting.value = false;
        // Clear submitting state from store
        questionAnswersStore.setQuestionSubmitting(props.quizId, props.question.id, false);
    }
}, 500); // 500ms debounce delay

// ฟังก์ชันเดิมสำหรับการเรียกใช้งานอื่นๆ ถ้าจำเป็นการ
async function handleConfirmAnswer() {
    return debouncedHandleConfirmAnswer();
}

const handleAddNewOption = (newOption) => {
    props.question.options.push(newOption);
    props.question.options.sort((a, b) => a.id - b.id);
};


async function deleteOption(id, idx) {
    try {
        optionActionIndex.value = idx;
        await axios.delete(`/options/${id}`);
        props.question.options.splice(idx, 1);
    } catch (error) {
        console.error('Error deleting option:', error);
    } finally {
        optionActionIndex.value = null;
    }
}

async function setCorrectAnswer(aid) {
    if (aid) {
        correctAnswer.value = aid;
        await axios.patch(`/questions/${props.question.id}/set_correct_option`, { answer: aid });
    } else {
        Swal.fire('ยังไม่เลือกคำตอบ','กรุณาเลือกคำตอบ','error' );
    }
}

const handleAnswerChange = (answerId) => {
    selectedOption.value = answerId;
    // Don't store temporary selection in the main store
    // Only store when answer is confirmed to prevent state changes before confirmation
    // Clear any previous errors when user changes answer
    questionAnswersStore.clearQuestionError(props.quizId, props.question.id);
};

const toggleAnswer = () => {
    isOpen.value = !isOpen.value;
};

const handleUpdateQuestion = (updated_question) => {
    props.question.text = updated_question.text;
    props.question.points = updated_question.points;
    props.question.pp_fine = updated_question.pp_fine;

    emit('update-question', updated_question);
};

const handleDeleteQuestionImage = (questionImageIndex) => {
    props.question.images.splice(questionImageIndex, 1);
};

const handleDeleteQuestion = () => {
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: "คุณไม่สามารถย้อนกลับการกระทำนี้ได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            emit('delete-question');
        }
    });
};

const deleteOptionImage = async (questionOptionIndex, optionImageIndex)=> {
    try {
        isOptionImageLoading.value = true;
        deletingOptionImageIndex.value = optionImageIndex;
        const imageId = props.question.options[questionOptionIndex].images[optionImageIndex].id;
        const delImgResp = await axios.delete(`/questions/${props.question.id}/images/${imageId}`);
        if (delImgResp.data.success) {
            props.question.images.splice(optionImageIndex, 1);
        } else {
            Swal.fire('ลบรูปภาพ', 'ลบรูปภาพไม่สำเร็จ', 'error');
        }      
    } catch (error) {
        // Handle error silently
    } finally {
        isOptionImageLoading.value = false;
        deletingOptionImageIndex.value = false;
    }
}
</script>

<template>
    <div>
        <div class="mx-auto space-y-4 p-2">
            <div class="transition-all duration-500">
                <div class="flex">
                    <button type="button" @click="toggleAnswer"
                        class="rounded-md flex items-center justify-between w-full p-3"
                        :class="question.isAnsweredByAuth ? 'bg-green-100' : 'bg-blue-200'">
                        <div class="flex items-center text-lg font-base text-gray-700 text-left w-full">
                            <p>
                                ข้อที่ {{ indexNumber + 1 }} {{ question.text }}
                            </p>
                            <p class="mx-2">
                                <span class="text-sm text-green-500 ">{{ '@'+question?.points + ' คะแนน ' }}</span>
                                <span class="text-sm text-red-500 ">{{ '#'+question?.pp_fine + ' แต้ม' }}</span>
                            </p>
                        </div>
                        <Icon icon="fa6-solid:chevron-down" class="w-4 h-4 text-gray-600 duration-500 transform" :class="{ 'rotate-180': isOpen }" />
                    </button>
                    <div v-if="props.isCourseOwner">
                        <VerticalDotsDropdownMenu
                            @edit-model="showEditModal = true" 
                            @delete-model="handleDeleteQuestion" 
                        >
                            <template #editModel>
                                <span>แก้ไขคำถาม</span>
                            </template>
                            <template #deleteModel>
                                <span>ลบคำถาม</span>
                            </template>
                        </VerticalDotsDropdownMenu>
                    </div>
                </div>

                <div v-show="isOpen" class="transform transition duration-500 ">
                    <QuestionImagesViewer
                        :modelId="question.id"
                        :images="question.images"
                        :isCourseOwner="props.isCourseOwner"

                        @delete-image="(indx)=>handleDeleteQuestionImage(indx)"
                    />

                    <div class="mt-2">
                        <div class="grid space-y-2">
                            <div v-for="(option, qo_idx) in question.options" :key="option.id" class="grid grid-cols-1 gap-4 border p-2 rounded-md">
                                <div class="flex w-full justify-between items-center">
                                    <label :for="`${question.id}+${option.id}`"
                                        class="bg-slate-100 p-3 rounded-full flex items-center w-full"
                                        :class="[{ 'bg-teal-200': selectedOption == option.id },{'hover:bg-teal-300': showConfirmAnswerButton}]">
                                        <input type="radio" :id="`${question.id}+${option.id}`"
                                            :name="`${question.id}`" :value="option.id" v-model="selectedOption" :disabled="showEditAnswerButton"
                                            :checked="selectedOption == option.id" @change="handleAnswerChange(option.id)"/>
                                        <p class="text-md text-gray-700 ml-3 dark:text-gray-400">
                                            {{ qo_idx + 1 }} . {{ option.text }}
                                        </p>
                                    </label>
                                    <button 
                                        v-if="props.isCourseOwner" 
                                        @click.prevent="deleteOption(option.id, qo_idx)"
                                        :disabled="optionActionIndex === qo_idx"
                                        :id="`btn-delete-option-${option.id}`" 
                                        class="w-9 h-9 bg-red-200 flex items-center justify-center rounded-full" 
                                    >
                                        <Icon v-if="optionActionIndex === qo_idx" icon="uiw:loading" class="w-5 h-5 text-red-600 animate-spin" />
                                        <Icon v-else icon="heroicons-outline:trash" class="w-5 h-5  text-red-600 font-bold"/>
                                    </button>
                                </div>

                                <div class="flex flex-wrap justify-center">
                                    <div v-for="(opt_image, oi_index) in option.images" :key="opt_image" class="relative  max-h-fit overflow-hidden">
                                        <img :src="opt_image.url" class="border rounded-lg md:max-w-xs" alt="Question image" />
                                        <div v-if="deletingOptionImageIndex === oi_index" 
                                            class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
                                            <Icon icon="svg-spinners:6-dots-rotate" class="w-16 h-16 text-white animate-spin" />
                                        </div>
                                        <button v-if="isCourseOwner" 
                                                @click="deleteOptionImage(qo_idx,oi_index)" 
                                                class="absolute top-2 right-2 rounded-full cursor-pointer bg-gray-100 p-[6px]">
                                            <Icon icon="fa-solid:trash-alt" class="w-4 h-4 text-red-500" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="my-4 flex flex-col items-center space-y-3" v-if="!props.isCourseOwner">
                            <button type="button"
                                :id="`btn-confirm-answer${question.id}`"
                                v-if="showConfirmAnswerButton && question.options"
                                @click.prevent="handleConfirmAnswer()"
                                :disabled="!selectedOption || isLoading || isSubmitting || questionAnswersStore.isQuestionSubmitting(props.quizId, props.question.id)"
                                class="px-6 py-3 inline-flex justify-center items-center gap-2 rounded-lg font-semibold text-white shadow-lg transform transition-all duration-200 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="[
                                    selectedOption && !isLoading && !isSubmitting
                                        ? 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 hover:scale-105 focus:ring-blue-500'
                                        : 'bg-gray-300 cursor-not-allowed'
                                ]">
                                <Icon v-if="isLoading || isSubmitting" icon="uiw:loading" class="animate-spin h-5 w-5"/>
                                <Icon v-else icon="heroicons:check-circle" class="w-5 h-5" />
                                <span v-if="!isLoading && !isSubmitting">ยืนยันคำตอบ</span>
                                <span v-else>กำลังบันทึก...</span>
                            </button>
                            
                            <!-- แสดงข้อความแจ้งเตือนเมื่อมีการพยายามตอบคำถามซ้ำ -->
                            <div v-if="props.question.isAnsweredByAuth && !showConfirmAnswerButton"
                                 class="w-full max-w-md p-3 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-700 rounded-lg text-sm flex items-center">
                                <Icon icon="heroicons:check-circle" class="w-5 h-5 mr-2 text-green-600" />
                                <span class="font-medium">คุณได้ตอบคำถามนี้ไปแล้ว</span>
                            </div>
                            
                            <button type="button"
                                :id="`btn-edit-answer${question.id}`"
                                v-if="canEditAnswer && $page.props.auth.user.pp >= question.pp_fine"
                                @click.prevent="setCanEditAnswer"
                                class="px-6 py-3 inline-flex justify-center items-center gap-2 rounded-lg font-semibold text-white shadow-lg transform transition-all duration-200 text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 hover:scale-105 focus:ring-amber-500">
                                <Icon icon="heroicons:pencil" class="w-5 h-5" />
                                <span>แก้ไขคำตอบ</span>
                                <span class="text-xs opacity-75">(-{{ question.pp_fine }} แต้ม)</span>
                            </button>
                        </div>

                        <div class="flex justify-between items-center" v-if="props.isCourseOwner">
                            <QuestionOptionForm
                                :questionableType="question.questionable_type"
                                :questionableId="question.questionable_id" 
                                :question_id="question.id"
                                :options="question.options"
                                @add-new-option="(newOption) => handleAddNewOption(newOption)"
                            />
                        </div>

                        <div class="my-2 mr-[50px]" v-if="props.isCourseOwner">
                            <select :id="`select-question-correct-answer${question.id}`"
                                class="py-1.5 px-4 pr-9 block w-full rounded-full text-md text-gray-700 dark:bg-slate-900 dark:border-gray-700 dark:text-gray-400"
                                name="set-correct-answer-selection"
                                :class="[correctAnswer ? 'border-indigo-400 bg-green-200' : 'border-red-500 bg-red-200']"
                                @change="setCorrectAnswer(correctAnswer)" v-model="correctAnswer">
                                <option selected :value="false">คำตอบที่ถูกต้องคือ ข้อ</option>
                                <option v-for="(option, i) in question.options" :key="option.id"
                                    :value="option.id">
                                    {{ i + 1 }} {{ option.text }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <EditQuestionModal 
            v-if="props.isCourseOwner"
            :question
            :questionApiRoute
            :images="question.images"
            :isVisible="showEditModal"
            :isCourseOwner="props.isCourseOwner"

            @update-question="(updatedQuestion)=>handleUpdateQuestion(updatedQuestion)"
            @delete-question-image="(image_index)=>handleDeleteQuestionImage(image_index)"
            @close-modal="showEditModal = false"
        />

    </div>
</template>
