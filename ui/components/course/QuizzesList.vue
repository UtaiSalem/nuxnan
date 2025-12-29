<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  quizzes: any[]
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  quizzes: () => [],
  isCourseAdmin: false
})

const emit = defineEmits<{
  'create': []
  'refresh': []
}>()

const api = useApi()
const isDeleting = ref(false)

// Navigate to quiz
const navigateToQuiz = (quiz: any) => {
  navigateTo(`/courses/${props.courseId}/quizzes/${quiz.id}`)
}

// Edit quiz
const editQuiz = (quiz: any) => {
  navigateTo(`/courses/${props.courseId}/quizzes/${quiz.id}/edit`)
}

// Start quiz
const startQuiz = (quiz: any) => {
  navigateTo(`/courses/${props.courseId}/quizzes/${quiz.id}/attempt`)
}

// Delete quiz
const deleteQuiz = async (quizId: number) => {
  if (!confirm('ยืนยันการลบแบบทดสอบนี้หรือไม่?')) return
  
  isDeleting.value = true
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/quizzes/${quizId}`)
    if (response.success) {
      emit('refresh')
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถลบแบบทดสอบได้')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
            <Icon icon="healthicons:i-exam-qualification-outline" class="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">แบบทดสอบ</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ quizzes.length }} ชุด</p>
          </div>
        </div>
        <button
          v-if="isCourseAdmin"
          @click="emit('create')"
          class="flex items-center gap-2 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors"
        >
          <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
          <span class="hidden sm:inline">เพิ่มแบบทดสอบ</span>
        </button>
      </div>
    </div>

    <!-- Quizzes List -->
    <TransitionGroup name="list" tag="div" class="space-y-3">
      <CourseQuizCard
        v-for="(quiz, index) in quizzes"
        :key="quiz.id"
        :quiz="quiz"
        :course-id="courseId"
        :quiz-index="index + 1"
        :is-course-admin="isCourseAdmin"
        @click="navigateToQuiz"
        @edit="editQuiz"
        @delete="deleteQuiz"
        @start="startQuiz"
      />
    </TransitionGroup>

    <!-- Empty State -->
    <div v-if="quizzes.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
      <Icon icon="healthicons:i-exam-qualification-outline" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีแบบทดสอบ</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-4">รายวิชานี้ยังไม่มีแบบทดสอบ</p>
      <button
        v-if="isCourseAdmin"
        @click="emit('create')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
        สร้างแบบทดสอบแรก
      </button>
    </div>
  </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}
.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
.list-move {
  transition: transform 0.3s ease;
}
</style>
