<script setup lang="ts">
import { Icon } from '@iconify/vue'

const props = defineProps({
  owner: { type: Object, required: true },
})

const ownerName = computed(() => props.owner?.name || props.owner?.username || 'ไม่ระบุผู้สอน')
const ownerAvatar = computed(() => props.owner?.avatar || props.owner?.profile_photo_url || '/images/default-avatar.png')
const ownerPath = computed(() => `/profile/${props.owner?.reference_code || props.owner?.id}`)
</script>

<template>
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <h3 class="mb-4 text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">
      ผู้จัดการรายวิชา
    </h3>
    
    <div class="flex items-center gap-4">
      <NuxtLink :to="ownerPath" class="shrink-0">
        <div class="h-14 w-14 overflow-hidden rounded-2xl border-2 border-vikinger-purple shadow-lg transition-transform hover:scale-110">
          <img :src="ownerAvatar" :alt="ownerName" class="h-full w-full object-cover" />
        </div>
      </NuxtLink>
      
      <div class="min-w-0 flex-1">
        <NuxtLink :to="ownerPath" class="block truncate text-base font-black text-gray-900 hover:text-vikinger-purple dark:text-white dark:hover:text-vikinger-cyan">
          {{ ownerName }}
        </NuxtLink>
        <p class="text-xs font-bold text-gray-500 dark:text-gray-400">Instructor</p>
      </div>
    </div>

    <div class="mt-5 flex gap-2">
      <NuxtLink 
        :to="ownerPath" 
        class="flex-1 flex items-center justify-center gap-2 rounded-xl bg-gray-100 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
      >
        <Icon icon="fluent:person-24-filled" class="w-4 h-4" />
        <span>โปรไฟล์</span>
      </NuxtLink>
      <button class="flex items-center justify-center rounded-xl bg-vikinger-purple/10 p-2.5 text-vikinger-purple hover:bg-vikinger-purple/20 dark:text-vikinger-cyan dark:hover:bg-vikinger-cyan/10 transition-colors">
        <Icon icon="fluent:chat-24-filled" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>
