<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

interface Department {
  id: number
  name: string
  description: string | null
  members_count: number
  head_user: {
    name: string
    profile_photo_url: string | null
  } | null
}

interface Props {
  academyId: number
  academyName: string
  limit?: number
}

const props = withDefaults(defineProps<Props>(), { limit: 5 })
const emit = defineEmits<{ viewAll: [] }>()

const api = useApi()
const departments = ref<Department[]>([])
const isLoading = ref(true)
const error = ref<string | null>(null)

const loadDepartments = async () => {
  isLoading.value = true
  error.value = null
  try {
    const res: any = await api.get(`/api/academies/${props.academyId}/departments?per_page=${props.limit}`)
    const data = res?.data?.departments || res?.departments?.data || res?.data
    departments.value = Array.isArray(data) ? data : []
  } catch (e: any) {
    error.value = e?.data?.message || e?.message || 'โหลดฝ่ายงานล้มเหลว'
  } finally {
    isLoading.value = false
  }
}

const containerRef = ref<HTMLElement | null>(null)
useIntersectionLoad(containerRef, loadDepartments)

const getAvatarUrl = (url: string | null) => {
  return url || '/images/default_avatar.png'
}
</script>

<template>
  <div ref="containerRef" v-if="!error || (error && isLoading)" class="bg-white dark:bg-vikinger-dark-200 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <span class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:building-24-regular" class="w-5 h-5 text-vikinger-purple" />
        ฝ่ายภายในโรงเรียน
      </span>
      <button
        @click="emit('viewAll')"
        class="text-xs font-semibold text-vikinger-purple hover:text-vikinger-purple/80"
      >
        ดูทั้งหมด
      </button>
    </div>

    <div class="p-4">
      <div v-if="isLoading" class="space-y-3">
        <div v-for="i in props.limit" :key="i" class="flex items-center gap-3 animate-pulse">
          <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
          <div class="flex-1 space-y-2">
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
          </div>
        </div>
      </div>

      <CommonEmptyState
        v-else-if="departments.length === 0"
        icon="fluent:building-24-regular"
        title="ยังไม่มีข้อมูลฝ่าย"
        description="โรงเรียนนี้ยังไม่ได้ตั้งค่าโครงสร้างฝ่ายงาน"
        compact
      />

      <div v-else class="flex flex-col gap-3">
        <NuxtLink
          v-for="dept in departments"
          :key="dept.id"
          :to="`/academies/${encodeURIComponent(academyName)}/groups/${dept.id}`"
          class="flex items-center gap-3 p-2 -mx-2 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors group"
        >
          <div class="w-8 h-8 flex-shrink-0 rounded-full border border-gray-200 dark:border-gray-700 overflow-hidden bg-white">
            <Icon v-if="!dept.head_user" icon="fluent:building-24-regular" class="w-full h-full p-1.5 text-gray-400" />
            <img v-else :src="getAvatarUrl(dept.head_user.profile_photo_url)" class="w-full h-full object-cover" :alt="dept.head_user.name" />
          </div>

          <div class="flex-1 min-w-0">
            <div class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-vikinger-purple transition-colors">
              {{ dept.name }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
              {{ dept.head_user ? dept.head_user.name : 'ยังไม่มีหัวหน้า' }}
            </div>
          </div>
          
          <div class="flex-shrink-0">
            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-5 px-1.5 text-[10px] font-bold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
              {{ dept.members_count || 0 }}
            </span>
          </div>
        </NuxtLink>
      </div>
    </div>
  </div>
</template>
