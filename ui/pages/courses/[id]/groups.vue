<script setup lang="ts">
import { Icon } from '@iconify/vue'
import GroupsList from '~/components/course/GroupsList.vue'
import GroupForm from '~/components/course/GroupForm.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

// Stores
const courseGroupStore = useCourseGroupStore()
const route = useRoute()

// State
const groups = computed(() => courseGroupStore.groups)
const isLoading = ref(false)
const showCreateModal = ref(false)
const editingGroup = ref<any>(null)

// API
const api = useApi()

// Load groups from store
const loadGroups = async () => {
  if (!course?.value?.id) return
  
  isLoading.value = true
  try {
    await courseGroupStore.fetchGroups(course.value.id)
  } catch (error) {
    console.error('Failed to load groups:', error)
  } finally {
    isLoading.value = false
  }
}

// Create new group
const handleCreate = () => {
  editingGroup.value = null
  showCreateModal.value = true
}

// Edit group
const handleEdit = (group: any) => {
  editingGroup.value = group
  showCreateModal.value = true
}

// Join group
const handleJoin = async (groupId: number) => {
  try {
    await api.post(`/api/courses/${course.value.id}/groups/${groupId}/members`)
    await handleRefresh()
  } catch (error: any) {
    alert(error.data?.message || 'ไม่สามารถเข้าร่วมกลุ่มได้')
  }
}

// Refresh groups list
const handleRefresh = async () => {
  if (course?.value?.id) {
    await courseGroupStore.fetchGroups(course.value.id, true) // Force refresh
  }
}

// Load on mount
onMounted(async () => {
  if (course?.value?.id) {
    // Check cache first
    if (!courseGroupStore.isCacheValid(course.value.id)) {
      await loadGroups()
    }
  }
})

// Watch course changes
watch(() => course?.value?.id, async (newId) => {
  if (newId) {
    await loadGroups()
  }
})
</script>

<template>
  <div>
    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <Icon icon="svg-spinners:ring-resize" class="w-8 h-8 text-blue-500" />
    </div>

    <!-- Groups List -->
    <GroupsList 
      v-else
      :groups="groups"
      :course-id="course?.id"
      :is-course-admin="isCourseAdmin"
      @create="handleCreate"
      @edit="handleEdit"
      @join="handleJoin"
      @refresh="handleRefresh"
    />

    <!-- Create/Edit Modal -->
    <DialogModal :show="showCreateModal" @close="showCreateModal = false" max-width="2xl">
      <template #title>
        <div class="flex items-center gap-3">
          <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl">
            <Icon icon="heroicons:user-group-solid" class="w-6 h-6 text-white" />
          </div>
          <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
              {{ editingGroup ? 'แก้ไขกลุ่ม' : 'สร้างกลุ่มใหม่' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">จัดการกลุ่มเรียนในรายวิชา</p>
          </div>
        </div>
      </template>

      <template #content>
        <GroupForm
          :course-id="course?.id"
          :group="editingGroup"
          @saved="showCreateModal = false"
          @cancel="showCreateModal = false"
        />
      </template>
    </DialogModal>
  </div>
</template>
