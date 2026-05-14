<template>
  <div class="group bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden border border-slate-200 dark:border-slate-700 hover:shadow-md transition-shadow flex flex-col">
    <!-- Cover -->
    <div class="relative h-44 overflow-hidden shrink-0 cursor-pointer" @click="$emit('enroll', course)">
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
        <span v-if="course.level" class="bg-black/50 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded uppercase font-bold w-fit">
          {{ course.level }}
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
        @click="$emit('enroll', course)"
      >
        {{ course.name }}
      </h3>

      <!-- Stats -->
      <div class="flex items-center gap-3 text-[10px] text-slate-500 dark:text-slate-400 mb-3">
        <div class="flex items-center gap-1">
          <Icon icon="mdi:book-open-variant" class="w-3 h-3" />
          {{ course.course_lessons_count || 0 }} บท
        </div>
        <div class="flex items-center gap-1">
          <Icon icon="mdi:clock-outline" class="w-3 h-3" />
          {{ course.hours || 0 }} ชม.
        </div>
        <div v-if="hasClone" class="flex items-center gap-1">
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

      <!-- Action section -->
      <div class="mt-auto border-t border-slate-100 dark:border-slate-700 pt-3">

        <!-- Both: clone + enroll -->
        <div v-if="hasClone && hasEnroll" class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-700">
          <!-- Clone column -->
          <div class="flex flex-col gap-1.5 pr-2">
          <span class="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
            <Icon icon="mdi:content-copy" class="w-3 h-3" /> โคลน
          </span>
          <div class="flex flex-col font-bold text-xs">
            <span v-if="course.price <= 0" class="text-green-600">FREE CLONE</span>
            <template v-else>
              <span class="text-violet-600">฿{{ formatNumber(course.price) }}</span>
              <span class="text-amber-600 flex items-center gap-0.5 text-[10px]">
                <Icon icon="mdi:database" class="w-3 h-3" />{{ formatNumber(Math.ceil(course.price * 1200)) }} P
              </span>
            </template>
          </div>
          <button @click="$emit('clone', course)" class="w-full bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-600 hover:text-white text-violet-700 dark:text-violet-300 text-xs font-bold px-2 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
            <Icon icon="mdi:cart-plus" class="w-3.5 h-3.5" />{{ isOwned ? 'ซื้อซ้ำ' : 'ซื้อ Master Copy' }}
          </button>
          </div>
          <!-- Enroll column -->
          <div class="flex flex-col gap-1.5 pl-2">
            <span class="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
              <Icon icon="mdi:school" class="w-3 h-3" /> เรียน
            </span>
            <template v-if="isOwner">
              <span class="text-[10px] text-violet-500 font-bold flex items-center gap-0.5">
                <Icon icon="mdi:crown" class="w-3 h-3" /> เจ้าของรายวิชา
              </span>
              <NuxtLink :to="`/Learn/Courses/${course.id}`" class="w-full bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-600 hover:text-white text-violet-700 dark:text-violet-300 text-xs font-bold px-2 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                <Icon icon="mdi:cog" class="w-3.5 h-3.5" />จัดการ
              </NuxtLink>
            </template>
            <div v-else-if="isMember">
              <button
                v-if="memberStatus === 'active'"
                @click="$emit('enroll', course)"
                class="w-full bg-blue-600 text-white text-xs font-bold px-2 py-1.5 rounded-lg block text-center hover:bg-blue-700 transition-colors shadow-sm shadow-blue-500/20"
              >
                เข้าสู่บทเรียน
              </button>
              <span v-else class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-bold px-2 py-1.5 rounded-lg block text-center">
                รออนุมัติ
              </span>
            </div>
            <template v-else>
              <span class="text-xs font-bold" :class="enrollIsFree ? 'text-green-600' : 'text-blue-600'">
                {{ enrollIsFree ? 'FREE' : `฿${formatNumber(course.tuition_fees)}` }}
              </span>
              <button @click="$emit('enroll', course)" class="w-full bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-600 hover:text-white text-blue-700 dark:text-blue-300 text-xs font-bold px-2 py-1.5 rounded-lg transition-colors flex items-center justify-center gap-1">
                <Icon icon="mdi:school" class="w-3.5 h-3.5" />สมัครเรียน
              </button>
            </template>
          </div>
        </div>

        <!-- Clone only -->
        <div v-else-if="hasClone" class="flex items-center justify-between">
          <div class="flex flex-col font-bold text-sm">
            <span v-if="course.price <= 0" class="text-green-600 text-xs">FREE CLONE</span>
            <template v-else>
              <span class="text-violet-600">฿{{ formatNumber(course.price) }}</span>
              <span class="text-amber-600 flex items-center gap-1 text-[10px]">
                <Icon icon="mdi:database" class="w-3.5 h-3.5" />{{ formatNumber(Math.ceil(course.price * 1200)) }} P
              </span>
            </template>
          </div>
          <button @click="$emit('clone', course)" class="bg-slate-100 dark:bg-slate-700 hover:bg-violet-600 hover:text-white text-slate-700 dark:text-slate-200 p-2 rounded-lg transition-colors group flex items-center gap-2 px-3">
            <Icon icon="mdi:cart-plus" class="w-5 h-5 group-hover:scale-110 transition-transform" />
            <span class="text-xs font-bold">{{ isOwned ? 'ซื้อซ้ำ' : 'ซื้อ Master Copy' }}</span>
          </button>
        </div>

        <!-- Enroll only (saleable=true, paid) -->
        <div v-else-if="hasEnroll" class="flex items-center justify-between">
          <template v-if="isOwner">
            <span class="text-xs text-violet-500 font-bold flex items-center gap-1">
              <Icon icon="mdi:crown" class="w-4 h-4" /> เจ้าของรายวิชา
            </span>
            <NuxtLink :to="`/Learn/Courses/${course.id}`" class="bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-600 hover:text-white text-violet-700 dark:text-violet-300 p-2 rounded-lg transition-colors group flex items-center gap-1.5 px-3 text-xs font-bold">
              <Icon icon="mdi:cog" class="w-4 h-4 group-hover:rotate-45 transition-transform" />จัดการ
            </NuxtLink>
          </template>
          <div v-else-if="isMember">
            <button
              v-if="memberStatus === 'active'"
              @click="$emit('enroll', course)"
              class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg block hover:bg-blue-700 transition-colors shadow-sm shadow-blue-500/20"
            >
              เข้าสู่บทเรียน
            </button>
            <span v-else class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-bold px-3 py-2 rounded-lg block">
              รออนุมัติ
            </span>
          </div>
          <template v-else>
            <span class="text-sm font-bold" :class="enrollIsFree ? 'text-green-600' : 'text-blue-600'">
              {{ enrollIsFree ? 'ฟรี' : `฿${formatNumber(course.tuition_fees)}` }}
            </span>
            <button @click="$emit('enroll', course)" class="bg-slate-100 dark:bg-slate-700 hover:bg-blue-600 hover:text-white text-slate-700 dark:text-slate-200 p-2 rounded-lg transition-colors group flex items-center gap-2 px-3">
              <Icon icon="mdi:school" class="w-5 h-5 group-hover:scale-110 transition-transform" />
              <span class="text-xs font-bold">สมัครเรียน</span>
            </button>
          </template>
        </div>

        <!-- Free enrollment fallback (saleable=false, is_for_marketplace=false) -->
        <div v-else class="flex items-center justify-between">
          <template v-if="isOwner">
            <span class="text-xs text-violet-500 font-bold flex items-center gap-1">
              <Icon icon="mdi:crown" class="w-4 h-4" /> เจ้าของรายวิชา
            </span>
            <NuxtLink :to="`/Learn/Courses/${course.id}`" class="bg-violet-100 dark:bg-violet-900/30 hover:bg-violet-600 hover:text-white text-violet-700 dark:text-violet-300 p-2 rounded-lg transition-colors group flex items-center gap-1.5 px-3 text-xs font-bold">
              <Icon icon="mdi:cog" class="w-4 h-4 group-hover:rotate-45 transition-transform" />จัดการ
            </NuxtLink>
          </template>
          <div v-else-if="isMember">
            <button
              v-if="memberStatus === 'active'"
              @click="$emit('enroll', course)"
              class="bg-blue-600 text-white text-xs font-bold px-4 py-2 rounded-lg block hover:bg-blue-700 transition-colors shadow-sm shadow-blue-500/20"
            >
              เข้าสู่บทเรียน
            </button>
            <span v-else class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-xs font-bold px-3 py-2 rounded-lg block">
              รออนุมัติ
            </span>
          </div>
          <template v-else>
            <span class="text-xs font-bold text-green-600">ฟรี</span>
            <button @click="$emit('enroll', course)" class="bg-slate-100 dark:bg-slate-700 hover:bg-blue-600 hover:text-white text-slate-700 dark:text-slate-200 p-2 rounded-lg transition-colors group flex items-center gap-2 px-3">
              <Icon icon="mdi:school" class="w-5 h-5 group-hover:scale-110 transition-transform" />
              <span class="text-xs font-bold">สมัครเรียน</span>
            </button>
          </template>
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

defineEmits(['clone', 'enroll'])

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

const hasClone = computed(() => props.course.is_for_marketplace)
const hasEnroll = computed(() => props.course.saleable)
const isOwner = computed(() => props.course.auth_role === 4)
const isOwned = computed(() => props.course.is_owned)
const enrollIsFree = computed(() => !props.course.tuition_fees || Number(props.course.tuition_fees) === 0)
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
