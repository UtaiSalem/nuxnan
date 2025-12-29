<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  member: any
  isCourseAdmin?: boolean
  courseId: string | number
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false
})

const emit = defineEmits<{
  'edit': [member: any]
  'remove': [memberId: number]
  'click': [member: any]
}>()

// Get role badge
const getRoleBadge = computed(() => {
  switch (props.member.role) {
    case 'teacher':
    case 'admin':
    case 'owner':
      return { text: 'ผู้สอน', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400', icon: 'fluent:crown-24-filled' }
    case 'assistant':
    case 'co-teacher':
      return { text: 'ผู้ช่วยสอน', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', icon: 'fluent:person-support-24-regular' }
    default:
      return { text: 'ผู้เรียน', class: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', icon: 'fluent:hat-graduation-24-regular' }
  }
})

// Format join date
const formatDate = (date: string) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>

<template>
  <div class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
    <button @click="emit('click', member)" class="flex items-center gap-3 flex-1 text-left">
      <!-- Avatar -->
      <div class="relative">
        <img
          :src="member.user?.avatar || '/images/default-avatar.png'"
          :alt="member.user?.name"
          class="w-12 h-12 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600"
        />
        <div 
          v-if="member.role === 'teacher' || member.role === 'admin' || member.role === 'owner'"
          class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-purple-500 flex items-center justify-center"
        >
          <Icon icon="fluent:crown-24-filled" class="w-3 h-3 text-white" />
        </div>
      </div>
      
      <!-- Info -->
      <div class="flex-1 min-w-0">
        <p class="font-medium text-gray-900 dark:text-white truncate">
          {{ member.user?.name }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
          @{{ member.user?.username }}
        </p>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-xs px-2 py-0.5 rounded-full" :class="getRoleBadge.class">
            {{ getRoleBadge.text }}
          </span>
          <span v-if="member.group?.name" class="text-xs text-gray-400">
            {{ member.group.name }}
          </span>
        </div>
      </div>
    </button>
    
    <!-- Actions -->
    <div v-if="isCourseAdmin && member.role !== 'owner'" class="flex items-center gap-1">
      <button 
        @click="emit('edit', member)"
        class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
        title="แก้ไข"
      >
        <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
      </button>
      <button 
        @click="emit('remove', member.id)"
        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
        title="ลบออก"
      >
        <Icon icon="fluent:person-delete-24-regular" class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>
