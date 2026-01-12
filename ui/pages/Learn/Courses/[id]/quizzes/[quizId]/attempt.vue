<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { onBeforeRouteLeave } from 'vue-router'
import Swal from 'sweetalert2'
import QuestionsListViewer from '@/components/learn/course/questions/QuestionsListViewer.vue'
import ContentLoader from '@/components/accessories/ContentLoader.vue'

const route = useRoute()
const courseId = route.params.id
const quizId = route.params.quizId
const api = useApi()
const router = useRouter()

const quiz = ref<any>(null)
const quizResult = ref<any>(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const questions = ref<any[]>([])

// Timer (Count Up)
const timeElapsed = ref(0)
const timerInterval = ref<any>(null)
const durationInterval = ref<any>(null)

const formattedTime = computed(() => {
  const minutes = Math.floor(timeElapsed.value / 60)
  const seconds = timeElapsed.value % 60
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
})

    // Fetch Data
    const initQuiz = async () => {
      try {
        const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`)
        quiz.value = res.quiz
        questions.value = quiz.value.questions || []
        
        // Handle existing result
        if (quiz.value.current_result) {
            quizResult.value = quiz.value.current_result;
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
    
      } catch (err) {
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

const showBackToTop = ref(false)

const checkScroll = () => {
    showBackToTop.value = window.scrollY > 300
}

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' })
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
    if (!isSubmitting.value) {
        e.preventDefault()
        e.returnValue = ''
    }
}

onMounted(() => {
    initQuiz()
    window.addEventListener('scroll', checkScroll)
    window.addEventListener('beforeunload', confirmLeave)
    
    // Auto save duration every 10 seconds
    durationInterval.value = setInterval(updateDuration, 10000)
})

onUnmounted(() => {
     if (timerInterval.value) clearInterval(timerInterval.value)
     if (durationInterval.value) clearInterval(durationInterval.value)
     window.removeEventListener('scroll', checkScroll)
     window.removeEventListener('beforeunload', confirmLeave)
})

// Modern Route Guard
onBeforeRouteLeave((to, from, next) => {
    if (isSubmitting.value) {
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
            next();
        } else {
            next(false);
        }
    });
});

const finishAttempt = async () => {
    // 1. Confirm intention to stop (optional, or just single click as requested)
    // User requested "No validation/sending answer anymore" and just "Stop Timer".
    // I will add a simple confirmation just in case they misclicked "Finish", but NOT "Are you sure you want to submit?".
    // Actually, user said "When done... NO need to press Send Answer again... Just press Pause Time".
    // So let's make it a "Finish / Stop Timer" button.
    
    // Set submitting flag to bypass route guard
    isSubmitting.value = true
    
    try {
        if (!quizResult.value || !quizResult.value.id) {
            throw new Error('Result ID not found')
        }

        // Call endpoint to finalize result (Heartbeat/Duration update)
        // We set completed_at to mark it as done
        await api.put(`/api/courses/${courseId}/quizzes/${quizId}/results/${quizResult.value.id}`, {
             completed_at: new Date().toISOString(),
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
  <div class="container mx-auto px-4 py-6 max-w-4xl">
      <!-- Loading -->
      <!-- Loading (ContentLoader) -->
      <ContentLoader v-if="isLoading" />

      <div v-else-if="quiz" class="relative">
          <!-- Sticky Header with Timer & Actions -->
          <div class="sticky top-4 z-20 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-100 dark:border-gray-700 p-4 mb-6 flex items-center justify-between transition-all duration-300">
              <div class="flex items-center gap-4">
                  <h1 class="text-lg font-bold text-gray-800 dark:text-white truncate max-w-[200px] sm:max-w-md hidden sm:block">
                      {{ quiz.title }}
                  </h1>
                   <span class="sm:hidden font-bold text-gray-800 dark:text-white">เวลาที่ใช้</span>
              </div>

              <div class="flex items-center gap-4">
                   <!-- Timer -->
                   <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg font-mono font-bold text-lg">
                       <Icon icon="fluent:timer-24-filled" class="w-6 h-6" />
                       {{ formattedTime }}
                   </div>

                   <!-- Finish Button (Header) -->
                   <button 
                      @click="finishAttempt"
                      :disabled="isSubmitting"
                      class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium shadow-md transition-colors flex items-center gap-2"
                   >
                       <span class="hidden sm:inline">สิ้นสุดการสอบ (หยุดเวลา)</span>
                       <Icon icon="fluent:stop-24-filled" class="w-5 h-5 sm:hidden" />
                   </button>

              </div>
          </div>


          <!-- Main Question Runner -->
          <!-- We pass quizResult.started_at to resume correct timer -->
          <!-- Main Question List (Legacy) -->
          <div v-if="quiz.questions && quiz.questions.length > 0">
             <QuestionsListViewer 
                v-model="quizResult"
                :questions="quiz.questions"
                :quizId="parseInt(quizId as string)"
                :courseId="parseInt(courseId as string)"
                :quiz="quiz"
                :quizResult="quizResult"
                :questionApiRoute="`/api/quizs/${quizId}`"
            />
            
            <div class="mt-8 flex justify-center pb-10">
                 <button @click="finishAttempt" class="px-8 py-3 bg-red-600 text-white rounded-full font-bold shadow-lg hover:bg-red-700 transition flex items-center gap-2">
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
       
       <!-- Back to Top Button -->
       <transition 
           enter-active-class="transition ease-out duration-300"
           enter-from-class="opacity-0 translate-y-10"
           enter-to-class="opacity-100 translate-y-0"
           leave-active-class="transition ease-in duration-300"
           leave-from-class="opacity-100 translate-y-0"
           leave-to-class="opacity-0 translate-y-10"
        >
             <button 
               v-show="showBackToTop"
               @click="scrollToTop"
               class="fixed bottom-6 right-6 p-3 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-all z-50"
               title="Back to Top"
             >
               <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
             </button>
        </transition>
  </div>
</template>
```
