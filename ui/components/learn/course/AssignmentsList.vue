<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref } from 'vue'
import AssignmentCard from './AssignmentCard.vue'
import CourseAssignmentFormModal from './CourseAssignmentFormModal.vue'

interface Props {
  assignments: any[]
  isCourseAdmin?: boolean
  courseId: string | number
  availableGroups?: any[]
}

const props = withDefaults(defineProps<Props>(), {
  assignments: () => [],
  isCourseAdmin: false,
  availableGroups: () => []
})

const emit = defineEmits<{
  'refresh': []
}>()

const api = useApi()
const isDeleting = ref(false)
const showModal = ref(false)
const editingAssignment = ref<any>(null)

// Open Modal (Create or Edit)
const openModal = (assignment: any = null) => {
  editingAssignment.value = assignment
  showModal.value = true
}

const handleModalSubmit = () => {
  emit('refresh')
  showModal.value = false
}

// Navigate to assignment details
const navigateToAssignment = (assignment: any) => {
  navigateTo(`/courses/${props.courseId}/assignments/${assignment.id}`)
}

// Edit assignment (triggered by Card)
const editAssignment = (assignment: any) => {
  openModal(assignment)
}

// Delete assignment
const deleteAssignment = async (assignmentId: number) => {
  if (!confirm('ยืนยันการลบภาระงานนี้หรือไม่?')) return
  
  isDeleting.value = true
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/assignments/${assignmentId}`)
    if (response) { // API wrapper usually returns handling
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
    <!-- Header -->
    <div
      v-if="isCourseAdmin"
      class="bg-gradient-to-r from-violet-600 via-indigo-600 to-cyan-600 dark:from-violet-900 dark:via-indigo-900 dark:to-cyan-900 rounded-2xl p-6 shadow-xl mb-6 text-white"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
            <Icon icon="material-symbols:assignment-outline" class="w-7 h-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold mb-1">ภาระงานทั้งหมด</h2>
            <p class="text-white/80 text-sm">{{ assignments.length }} งาน</p>
          </div>
        </div>
        <button
          v-if="isCourseAdmin"
          @click="openModal(null)"
          class="flex items-center gap-2 px-5 py-3 bg-white/10 text-white rounded-xl hover:bg-white/20 border border-white/20 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105 font-bold backdrop-blur-sm"
        >
          <Icon icon="fluent:add-circle-24-filled" class="w-5 h-5" />
          <span>สร้างงานใหม่</span>
        </button>
      </div>
    </div>

    <!-- Assignments List -->
    <div v-if="assignments.length > 0" class="space-y-3">
      <AssignmentCard
        v-for="assignment in assignments"
        :key="assignment.id"
        :assignment="assignment"
        :course-id="courseId"
        :is-course-admin="isCourseAdmin"
        @click="navigateToAssignment"
        @edit="editAssignment"
        @delete="deleteAssignment"
        @refresh="emit('refresh')"
      />
    </div>

    <!-- Empty State -->
    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center border border-gray-100 dark:border-gray-700">
      <div class="w-24 h-24 bg-gray-50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-6">
        <Icon icon="fluent:clipboard-task-24-regular" class="w-12 h-12 text-gray-400" />
      </div>
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีภาระงาน</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-xs mx-auto">
        {{ isCourseAdmin ? 'เริ่มต้นสร้างภาระงานให้นักเรียนของคุณ' : 'อาจารย์ยังไม่ได้มอบหมายงานในขณะนี้' }}
      </p>
       <button
        v-if="isCourseAdmin"
        @click="openModal(null)"
        class="inline-flex items-center gap-2 px-6 py-3 bg-orange-500 text-white rounded-xl hover:bg-orange-600 transition-all shadow-lg hover:shadow-orange-500/30 font-semibold"
      >
        <Icon icon="fluent:add-24-filled" class="w-5 h-5" />
        สร้างภาระงานแรก
      </button>
    </div>

    <!-- Edit/Create Modal -->
    <CourseAssignmentFormModal
      :show="showModal"
      :assignment="editingAssignment"
      :course-id="courseId"
      :available-groups="availableGroups"
      @close="showModal = false"
      @submit="handleModalSubmit"
    />
  </div>
</template>
