<script setup lang="ts">
import { computed } from 'vue'
import type { StudentProfile, ClassroomInfo, AcademyInfo } from '~/composables/useStudentProfile'

const props = defineProps<{
  student: StudentProfile
  classroom?: ClassroomInfo | null
  academy?: AcademyInfo | null
  accessLevel?: string
  accessLevelLabel?: string
}>()

const fullNameTh = computed(() => {
  return [props.student.title_prefix_th, props.student.first_name_th, props.student.last_name_th]
    .filter(Boolean).join(' ')
})

const fullNameEn = computed(() => {
  return [props.student.title_prefix_en, props.student.first_name_en, props.student.last_name_en]
    .filter(Boolean).join(' ')
})

const classDisplay = computed(() => {
  if (!props.student.class_level) return '-'
  return `ม.${props.student.class_level}/${props.student.class_section || '-'}`
})

const userInitial = computed(() => {
  return props.student.first_name_th?.charAt(0) || props.student.first_name_en?.charAt(0) || 'S'
})

const statusConfig = computed(() => {
  const map: Record<string, { label: string; color: string }> = {
    active: { label: 'กำลังศึกษา', color: 'bg-green-100 text-green-800' },
    inactive: { label: 'ไม่เปิดใช้', color: 'bg-gray-100 text-gray-800' },
    graduated: { label: 'สำเร็จการศึกษา', color: 'bg-blue-100 text-blue-800' },
    transferred: { label: 'ย้าย', color: 'bg-yellow-100 text-yellow-800' },
  }
  return map[props.student.status] || { label: props.student.status, color: 'bg-gray-100 text-gray-800' }
})

const genderIcon = computed(() => {
  if (props.student.gender === 1) return '♂'
  if (props.student.gender === 0) return '♀'
  return ''
})

const profileImageUrl = computed(() => {
  if (!props.student.profile_image) return null
  const level = props.student.class_level
  const section = props.student.class_section
  if (level && section) {
    return `/storage/images/students/${level}/${section}/${props.student.profile_image}`
  }
  return null
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <!-- Cover Gradient -->
    <div class="h-32 sm:h-40 bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-700 relative">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-4 right-8 w-20 h-20 bg-white rounded-full"></div>
        <div class="absolute bottom-2 left-12 w-16 h-16 bg-white rounded-full"></div>
      </div>
      <!-- Access Level Badge -->
      <div v-if="accessLevelLabel" class="absolute top-3 right-3 sm:top-4 sm:right-4">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          {{ accessLevelLabel }}
        </span>
      </div>
      <!-- Academy Name -->
      <div v-if="academy" class="absolute top-3 left-3 sm:top-4 sm:left-4">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm">
          🏫 {{ academy.name }}
        </span>
      </div>
    </div>

    <!-- Profile Content -->
    <div class="relative px-4 sm:px-6 pb-6">
      <!-- Avatar -->
      <div class="flex flex-col sm:flex-row sm:items-end -mt-16 sm:-mt-20 mb-4">
        <div class="relative flex-shrink-0">
          <div class="w-28 h-28 sm:w-36 sm:h-36 rounded-2xl border-4 border-white shadow-lg overflow-hidden bg-gradient-to-br from-blue-100 to-indigo-100">
            <img v-if="profileImageUrl" :src="profileImageUrl" :alt="fullNameTh" class="w-full h-full object-cover" @error="($event.target as HTMLImageElement).style.display='none'">
            <div v-if="!profileImageUrl" class="w-full h-full flex items-center justify-center">
              <span class="text-4xl sm:text-5xl font-bold text-blue-600">{{ userInitial }}</span>
            </div>
          </div>
          <!-- Gender Badge -->
          <span v-if="genderIcon" class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full flex items-center justify-center text-lg shadow-md"
                :class="student.gender === 1 ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600'">
            {{ genderIcon }}
          </span>
        </div>

        <!-- Name & Info -->
        <div class="mt-4 sm:mt-0 sm:ml-5 sm:pb-2 flex-1 min-w-0">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="min-w-0">
              <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 truncate">{{ fullNameTh }}</h1>
              <p v-if="fullNameEn" class="text-sm text-gray-500 truncate">{{ fullNameEn }}</p>
            </div>
            <span :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold whitespace-nowrap', statusConfig.color]">
              {{ statusConfig.label }}
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Info Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-2">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-3 text-center">
          <p class="text-xs text-blue-600 font-medium">รหัสนักเรียน</p>
          <p class="text-sm sm:text-base font-bold text-blue-900 mt-1">{{ student.student_id || '-' }}</p>
        </div>
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-3 text-center">
          <p class="text-xs text-purple-600 font-medium">ชั้นเรียน</p>
          <p class="text-sm sm:text-base font-bold text-purple-900 mt-1">{{ classDisplay }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-3 text-center">
          <p class="text-xs text-amber-600 font-medium">ชื่อเล่น</p>
          <p class="text-sm sm:text-base font-bold text-amber-900 mt-1">{{ student.nickname || '-' }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-3 text-center">
          <p class="text-xs text-emerald-600 font-medium">อายุ</p>
          <p class="text-sm sm:text-base font-bold text-emerald-900 mt-1">{{ student.age ? student.age + ' ปี' : '-' }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
