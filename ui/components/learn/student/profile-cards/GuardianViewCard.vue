<script setup lang="ts">
import type { StudentGuardian } from '~/composables/useStudentProfile'

defineProps<{
  guardians?: StudentGuardian[]
}>()

const typeText = (type: string) => {
  const map: Record<string, string> = {
    father: 'บิดา',
    mother: 'มารดา',
    guardian: 'ผู้ปกครอง',
    relative: 'ญาติ',
    other: 'อื่นๆ',
  }
  return map[type] || type || '-'
}

const typeIcon = (type: string) => {
  const map: Record<string, string> = {
    father: '👨',
    mother: '👩',
    guardian: '🧑‍🦳',
    relative: '👥',
    other: '🧑',
  }
  return map[type] || '🧑'
}

const fullName = (g: StudentGuardian) => {
  return [g.title_prefix, g.first_name, g.last_name].filter(Boolean).join(' ') || '-'
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-5 py-4">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลผู้ปกครอง</h3>
      </div>
    </div>
    <div class="p-5">
      <div v-if="!guardians || guardians.length === 0" class="text-center py-8">
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลผู้ปกครอง</p>
      </div>
      <div v-else class="space-y-4">
        <div v-for="g in guardians" :key="g.id" class="rounded-xl border border-gray-250 p-4">
          <div class="flex items-start gap-3">
            <span class="text-2xl flex-shrink-0 mt-0.5">{{ typeIcon(g.guardian_type) }}</span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h4 class="text-sm font-bold text-gray-900">{{ fullName(g) }}</h4>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                  {{ typeText(g.guardian_type) }}
                </span>
                <span v-if="g.is_primary_contact" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-purple-600 text-white">หลัก</span>
                <span v-if="g.is_emergency_contact" class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ฉุกเฉิน</span>
              </div>
              <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <div v-if="g.relationship">
                  <dt class="text-gray-500 text-xs">ความสัมพันธ์</dt>
                  <dd class="text-gray-900">{{ g.relationship }}</dd>
                </div>
                <div v-if="g.occupation">
                  <dt class="text-gray-500 text-xs">อาชีพ</dt>
                  <dd class="text-gray-900">{{ g.occupation }}</dd>
                </div>
                <div v-if="g.workplace">
                  <dt class="text-gray-500 text-xs">สถานที่ทำงาน</dt>
                  <dd class="text-gray-900">{{ g.workplace }}</dd>
                </div>
                <div v-if="g.monthly_income">
                  <dt class="text-gray-500 text-xs">รายได้ต่อเดือน</dt>
                  <dd class="text-gray-900">{{ Number(g.monthly_income).toLocaleString() }} บาท</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
