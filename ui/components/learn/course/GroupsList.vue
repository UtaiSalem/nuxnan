<script setup lang="ts">
import { Icon } from '@iconify/vue'
import GroupOrderWidget from '~/components/learn/course/groups/GroupOrderWidget.vue'

interface Props {
  groups: any[]
  isCourseAdmin?: boolean
  courseId: string | number
  joiningGroupId?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  groups: () => [],
  isCourseAdmin: false,
  joiningGroupId: null
})

const emit = defineEmits<{
  'create': []
  'edit': [group: any]
  'join': [groupId: number]
  'refresh': []
  'deleted': [groupId: number]
}>()

const api = useApi()
const swal = useSweetAlert()
const isDeleting = ref(false)
const showCreateModal = ref(false)

// Local groups list for immediate UI updates
const localGroups = ref<any[]>([])

// Sync props to local state
watch(() => props.groups, (newGroups) => {
  localGroups.value = [...newGroups]
}, { immediate: true, deep: true })

// Navigate to group
const navigateToGroup = (group: any) => {
  navigateTo(`/Learn/Courses/${props.courseId}/groups/${group.id}`)
}

// Edit group
const editGroup = (group: any) => {
  navigateTo(`/Learn/Courses/${props.courseId}/groups/${group.id}/edit`)
}

// Delete group
const deleteGroup = async (groupId: number) => {
  const confirmed = await swal.confirm(
    'สมาชิกในกลุ่มจะถูกย้ายไปยังกลุ่มหลัก คุณต้องการลบกลุ่มนี้หรือไม่?',
    'ยืนยันการลบกลุ่ม',
    {
      confirmText: 'ลบกลุ่ม',
      cancelText: 'ยกเลิก',
      icon: 'warning',
      isDanger: true
    }
  )
  
  if (!confirmed) return
  
  isDeleting.value = true
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/groups/${groupId}`)
    if (response.success) {
      // Remove from local list immediately
      localGroups.value = localGroups.value.filter(g => g.id !== groupId)
      swal.success('กลุ่มถูกลบเรียบร้อยแล้ว', 'ลบกลุ่มสำเร็จ')
      emit('deleted', groupId)
      emit('refresh')
    }
  } catch (err: any) {
    swal.error(err.data?.msg || 'เกิดข้อผิดพลาดในการลบกลุ่ม', 'ไม่สามารถลบกลุ่มได้')
  } finally {
    isDeleting.value = false
  }
}

// Add new group to local list (called from parent after create success)
const addGroup = (group: any) => {
  localGroups.value.push(group)
}

// Update group in local list
const updateGroup = (updatedGroup: any) => {
  const index = localGroups.value.findIndex(g => g.id === updatedGroup.id)
  if (index !== -1) {
    localGroups.value[index] = { ...localGroups.value[index], ...updatedGroup }
  }
}

// Expose methods to parent
defineExpose({
  addGroup,
  updateGroup,
  localGroups
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center">
            <Icon icon="heroicons-outline:user-group" class="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">กลุ่มเรียน</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ localGroups.length }} กลุ่ม</p>
          </div>
        </div>
        <button
          v-if="isCourseAdmin"
          @click="emit('create')"
          class="flex items-center gap-2 px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors"
        >
          <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
          <span class="hidden sm:inline">เพิ่มกลุ่ม</span>
        </button>
      </div>
    </div>

    <!-- Group Reorder (Admin Only) -->
    <div v-if="isCourseAdmin && localGroups.length > 1" class="mb-6">
      <details class="group bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-200 dark:border-teal-800 transition-all duration-300">
        <summary class="flex items-center justify-between p-4 cursor-pointer list-none">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-teal-100 dark:bg-teal-800 rounded-lg group-open:rotate-12 transition-transform duration-300">
              <Icon icon="fluent:re-order-24-regular" class="w-5 h-5 text-teal-600 dark:text-teal-400" />
            </div>
            <div>
              <h4 class="font-bold text-gray-900 dark:text-white">จัดลำดับกลุ่ม</h4>
              <p class="text-xs text-gray-500 dark:text-gray-400">ลากเพื่อเปลี่ยนลำดับการแสดงผลของกลุ่มเรียน</p>
            </div>
          </div>
          <Icon icon="fluent:chevron-down-24-regular" class="w-5 h-5 text-gray-400 group-open:rotate-180 transition-transform duration-300" />
        </summary>
        <div class="px-4 pb-4 animate-in fade-in slide-in-from-top-1 duration-300">
          <GroupOrderWidget 
            :groups="localGroups" 
            :course-id="Number(courseId)" 
            @saved="emit('refresh')"
          />
        </div>
      </details>
    </div>

    <!-- Groups Grid -->
    <div v-if="localGroups.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <GroupCard
        v-for="group in localGroups"
        :key="group.id"
        :group="group"
        :course-id="courseId"
        :is-course-admin="isCourseAdmin"
        :loading="group.id === joiningGroupId"
        @click="navigateToGroup"
        @edit="emit('edit', group)"
        @join="emit('join', group.id)"
        @delete="deleteGroup"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
      <Icon icon="heroicons-outline:user-group" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีกลุ่มเรียน</h3>
      <p class="text-gray-500 dark:text-gray-400 mb-4">รายวิชานี้ยังไม่มีกลุ่มเรียน</p>
      <button
        v-if="isCourseAdmin"
        @click="emit('create')"
        class="inline-flex items-center gap-2 px-4 py-2 bg-teal-500 text-white rounded-lg hover:bg-teal-600 transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
        สร้างกลุ่มแรก
      </button>
    </div>
  </div>
</template>
