<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

import CourseActionButton from './CourseActionButton.vue'

const props = defineProps({
  course: { type: Object, required: true },
  isAdmin: { type: Boolean, default: false },
  academy: { type: Object, default: null },
  courseMemberOfAuth: { type: Object, default: null },
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
</script>

<template>
  <div class="relative w-full max-w-7xl mx-auto overflow-hidden bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-xl border border-gray-100 dark:border-vikinger-dark-100">
    <!-- Cover Image -->
    <div class="relative h-[140px] xs:h-[180px] sm:h-[220px] md:h-[260px] lg:h-[300px] w-full">
      <img :src="coverUrl" alt="Course Cover" class="h-full w-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>
      
      <!-- Cover Edit Button (Admin) -->
      <div v-if="isAdmin" class="absolute top-4 right-4 z-10">
        <input type="file" ref="coverInput" class="hidden" accept="image/*" @change="onCoverInputChange">
        <button 
          @click="browseCover" 
          :disabled="isUpdatingCover"
          class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-black/50 backdrop-blur-md text-white border border-white/20 hover:bg-black/70 transition-all text-xs font-bold shadow-xl"
        >
          <Icon v-if="isUpdatingCover" icon="svg-spinners:ring-resize" class="w-4 h-4" />
          <Icon v-else icon="fluent:camera-24-filled" class="w-4 h-4" />
          <span class="hidden sm:inline">เปลี่ยนหน้าปก</span>
        </button>
      </div>

      <!-- Price Badge -->
      <div v-if="course?.tuition_fees" class="absolute top-4 left-4 z-10">
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500 text-white font-black shadow-lg border border-amber-400 text-xs">
          <Icon icon="ri:bit-coin-fill" class="w-4 h-4" />
          <span>฿{{ course.tuition_fees.toLocaleString() }}</span>
        </div>
      </div>
    </div>

    <!-- Course Info Area -->
    <div class="px-4 sm:px-6 lg:px-8 pb-6 relative z-20">
      <div class="flex flex-col md:flex-row items-center md:items-end gap-4 md:gap-8 text-center md:text-left">
        <!-- Logo/Avatar -->
        <div class="relative -mt-10 xs:-mt-14 sm:-mt-16 md:-mt-20 lg:-mt-24 group">
          <div class="h-20 w-20 xs:h-28 xs:w-28 sm:h-32 sm:w-32 md:h-40 md:w-40 lg:h-48 lg:w-48 overflow-hidden rounded-3xl border-[4px] sm:border-[6px] border-white dark:border-vikinger-dark-200 bg-gray-100 dark:bg-gray-800 shadow-xl">
            <img :src="logoUrl" alt="Course Logo" class="h-full w-full object-cover" />
          </div>
          
          <!-- Logo Edit Button (Admin) -->
          <div v-if="isAdmin" class="absolute -bottom-1 -right-1">
            <input type="file" ref="logoInput" class="hidden" accept="image/*" @change="onLogoInputChange">
            <button 
              @click="browseLogo" 
              :disabled="isUpdatingLogo"
              class="w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg hover:scale-110 active:scale-95 transition-all border-2 border-white dark:border-vikinger-dark-200"
            >
              <Icon v-if="isUpdatingLogo" icon="svg-spinners:ring-resize" class="w-4 h-4 sm:w-5 h-5" />
              <Icon v-else icon="fluent:camera-24-filled" class="w-4 h-4 sm:w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Title & Subtitle -->
        <div class="flex-1 pt-2 md:pb-2">
          <div class="flex items-start justify-center md:justify-start gap-2 mb-2">
            <h1 class="text-xl xs:text-2xl sm:text-3xl lg:text-4xl font-black text-gray-900 dark:text-white leading-tight">
              {{ course?.name || 'รายวิชาไม่มีชื่อ' }}
            </h1>
            <button 
              v-if="isAdmin" 
              @click="$emit('edit-name')"
              class="mt-1 p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all"
            >
              <Icon icon="fluent:edit-24-filled" class="w-5 h-5" />
            </button>
          </div>

          <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 text-gray-500 dark:text-gray-400 font-bold text-sm sm:text-base">
            <NuxtLink v-if="courseOwnerPath" :to="courseOwnerPath" class="hover:text-vikinger-purple dark:hover:text-vikinger-cyan transition-colors">
              {{ courseOwnerName }}
            </NuxtLink>
            <span v-else>{{ courseOwnerName }}</span>
            
            <span v-if="course?.code" class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800 text-xs font-black">
              #{{ course.code }}
            </span>

            <NuxtLink v-if="academy" :to="`/academies/${academy.id}`" class="hover:text-vikinger-purple dark:hover:text-vikinger-cyan transition-colors flex items-center gap-1">
              <Icon icon="mdi:school" class="w-4 h-4" />
              {{ academy.name }}
            </NuxtLink>
          </div>

          <!-- Desktop Stats & Action Row -->
          <div class="hidden md:flex items-center justify-between mt-6 pt-6 border-t border-gray-100 dark:border-vikinger-dark-100">
            <div class="flex items-center gap-4">
              <slot name="stats" />
            </div>
            
            <div v-if="!isAdmin">
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

      <!-- Mobile Action & Stats Area -->
      <div class="md:hidden mt-6 pt-6 border-t border-gray-100 dark:border-vikinger-dark-100 space-y-4">
        <slot name="stats" />
        
        <div v-if="!isAdmin">
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
</template>
