<template>
  <div class="space-y-6">
      <div v-for="(q, index) in questions" :key="q.id" class="p-6 bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-xl">
          <div class="flex gap-4">
              <span class="font-bold text-lg text-blue-600 dark:text-blue-400 min-w-[24px]">{{ index + 1 }}.</span>
              <div class="flex-grow">
                  <!-- Header: Text & Badges -->
                  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                      <!-- Question Text -->
                      <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100 flex-grow" v-html="q.text"></div>
  
                      <!-- Badges (Flex Item) -->
                      <div class="flex items-center gap-2 flex-shrink-0">
                           <!-- Question Value -->
                           <div class="text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-2 py-1 rounded-md border border-blue-100 dark:border-blue-800">
                               {{ q.points }} คะแนน
                           </div>
    
                           <!-- PP Fine (แต้มค่าปรับสำหรับแก้ไขคำตอบ) -->
                           <div class="text-xs font-medium bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300 px-2 py-1 rounded-md border border-orange-100 dark:border-orange-800 flex items-center gap-1">
                               <span>{{ q.pp_fine || 0 }} แต้ม</span>
                           </div>
                      </div>
                  </div>
                  
                  <!-- Question Images -->
                   <div v-if="q.images && q.images.length > 0" class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div v-for="img in q.images" :key="img.id" class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                             <img :src="img.full_url" class="w-full h-auto object-cover" loading="lazy" />
                        </div>
                   </div>

                  <div class="space-y-3">
                      <!-- 
                        Options Logic: 
                        - Disabled if: Question is Answered AND Not in Editing Mode
                      -->
                      <div v-for="opt in q.options" :key="opt.id" 
                          class="relative flex items-start p-3 rounded-lg border transition-all group"
                          :class="[
                            getOptionClass(q.id, opt.id),
                            isLocked(q.id) ? 'opacity-70 cursor-not-allowed bg-gray-50 dark:bg-gray-800' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50'
                          ]"
                          @click="!isLocked(q.id) && selectOption(q, opt)"
                      >
                          <div class="flex items-center h-5 mt-1">
                              <input 
                                  :name="`question_${q.id}`" 
                                  type="radio" 
                                  :checked="isSelected(q.id, opt.id)"
                                  :disabled="isLocked(q.id)"
                                  class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600 disabled:text-gray-400"
                              />
                          </div>
                          <div class="ml-3 text-sm flex-grow">
                              <span class="font-medium text-gray-900 dark:text-gray-100" v-html="opt.text"></span>
                               <!-- Option Images -->
                               <div v-if="opt.images && opt.images.length > 0" class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div v-for="optImg in opt.images" :key="optImg.id" class="rounded overflow-hidden border border-gray-200 dark:border-gray-700">
                                         <img :src="optImg.full_url" class="w-full h-24 object-cover" loading="lazy" />
                                    </div>
                               </div>
                          </div>
                      </div>
                      
                      <!-- Action Buttons -->
                      <transition name="fade" mode="out-in">
                        <div class="mt-4 flex justify-end gap-2">
                            <!-- Case 1: Is Answered AND Not Editing -> Show "Edit Answer" -->
                            <button 
                                v-if="isAnswered(q.id) && !isEditing(q.id)"
                                @click="startEditing(q.id)"
                                class="flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <Icon icon="heroicons:pencil-square" />
                                แก้ไขคำตอบ
                            </button>

                            <!-- Case 2: Show Confirm Button (If unconfirmed changes OR Editing) -->
                            <button 
                                v-else-if="hasUnconfirmedChanges(q.id) || isEditing(q.id)"
                                @click.stop="confirmAnswer(q)"
                                :disabled="store.isQuestionSubmitting(quizId, q.id)"
                                class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <Icon v-if="store.isQuestionSubmitting(quizId, q.id)" icon="eos-icons:loading" class="animate-spin" />
                                <Icon v-else icon="heroicons:check-circle" />
                                {{ store.isQuestionSubmitting(quizId, q.id) ? 'กำลังบันทึก...' : 'ยืนยันคำตอบ' }}
                            </button>
                            
                             <!-- Cancel Edit Button (Optional, if editing) -->
                             <button 
                                v-if="isEditing(q.id)"
                                @click="cancelEditing(q.id)"
                                class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg text-sm font-medium transition-colors"
                            >
                                ยกเลิก
                            </button>
                        </div>
                      </transition>
                  </div>
              </div>
          </div>
      </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { ref, onMounted } from 'vue'
