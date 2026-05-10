<script setup lang="ts">
import AssignmentsList from '~/components/learn/course/AssignmentsList.vue'
import { Icon } from '@iconify/vue'
import ContentLoader from '~/components/accessories/ContentLoader.vue'

// Inject course data from parent layout
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin') as Ref<boolean>

const api = useApi()
const assignments = ref<any[]>([])
const groups = ref<any[]>([])
const isLoading = ref(true)

const fetchAssignments = async () => {
  if (!course.value?.id) return
  
  isLoading.value = true
  try {
    const courseId = course.value.id; // Added this line
    const response = await api.get(`/api/courses/${courseId}/assignments`)
    const data = response.assignments || []
    assignments.value = Array.isArray(data) ? data : (data.data || [])
    // assignments.value = response.assignments || []
    // assignments.value = response.assignments || []
    groups.value = response.groups || []
  } catch (error) {
    console.error('Failed to load assignments:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAssignments()
})
</script>

<template>
  <div class="px-3 sm:px-4 lg:px-0 pb-6">
    <ContentLoader v-if="isLoading" />
    
    <AssignmentsList 
      v-else
      :assignments="assignments"
      :available-groups="groups"
      :course-id="course?.id"
      :is-course-admin="isCourseAdmin"
      @refresh="fetchAssignments"
    />
  </div>
</template>
