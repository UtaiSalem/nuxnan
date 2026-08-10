<template>
  <div class="space-y-6">
      <div v-for="(q, index) in questions" :key="q.id" class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 rounded-xl">
          <div class="flex gap-2 sm:gap-4">
              <span class="font-bold text-lg text-blue-600 dark:text-blue-400 min-w-[24px]">{{ index + 1 }}.</span>
              <div class="flex-grow">
                  <!-- Header: Text & Badges -->
                  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 sm:gap-4 mb-3 sm:mb-4">
                      <!-- Question Text -->
                      <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100 flex-grow">
                        <CommonRichTextViewer :content="q.text" :can-expand="false" />
                      </div>

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
                        <button 
                            type="button"
                            v-for="(img, idx) in q.images" 
                            :key="img.id" 
                            @click="openGallery(q.images, idx)"
                            aria-label="ดูรูปขนาดเต็ม"
                            class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"
                        >
                             <img :src="img.full_url" class="w-full h-auto object-contain max-h-64 sm:max-h-72 mx-auto cursor-zoom-in" loading="lazy" />
                             <div class="absolute bottom-2 right-2 bg-black/50 text-white rounded-full p-1.5 flex items-center justify-center">
                                 <Icon icon="heroicons:magnifying-glass-plus" class="w-5 h-5" />
                             </div>
                        </button>
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
                            isLocked(q.id) 
                              ? (isSelected(q.id, opt.id) ? 'cursor-not-allowed' : 'opacity-60 cursor-not-allowed bg-gray-50 dark:bg-gray-800')
                              : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50'
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
                              <span class="font-medium text-gray-900 dark:text-gray-100">
                                <CommonRichTextViewer :content="opt.text" :can-expand="false" />
                              </span>
                               <!-- Option Images -->
                               <div v-if="opt.images && opt.images.length > 0" class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <button 
                                        type="button"
                                        v-for="(optImg, idx) in opt.images" 
                                        :key="optImg.id" 
                                        @click.stop="openGallery(opt.images, idx)"
                                        aria-label="ดูรูปขนาดเต็ม"
                                        class="relative rounded overflow-hidden border border-gray-200 dark:border-gray-700"
                                    >
                                         <img :src="optImg.full_url" class="w-full h-auto object-contain max-h-48 sm:max-h-40 mx-auto cursor-zoom-in" loading="lazy" />
                                         <div class="absolute bottom-2 right-2 bg-black/50 text-white rounded-full p-1.5 flex items-center justify-center">
                                             <Icon icon="heroicons:magnifying-glass-plus" class="w-5 h-5" />
                                         </div>
                                    </button>
                               </div>
                          </div>
                      </div>
                      
                      <!-- Action Buttons -->
                      <transition name="fade" mode="out-in">
                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                            <!-- Case 1: Is Answered AND Not Editing -> Show "Edit Answer" (only if user has enough pp) -->
                            <button 
                                v-if="isAnswered(q.id) && !isEditing(q.id) && hasEnoughPpForEdit(q.pp_fine)"
                                @click="requestEditing(q)"
                                class="flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <Icon icon="heroicons:pencil-square" />
                                แก้ไขคำตอบ
                                <span class="text-xs opacity-75">(-{{ q.pp_fine || 0 }} แต้ม)</span>
                            </button>
                            
                            <!-- Case 1b: Is Answered but not enough pp to edit -->
                            <div 
                                v-else-if="isAnswered(q.id) && !isEditing(q.id) && !hasEnoughPpForEdit(q.pp_fine)"
                                class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm font-medium rounded-lg cursor-not-allowed"
                            >
                                <Icon icon="heroicons:lock-closed" />
                                แต้มไม่พอแก้ไข (ต้องใช้ {{ q.pp_fine || 0 }} แต้ม)
                            </div>

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
      <ImageGalleryModal
        :show="showGallery"
        :images="galleryImages"
        :start-index="galleryIndex"
        :title="galleryTitle"
        @close="closeGallery"
      />
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
import { ref, onMounted } from 'vue'
import ImageGalleryModal from '@/components/ImageGalleryModal.vue'
import Swal from 'sweetalert2'
import { useQuestionAnswersStore } from '@/stores/questionAnswers'
import { useAuthStore } from '@/stores/auth'

const showGallery = ref(false)
const galleryImages = ref([])
const galleryIndex = ref(0)
const galleryTitle = ref('')

