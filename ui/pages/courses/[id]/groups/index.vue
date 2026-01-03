<script setup lang="ts">
import { Icon } from '@iconify/vue'
import GroupsList from '~/components/course/GroupsList.vue'
import GroupForm from '~/components/course/GroupForm.vue'
import { usePage } from '@inertiajs/vue3'
import { useCourseMemberStore } from '~/stores/courseMember'
import { useAuthStore } from '~/stores/auth'
import { useCourseStore } from '~/stores/course'

// Inject course data from parent
const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const refreshCourse = inject<() => Promise<void>>('refreshCourse')

// Stores
const courseGroupStore = useCourseGroupStore()
const courseMemberStore = useCourseMemberStore()
const authStore = useAuthStore()
const courseStore = useCourseStore()
const route = useRoute()
const page = usePage()

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

// Join state
const joiningGroupId = ref<number | null>(null)

// Join group
const handleJoin = async (groupId: number) => {
  // Check Points first
  const userPP = authStore.user?.pp || 0
  const tuitionFees = courseStore.course?.tuition_fees || 0

  if (userPP < tuitionFees) {
      await swal.fire({
          icon: 'warning',
          title: 'ขออภัย',
          text: 'คุณมีแต้มสะสมไม่เพียงพอที่จะสมัครเข้าร่วมกลุ่มรายวิชานี้ กรุณาเติมแต้มสะสมก่อน',
          confirmButtonText: 'ตกลง'
      })
      return
  }

  // Capture old group ID before any changes
  const oldGroupId = courseMemberStore.member?.group_id
  const userId = authStore.user?.id

  joiningGroupId.value = groupId
  try {
    const res = await api.post(`/api/courses/${course.value.id}/groups/${groupId}/members`)
    
    // Handle Resource nesting (data wrapper)
    let memberData = res.courseMemberOfAuth
    if (memberData && memberData.data) {
        memberData = memberData.data
    }
    
    // --- 1. Optimistic UI Update (Immediate) ---
    // Update Old Group (if exists and different from new)
    if (oldGroupId && oldGroupId != groupId) {
        const oldGroup = courseGroupStore.getGroupById(oldGroupId)
        if (oldGroup) {
            courseGroupStore.updateGroup(oldGroupId, {
                members_count: Math.max(0, (oldGroup.members_count || 0) - 1),
                groupMemberOfAuth: null
            })
        }
    }

    // Update New Group
    const newGroup = courseGroupStore.getGroupById(groupId)
    if (newGroup) {
         // Only increment if not already a member locally
         const newCount = newGroup.groupMemberOfAuth ? (newGroup.members_count || 0) : ((newGroup.members_count || 0) + 1)
         
         courseGroupStore.updateGroup(groupId, {
             members_count: newCount,
             groupMemberOfAuth: {
                 user_id: userId,
                 group_id: groupId,
                 status: 1,
                 ...memberData // usage of memberData properties if needed
             }
         })
    }

    // --- 2. Update Stores ---
    // Update Member Store
    courseMemberStore.setMember(memberData)
    
    // Refresh Group Store (Server Sync) - acts as confirmation
    await courseGroupStore.fetchGroups(course.value.id, true)

    // Show success message
    await swal.success(
        res.message || 'ดำเนินการเรียบร้อยแล้ว',
        'สำเร็จ (Success)'
    )
    
  } catch (error: any) {
    console.error('Join Error:', error)
    swal.error(error.data?.message || 'ไม่สามารถเข้าร่วมกลุ่มได้', 'เกิดข้อผิดพลาด')
  } finally {
    joiningGroupId.value = null
  }
}

// Refresh groups list
const handleRefresh = async () => {
    // ... existing ... 
    if (course?.value?.id) {
        await courseGroupStore.fetchGroups(course.value.id, true)
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
      :joining-group-id="joiningGroupId"
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
