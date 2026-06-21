<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { getAcademyGroupTypeMeta, GROUP_TYPE_COLOR_CLASSES } from '~/constants/academyGroupTypes'

interface Props {
  group: any
  stats: { members_count?: number; posts_count?: number; admins_count?: number } | null
  isMember: boolean
  isAdmin: boolean
  canManage: boolean
  isMuted: boolean
}
const props = defineProps<Props>()
const emit = defineEmits<{
  mute: []
  unmute: []
  manage: []
  share: []
}>()

const meta = computed(() => props.group ? getAcademyGroupTypeMeta(props.group.type) : null)
const cls = computed(() => {
  if (!meta.value) return { bg: '', text: '', gradient: 'from-vikinger-purple to-pink-500', badge: '' }
  return GROUP_TYPE_COLOR_CLASSES[meta.value.color] || { bg: '', text: '', gradient: 'from-vikinger-purple to-pink-500', badge: '' }
})

const share = async () => {
  if (!import.meta.client) return
  const url = window.location.href
  if (navigator.share) {
    try { 
      await navigator.share({ title: props.group.name, url })
      return 
    } catch {}
  }
  try {
    await navigator.clipboard.writeText(url)
    const toast = useToast()
    if (toast) {
      toast.add({ severity: 'success', summary: 'คัดลอกลิงก์เรียบร้อยแล้ว', life: 3000 })
    }
    emit('share')
  } catch (e) {
    console.error('Failed to copy link:', e)
  }
}
</script>

<template>
  <section class="bg-white dark:bg-vikinger-dark-200 rounded-t-xl shadow-sm overflow-hidden border border-b-0 border-gray-200 dark:border-gray-700">
    <!-- Cover gradient -->
    <div :class="['relative h-32 bg-gradient-to-br', cls.gradient]">
      <div
        class="absolute inset-0 opacity-20 mix-blend-overlay"
        style="background-image: radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 24px 24px;"
      ></div>
      <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/30"></div>
    </div>

    <!-- Identity row -->
    <div class="px-5 md:px-8 pb-4">
      <div class="flex items-end gap-4 -mt-10">
        <!-- Icon medallion -->
        <div :class="['w-20 h-20 rounded-2xl flex items-center justify-center border-4 border-white dark:border-vikinger-dark-200 shadow-md bg-gradient-to-br flex-shrink-0', cls.gradient]">
          <Icon v-if="meta" :icon="meta.icon" class="w-10 h-10 text-white" />
          <Icon v-else icon="heroicons:squares-2x2" class="w-10 h-10 text-white" />
        </div>

        <!-- Name + type badge + stats -->
        <div class="flex-1 min-w-0 pb-2">
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white truncate">
              {{ group.name }}
            </h1>
            <span v-if="meta" :class="['text-[11px] px-2 py-0.5 rounded-full font-bold', cls.badge]">
              {{ meta.label }}
            </span>
          </div>
          <div class="flex items-center gap-4 mt-1 text-sm">
            <span class="text-gray-600 dark:text-gray-400">
              <b class="text-gray-900 dark:text-white font-bold">{{ stats?.members_count ?? 0 }}</b>
              สมาชิก
            </span>
            <span class="text-gray-600 dark:text-gray-400">
              <b class="text-gray-900 dark:text-white font-bold">{{ stats?.posts_count ?? 0 }}</b>
              โพสต์
            </span>
          </div>
        </div>

        <!-- Actions (desktop only inline) -->
        <div class="hidden md:flex items-center gap-2 pb-2">
          <button
            v-if="canManage"
            class="px-4 py-2 rounded-lg text-xs md:text-sm font-semibold bg-vikinger-purple text-white hover:bg-vikinger-purple/90 flex items-center gap-1.5 transition-colors"
            @click="emit('manage')"
          >
            <Icon icon="heroicons:cog-6-tooth" class="w-4 h-4" />
            จัดการ
          </button>
          <button
            class="px-4 py-2 rounded-lg text-xs md:text-sm font-semibold border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 flex items-center gap-1.5 transition-colors"
            @click="share"
          >
            <Icon icon="heroicons:share" class="w-4 h-4" />
            แชร์
          </button>
          <button
            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 transition-colors border border-transparent hover:border-gray-200 dark:hover:border-gray-700"
            :title="isMuted ? 'เปิดการแจ้งเตือน' : 'ปิดการแจ้งเตือน'"
            @click="emit(isMuted ? 'unmute' : 'mute')"
          >
            <Icon :icon="isMuted ? 'heroicons:bell-slash' : 'heroicons:bell'" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Description -->
      <p v-if="group.description" class="mt-3 text-sm text-gray-600 dark:text-gray-300 leading-relaxed font-medium">
        {{ group.description }}
      </p>

      <!-- Action bar (mobile) -->
      <div class="flex md:hidden gap-2 mt-4">
        <button
          v-if="canManage"
          class="flex-1 px-3 py-2 rounded-lg text-sm font-bold bg-vikinger-purple text-white hover:bg-vikinger-purple/90 transition-colors"
          @click="emit('manage')"
        >
          จัดการ
        </button>
        <button 
          class="px-3 py-2 rounded-lg text-sm font-bold border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors flex items-center justify-center gap-1" 
          @click="share"
        >
          <Icon icon="heroicons:share" class="w-4 h-4" />
          แชร์
        </button>
        <button 
          class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors flex items-center justify-center" 
          @click="emit(isMuted ? 'unmute' : 'mute')"
        >
          <Icon :icon="isMuted ? 'heroicons:bell-slash' : 'heroicons:bell'" class="w-4 h-4" />
        </button>
      </div>
    </div>
  </section>
</template>