const openGallery = (images, index, title = 'รูปภาพประกอบข้อสอบ') => {
    galleryImages.value = (images || []).map(img => ({ ...img, url: img.full_url || img.image_url }))
    galleryIndex.value = index
    galleryTitle.value = title
    showGallery.value = true
}

const closeGallery = () => {
    showGallery.value = false
    galleryImages.value = []
    galleryIndex.value = 0
}

const props = defineProps({
    courseId: { type: [Number, String], required: false },
    questions: { type: Array, required: true },
    quizId: { type: Number, required: true },
    quiz: { type: Object, required: true },
    quizResult: { type: Object, required: false }
})

const api = useApi()
const { user } = useAuth()
const authStore = useAuthStore()
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

// Check if an option is currently selected (Temporary state OR Confirmed state)
const isSelected = (questionId, optionId) => {
    const tempAnswer = store.getTemporaryAnswer(props.quizId, questionId);
    if (tempAnswer) {
        return tempAnswer === optionId;
    }
    // Fallback to confirmed answer if no temporary selection
    return store.getAnswerForQuestion(props.quizId, questionId) === optionId;
}

const getOptionClass = (questionId, optionId) => {
    // Selected state with Dark Mode support
    return isSelected(questionId, optionId) 
        ? 'border-blue-600 border-2 bg-blue-100 dark:bg-blue-900/60 dark:border-blue-500 ring-1 ring-blue-600 dark:ring-blue-500 shadow-md z-10' 
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

// Check if user has enough pp (Plearnd Points) to edit an answer
const hasEnoughPpForEdit = (ppFine) => {
    const userPp = Number(user.value?.points) || 0;
    return userPp >= (ppFine || 0);
}

// Request editing with pp_fine confirmation
const requestEditing = async (question) => {
    const ppFine = question.pp_fine || 0;
    
    if (ppFine > 0) {
        const result = await Swal.fire({
            title: 'แก้ไขคำตอบ',
            text: `ต้องใช้แต้มสะสม ${ppFine} แต้ม`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#7c3aed',
            cancelButtonColor: '#f87171',
            confirmButtonText: 'ยืนยันการแก้ไข',
            cancelButtonText: 'ยกเลิก'
        });
        
        if (!result.isConfirmed) return;
    }
    
    startEditing(question.id);
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

    // ตรวจสอบ pp_fine เฉพาะเมื่อแก้ไขคำตอบ (ตอบครั้งแรกฟรี)
    const isEditingAnswer = isEditing(question.id);
    if (isEditingAnswer) {
        const ppFine = question.pp_fine || 0;
        const userPp = Number(user.value?.points) || 0;
        
        if (ppFine > 0 && userPp < ppFine) {
            await Swal.fire({
                icon: 'error',
                title: 'แต้มสะสมไม่เพียงพอ!',
                text: `คุณมี ${userPp} แต้ม แต่ต้องใช้ ${ppFine} แต้มในการแก้ไขคำตอบ`,
                confirmButtonText: 'ตกลง'
            });
            return;
        }
    }

    // Proceed to Save
    store.setQuestionSubmitting(props.quizId, question.id, true);
    
    const payload = {
        answer_id: selectedOptionId,
        course_id: props.courseId || props.quiz.course_id
    };

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
            handleSuccess(question.id, selectedOptionId, response.authAnswerQuestion, response.points, response);
        }

    } catch (e) {
        // Handle 422 (Answer already exists)
        // Check various properties for status code as it depends on the fetch client wrapper
        const status = e.statusCode || e.status || e.response?.status;

        if (status === 422 && e.data && e.data.existing_answer_id) {
             const existingId = e.data.existing_answer_id;
             
             try {
                 const response = await api.put(`/api/quizs/${props.quizId}/questions/${question.id}/answers/${existingId}`, payload);
                 if (response && response.authAnswerQuestion) {
                     handleSuccess(question.id, selectedOptionId, response.authAnswerQuestion, response.points, response);
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

const handleSuccess = async (questionId, optionId, userAnswerId, points, responseData = {}) => {
    // Update Store
    store.setAnsweredQuestion(props.quizId, questionId, optionId);
    
    // Update Local Cache for API usage
    localUserAnswerIds.value[questionId] = userAnswerId;
    
    // Update Local Points
    if (points !== undefined) {
        localEarnedPoints.value[questionId] = points;
    }
    
    // Sync user pp from backend response (if returned)
    if (responseData.user_pp !== undefined) {
        authStore.setPoints(responseData.user_pp);
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
