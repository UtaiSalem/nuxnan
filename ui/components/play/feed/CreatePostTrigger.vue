<script setup>
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

const props = defineProps({
  context: {
    type: String,
    default: 'newsfeed'
  },
  contextId: {
    type: Number,
    default: null
  },
  lockedGroupId: {
    type: Number,
    default: null
  },
  postedAsGroupId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['open-modal', 'update:postedAsGroupId'])

const authStore = useAuthStore()
const { getAvatarUrl } = useAvatar()

const userAvatar = computed(() => getAvatarUrl(authStore.user))

const openModal = (tab = 'status') => {
  emit('open-modal', tab)
}
</script>

<template>
  <div class="create-post-trigger group relative overflow-hidden bg-white dark:bg-vikinger-dark-100 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-50/20 hover:shadow-md transition-shadow duration-300">
    <!-- Decorative gradient line at top -->
    <div class="h-0.5 bg-gradient-to-r from-vikinger-purple via-vikinger-cyan to-vikinger-purple"></div>
    
    <div class="p-4">
      <!-- Avatar + Input Row -->
      <div class="flex items-center gap-3 mb-3">
        <div class="relative flex-shrink-0">
          <img 
            :src="userAvatar" 
            alt="Avatar" 
            class="w-11 h-11 rounded-full object-cover ring-2 ring-vikinger-purple/30 shadow-sm"
          />
          <div class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-green-500 border-2 border-white dark:border-vikinger-dark-100 rounded-full"></div>
        </div>
        <button
          @click="openModal()"
          class="flex-1 text-left px-5 py-3 bg-gray-50 dark:bg-vikinger-dark-200/60 rounded-full text-gray-400 dark:text-gray-500 hover:bg-gray-100 dark:hover:bg-vikinger-dark-200 transition-all duration-200 text-sm border border-transparent hover:border-vikinger-purple/20"
        >
          {{ authStore.user?.name ? `${authStore.user.name} คุณกำลังคิดอะไรอยู่?` : 'คุณกำลังคิดอะไรอยู่?' }}
        </button>
      </div>

      <!-- Divider -->
      <div class="border-t border-gray-100 dark:border-vikinger-dark-50/15 mb-2"></div>

      <!-- Quick Action Buttons -->
      <div class="flex items-center justify-around">
        <button 
          @click="openModal()" 
          class="flex-1 flex items-center justify-center gap-2 px-2 py-2 mx-1 rounded-xl hover:bg-green-50 dark:hover:bg-green-900/15 transition-all duration-200 group/btn"
        >
          <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center group-hover/btn:scale-110 transition-transform">
            <Icon icon="fluent:image-24-filled" class="w-4.5 h-4.5 text-green-600 dark:text-green-400" />
          </div>
          <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 group-hover/btn:text-green-600 dark:group-hover/btn:text-green-400 transition-colors hidden sm:inline">รูปภาพ</span>
        </button>

        <div class="w-px h-6 bg-gray-200 dark:bg-vikinger-dark-50/20"></div>

        <button 
          @click="openModal()" 
          class="flex-1 flex items-center justify-center gap-2 px-2 py-2 mx-1 rounded-xl hover:bg-yellow-50 dark:hover:bg-yellow-900/15 transition-all duration-200 group/btn"
        >
          <div class="w-8 h-8 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center group-hover/btn:scale-110 transition-transform">
            <Icon icon="fluent:emoji-24-filled" class="w-4.5 h-4.5 text-yellow-600 dark:text-yellow-400" />
          </div>
          <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 group-hover/btn:text-yellow-600 dark:group-hover/btn:text-yellow-400 transition-colors hidden sm:inline">ความรู้สึก</span>
        </button>

        <div class="w-px h-6 bg-gray-200 dark:bg-vikinger-dark-50/20"></div>

        <button 
          @click="openModal('poll')" 
          class="flex-1 flex items-center justify-center gap-2 px-2 py-2 mx-1 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-900/15 transition-all duration-200 group/btn"
        >
          <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center group-hover/btn:scale-110 transition-transform">
            <Icon icon="fluent:poll-24-filled" class="w-4.5 h-4.5 text-amber-600 dark:text-amber-400" />
          </div>
          <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 group-hover/btn:text-amber-600 dark:group-hover/btn:text-amber-400 transition-colors hidden sm:inline">โพล</span>
        </button>

        <div class="w-px h-6 bg-gray-200 dark:bg-vikinger-dark-50/20"></div>

        <button 
          @click="openModal()" 
          class="flex-1 flex items-center justify-center gap-2 px-2 py-2 mx-1 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/15 transition-all duration-200 group/btn"
        >
          <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center group-hover/btn:scale-110 transition-transform">
            <Icon icon="fluent:location-24-filled" class="w-4.5 h-4.5 text-red-500 dark:text-red-400" />
          </div>
          <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 group-hover/btn:text-red-500 dark:group-hover/btn:text-red-400 transition-colors hidden sm:inline">เช็คอิน</span>
        </button>
      </div>

      <!-- Identity selection row -->
      <div 
        v-if="context === 'academy'" 
        class="mt-3 pt-3 border-t border-gray-100 dark:border-vikinger-dark-50/15 flex items-center justify-between"
      >
        <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold">โพสต์ในนาม:</span>
        <AcademyGroupsPostAsSelector
          :model-value="postedAsGroupId"
          @update:model-value="val => emit('update:postedAsGroupId', val)"
          :academy-id="contextId"
          :locked-group-id="lockedGroupId"
          :user="authStore.user"
          variant="compact"
        />
      </div>
    </div>
  </div>
</template>
