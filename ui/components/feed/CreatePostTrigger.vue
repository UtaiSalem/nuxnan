<script setup>
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const { getAvatarUrl } = useAvatar()
const emit = defineEmits(['open-modal'])

const userAvatar = computed(() => getAvatarUrl(authStore.user))

const openModal = () => {
  emit('open-modal')
}
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-100 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-vikinger-dark-50/20">
    <div class="flex items-center gap-3 mb-3">
      <img 
        :src="userAvatar" 
        alt="Avatar" 
        class="w-10 h-10 rounded-full object-cover ring-2 ring-vikinger-purple/20"
      />
      <button
        @click="openModal"
        class="flex-1 text-left px-4 py-2.5 bg-gray-100 dark:bg-vikinger-dark-200/50 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-vikinger-dark-200 transition-colors text-sm"
      >
        คุณกำลังคิดอะไรอยู่?
      </button>
    </div>

    <div class="flex items-center justify-around pt-2 border-t border-gray-100 dark:border-vikinger-dark-50/20">
      <button @click="openModal" class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">
        <Icon icon="mdi:image" class="w-5 h-5 text-green-500" />
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">รูปภาพ</span>
      </button>
      <button @click="openModal" class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">
        <Icon icon="mdi:emoticon" class="w-5 h-5 text-amber-500" />
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">ความรู้สึก</span>
      </button>
      <button @click="openModal" class="flex items-center gap-2 px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-vikinger-dark-200/50 rounded-lg transition-colors">
        <Icon icon="mdi:map-marker" class="w-5 h-5 text-red-500" />
        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">สถานที่</span>
      </button>
    </div>
  </div>
</template>
