<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { useApi } from '@/composables/useApi'
import { useSweetAlert } from '@/composables/useSweetAlert'
import ImageLightbox from '~/components/play/feed/ImageLightbox.vue'

interface Props {
  questions: any[]
  lessonId: number
  isCreator?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:questions': [questions: any[]]
  'create': []
  'edit': [question: any]
  'delete': [question: any]
}>()

const api = useApi()
const swal = useSweetAlert()

const hasQuestions = computed(() => props.questions && props.questions.length > 0)

// --- Student Logic ---
// We need a local copy of questions to allow shuffling options without mutating props
const localQuestions = ref<any[]>([])

// Track selected option ID for each question ID
const selectedAnswers = ref<Record<number, number>>({}) 
// Track result status { is_correct, points, message }
const answerResults = ref<Record<number, { is_correct: boolean, points: number, message: string }>>({})
// Track submitting state
const submitting = ref<Record<number, boolean>>({})

// Initialize and sync questions
watch(() => props.questions, (newVal) => {
    if (newVal) {
        localQuestions.value = JSON.parse(JSON.stringify(newVal))
        
        // Restore persisted answers
        newVal.forEach(q => {
            if (q.user_answer) {
                // Restore result feedback (ถูก/ผิด)
                answerResults.value[q.id] = {
                    is_correct: !!q.user_answer.is_correct,
                    points: q.user_answer.points || 0,
                    message: q.user_answer.is_correct ? 'ถูกต้อง!' : 'ยังไม่ถูกต้อง'
                }
                // Restore selected answer เฉพาะข้อที่ถูก (ข้อผิดให้ลองใหม่ได้)
                if (q.user_answer.is_correct) {
                    selectedAnswers.value[q.id] = q.user_answer.answer_id
                }
            }
        })
    }
}, { immediate: true, deep: true })

const answeredCount = computed(() => {
    const ids = new Set(Object.keys(answerResults.value).map(Number))
    props.questions?.forEach(q => { if (q.user_answer) ids.add(q.id) })
    return ids.size
})

const earnedPoints = computed(() => {
    let total = 0
    // From current session
    Object.values(answerResults.value).forEach((r: any) => { if (r.is_correct) total += (r.points || 0) })
    // From persisted (not yet in session answerResults)
    props.questions?.forEach(q => {
        if (q.user_answer?.is_correct && !answerResults.value[q.id]) {
            total += (q.user_answer.points || 0)
        }
    })
    return total
})

const totalPoints = computed(() => {
    return props.questions?.reduce((sum, q) => sum + (q.points || 1), 0) || 0
})

// Progress Value
const progressPercentage = computed(() => {
    if (!props.questions || props.questions.length === 0) return 0
    return Math.round((answeredCount.value / props.questions.length) * 100)
})

const selectOption = (questionId: number, optionId: number) => {
    // If already correct, forbid changing? Let's stick to "if correct, done".
    if (answerResults.value[questionId]?.is_correct) return 
    selectedAnswers.value[questionId] = optionId
}

