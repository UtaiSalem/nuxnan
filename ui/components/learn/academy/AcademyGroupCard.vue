<script setup lang="ts">
import { Icon } from '@iconify/vue'

interface Props {
  group: any
  isAcademyAdmin?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isAcademyAdmin: false,
  loading: false
})

const emit = defineEmits<{
  'edit': [group: any]
  'delete': [groupId: number]
  'click': [group: any]
  'join': [groupId: number]
}>()

// Group color based on type
const groupColors: Record<string, string> = {
  'department': 'from-indigo-400 to-purple-500',
  'classroom': 'from-cyan-400 to-blue-500',
  'club': 'from-green-400 to-teal-500'
}

const groupColor = computed(() => {
  return groupColors[props.group.type] || 'from-purple-400 to-pink-500'
})

// Group type label in Thai
const typeLabels: Record<string, string> = {
  'department': 'ฝ่ายงาน',
  'classroom': 'ห้องเรียน',
  'club': 'ชมรม'
}

const typeLabel = computed(() => {
  return typeLabels[props.group.type] || 'กลุ่ม'
})

// Group icon based on type
const typeIcons: Record<string, string> = {
  'department': 'heroicons:briefcase-solid',
  'classroom': 'heroicons:academic-cap-solid',
  'club': 'heroicons:star-solid'
}

const typeIcon = computed(() => {
  return typeIcons[props.group.type] || 'heroicons:user-group-solid'
})
</script>

<template>
  <div 
    class="group/card relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:scale-[1.02] cursor-pointer"
    @click="emit('click', group)"
  >
    <!-- Cover / Header -->
    <div 
      class="relative h-24 bg-gradient-to-br flex items-center justify-center"
      :class="groupColor"
    >
      <!-- Type Badge -->
      <div class="absolute top-3 left-3 flex items-center gap-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full px-3 py-1 shadow-lg">
        <Icon :icon="typeIcon" class="w-4 h-4" :class="`text-${group.type === 'department' ? 'indigo' : group.type === 'classroom' ? 'cyan' : 'green'}-600`" />
        <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ typeLabel }}</span>
      </div>

      <!-- Admin Actions Badge -->
      <div v-if="isAcademyAdmin" class="absolute top-3 right-3 flex items-center gap-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full px-2 py-1 shadow-lg">
        <button 
          @click.stop="emit('edit', group)"
          class="p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-full transition-colors"
          title="แก้ไข"
        >
          <Icon icon="fluent:edit-24-filled" class="w-4 h-4" />
        </button>
        <button 
          @click.stop="emit('delete', group.id)"
          class="p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors"
          title="ลบ"
        >
          <Icon icon="fluent:delete-24-filled" class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="relative px-5 py-4">
      <!-- Group Avatar (Overlapping) -->
      <div class="flex justify-center -mt-12 mb-3">
        <div class="relative">
          <div class="absolute inset-0 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 rounded-2xl blur-lg opacity-50 group-hover/card:opacity-70 transition-opacity"></div>
          <div class="relative w-20 h-20 rounded-2xl border-4 border-white dark:border-gray-800 bg-white dark:bg-gray-700 overflow-hidden shadow-xl">
            <div :class="`w-full h-full flex items-center justify-center bg-gradient-to-br ${groupColor}`">
              <Icon :icon="typeIcon" class="w-8 h-8 text-white" />
            </div>
          </div>
        </div>
      </div>

      <!-- Group Name -->
      <h3 class="text-center text-lg font-bold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover/card:text-purple-600 dark:group-hover/card:text-purple-400 transition-colors">
        {{ group.name }}
      </h3>

      <!-- Description -->
      <p 
        v-if="group.description" 
        class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2"
      >
        {{ group.description }}
      </p>
      <div v-else class="mb-4"></div>

      <!-- Stats -->
      <div class="flex items-center justify-center gap-6 mb-4">
        <div class="text-center">
          <div class="text-xl font-black text-gray-900 dark:text-white">
            {{ group.members_count || 0 }}
          </div>
          <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            สมาชิก
          </div>
        </div>
      </div>

      <!-- Join / View Button -->
      <button
        @click.stop="emit('click', group)"
        class="w-full py-2.5 bg-gradient-to-r from-purple-500 via-blue-500 to-purple-600 hover:from-purple-600 hover:via-blue-600 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 group/btn"
      >
        <Icon icon="heroicons:eye-solid" class="w-5 h-5 group-hover/btn:scale-110 transition-transform" />
        <span>ดูรายละเอียด</span>
      </button>
    </div>
  </div>
</template>
