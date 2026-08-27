<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useCourseMemberStore } from '~/stores/courseMember'
import { storeToRefs } from 'pinia'

interface Props {
  group: any
  isCourseAdmin?: boolean
  courseId: string | number
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCourseAdmin: false,
  loading: false
})

const emit = defineEmits<{
  'edit': [group: any]
  'delete': [groupId: number]
  'click': [group: any]
  'join': [groupId: number]
}>()

const config = useRuntimeConfig()

// Get group color based on index or id
const groupColors = [
  'from-sky-300/80 to-blue-400/80',
  'from-violet-300/80 to-purple-400/80',
  'from-emerald-300/80 to-teal-400/80',
  'from-amber-300/80 to-orange-400/80',
  'from-indigo-300/80 to-slate-400/80',
  'from-rose-300/80 to-pink-400/80'
]

const groupColor = computed(() => {
  const index = props.group.id % groupColors.length
  return groupColors[index]
})

// Cover image URL
const coverUrl = computed(() => {
  if (props.group.cover) {
    return `${config.public.apiBase}/storage/images/courses/groups/covers/${props.group.cover}`
  }
  return null
})

// Group logo/avatar URL
const groupImageUrl = computed(() => {
  if (props.group.image_url) {
    return `${config.public.apiBase}/storage/images/courses/groups/${props.group.image_url}`
  }
  return null
})

// Get member avatars (max 5)
const memberAvatars = computed(() => {
  if (!props.group.members || props.group.members.length === 0) return []
  
  return props.group.members
    .slice(0, 5)
    .map((m: any) => {
      const user = m.user || m.member
      return {
        name: user?.name || 'User',
        avatar: user?.avatar || '/images/default-avatar.png'
      }
    })
})

// Remaining members count
const remainingMembers = computed(() => {
  const count = (props.group.members_count || 0) - 5
  return count > 0 ? count : 0
})

// Format large numbers
const formatNumber = (num: number | undefined | null) => {
  const n = Number(num) || 0
  if (n >= 1000) {
    return (n / 1000).toFixed(1) + 'K'
  }
  return String(n)
}

// Check if user is member
const isMember = computed(() => !!props.group.groupMemberOfAuth)

// Check if user is member of ANY group (from global state)
const courseMemberStore = useCourseMemberStore()
const { member } = storeToRefs(courseMemberStore)

const isMemberOfOtherGroup = computed(() => {
    return member.value?.group_id && member.value.group_id != props.group.id
})

