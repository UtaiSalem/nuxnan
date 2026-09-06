<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { onBeforeRouteLeave } from 'vue-router'
import Swal from 'sweetalert2'
import QuestionsListViewer from '@/components/learn/course/questions/QuestionsListViewer.vue'
import ContentLoader from '@/components/accessories/ContentLoader.vue'

import { useQuestionAnswersStore } from '@/stores/questionAnswers'

const route = useRoute()
const courseId = route.params.id
const quizId = route.params.quizId
const api = useApi()
const router = useRouter()
const answersStore = useQuestionAnswersStore()

const quiz = ref<any>(null)
const quizResult = ref<any>(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const eligibility = ref<any>(null)
const questionsHiddenReason = ref<string | null>(null)
const isLeavingConfirmed = ref(false)

// Normalize questions to handle both array and {data: []} formats
const normalizedQuestions = computed(() => {
  if (!quiz.value?.questions) return []
  return Array.isArray(quiz.value.questions) ? quiz.value.questions : (quiz.value.questions.data || [])
})

// Progress Tracking
const answeredCount = computed(() => {
  return answersStore.answeredQuestionsCount(quizId as string)
})

// Timer (Count Up)
const timeElapsed = ref(0)
const timerInterval = ref<any>(null)
const durationInterval = ref<any>(null)

const formattedTime = computed(() => {
  const hours = Math.floor(timeElapsed.value / 3600)
  const minutes = Math.floor((timeElapsed.value % 3600) / 60)
  const seconds = timeElapsed.value % 60

  return [
    hours.toString().padStart(2, '0'),
    minutes.toString().padStart(2, '0'),
    seconds.toString().padStart(2, '0'),
  ].join(':')
})

// Fetch Data
const initQuiz = async () => {
      try {
        const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`)
        eligibility.value = res.eligibility
        questionsHiddenReason.value = res.questions_hidden_reason

        // Guard: Not eligible to take exam
        if (res.canTakeExam === false) {
           await Swal.fire({
             icon: 'error',
             title: 'ไม่มีสิทธิ์เข้าสอบ',
             text: res.questions_hidden_reason || 'คุณยังไม่มีสิทธิ์ทำข้อสอบนี้ หรือคะแนนสะสมไม่เพียงพอ',
             confirmButtonText: 'กลับไปหน้ารายละเอียด'
           })
           isLeavingConfirmed.value = true
           router.replace(`/courses/${courseId}/quizzes/${quizId}`)
           return
        }

        quiz.value = res.quiz

        // Handle existing result
        if (quiz.value.current_result) {
            // Check if already completed
            if (quiz.value.current_result.completed_at) {
                const retry = await Swal.fire({
                    title: 'คุณทำข้อสอบไปแล้ว',
                    text: 'ต้องการเริ่มทำใหม่หรือไม่?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'เริ่มใหม่',
                    cancelButtonText: 'ยกเลิก'
                })

                if (retry.isConfirmed) {
                    const resultRes = await api.post(`/api/courses/${courseId}/quizzes/${quizId}/results`, {})
                    quizResult.value = resultRes.quizResult
                } else {
                    isLeavingConfirmed.value = true
                    router.replace(`/courses/${courseId}/quizzes/${quizId}`)
                    return
                }
            } else {
                quizResult.value = quiz.value.current_result;
            }
        } else {
            // Start new attempt
            const resultRes = await api.post(`/api/courses/${courseId}/quizzes/${quizId}/results`, {})
            quizResult.value = resultRes.quizResult
        }
    
        // Init Timer logic (Count Up)
        // Load previous duration if exists
        if (quizResult.value && quizResult.value.duration) {
             timeElapsed.value = parseInt(quizResult.value.duration)
        } else {
             timeElapsed.value = 0
        }
        
        startTimer()
    
      } catch (err: any) {
        console.error(err)
        Swal.fire('Error', 'Failed to load quiz: ' + (err.message || err), 'error')
      } finally {
        isLoading.value = false
      }
    }

const startTimer = () => {
    if (timerInterval.value) clearInterval(timerInterval.value)
    timerInterval.value = setInterval(() => {
        timeElapsed.value++
    }, 1000)
}

// Heartbeat & Warning
const updateDuration = async () => {
    if (!quizResult.value || !quizResult.value.id || isSubmitting.value) return
    try {
        await api.put(`/api/courses/${courseId}/quizzes/${quizId}/results/${quizResult.value.id}`, {
             duration: timeElapsed.value
        })
    } catch (e) {
        // Silent fail
        console.error('Heartbeat failed', e)
    }
}





    
    // Auto save duration every 10 seconds
// Native Browser Guard (For Refresh/Close)
const confirmLeave = (e: BeforeUnloadEvent) => {
    if (!isSubmitting.value && !isLeavingConfirmed.value) {
        e.preventDefault()
        e.returnValue = ''
    }
}

onMounted(() => {
    initQuiz()
    window.addEventListener('beforeunload', confirmLeave)
    
    // Auto save duration every 10 seconds
    durationInterval.value = setInterval(updateDuration, 10000)
})

onUnmounted(() => {
     if (timerInterval.value) clearInterval(timerInterval.value)
     if (durationInterval.value) clearInterval(durationInterval.value)
     window.removeEventListener('beforeunload', confirmLeave)
})

// Modern Route Guard
onBeforeRouteLeave((to, from, next) => {
    if (isSubmitting.value || isLeavingConfirmed.value) {
        next();
        return;
    }

    Swal.fire({
        title: 'ออกจากหน้าสอบ?',
        text: "การทำข้อสอบจะยังไม่ถูกส่ง คุณต้องการออกจริงหรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ออกเลย',
        cancelButtonText: 'ทำต่อ'
    }).then((result) => {
        if (result.isConfirmed) {
            isLeavingConfirmed.value = true
            next();
        } else {
            next(false);
        }
    });
});

const finishAttempt = async () => {
    isSubmitting.value = true
    
    // หยุด timer ทันทีก่อน finalize — ป้องกัน race condition และ timer เดินระหว่าง Swal
    if (timerInterval.value) {
        clearInterval(timerInterval.value)
        timerInterval.value = null
    }
    if (durationInterval.value) {
        clearInterval(durationInterval.value)
        durationInterval.value = null
    }
    
    try {
        if (!quizResult.value || !quizResult.value.id) {
            throw new Error('Result ID not found')
        }

        // Call endpoint to finalize result (Heartbeat/Duration update)
        // We set completed_at to mark it as done
        await api.put(`/api/courses/${courseId}/quizzes/${quizId}/results/${quizResult.value.id}`, {
             finalize: true,
             duration: timeElapsed.value
        })
        
        await Swal.fire({
            icon: 'success',
            title: 'สิ้นสุดการทำข้อสอบ',
            text: 'ระบบบันทึกเวลาเรียบร้อยแล้ว',
            timer: 1500,
            showConfirmButton: false
        })
        
        // Use replace to prevent back navigation
        router.replace(`/courses/${courseId}/quizzes/${quizId}`)
    } catch (err) {
        console.error(err)
        // Even if it fails, allowing exit might be safer, but let's warn
        Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึกเวลา', 'error')
        isSubmitting.value = false
    }
}


</script>

<template>
  <div class="container mx-auto px-0 sm:px-3 py-4 sm:px-4 sm:py-6 max-w-4xl">
      <!-- Loading -->
      <!-- Loading (ContentLoader) -->
      <ContentLoader v-if="isLoading" />

      <div v-else-if="quiz" class="relative">
          <!-- Sticky Header with Timer & Actions -->
          <div class="sticky top-2 sm:top-4 z-20 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl shadow-xl border border-blue-100 dark:border-gray-700 p-2 sm:p-4 mb-4 sm:mb-6 flex flex-col sm:flex-row items-center justify-between transition-all duration-300 gap-2 sm:gap-4">
              <div class="flex items-center justify-between w-full sm:flex-1 sm:min-w-0 px-1 sm:px-0">
                  <div class="flex items-center gap-2 overflow-hidden">
                      <div class="w-1.5 h-6 bg-blue-600 rounded-full hidden sm:block"></div>
                      <h1 class="text-sm sm:text-lg font-bold text-gray-800 dark:text-white truncate max-w-[180px] sm:max-w-md">
                          {{ quiz.title }}
                      </h1>
                  </div>
                  <!-- Mobile Answered Count -->
                  <div class="sm:hidden flex items-center gap-1 text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-full border border-blue-100 dark:border-blue-800">
                      <Icon icon="fluent:checkbox-checked-24-filled" class="w-3 h-3" />
                      <span>{{ answeredCount }}/{{ normalizedQuestions.length }}</span>
                  </div>
              </div>

              <div class="flex items-center justify-between sm:justify-end w-full sm:shrink-0 gap-2 sm:gap-4 border-t sm:border-t-0 border-gray-100 dark:border-gray-700 pt-2 sm:pt-0">
                   <!-- Answered Count (Desktop) -->
                   <div class="hidden sm:flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg border border-gray-100 dark:border-gray-600">
                       <Icon icon="fluent:checkbox-checked-24-filled" class="w-5 h-5 text-green-500" />
                       <span class="whitespace-nowrap">ตอบแล้ว <span class="font-bold text-gray-900 dark:text-white">{{ answeredCount }}</span> / {{ normalizedQuestions.length }} ข้อ</span>
                   </div>

                   <div class="flex items-center justify-between xs:justify-end gap-2 w-full sm:w-auto min-w-0">
                        <!-- Timer Card -->
                        <div :class="[
                            'shrink-0 inline-flex h-10 sm:h-12 w-[136px] sm:w-[156px] items-center justify-center gap-1.5 px-3 rounded-xl font-mono font-bold text-base sm:text-xl tabular-nums whitespace-nowrap text-white shadow-lg ring-2 transition-all duration-300',
                            isSubmitting 
                                ? 'bg-gray-500 ring-gray-200 dark:ring-gray-700 opacity-75' 
                                : 'bg-gradient-to-r from-blue-700 to-blue-500 shadow-blue-200 dark:shadow-none ring-blue-100 dark:ring-blue-900/50'
                        ]">
                            <Icon icon="fluent:timer-24-filled" :class="['w-5 h-5 sm:w-6 sm:h-6', !isSubmitting && 'animate-pulse']" />
                            {{ formattedTime }}
                        </div>

                        <!-- Finish Button (Header) -->
                        <button 
                           @click="finishAttempt"
                           :disabled="isSubmitting"
                           class="min-h-[44px] sm:min-h-0 shrink-0 px-3 sm:px-6 py-1.5 sm:py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 font-bold shadow-lg shadow-red-200 dark:shadow-none transition-all active:scale-95 flex items-center justify-center gap-2 border-b-4 border-red-800 hover:border-b-2 hover:translate-y-[2px]"
                        >
                            <span class="text-sm sm:text-base hidden sm:inline">สิ้นสุดการสอบ</span>
                            <Icon icon="fluent:stop-24-filled" class="w-5 h-5" />
                        </button>
                   </div>
              </div>
          </div>


          <!-- Main Question Runner -->
          <!-- We pass quizResult.started_at to resume correct timer -->
          <!-- Main Question List (Legacy) -->
          <div v-if="normalizedQuestions.length > 0">
             <QuestionsListViewer 
                v-model="quizResult"
                :questions="normalizedQuestions"
                :quizId="parseInt(quizId as string)"
                :courseId="parseInt(courseId as string)"
                :quiz="quiz"
                :quizResult="quizResult"
                :questionApiRoute="`/api/quizs/${quizId}`"
            />
            
            <div class="mt-8 flex justify-center pb-10">
                 <button 
                    @click="finishAttempt" 
                    :disabled="isSubmitting"
                    :class="[
                        'px-8 py-3 text-white rounded-full font-bold shadow-lg transition flex items-center gap-2',
                        isSubmitting ? 'bg-gray-500 opacity-75 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700'
                    ]"
                 >
                    <Icon icon="fluent:stop-24-filled" class="w-5 h-5" />
                    สิ้นสุดการทำข้อสอบ (หยุดเวลา)
                 </button>
            </div>
          </div>

          <div v-else class="flex flex-col items-center justify-center p-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
             <Icon icon="fluent:document-error-24-regular" class="w-16 h-16 text-gray-300 mb-4" />
             <h3 class="text-xl font-medium text-gray-600 dark:text-gray-400">ยังไม่มีคำถามในแบบทดสอบนี้</h3>
             <p class="text-gray-400 mt-2">โปรดติดต่อผู้สอน</p>
          </div>
      </div>
       
  </div>
</template>
