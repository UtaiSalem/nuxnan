<script setup lang="ts">
import { computed } from 'vue'
import type { StudentProfile } from '~/composables/useStudentProfile'

const props = defineProps<{
  student: StudentProfile
}>()

const formatDate = (dateStr: string | null) => {
  if (!dateStr) return '-'
  try {
    return new Intl.DateTimeFormat('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date(dateStr))
  } catch {
    return dateStr
  }
}

const formatCitizenId = (id: string | null) => {
  if (!id) return '-'
  const digits = id.replace(/\D/g, '')
  if (digits.length !== 13) return id
  return `${digits[0]}-${digits.substring(1, 5)}-${digits.substring(5, 10)}-${digits.substring(10, 12)}-${digits[12]}`
}

const genderText = computed(() => {
  if (props.student.gender === 1) return 'ชาย'
  if (props.student.gender === 0) return 'หญิง'
  return 'ไม่ระบุ'
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-5 py-4">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลส่วนตัว</h3>
      </div>
    </div>
    <div class="p-5">
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">เลขประจำตัวประชาชน</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatCitizenId(student.citizen_id) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">เพศ</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ genderText }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">วันเกิด</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(student.date_of_birth) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">สัญชาติ</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ student.nationality || '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">ศาสนา</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ student.religion || '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">กรุ๊ปเลือด</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ student.blood_type || '-' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">วันที่เข้าเรียน</dt>
          <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(student.enrollment_date) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
