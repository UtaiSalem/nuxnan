<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'

const config = useRuntimeConfig()

interface AnnouncementCreator {
  name?: string | null
  avatar?: string | null
  profile_photo_path?: string | null
  email_verified_at?: string | null
  is_verified?: boolean
}

interface Announcement {
  id: number
  title: string
  content: string
  priority?: string | null
  target_audience?: any
  published_at?: string | null
  created_at?: string | null
  view_count?: number | null
  creator?: AnnouncementCreator | null
}

const props = defineProps<{
  announcement: Announcement
}>()

const emit = defineEmits<{
  open: [id: number]
}>()

const creatorAvatar = computed(() => {
  const raw = props.announcement.creator?.avatar || props.announcement.creator?.profile_photo_path || null
  if (!raw) return null
  if (raw.startsWith('http')) return raw
  if (raw.startsWith('/')) return `${config.public.apiBase}${raw}`
  return `${config.public.apiBase}/storage/${raw}`
})

const creatorIsVerified = computed(() => {
  return Boolean(
    props.announcement.creator?.is_verified ||
    props.announcement.creator?.email_verified_at,
  )
})

const priorityBadge = computed(() => {
  switch (props.announcement.priority) {
    case 'urgent':
      return { label: 'ด่วน', cls: 'bg-red-500/15 text-red-600 dark:text-red-400', dot: 'bg-red-500' }
    case 'high':
      return { label: 'สูง', cls: 'bg-amber-500/15 text-amber-700 dark:text-amber-400', dot: 'bg-amber-500' }
    default:
      return null
  }
})

const audienceTokens = computed(() => {
  const raw = props.announcement.target_audience

  if (!raw) return ['all']
  if (Array.isArray(raw)) return raw.length ? raw : ['all']

  const tokens: string[] = []
  if (Array.isArray(raw.roles)) tokens.push(...raw.roles)
  if (Array.isArray(raw.grades) && raw.grades.length) tokens.push(...raw.grades)
  if (Array.isArray(raw.departments) && raw.departments.length) tokens.push(...raw.departments)

  return tokens.length ? tokens : ['all']
})

const audienceLabel = computed(() => {
  const tokens = audienceTokens.value
  if (!tokens.length || tokens.includes('all')) return 'ชุมชนโรงเรียนทุกคน'

  const map: Record<string, string> = {
    student: 'นักเรียน',
    teacher: 'ครูและบุคลากร',
    parent: 'ผู้ปกครอง',
    staff: 'เจ้าหน้าที่',
  }

  return tokens.map((token) => map[token] || token).join(' • ')
})

const relativeTime = computed(() => {
  const iso = props.announcement.published_at || props.announcement.created_at
  if (!iso) return ''

  const diff = (Date.now() - new Date(iso).getTime()) / 1000
  if (diff < 60) return 'เมื่อสักครู่'
  if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`
  if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`
  if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} วันที่แล้ว`

  return new Date(iso).toLocaleDateString('th-TH')
})

const initial = computed(() => {
  return (props.announcement.creator?.name || 'A').charAt(0).toUpperCase()
})
</script>

<template>
  <article class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm ring-2 ring-vikinger-purple/60 p-4 md:p-5">
    <div class="flex items-center gap-1.5 mb-3 text-vikinger-purple text-xs font-bold">
      <Icon icon="heroicons:bookmark-solid" class="w-3.5 h-3.5" />
      ปักหมุดไว้
    </div>

    <div class="flex gap-3">
      <div class="w-10 h-10 rounded-full bg-gradient-to-br from-vikinger-purple to-vikinger-cyan flex items-center justify-center text-white font-bold text-sm flex-shrink-0 overflow-hidden">
        <img v-if="creatorAvatar" :src="creatorAvatar" class="w-full h-full object-cover" alt="announcement author avatar" loading="lazy" decoding="async" />
        <span v-else>{{ initial }}</span>
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <span class="font-bold text-sm text-gray-900 dark:text-white">
            {{ announcement.creator?.name || 'ฝ่ายวิชาการ' }}
          </span>
          <Icon
            v-if="creatorIsVerified"
            icon="heroicons:check-badge-solid"
            class="w-4 h-4 text-vikinger-cyan"
          />
          <span
            v-if="priorityBadge"
            :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold', priorityBadge.cls]"
          >
            <span :class="['w-1.5 h-1.5 rounded-full', priorityBadge.dot]" />
            {{ priorityBadge.label }}
          </span>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          ประกาศอย่างเป็นทางการ • {{ relativeTime }}
        </div>
      </div>

      <button
        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
        title="ดูรายละเอียด"
        @click="emit('open', announcement.id)"
      >
        <Icon icon="heroicons:ellipsis-horizontal" class="w-5 h-5" />
      </button>
    </div>

    <h3 class="mt-3 mb-1.5 text-base md:text-lg font-bold text-gray-900 dark:text-white leading-snug">
      {{ announcement.title }}
    </h3>
    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
      {{ announcement.content }}
    </p>

    <div class="mt-3 inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
      <Icon icon="heroicons:user-group" class="w-4 h-4" />
      <span>กลุ่มเป้าหมาย: {{ audienceLabel }}</span>
    </div>

    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-4 text-sm text-gray-500 dark:text-gray-400">
      <div class="inline-flex items-center gap-1.5 font-semibold">
        <Icon icon="heroicons:eye" class="w-5 h-5" />
        {{ announcement.view_count ?? 0 }}
      </div>
      <button
        class="inline-flex items-center gap-1.5 font-semibold hover:text-vikinger-cyan transition-colors"
        @click="emit('open', announcement.id)"
      >
        <Icon icon="heroicons:document-text" class="w-5 h-5" />
        อ่านประกาศ
      </button>
    </div>
  </article>
</template>