import Swal from 'sweetalert2'
import { useQuestionAnswersStore } from '@/stores/questionAnswers'

const props = defineProps({
    courseId: { type: [Number, String], required: false },
    questions: { type: Array, required: true },
    quizId: { type: Number, required: true },
    quiz: { type: Object, required: true },
    quizResult: { type: Object, required: false }
})

const api = useApi()
const { user } = useAuth()
const store = useQuestionAnswersStore()

// Local state for editing mode
const editingQuestions = ref({}) // { questionId: boolean }

// Helper to check if a question is already answered (Confirmed)
const isAnswered = (questionId) => store.isQuestionAnswered(props.quizId, questionId)

// Helper to check if we are currently editing a question
const isEditing = (questionId) => !!editingQuestions.value[questionId]

// Locked = Answered AND Not Editing
const isLocked = (questionId) => isAnswered(questionId) && !isEditing(questionId)

// Local cache for created IDs in this session (since store only tracks option IDs currently)
// Used to store the Answer ID returned from the API so we can use it for PUT updates later.
const localUserAnswerIds = ref({}); 
// Local cache for earned points (Freshly updated)
const localEarnedPoints = ref({});

onMounted(() => {
    // Initialize store from props
    // We populate the store with existing answers to sync state
    if (props.questions) {
        props.questions.forEach(q => {
            // Check if user has answered this question previously (from backend data)
            const existingAnswerId = q.user_answer?.answer_id || q.isAnsweredByAuth;
            const existingUserAnswerId = q.user_answer?.id; // The ID of the answer record itself

            if (existingAnswerId) {
                // Set as Answered in Store
                store.setAnsweredQuestion(props.quizId, q.id, existingAnswerId);
                // Also set as temporary answer to reflect selection
                store.setTemporaryAnswer(props.quizId, q.id, existingAnswerId);
                
                // Cache the User Answer ID if available
                if (existingUserAnswerId) {
                    localUserAnswerIds.value[q.id] = existingUserAnswerId;
                }
            }
        });
    }
})

// Check if an option is currently selected (Temporary state)
const isSelected = (questionId, optionId) => {
    return store.getTemporaryAnswer(props.quizId, questionId) === optionId;
}

const getOptionClass = (questionId, optionId) => {
    return isSelected(questionId, optionId) 
        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/10 ring-1 ring-blue-500' 
        : 'border-gray-200 dark:border-gray-700';
}

const selectOption = (question, option) => {
    // Update Temporary State in Store
    if (isLocked(question.id)) return; // Double check
    store.setTemporaryAnswer(props.quizId, question.id, option.id);
}

const hasUnconfirmedChanges = (questionId) => {
    // Show confirm if:
    // 1. We have a temp selection
    // 2. AND (Answer is NOT confirmed yet OR Temp selection != Confirmed Answer)
    const temp = store.getTemporaryAnswer(props.quizId, questionId);
    const confirmed = store.getAnswerForQuestion(props.quizId, questionId);
    
    // If not answered yet, any selection is a change
    if (!store.isQuestionAnswered(props.quizId, questionId)) {
        return !!temp;
    }
    
    // If answered, only changes if temp differs
    return temp && temp !== confirmed;
}

const startEditing = (questionId) => {
    editingQuestions.value[questionId] = true;
}

const cancelEditing = (questionId) => {
    // Revert temporary selection to the confirmed answer
    const confirmed = store.getAnswerForQuestion(props.quizId, questionId);
    if (confirmed) {
        store.setTemporaryAnswer(props.quizId, questionId, confirmed);
    }
    editingQuestions.value[questionId] = false;
}

