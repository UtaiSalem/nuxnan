<script setup lang="ts">
import { Icon } from '@iconify/vue'
import GroupsList from '~/components/course/GroupsList.vue'
import GroupForm from '~/components/course/GroupForm.vue'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const refreshCourse = inject<() => Promise<void>>('refreshCourse')

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
const swal = useSweetAlert()

// Ref for GroupsList component
const groupsListRef = ref<InstanceType<typeof GroupsList> | null>(null)

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

// Handle group deleted - refresh course data to update groups count
const handleGroupDeleted = async (groupId: number) => {
  // Refresh course data to update groups count in UI
  if (refreshCourse) {
    await refreshCourse()
  }
}

// Handle group saved (created or updated)
const handleGroupSaved = async (savedGroup: any) => {
  showCreateModal.value = false
  
  if (editingGroup.value) {
    // Update existing group
    groupsListRef.value?.updateGroup(savedGroup)
    swal.success('อัปเดตกลุ่มสำเร็จ', 'แก้ไขกลุ่มสำเร็จ')
  } else {
    // Add new group immediately to UI
    groupsListRef.value?.addGroup(savedGroup)
    swal.success('สร้างกลุ่มใหม่สำเร็จ', 'สร้างกลุ่มสำเร็จ')
    
    // Refresh course data to update groups count
    if (refreshCourse) {
      await refreshCourse()
    }
  }
  
  // Also refresh from API to ensure sync
  await handleRefresh()
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
      ref="groupsListRef"
      :groups="groups"
      :course-id="course?.id"
      :is-course-admin="isCourseAdmin"
      @create="handleCreate"
      @edit="handleEdit"
      @join="handleJoin"
      @refresh="handleRefresh"
      @deleted="handleGroupDeleted"
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
          @saved="handleGroupSaved"
          @cancel="showCreateModal = false"
        />
      </template>
    </DialogModal>
  </div>
</template>