// Capacity Badge Logic
const capacityInfo = computed(() => {
    if (!props.group.max_members) return null
    
    const count = props.group.members_count || 0
    const max = props.group.max_members
    const remaining = max - count

    if (count >= max) {
        return { label: 'เต็ม (Full)', class: 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800' }
    } else if (count / max >= 0.8) {
        return { label: `เหลือ ${remaining} ที่`, class: 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800' }
    } else {
        return { label: `ว่าง ${remaining} ที่`, class: 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' }
    }
})

const handleJoinClick = async () => {
    if (isMemberOfOtherGroup.value) {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'ยืนยันการย้ายกลุ่ม',
            text: 'คุณเป็นสมาชิกกลุ่มอื่นอยู่แล้ว การเข้าร่วมกลุ่มใหม่จะเป็นการออกจากกลุ่มเดิมโดยอัตโนมัติ คุณต้องการดำเนินการต่อหรือไม่?',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ย้ายกลุ่ม',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#6b7280'
        })
        
        if (!result.isConfirmed) return
    }
    
    emit('join', props.group.id)
}
</script>

<template>
  <div 
    class="group/card relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:scale-[1.02] cursor-pointer"
    @click="emit('click', group)"
  >
    <!-- Cover Image -->
    <div 
      class="relative h-32 bg-gradient-to-br flex items-center justify-center"
      :class="groupColor"
      :style="coverUrl ? { backgroundImage: `url(${coverUrl})`, backgroundSize: 'cover', backgroundPosition: 'center' } : {}"
    >
      <!-- Overlay gradient -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
      
      <!-- Capacity Badge -->
      <div v-if="capacityInfo" class="absolute top-3 left-3 px-2 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border shadow-sm backdrop-blur-md" :class="capacityInfo.class">
        {{ capacityInfo.label }}
      </div>

      <!-- Admin Actions Badge -->
      <div v-if="isCourseAdmin" class="absolute top-3 right-3 flex items-center gap-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full px-2 py-1 shadow-lg">
        <button 
          @click.stop="emit('edit', group)"
          class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-full transition-colors"
          title="แก้ไข"
        >
          <Icon icon="fluent:edit-24-filled" class="w-4 h-4" />
        </button>
        <button 
          @click.stop="emit('delete', group.id)"
          class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors"
          title="ลบ"
        >
          <Icon icon="fluent:delete-24-filled" class="w-4 h-4" />
        </button>
      </div>

      <!-- Group Icon Badge (if no admin actions) -->
      <div v-else class="absolute top-3 right-3 w-10 h-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg">
        <Icon icon="heroicons:user-group-solid" class="w-5 h-5 text-purple-600" />
      </div>
    </div>

    <!-- Content -->
    <div class="relative px-5 pb-5">
      <!-- Group Avatar (Overlapping) -->
      <div class="flex justify-center -mt-12 mb-3">
        <div class="relative">
          <!-- Glow effect -->
          <div class="absolute inset-0 bg-gradient-to-r from-gray-400 via-slate-400 to-gray-500 rounded-2xl blur-lg opacity-30 group-hover/card:opacity-50 transition-opacity"></div>
          
          <!-- Avatar -->
          <div class="relative w-24 h-24 rounded-2xl border-4 border-white dark:border-gray-800 bg-white dark:bg-gray-700 overflow-hidden shadow-xl">
            <img 
              v-if="groupImageUrl" 
              :src="groupImageUrl" 
              :alt="group.name"
              class="w-full h-full object-cover"
            >
            <div v-else :class="`w-full h-full flex items-center justify-center bg-gradient-to-br ${groupColor}`">
              <span class="text-3xl font-bold text-white">
                {{ group.name?.charAt(0) || 'G' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Group Name -->
      <h3 class="text-center text-xl font-bold text-gray-900 dark:text-white mb-1 line-clamp-1 group-hover/card:text-purple-600 dark:group-hover/card:text-purple-400 transition-colors">
        {{ group.name }}
      </h3>

      <!-- Description -->
      <p 
        v-if="group.description" 
        class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2 uppercase tracking-wide font-medium"
      >
        {{ group.description }}
      </p>
      <div v-else class="mb-4"></div>

      <!-- Stats -->
      <div class="flex flex-col items-center justify-center gap-3 mb-5">
        <!-- Members (Primary Stat) -->
        <div class="flex items-center gap-2">
            <span class="text-3xl font-black text-gray-900 dark:text-white leading-none">
                {{ group.members_count || 0 }}
            </span>
            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">
                Members
            </span>
        </div>

        <!-- Posts & Visits (Secondary Stats) -->
        <div class="flex items-center gap-4 text-gray-400 dark:text-gray-500">
            <div class="flex items-center gap-1">
                <Icon icon="fluent:chat-24-regular" class="w-3.5 h-3.5" />
                <span class="text-xs font-semibold">{{ formatNumber(group.posts_count) }} Posts</span>
            </div>
            <div class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-700"></div>
            <div class="flex items-center gap-1">
                <Icon icon="fluent:eye-24-regular" class="w-3.5 h-3.5" />
                <span class="text-xs font-semibold">{{ formatNumber(group.visits_count) }} Visits</span>
            </div>
        </div>
      </div>

      <!-- Member Avatars -->
      <div v-if="memberAvatars.length > 0" class="flex items-center justify-center mb-5">
        <div class="flex -space-x-2">
          <div 
            v-for="(member, index) in memberAvatars" 
            :key="index"
            class="relative w-8 h-8 rounded-full border-2 border-white dark:border-gray-800 overflow-hidden shadow-md hover:scale-110 hover:z-10 transition-transform"
            :title="member.name"
          >
            <img 
              :src="member.avatar" 
              :alt="member.name"
              class="w-full h-full object-cover"
            >
          </div>
          
          <!-- Remaining count -->
          <div 
            v-if="remainingMembers > 0"
            class="relative w-8 h-8 rounded-full border-2 border-white dark:border-gray-800 bg-gradient-to-br from-slate-400 to-gray-500 flex items-center justify-center shadow-md"
          >
            <span class="text-[10px] font-bold text-white">+{{ remainingMembers }}</span>
          </div>
        </div>
      </div>

      <!-- Join Button -->
      <!-- Adjusted logic to use handleJoinClick and show Move Group -->
      <button
        v-if="!isMember && !isCourseAdmin"
        @click.stop="handleJoinClick"
        :disabled="loading"
        class="w-full py-3 bg-gradient-to-r text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2 group/btn disabled:opacity-70 disabled:cursor-not-allowed"
        :class="isMemberOfOtherGroup ? 
            'from-amber-400 to-orange-500 hover:from-amber-500 hover:to-orange-600' : 
            'from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600'"
      >
        <Icon v-if="loading" icon="svg-spinners:ring-resize" class="w-5 h-5 animate-spin" />
        <Icon v-else :icon="isMemberOfOtherGroup ? 'heroicons:arrow-path-rounded-square' : 'heroicons:user-plus-solid'" class="w-5 h-5 group-hover/btn:scale-110 transition-transform" />
        <span>{{ loading ? 'Processing...' : (isMemberOfOtherGroup ? 'Move Group' : 'Join Group!') }}</span>
      </button>

      <!-- Already Member -->
      <div
        v-else-if="isMember"
        class="w-full py-3 bg-gradient-to-r from-emerald-400 to-teal-400 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-2"
      >
        <Icon icon="heroicons:check-circle-solid" class="w-5 h-5" />
        <span>Member</span>
      </div>

      <!-- Admin View -->
      <button
        v-else-if="isCourseAdmin"
        @click.stop="emit('click', group)"
        class="w-full py-3 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2"
      >
        <Icon icon="heroicons:cog-6-tooth-solid" class="w-5 h-5" />
        <span>Manage</span>
      </button>
    </div>
  </div>
</template>