// Utility to shuffle array
const shuffleArray = (array: any[]) => {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

const submitAnswer = async (question: any) => {
    const answerId = selectedAnswers.value[question.id]
    if (!answerId) return

    submitting.value[question.id] = true
    try {
        const response = await api.post(`/api/lessons/${props.lessonId}/questions/${question.id}/answer`, {
          answer_id: answerId
        })
        
        const data = response
        answerResults.value[question.id] = {
            is_correct: data.is_correct,
            points: data.points,
            message: data.message
        }
        
        if (data.is_correct) {
            swal.toast('ถูกต้อง! เก่งมาก', 'success')
        } else {
            swal.toast('ยังไม่ถูกต้อง ลองใหม่นะ', 'error')
            
            // Shuffle options for this question to provide variety on retry
            const qIndex = localQuestions.value.findIndex(q => q.id === question.id)
            if (qIndex !== -1 && localQuestions.value[qIndex].options) {
                setTimeout(() => {
                    localQuestions.value[qIndex].options = shuffleArray([...localQuestions.value[qIndex].options])
                    selectedAnswers.value[question.id] = 0 // Clear selection
                }, 1000)
            }
        }

    } catch (error) {
        console.error('Submit answer failed', error)
        swal.error('ส่งคำตอบไม่สำเร็จ')
    } finally {
        submitting.value[question.id] = false
    }
}
// ---------------------

// --- Creator Logic ---
const deleteQuestion = async (question: any) => {
    const confirmed = await swal.confirm('ลบคำถาม', 'คุณแน่ใจหรือไม่ที่จะลบคำถามนี้?')
    if (!confirmed) return

    try {
        await api.delete(`/api/lessons/${props.lessonId}/questions/${question.id}`)
        swal.toast('ลบคำถามเรียบร้อย', 'success')
        const newQuestions = props.questions.filter(q => q.id !== question.id)
        emit('update:questions', newQuestions)
    } catch (error) {
        console.error('Failed to delete question:', error)
        swal.error('ไม่สามารถลบคำถามได้')
    }
}

// Lightbox Logic
const showLightbox = ref(false)
const lightboxImages = ref<any[]>([])
const lightboxIndex = ref(0)

const openLightbox = (images: any[], index: number) => {
    lightboxImages.value = images.map(img => ({
        ...img,
        url: img.full_url || img.image_url
    }))
    lightboxIndex.value = index
    showLightbox.value = true
}

const closeLightbox = () => {
    showLightbox.value = false
    lightboxImages.value = []
    lightboxIndex.value = 0
}



</script>

<template>
  <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 mb-6">
      <div class="flex items-center justify-between">
        <h3 class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white">
            <Icon icon="fluent:quiz-new-24-filled" class="w-6 h-6 text-orange-600" />
            แบบทดสอบ
            <span class="px-2 py-0.5 text-sm font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full">
            {{ questions.length }} ข้อ
            </span>
        </h3>
        
        <!-- Admin Actions -->
        <button 
            v-if="isCreator"
            @click="emit('create')"
            class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm font-medium"
        >
            <Icon icon="fluent:add-24-filled" class="w-4 h-4" />
            เพิ่มคำถาม
        </button>
      </div>

       <!-- Progress Bar (Student Only) -->
       <div v-if="!isCreator && hasQuestions" class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
            <div 
                class="bg-orange-600 h-2.5 rounded-full transition-all duration-500 ease-out" 
                :style="{ width: `${progressPercentage}%` }"
            ></div>
      </div>
      <div v-if="!isCreator && hasQuestions" class="text-right text-sm text-gray-500 dark:text-gray-400">
        ความคืบหน้า: {{ progressPercentage }}%
      </div>
    </div>

    <!-- Score Summary (Student Only, showing when there's progress) -->
    <div v-if="!isCreator && hasQuestions && answeredCount > 0"
         class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 
                rounded-xl p-4 border border-orange-200 dark:border-orange-800 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Icon icon="fluent:trophy-24-filled" class="w-8 h-8 text-orange-500" />
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">คะแนนที่ได้</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
              {{ earnedPoints }} <span class="text-sm font-normal text-gray-500">/ {{ totalPoints }}</span>
            </p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-600 dark:text-gray-400">ตอบแล้ว</p>
          <p class="text-lg font-bold text-gray-900 dark:text-white">
            {{ answeredCount }} <span class="text-sm font-normal text-gray-500">/ {{ questions.length }} ข้อ</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Student View: Active Quiz List (Using localQuestions) -->
    <div v-if="!isCreator && hasQuestions" class="space-y-8">
        <div 
          v-for="(question, index) in localQuestions" 
          :key="question.id"
          class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition-all hover:shadow-md"
        >
            <div class="flex items-start gap-5">
                <!-- Question Number -->
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg font-bold text-lg">
                    {{ index + 1 }}
                </div>
                
                <div class="flex-1 w-full min-w-0">
                    <!-- Question Text -->
                    <p class="font-medium text-lg text-gray-900 dark:text-white mb-4 leading-relaxed whitespace-pre-wrap">{{ question.text }}</p>
                    
                    <!-- Question Images -->
                    <div v-if="question.images?.length" class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide">
                        <img 
                            v-for="(img, imgIndex) in question.images" 
                            :key="img.id" 
                            :src="img.full_url || img.image_url" 
                            class="h-40 w-auto rounded-xl object-cover border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-95 transition-opacity shadow-sm" 
                            @click="openLightbox(question.images, imgIndex)"
                        />
                    </div>

                    <!-- Options -->
                    <div class="space-y-3 mt-2">
                         <transition-group name="list" tag="div" class="space-y-3">
                            <button
                                v-for="option in question.options"
                                :key="option.id"
                                @click="selectOption(question.id, option.id)"
                                :disabled="submitting[question.id] || answerResults[question.id]?.is_correct"
                                class="w-full flex items-center gap-4 p-4 rounded-xl border-2 transition-all text-left group relative overflow-hidden"
                                :class="[
                                    selectedAnswers[question.id] === option.id 
                                        ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/10' 
                                        : 'border-gray-200 dark:border-gray-700 hover:border-orange-200 dark:hover:border-orange-800 hover:bg-gray-50 dark:hover:bg-gray-800/50'
                                ]"
                            >
                                <!-- Selection Circle -->
                                <div 
                                    class="w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-colors"
                                    :class="[
                                        selectedAnswers[question.id] === option.id 
                                            ? 'border-orange-500 bg-orange-500 text-white' 
                                            : 'border-gray-300 dark:border-gray-600 group-hover:border-orange-400'
                                    ]"
                                >
                                    <Icon v-if="selectedAnswers[question.id] === option.id" icon="fluent:checkmark-16-filled" class="w-4 h-4" />
                                </div>
                                
                                <!-- Option Content -->
                                <div class="flex-1">
                                    <span class="text-gray-900 dark:text-gray-200 text-base">{{ option.text }}</span>
                                    <div v-if="option.images?.length" class="mt-3">
                                        <img 
                                            :src="option.images[0].full_url || option.images[0].image_url" 
                                            class="h-32 w-auto rounded-lg object-cover cursor-pointer hover:opacity-95 shadow-sm border border-gray-200 dark:border-gray-700"
                                            @click.stop="openLightbox(option.images, 0)"
                                        />
                                    </div>
                                </div>
                            </button>
                        </transition-group>
                    </div>

                    <!-- Actions & Feedback Area -->
                    <div class="mt-6 flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700/50">
                        <div class="flex-1 min-h-[40px] flex items-center">
                             <transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                             >
                                 <div v-if="answerResults[question.id]" class="flex items-center gap-3 font-medium px-4 py-2 rounded-lg" 
                                      :class="answerResults[question.id].is_correct ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'">
                                    <Icon :icon="answerResults[question.id].is_correct ? 'fluent:checkmark-circle-24-filled' : 'fluent:dismiss-circle-24-filled'" class="w-6 h-6" />
                                    <span>{{ answerResults[question.id].message }}</span>
                                    <span v-if="answerResults[question.id].is_correct" class="text-sm opacity-80 ml-1">
                                        (+{{ answerResults[question.id].points }} คะแนน)
                                    </span>
                                 </div>
                             </transition>
                        </div>

                        <button 
                            v-if="!answerResults[question.id]?.is_correct"
                            @click="submitAnswer(question)"
                            :disabled="!selectedAnswers[question.id] || submitting[question.id]"
                            class="px-8 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-medium rounded-full shadow-lg shadow-orange-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2 transform active:scale-95"
                        >
                            <span v-if="submitting[question.id]" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span>{{ submitting[question.id] ? 'กำลังส่ง...' : 'ตรวจคำตอบ' }}</span>
                            <Icon v-if="!submitting[question.id]" icon="fluent:send-24-filled" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Question List (Legacy / Creator Mode) - Keeps using props.questions -->
    <div v-if="isCreator" class="space-y-4">
        <div v-if="!hasQuestions" class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl border-dashed border-2 border-gray-200 dark:border-gray-700">
             <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <Icon icon="fluent:question-circle-24-regular" class="w-8 h-8 text-orange-500" />
             </div>
             <p class="text-gray-500 dark:text-gray-400">ยังไม่มีคำถาม</p>
             <button @click="emit('create')" class="mt-4 text-orange-600 hover:text-orange-700 font-medium">
                เพิ่มคำถามแรก
             </button>
        </div>

        <div 
          v-for="(question, index) in questions" 
          :key="question.id"
          class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm relative group"
        >
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg font-bold">
                    {{ index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white mb-2">{{ question.text }}</p>
                    
                    <!-- Images -->
                    <div v-if="question.images?.length" class="flex gap-2 overflow-x-auto pb-2">
                        <img 
                            v-for="(img, imgIndex) in question.images" 
                            :key="img.id" 
                            :src="img.full_url || img.image_url" 
                            class="h-16 w-auto rounded-lg object-cover border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-90 transition-opacity" 
                            @click="openLightbox(question.images, imgIndex)"
                        />
                    </div>

                    <!-- Options Preview -->
                    <div class="mt-3 space-y-1">
                        <div 
                           v-for="option in question.options" 
                           :key="option.id"
                           class="flex items-center gap-2 text-sm"
                           :class="option.is_correct ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-500 dark:text-gray-400'"
                        >
                            <Icon 
                                :icon="option.is_correct ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'" 
                                class="w-4 h-4 flex-shrink-0"
                            />
                            <img 
                                v-if="option.images?.length" 
                                :src="option.images[0].full_url || option.images[0].image_url" 
                                class="h-10 w-auto rounded border border-gray-200 dark:border-gray-700 object-cover cursor-pointer hover:opacity-90 transition-opacity" 
                                @click.stop="openLightbox(option.images, 0)"
                            />
                            <span v-if="option.text">{{ option.text }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button 
                        @click="emit('edit', question)" 
                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg"
                        title="แก้ไข"
                    >
                        <Icon icon="fluent:edit-20-regular" class="w-5 h-5" />
                    </button>
                    <button 
                        @click="deleteQuestion(question)" 
                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                        title="ลบ"
                    >
                        <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
                    </button>
                </div>
            </div>
            
             <div class="absolute top-4 right-4 text-xs font-medium text-gray-400">
                {{ question.points }} คะแนน
            </div>
        </div>
    </div>
    
    <ImageLightbox 
        :show="showLightbox"
        :images="lightboxImages"
        :initial-index="lightboxIndex"
        @close="closeLightbox"
    />
  </div>
</template>

<style scoped>
    .list-move, /* apply transition to moving elements */
    .list-enter-active,
    .list-leave-active {
        transition: all 0.5s ease;
    }

    .list-enter-from,
    .list-leave-to {
        opacity: 0;
        transform: translateX(30px);
    }

    /* ensure leaving items are taken out of layout flow so that moving
       items can be calculated correctly. */
    .list-leave-active {
        position: absolute;
    }
</style>
