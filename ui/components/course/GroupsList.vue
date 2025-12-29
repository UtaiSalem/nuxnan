<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  groups: any[]
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  groups: () => [],
  isCourseAdmin: false
})

const emit = defineEmits<{
  'create': []
  'edit': [group: any]
  'join': [groupId: number]
  'refresh': []
}>()

const api = useApi()
const isDeleting = ref(false)
const showCreateModal = ref(false)

// Navigate to group
const navigateToGroup = (group: any) => {
  navigateTo(`/courses/${props.courseId}/groups/${group.id}`)
}

// Edit group
const editGroup = (group: any) => {
  navigateTo(`/courses/${props.courseId}/groups/${group.id}/edit`)
}

// Delete group
const deleteGroup = async (groupId: number) => {
  if (!confirm('ยืนยันการลบกลุ่มนี้หรือไม่? สมาชิกในกลุ่มจะถูกย้ายไปยังกลุ่มหลัก')) return
  
  isDeleting.value = true
  try {
    const response = await api.delete(`/api/courses/${props.courseId}/groups/${groupId}`)
    if (response.success) {
      emit('refresh')
    }
  } catch (err: any) {
    alert(err.data?.msg || 'ไม่สามารถลบกลุ่มได้')
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
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-cyan-500 flex items-center justify-center">
            <Icon icon="heroicons-outline:user-group" class="w-5 h-5 text-white" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">กลุ่มเรียน</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ groups.length }} กลุ่ม</p>
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

    <!-- Groups Grid -->
    <div v-if="groups.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <GroupCard
        v-for="group in groups"
        :key="group.id"
        :group="group"
        :course-id="courseId"
        :is-course-admin="isCourseAdmin"
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
