<script setup lang="ts">
import { Icon } from '@iconify/vue'
import QuizPost from '~/components/learn/course/quiz/QuizPost.vue'
import ContentLoader from '@/components/accessories/ContentLoader.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const route = useRoute()
const api = useApi()

// State
const quizzes = ref<any[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)
const showScrollButton = ref(false)

// Determine if at root route
const isRoot = computed(() => {
  return /\/quizzes\/?$/.test(route.path)
})

// Fetch quizzes
const fetchQuizzes = async () => {
  if (!course?.value?.id) return
  isLoading.value = true
  error.value = null
  try {
    const response = (await api.get(`/api/courses/${course.value.id}/quizzes`)) as any
    quizzes.value = response.quizzes || response.data || response || []
  } catch (err: any) {
    console.error('Error fetching quizzes:', err)
    error.value = err.message || 'ไม่สามารถโหลดแบบทดสอบได้'
  } finally {
    isLoading.value = false
  }
}

// Handlers
const handleCreate = () => {
  navigateTo(`/courses/${course?.value?.id}/quizzes/create`)
}

const handleEdit = (quiz: any) => {
  navigateTo(`/courses/${course?.value?.id}/quizzes/${quiz.id}/edit`)
}

const handleDelete = async (quizId: number) => {
  if (!confirm('ยืนยันการลบแบบทดสอบนี้หรือไม่?')) return
  
  try {
    await api.delete(`/api/courses/${course?.value?.id}/quizzes/${quizId}`)
    await fetchQuizzes()
  } catch (err) {
    console.error('Error deleting quiz:', err)
    alert('ไม่สามารถลบแบบทดสอบได้')
  }
}

const handleStart = (quiz: any) => {
  navigateTo(`/courses/${course?.value?.id}/quizzes/${quiz.id}/attempt`)
}

const handleView = (quiz: any) => {
  navigateTo(`/courses/${course?.value?.id}/quizzes/${quiz.id}`)
}

const scrollToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleScroll = () => {
  showScrollButton.value = window.scrollY > 300
}

onMounted(() => {
  fetchQuizzes()
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <div>
    <!-- Child Route Content -->
    <NuxtPage />

    <!-- Main Quizzes List (only show when at root) -->
    <template v-if="isRoot">
      <!-- Loading State -->
      <ContentLoader v-if="isLoading" />
      
      <!-- Error State -->
      <div
        v-else-if="error"
        class="bg-red-50 dark:bg-red-900/20 rounded-xl p-8 text-center max-w-md mx-auto"
      >
        <Icon icon="fluent:error-circle-24-regular" class="w-16 h-16 text-red-500 mx-auto mb-4" />
        <h3 class="text-xl font-bold text-red-700 dark:text-red-400 mb-2">เกิดข้อผิดพลาด</h3>
        <p class="text-red-600 dark:text-red-400 mb-4">{{ error }}</p>
        <button
          @click="fetchQuizzes"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          ลองใหม่
        </button>
      </div>

      <!-- Content -->
      <template v-else>
        <!-- Header with Create Button (Admin Only) -->
        <div
          v-if="isCourseAdmin"
          class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-800 dark:via-indigo-800 dark:to-blue-800 rounded-2xl p-6 shadow-xl mb-6"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div
                class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg"
              >
                <Icon icon="healthicons:i-exam-qualification-outline" class="w-7 h-7 text-white" />
              </div>
              <div>
                <h2 class="text-2xl font-bold text-white mb-1">แบบทดสอบทั้งหมด</h2>
                <p class="text-white/80 text-sm">{{ quizzes.length }} ชุด</p>
              </div>
            </div>
            <button
              @click="handleCreate"
              class="flex items-center gap-2 px-5 py-3 bg-white text-purple-600 rounded-xl hover:bg-purple-50 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold"
            >
              <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
              <span>เพิ่มแบบทดสอบ</span>
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="!quizzes.length"
          class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center"
        >
          <Icon
            icon="healthicons:i-exam-qualification-outline"
            class="w-24 h-24 text-gray-300 dark:text-gray-600 mx-auto mb-4"
          />
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
            ยังไม่มีแบบทดสอบในรายวิชานี้
          </h3>
          <p class="text-gray-600 dark:text-gray-400">
            {{ isCourseAdmin ? 'เริ่มสร้างแบบทดสอบแรกของคุณ' : 'อาจารย์กำลังเตรียมแบบทดสอบอยู่' }}
          </p>
        </div>

        <!-- Quizzes Feed -->
        <div v-for="(quiz, index) in quizzes" :key="quiz.id">
          <QuizPost
            :quiz="quiz"
            :is-admin="isCourseAdmin"
            :course-id="course?.id"
            :quiz-index="index + 1"
            @edit="handleEdit"
            @delete="handleDelete"
            @start="handleStart"
            @view="handleView"
          />
        </div>
      </template>
    </template>
    
    <!-- Scroll to Top Button -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 translate-y-10"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-10"
    >
      <button 
        v-if="showScrollButton"
        @click="scrollToTop"
        class="fixed bottom-8 right-8 z-[999] p-3 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-full shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl hover:-translate-y-1 transition-all"
        title="เลื่อนขึ้นด้านบน"
      >
        <Icon icon="fluent:arrow-up-24-filled" class="w-6 h-6" />
      </button>
    </transition>
  </div>
</template>
