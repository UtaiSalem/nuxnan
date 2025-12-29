<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  assignments: any[]
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  assignments: () => [],
  isCourseAdmin: false
})

const emit = defineEmits<{
  'create': []
  'refresh': []
}>()

const api = useApi()
const isDeleting = ref(false)

// Navigate to assignment
const navigateToAssignment = (assignment: any) => {
  navigateTo(`/courses/${props.courseId}/assignments/${assignment.id}`)
}

// Edit assignment
const editAssignment = (assignment: any) => {
  navigateTo(`/courses/${props.courseId}/assignments/${assignment.id}/edit`)
}

// Delete assignment
const deleteAssignment = async (assignmentId: number) => {
  if (!confirm('ยืนยันการลบภาระงานนี้หรือไม่?')) return
  
  isDeleting.value = true
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/assignments/${assignmentId}`)
    if (response.success) {
      emit('refresh')
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถลบภาระงานได้')
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
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
            <Icon icon="material-symbols:assignment-outline" class="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">ภาระงาน</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ assignments.length }} รายการ</p>
          </div>
        </div>
        <button
          v-if="isCourseAdmin"
          @click="emit('create')"
          class="flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
        >
          <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
          <span class="hidden sm:inline">เพิ่มภาระงาน</span>
        </button>
      </div>
    </div>

    <!-- Assignments List -->
    <div v-if="assignments.length > 0" class="space-y-3">
      <CourseAssignmentCard
        v-for="assignment in assignments"
        :key="assignment.id"
        :assignment="assignment"
        :course-id="courseId"
        :is-course-admin="isCourseAdmin"
        @click="navigateToAssignment"
        @edit="editAssignment"
        @delete="deleteAssignment"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
      <Icon icon="material-symbols:assignment-outline" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีภาระงาน</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-4">รายวิชานี้ยังไม่มีภาระงาน</p>
      <button
        v-if="isCourseAdmin"
        @click="emit('create')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
        สร้างภาระงานแรก
      </button>
    </div>
  </div>
</template>
