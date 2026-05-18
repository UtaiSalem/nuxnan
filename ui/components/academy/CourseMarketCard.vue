<template>
  <div class="group bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-md transition-shadow flex flex-col">
    <!-- Cover -->
    <div class="relative h-44 overflow-hidden shrink-0 cursor-pointer" @click="navigateTo(`/Learn/Courses/${course.id}`)">
      <img
        :src="course.cover || `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`"
        :alt="course.name"
        class="w-full h-full object-cover transition-transform group-hover:scale-105 duration-500"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex flex-col gap-1">
        <div
          v-if="getBadgeType(course, index ?? 0)"
          :class="[
            'px-2 py-0.5 text-white text-[10px] font-bold rounded shadow-lg backdrop-blur-md uppercase',
            getBadgeType(course, index ?? 0) === 'bestseller' ? 'bg-blue-500/80' : 'bg-orange-500/80',
          ]"
        >
          {{ getBadgeType(course, index ?? 0) === 'bestseller' ? 'Best Seller' : 'Trending' }}
        </div>
        <span v-if="levelLabel" class="bg-black/50 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold w-fit">
          {{ levelLabel }}
        </span>
        <span v-if="semesterBadge" class="bg-indigo-500/80 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded font-bold w-fit uppercase">
          {{ semesterBadge }}
        </span>
        <span v-if="isOwned" class="bg-green-500/80 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold flex items-center gap-1 w-fit">
          <Icon icon="mdi:check-circle" class="w-3 h-3" />
          โคลนแล้ว
        </span>
      </div>

      <!-- Favorite Button -->
      <button
        class="absolute top-2 right-2 p-1.5 rounded-full bg-white/80 dark:bg-black/50 hover:bg-white dark:hover:bg-black/70 transition-all text-red-500 z-10 shadow-sm backdrop-blur-sm"
        @click="toggleFavorite"
      >
        <Icon 
          :icon="isFavorited ? 'fluent:heart-24-filled' : 'fluent:heart-24-regular'" 
          class="w-4 h-4 transition-transform active:scale-95"
          :class="{ 'animate-pulse': isLoadingFavorite }" 
        />
      </button>

      <!-- Rating Badge -->
      <div
        class="absolute bottom-2 left-2 px-1.5 py-0.5 bg-yellow-400/90 backdrop-blur-sm text-gray-900 rounded text-[10px] font-bold flex items-center gap-1"
      >
        <Icon icon="fluent:star-16-filled" class="w-2.5 h-2.5" />
        <span>{{ course.rating || '4.5' }}</span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 flex flex-col flex-1">
      <!-- Instructor -->
      <div class="flex items-center gap-2 mb-2">
        <img
          :src="course.user?.profile_photo_url || '/images/avatar-placeholder.png'"
          class="w-6 h-6 rounded-full object-cover shrink-0 border border-slate-200 dark:border-slate-700"
        />
        <div class="min-w-0">
          <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate leading-none mb-0.5">
            By: {{ course.user?.name || 'Unknown' }}
          </p>
          <p class="text-[10px] text-blue-500 dark:text-blue-400 truncate leading-none font-medium">
            {{ course.category || 'General' }}
          </p>
        </div>
      </div>

      <!-- Title -->
      <h3
        class="font-bold text-slate-800 dark:text-white line-clamp-2 h-10 mb-2 text-sm leading-tight hover:text-blue-600 transition-colors cursor-pointer"
        @click="navigateTo(`/Learn/Courses/${course.id}`)"
      >
        {{ course.name }}
      </h3>

      <!-- Semester & Academic Year Badges -->
      <div v-if="course.semester || course.academic_year" class="flex flex-wrap gap-1.5 mb-2">
        <div 
          v-if="course.semester" 
          class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[9px] font-medium rounded border border-blue-100 dark:border-blue-800"
        >
          <Icon icon="fluent:calendar-16-regular" class="w-2.5 h-2.5" />
          <span>ภาคเรียนที่ {{ course.semester }}</span>
        </div>
        <div 
          v-if="course.academic_year" 
          class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-slate-50 dark:bg-slate-700/30 text-slate-600 dark:text-slate-400 text-[9px] font-medium rounded border border-slate-100 dark:border-slate-700"
        >
          <Icon icon="fluent:hat-graduation-16-regular" class="w-2.5 h-2.5" />
          <span>ปีการศึกษา {{ course.academic_year }}</span>
        </div>
      </div>

      <!-- Stats -->
      <div class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-slate-400 mb-3">
        <div class="flex items-center gap-1">
          <Icon icon="mdi:book-open-variant" class="w-3 h-3" />
          {{ course.course_lessons_count ?? course.lessons_count ?? course.lessons ?? 0 }} บท
        </div>
        <div class="flex items-center gap-1">
          <Icon icon="mdi:clock-outline" class="w-3 h-3" />
          {{ course.hours ?? course.duration ?? course.hours_per_week ?? 0 }} ชม.
        </div>
        <div v-if="course.is_for_marketplace" class="flex items-center gap-1">
          <Icon icon="mdi:content-copy" class="w-3 h-3" />
          {{ course.total_sales || 0 }}
        </div>
      </div>

      <!-- Member Progress -->
      <div v-if="!isOwner && isMember && memberStatus === 'active'" class="mb-3">
        <div class="flex items-center justify-between text-[10px] font-medium mb-1">
          <span class="text-green-600 dark:text-green-400 flex items-center gap-1">
            <Icon icon="fluent:hat-graduation-16-filled" />
            กำลังเรียน
          </span>
          <span class="text-slate-500">{{ Math.round(course.auth_progress || 0) }}%</span>
        </div>
        <div class="h-1 w-full bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
          <div 
            class="h-full bg-green-500 rounded-full transition-all duration-500"
            :style="{ width: `${course.auth_progress || 0}%` }"
          ></div>
        </div>
      </div>

      <!-- Status section -->
      <div class="mt-auto border-t border-slate-100 dark:border-slate-700 pt-3 flex items-center justify-between gap-2">

        <!-- Left: Price / marketplace info -->
        <div class="flex items-center gap-1.5 flex-wrap">
          <span v-if="course.tuition_fees > 0 && !isMember && !isOwner" class="flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-blue-400">
            <Icon icon="mdi:currency-thb" class="w-3.5 h-3.5" />
            ค่าเรียน ฿{{ formatNumber(course.tuition_fees) }}
          </span>
          <span v-if="course.is_for_marketplace && course.price > 0" class="flex items-center gap-1 text-xs font-bold text-violet-600 dark:text-violet-400">
            <Icon icon="mdi:content-copy" class="w-3.5 h-3.5" />
            ซื้อ ฿{{ formatNumber(course.price) }}
          </span>
          <span v-else-if="course.is_for_marketplace" class="text-xs font-bold text-violet-600 dark:text-violet-400 flex items-center gap-1">
            <Icon icon="mdi:content-copy" class="w-3.5 h-3.5" /> ตลาดวิชา
          </span>
          <span v-if="!course.tuition_fees && !course.price && !course.is_for_marketplace && !isMember && !isOwner" class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] font-bold rounded-full">
            ฟรี
          </span>
        </div>

        <!-- Right: Membership status / action button -->
        <div class="shrink-0 flex flex-col items-end gap-1">
          <NuxtLink
            v-if="isOwner"
            :to="`/Learn/Courses/${course.id}`"
            class="flex items-center gap-1 px-2.5 py-1 bg-violet-500 hover:bg-violet-600 text-white text-[10px] font-bold rounded-full transition-colors"
          >
            <Icon icon="mdi:cog" class="w-3 h-3" /> จัดการวิชา
          </NuxtLink>
          <NuxtLink
            v-else-if="isMember && memberStatus === 'active'"
            :to="`/Learn/Courses/${course.id}`"
            class="flex items-center gap-1 px-2.5 py-1 bg-blue-500 hover:bg-blue-600 text-white text-[10px] font-bold rounded-full transition-colors"
          >
            <Icon icon="mdi:play-circle" class="w-3 h-3" /> เข้าสู่รายวิชา
          </NuxtLink>
          <span v-else-if="isMember && memberStatus === 'pending'" class="flex items-center gap-1 px-2.5 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-[10px] font-bold rounded-full">
            <Icon icon="mdi:clock-outline" class="w-3 h-3" /> รออนุมัติ
          </span>
          <span v-else-if="isOwned" class="flex items-center gap-1 px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] font-bold rounded-full">
            <Icon icon="mdi:check-circle" class="w-3 h-3" /> โคลนแล้ว
          </span>
          <NuxtLink
            v-else
            :to="`/Learn/Courses/${course.id}`"
            class="flex items-center gap-1 px-2.5 py-1 bg-blue-500 hover:bg-blue-600 text-white text-[10px] font-bold rounded-full transition-colors"
          >
            <Icon icon="mdi:arrow-right-circle" class="w-3 h-3" /> รายละเอียด
          </NuxtLink>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  course: any
  index?: number
}>()

