<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

import CourseActionButton from './CourseActionButton.vue'

const props = defineProps({
  course: { type: Object, required: true },
  isAdmin: { type: Boolean, default: false },
  academy: { type: Object, default: null },
  courseMemberOfAuth: { type: Object, default: null },
  hideCover: { type: Boolean, default: false },
})

const emit = defineEmits(['edit-name', 'refresh', 'request-member', 'purchase-course'])

const config = useRuntimeConfig()
const courseStore = useCourseStore()
const api = useApi()

// File inputs
const coverInput = ref<HTMLInputElement | null>(null)
const logoInput = ref<HTMLInputElement | null>(null)

// Preview states
const coverPreview = ref<string | null>(null)
const logoPreview = ref<string | null>(null)
const isUpdatingCover = ref(false)
const isUpdatingLogo = ref(false)

const coverUrl = computed(() => {
  if (coverPreview.value) return coverPreview.value
  if (props.course?.cover) {
    if (props.course.cover.startsWith('http')) return props.course.cover
    return `${config.public.apiBase}/storage/images/courses/covers/${props.course.cover}`
  }
  return `${config.public.apiBase}/storage/images/courses/covers/default_cover.jpg`
})

const logoUrl = computed(() => {
  if (logoPreview.value) return logoPreview.value
  if (props.course?.logo) {
    if (props.course.logo.startsWith('http')) return props.course.logo
    return `${config.public.apiBase}/storage/images/courses/logos/${props.course.logo}`
  }
  if (props.course?.user?.avatar) return props.course.user.avatar
  return '/images/default-avatar.png'
})

const browseCover = () => coverInput.value?.click()
const browseLogo = () => logoInput.value?.click()

async function onCoverInputChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  coverPreview.value = URL.createObjectURL(file)
  isUpdatingCover.value = true
  try {
    const formData = new FormData()
    formData.append('cover', file)
    const response = await api.post(`/api/courses/${props.course.id}/cover`, formData)
    if (response.cover) courseStore.updateCourse({ cover: response.cover })
    emit('refresh')
  } catch (error) {
    console.error('Failed to update cover:', error)
    coverPreview.value = null
  } finally {
    isUpdatingCover.value = false
  }
}

async function onLogoInputChange(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return
  logoPreview.value = URL.createObjectURL(file)
  isUpdatingLogo.value = true
  try {
    const formData = new FormData()
    formData.append('logo', file)
    const response = await api.post(`/api/courses/${props.course.id}/logo`, formData)
    if (response.logo) courseStore.updateCourse({ logo: response.logo })
    emit('refresh')
  } catch (error) {
    console.error('Failed to update logo:', error)
    logoPreview.value = null
  } finally {
    isUpdatingLogo.value = false
  }
}

const courseOwnerName = computed(() => props.course?.user?.name || props.course?.owner?.name || 'ไม่ระบุผู้สอน')
const courseOwnerPath = computed(() => {
  const owner = props.course?.user || props.course?.owner
  if (!owner) return null
  return `/profile/${owner.reference_code || owner.id}`
})

const semesterLabel = computed(() => {
  const s = props.course?.semester
  if (!s) return null
  const map: Record<string, string> = {
    '1': 'ภาคเรียนที่ 1',
    '2': 'ภาคเรียนที่ 2',
    '3': 'ภาคเรียนที่ 3',
    'summer': 'ภาคฤดูร้อน',
    'weekend': 'ภาคสุดสัปดาห์',
  }
  return map[s] ?? null
})

const academicYearLabel = computed(() => {
  const y = props.course?.academic_year
  if (!y) return null
  return `ปีการศึกษา ${y}`
})

const educationLevelLabel = computed(() => {
  const level = props.course?.education_level
  const year = props.course?.education_year
  if (level && year) return `${level} ปีที่ ${year}`
  if (level) return level
  if (props.course?.level) return props.course.level
  return null
})

const hasMetadata = computed(() => !!(semesterLabel.value || academicYearLabel.value || educationLevelLabel.value))
</script>

