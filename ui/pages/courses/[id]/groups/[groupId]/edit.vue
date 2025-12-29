<script setup lang="ts">
import { Icon } from '@iconify/vue'
import GroupForm from '~/components/course/GroupForm.vue'

// Inject course data
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

// Routes and API
const route = useRoute()
const router = useRouter()
const api = useApi()

// Group ID from route
const groupId = computed(() => route.params.groupId)

// State
const group = ref<any>(null)
const isLoading = ref(true)

// Check admin permission
if (!isCourseAdmin?.value) {
  navigateTo(`/courses/${route.params.id}/groups`)
}

// Load group details
const loadGroup = async () => {
  if (!course?.value?.id || !groupId.value) return
  
  isLoading.value = true
  try {
    const response = await api.get(`/api/courses/${course.value.id}/groups/${groupId.value}`)
    group.value = response.group || response.data?.group || response
  } catch (error) {
    console.error('Failed to load group:', error)
  } finally {
    isLoading.value = false
  }
}

// Handle save
const handleSaved = () => {
  navigateTo(`/courses/${course.value.id}/groups/${groupId.value}`)
}

// Handle cancel
const handleCancel = () => {
  router.back()
}

// Load on mount
onMounted(() => {
  loadGroup()
})
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-blue-500" />
    </div>

    <!-- Edit Form -->
    <div v-else-if="group" class="max-w-3xl mx-auto">
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-lg">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl">
            <Icon icon="heroicons:pencil-square" class="w-6 h-6 text-white" />
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขกลุ่ม</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ group.name }}</p>
          </div>
        </div>

        <!-- Form -->
        <GroupForm
          :course-id="course?.id"
          :group="group"
          @saved="handleSaved"
          @cancel="handleCancel"
        />
      </div>
    </div>

    <!-- Error State -->
    <div v-else class="text-center py-12">
      <Icon icon="heroicons:exclamation-circle" class="w-16 h-16 text-red-500 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบข้อมูลกลุ่ม</h3>
      <NuxtLink 
        :to="`/courses/${course?.id}/groups`"
        class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700"
      >
        <Icon icon="heroicons:arrow-left" class="w-5 h-5" />
        กลับไปหน้ากลุ่ม
      </NuxtLink>
    </div>
  </div>
</template>