const config = useRuntimeConfig()
const api = useApi()

// Favorite State
const isFavorited = ref(props.course.is_favorited)
const isLoadingFavorite = ref(false)

watch(() => props.course.is_favorited, (newVal) => {
  isFavorited.value = newVal
})

const toggleFavorite = async (e: Event) => {
  e.stopPropagation() // Prevent any other click actions
  if (isLoadingFavorite.value) return
  
  const previousState = isFavorited.value
  isFavorited.value = !previousState
  isLoadingFavorite.value = true

  try {
    const res = (await api.post(`/api/courses/${props.course.id}/favorite`)) as any
    if (res.is_favorited !== undefined) {
      isFavorited.value = res.is_favorited
      props.course.is_favorited = res.is_favorited
    }
  } catch (error) {
    isFavorited.value = previousState
    console.error('Failed to toggle favorite', error)
  } finally {
    isLoadingFavorite.value = false
  }
}

const levelLabel = computed(() => {
  const lvl = props.course.education_level
  const yr  = props.course.education_year
  if (!lvl) return null
  return yr ? `${lvl} ปี ${yr}` : lvl
})

const semesterLabels: Record<string, string> = {
  '1': 'ภาค 1',
  '2': 'ภาค 2',
  '3': 'ภาค 3',
  'summer': 'ภาคฤดูร้อน',
  'weekend': 'เสา-อาทิตย์'
}

const semesterBadge = computed(() => {
  const s = props.course.semester
  const y = props.course.academic_year
  if (!s && !y) return null
  const parts: string[] = []
  if (s) parts.push(semesterLabels[s] ?? s)
  if (y) parts.push(String(y))
  return parts.join(' / ')
})

const isOwner = computed(() => props.course.auth_role === 4)
const isOwned = computed(() => props.course.is_owned)
const isMember = computed(() => props.course.enrollment_status?.is_member)
const memberStatus = computed(() => props.course.enrollment_status?.status)

const getBadgeType = (course: any, index: number) => {
  if (index === undefined) return null
  if (course.enrolled_students > 50) return 'bestseller'
  if (index < 3) return 'trending'
  return null
}

const formatNumber = (num: number) => new Intl.NumberFormat().format(num || 0)
</script>