<template>
  <div class="relative w-full bg-white dark:bg-vikinger-dark-200 rounded-2xl overflow-hidden border border-gray-100 dark:border-vikinger-dark-100 shadow-sm">
    <!-- ── Cover Image ── -->
    <div v-if="!hideCover" class="relative h-40 xs:h-52 sm:h-60 md:h-72 lg:h-80 w-full overflow-hidden">
      <img :src="coverUrl" alt="Course Cover" class="h-full w-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent pointer-events-none" />

      <!-- Cover edit button (admin) -->
      <div v-if="isAdmin" class="absolute top-3 right-3 z-10">
        <input type="file" ref="coverInput" class="hidden" accept="image/*" @change="onCoverInputChange" />
        <button
          @click="browseCover"
          :disabled="isUpdatingCover"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-black/50 backdrop-blur-md text-white border border-white/20 hover:bg-black/70 transition-all text-xs font-bold"
        >
          <Icon v-if="isUpdatingCover" icon="svg-spinners:ring-resize" class="w-3.5 h-3.5" />
          <Icon v-else icon="fluent:camera-24-filled" class="w-3.5 h-3.5" />
          <span class="hidden sm:inline">เปลี่ยนหน้าปก</span>
        </button>
      </div>

      <!-- Price badge -->
      <div v-if="course?.tuition_fees" class="absolute top-3 left-3 z-10">
        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500 text-white font-black shadow border border-amber-400 text-xs">
          <Icon icon="ri:bit-coin-fill" class="w-3.5 h-3.5" />
          <span>฿{{ course.tuition_fees.toLocaleString() }}</span>
        </div>
      </div>
    </div>

    <!-- ── Info section: matches grid container padding exactly ── -->
    <div 
      class="mx-auto w-full max-w-[1440px] 2xl:max-w-[1600px] px-4 sm:px-6 lg:px-8 pb-5"
      :class="hideCover ? 'pt-6' : ''"
    >
      <div class="flex flex-col md:flex-row md:items-end gap-4 md:gap-6">

        <!-- Logo with overlap -->
        <div 
          class="relative shrink-0 self-start group"
          :class="!hideCover ? '-mt-10 sm:-mt-14 md:-mt-18 lg:-mt-20' : ''"
        >
          <div class="h-20 w-20 sm:h-28 sm:w-28 md:h-36 md:w-36 lg:h-44 lg:w-44 overflow-hidden rounded-2xl border-4 border-white dark:border-vikinger-dark-200 bg-gray-100 dark:bg-gray-800 shadow-xl">
            <img :src="logoUrl" alt="Course Logo" class="h-full w-full object-cover" />
          </div>
          <!-- Logo edit (admin) -->
          <div v-if="isAdmin" class="absolute -bottom-1 -right-1">
            <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="onLogoInputChange" />
            <button
              @click="browseLogo"
              :disabled="isUpdatingLogo"
              class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg hover:scale-110 active:scale-95 transition-all border-2 border-white dark:border-vikinger-dark-200"
            >
              <Icon v-if="isUpdatingLogo" icon="svg-spinners:ring-resize" class="w-4 h-4" />
              <Icon v-else icon="fluent:camera-24-filled" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Title block -->
        <div class="flex-1 min-w-0 pt-2 md:pb-3">
          <!-- Course name + edit -->
          <div class="flex items-start gap-2 mb-1.5">
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-gray-900 dark:text-white leading-tight">
              {{ course?.name || 'รายวิชาไม่มีชื่อ' }}
            </h1>
            <button
              v-if="isAdmin"
              @click="$emit('edit-name')"
              class="mt-0.5 p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all shrink-0"
            >
              <Icon icon="fluent:edit-24-filled" class="w-4 h-4" />
            </button>
          </div>

          <!-- Owner + code + academy -->
          <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-semibold mb-4">
            <NuxtLink v-if="courseOwnerPath" :to="courseOwnerPath" class="hover:text-vikinger-purple dark:hover:text-vikinger-cyan transition-colors">
              {{ courseOwnerName }}
            </NuxtLink>
            <span v-else>{{ courseOwnerName }}</span>

            <span v-if="course?.code" class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800 text-xs font-black">
              #{{ course.code }}
            </span>

            <NuxtLink
              v-if="academy"
              :to="`/academies/${academy.id}`"
              class="flex items-center gap-1 hover:text-vikinger-purple dark:hover:text-vikinger-cyan transition-colors"
            >
              <Icon icon="mdi:school" class="w-3.5 h-3.5" />
              {{ academy.name }}
            </NuxtLink>
          </div>

          <!-- Semester / Academic year / Education level badges -->
          <div v-if="hasMetadata" class="flex flex-wrap items-center gap-2 mb-4">
            <span v-if="semesterLabel" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-900/25 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800 text-xs font-bold">
              <Icon icon="fluent:calendar-ltr-24-filled" class="w-3 h-3" />
              {{ semesterLabel }}
            </span>
            <span v-if="academicYearLabel" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-900/25 text-amber-700 dark:text-amber-300 border border-amber-100 dark:border-amber-800 text-xs font-bold">
              <Icon icon="fluent:hat-graduation-24-filled" class="w-3 h-3" />
              {{ academicYearLabel }}
            </span>
            <span v-if="educationLevelLabel" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-900/25 text-emerald-700 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-800 text-xs font-bold">
              <Icon icon="fluent:people-community-24-filled" class="w-3 h-3" />
              {{ educationLevelLabel }}
            </span>
          </div>

          <!-- Stats + Action (desktop inline) -->
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
              <slot name="stats" />
            </div>
            <div v-if="!isAdmin" class="shrink-0">
              <CourseActionButton
                variant="hero"
                :course="course"
                :course-member-of-auth="courseMemberOfAuth"
                @refresh="emit('refresh')"
                @request-member="emit('request-member')"
                @purchase-course="emit('purchase-course')"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
