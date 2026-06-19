<script setup lang="ts">
import type { AcademicInfo } from '~/composables/useStudentProfile'

defineProps<{
  academicInfo?: AcademicInfo[]
}>()

const educationLevelText = (level: number | null) => {
  const map: Record<number, string> = { 1: 'ประถมศึกษา', 2: 'มัธยมศึกษา', 3: 'อุดมศึกษา' }
  return level ? (map[level] || '-') : '-'
}

const statusText = (status: string | null) => {
  const map: Record<string, string> = {
    studying: 'กำลังศึกษา',
    graduated: 'สำเร็จการศึกษา',
    transferred: 'ย้ายสถานศึกษา',
    dropped: 'พ้นสภาพ',
  }
  return status ? (map[status] || status) : '-'
}

const statusColor = (status: string | null) => {
  const map: Record<string, string> = {
    studying: 'bg-green-100 text-green-800',
    graduated: 'bg-blue-100 text-blue-800',
    transferred: 'bg-yellow-100 text-yellow-800',
    dropped: 'bg-red-100 text-red-800',
  }
  return status ? (map[status] || 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-5 py-4">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ประวัติการศึกษา</h3>
      </div>
    </div>
    <div class="p-5">
      <div v-if="!academicInfo || academicInfo.length === 0" class="text-center py-8">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลประวัติการศึกษา</p>
      </div>
      <div v-else class="space-y-4">
        <div v-for="info in academicInfo" :key="info.id"
             :class="['rounded-xl border p-4 transition-all', info.is_current ? 'border-blue-200 bg-blue-50/50 ring-1 ring-blue-200' : 'border-gray-200 bg-white']">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span v-if="info.is_current" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-600 text-white">
                ปัจจุบัน
              </span>
              <span class="text-sm font-semibold text-gray-900">ปีการศึกษา {{ info.academic_year || '-' }}</span>
            </div>
            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', statusColor(info.study_status)]">
              {{ statusText(info.study_status) }}
            </span>
          </div>
          <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
            <div>
              <dt class="text-gray-500 text-xs">ระดับชั้น</dt>
              <dd class="font-medium text-gray-900">{{ info.current_grade ? 'ม.' + info.current_grade : '-' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 text-xs">ห้อง</dt>
              <dd class="font-medium text-gray-900">{{ info.current_class || '-' }}</dd>
            </div>
            <div>
              <dt class="text-gray-500 text-xs">ระดับการศึกษา</dt>
              <dd class="font-medium text-gray-900">{{ educationLevelText(info.education_level) }}</dd>
            </div>
            <div class="col-span-2 sm:col-span-3">
              <dt class="text-gray-500 text-xs">สถานศึกษา</dt>
              <dd class="font-medium text-gray-900">{{ info.school_name || '-' }}</dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>
