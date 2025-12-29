<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  lessons: any[]
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  lessons: () => [],
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [lesson: any]
  'delete': [lessonId: number]
  'create': []
  'refresh': []
}>()

const api = useApi()
const isDeleting = ref(false)

// Navigate to lesson
const navigateToLesson = (lesson: any) => {
  navigateTo(`/courses/${props.courseId}/lessons/${lesson.id}`)
}

// Edit lesson
const editLesson = (lesson: any) => {
  navigateTo(`/courses/${props.courseId}/lessons/${lesson.id}/edit`)
}

// Delete lesson
const deleteLesson = async (lessonId: number) => {
  if (!confirm('ยืนยันการลบบทเรียนนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) return
  
  isDeleting.value = true
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/lessons/${lessonId}`)
    if (response.success) {
      emit('refresh')
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถลบบทเรียนได้')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
            <Icon icon="fluent:book-24-filled" class="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">บทเรียน</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ lessons.length }} บทเรียน</p>
          </div>
        </div>
        <button
          v-if="isCourseAdmin"
          @click="emit('create')"
          class="flex items-center gap-2 px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors"
        >
          <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
          <span class="hidden sm:inline">เพิ่มบทเรียน</span>
        </button>
      </div>
    </div>

    <!-- Lessons List -->
    <div v-if="lessons.length > 0" class="space-y-3">
      <CourseLessonCard
        v-for="lesson in lessons"
        :key="lesson.id"
        :lesson="lesson"
        :is-course-admin="isCourseAdmin"
        @click="navigateToLesson"
        @edit="editLesson"
        @delete="deleteLesson"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
      <Icon icon="fluent:book-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีบทเรียน</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-4">รายวิชานี้ยังไม่มีบทเรียน</p>
      <button
        v-if="isCourseAdmin"
        @click="emit('create')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
        สร้างบทเรียนแรก
      </button>
    </div>
  </div>
</template>