// Helper to get earned points (Priority: Local Fresh > Prop)
const getEarnedPoints = (question) => {
    if (localEarnedPoints.value[question.id] !== undefined) {
        return localEarnedPoints.value[question.id];
    }
    // Check prop
    if (question.user_answer && question.user_answer.points !== undefined) {
        return question.user_answer.points;
    }
    // If just confirmed but no points returned yet (rare), or not answered
    return null;
}

const confirmAnswer = async (question) => {
    const selectedOptionId = store.getTemporaryAnswer(props.quizId, question.id);
    if (!selectedOptionId) return;

    // 1. Check Points Logic
    const userPoints = user.value?.points || 0;
    const requiredPoints = question.points || 0;

    if (userPoints < requiredPoints) {
        await Swal.fire({
            icon: 'error',
            title: 'แต้มไม่เพียงพอ!',
            text: `คุณมี ${userPoints} แต้ม แต่ข้อนี้ต้องใช้ ${requiredPoints} แต้มในการตอบ`,
            confirmButtonText: 'ตกลง'
        });
        return; // Stop here
    }

    // 2. Proceed to Save
    store.setQuestionSubmitting(props.quizId, question.id, true);
    
    const payload = {
        answer_id: selectedOptionId,
        course_id: props.courseId || props.quiz.course_id
    };
    console.log('Sending answer payload:', payload);

    try {
        let response;
        
        // Determine ID for PUT
        let userAnswerId = question.user_answer?.id; 
        
        // Fallback to local cache if not in prop (e.g. created in this session)
        if (!userAnswerId && localUserAnswerIds.value[question.id]) {
            userAnswerId = localUserAnswerIds.value[question.id];
        }

        // Check if we assume it exists
        if (userAnswerId || store.isQuestionAnswered(props.quizId, question.id)) {
             if (userAnswerId) {
                 response = await api.put(`/api/quizs/${props.quizId}/questions/${question.id}/answers/${userAnswerId}`, payload);
             } else {
                 // Try POST as fallback, expect 422 if it exists
                 response = await api.post(`/api/quizs/${props.quizId}/questions/${question.id}/answers`, payload);
             }
        } else {
             response = await api.post(`/api/quizs/${props.quizId}/questions/${question.id}/answers`, payload);
        }
        
        if (response && response.authAnswerQuestion) {
            handleSuccess(question.id, selectedOptionId, response.authAnswerQuestion, response.points);
        }

    } catch (e) {
        // Handle 422 (Answer already exists)
        // Check various properties for status code as it depends on the fetch client wrapper
        const status = e.statusCode || e.status || e.response?.status;
        console.log('Error caught in confirmAnswer:', { status, data: e.data, error: e });
        
        if (status === 422 && e.data && e.data.existing_answer_id) {
             console.log('Answer already exists, switching to update...', e.data.existing_answer_id);
             const existingId = e.data.existing_answer_id;
             
             try {
                 const response = await api.put(`/api/quizs/${props.quizId}/questions/${question.id}/answers/${existingId}`, payload);
                 if (response && response.authAnswerQuestion) {
                     handleSuccess(question.id, selectedOptionId, response.authAnswerQuestion, response.points);
                     return;
                 }
             } catch (retryError) {
                 console.error('Failed to update existing answer', retryError);
             }
        }

        console.error('Failed to save answer', e)
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: e.data?.message || 'ไม่สามารถบันทึกคำตอบได้ กรุณาลองใหม่',
        });
    } finally {
        store.setQuestionSubmitting(props.quizId, question.id, false);
    }
}

const handleSuccess = async (questionId, optionId, userAnswerId, points) => {
    // Update Store
    store.setAnsweredQuestion(props.quizId, questionId, optionId);
    
    // Update Local Cache for API usage
    localUserAnswerIds.value[questionId] = userAnswerId;
    
    // Update Local Points
    if (points !== undefined) {
        localEarnedPoints.value[questionId] = points;
    }
    
    // Exit Editing Mode
    editingQuestions.value[questionId] = false;
    
    await Swal.fire({
        icon: 'success',
        title: 'บันทึกคำตอบสำเร็จ',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500
    });
}
</script>
