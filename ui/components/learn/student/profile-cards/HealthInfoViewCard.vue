<script setup lang="ts">
import { computed } from 'vue'
import type { StudentHealthInfo } from '~/composables/useStudentProfile'

const props = defineProps<{
  healthInfo?: StudentHealthInfo | null
}>()

const bmi = computed(() => {
  if (!props.healthInfo?.height_cm || !props.healthInfo?.weight_kg) return null
  const h = props.healthInfo.height_cm / 100
  const val = props.healthInfo.weight_kg / (h * h)
  return val.toFixed(1)
})

const bmiStatus = computed(() => {
  if (!bmi.value) return null
  const v = parseFloat(bmi.value)
  if (v < 18.5) return { label: 'น้ำหนักต่ำ', color: 'text-yellow-600' }
  if (v < 25) return { label: 'ปกติ', color: 'text-green-600' }
  if (v < 30) return { label: 'น้ำหนักเกิน', color: 'text-orange-600' }
  return { label: 'อ้วน', color: 'text-red-600' }
})
</script>

<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 px-5 py-4">
      <div class="flex items-center">
        <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </div>
        <h3 class="ml-3 text-lg font-semibold text-white">ข้อมูลสุขภาพ</h3>
      </div>
    </div>
    <div class="p-5">
      <div v-if="!healthInfo" class="text-center py-8">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
        <p class="text-sm text-gray-500">ยังไม่มีข้อมูลสุขภาพ</p>
      </div>
      <div v-else>
        <!-- Body Stats -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
          <div class="bg-gradient-to-br from-rose-50 to-rose-100 rounded-xl p-3 text-center">
            <p class="text-xs text-rose-600 font-medium">ส่วนสูง</p>
            <p class="text-lg font-bold text-rose-900">{{ healthInfo.height_cm || '-' }} <span class="text-xs font-normal">ซม.</span></p>
          </div>
          <div class="bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl p-3 text-center">
            <p class="text-xs text-pink-600 font-medium">น้ำหนัก</p>
            <p class="text-lg font-bold text-pink-900">{{ healthInfo.weight_kg || '-' }} <span class="text-xs font-normal">กก.</span></p>
          </div>
          <div class="bg-gradient-to-br from-fuchsia-50 to-fuchsia-100 rounded-xl p-3 text-center">
            <p class="text-xs text-fuchsia-600 font-medium">BMI</p>
            <p class="text-lg font-bold text-fuchsia-900">{{ bmi || '-' }}</p>
            <p v-if="bmiStatus" :class="['text-xs font-medium mt-0.5', bmiStatus.color]">{{ bmiStatus.label }}</p>
          </div>
          <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-3 text-center">
            <p class="text-xs text-red-600 font-medium">หมู่เลือด</p>
            <p class="text-lg font-bold text-red-900">{{ healthInfo.blood_type || '-' }}{{ healthInfo.rh_factor ? '(' + healthInfo.rh_factor + ')' : '' }}</p>
          </div>
        </div>
        <!-- Medical Info -->
        <dl class="space-y-3">
          <div v-if="healthInfo.allergies" class="rounded-xl bg-yellow-50 border border-yellow-200 p-3">
            <dt class="text-xs font-medium text-yellow-700 flex items-center">
              <span class="mr-1">⚠️</span> ประวัติแพ้ยา/อาหาร
            </dt>
            <dd class="mt-1 text-sm text-yellow-900">{{ healthInfo.allergies }}</dd>
          </div>
          <div v-if="healthInfo.chronic_diseases" class="rounded-xl bg-orange-50 border border-orange-200 p-3">
            <dt class="text-xs font-medium text-orange-700 flex items-center">
              <span class="mr-1">🏥</span> โรกประจำตัว
            </dt>
            <dd class="mt-1 text-sm text-orange-900">{{ healthInfo.chronic_diseases }}</dd>
          </div>
          <div v-if="healthInfo.medications" class="rounded-xl bg-blue-50 border border-blue-200 p-3">
            <dt class="text-xs font-medium text-blue-700 flex items-center">
              <span class="mr-1">💊</span> ยาที่ใช้ประจำ
            </dt>
            <dd class="mt-1 text-sm text-blue-900">{{ healthInfo.medications }}</dd>
          </div>
          <p v-if="!healthInfo.allergies && !healthInfo.chronic_diseases && !healthInfo.medications"
             class="text-sm text-gray-500 text-center py-2">
            ไม่มีข้อมูลทางการแพทย์ที่ต้องระวัง
          </p>
        </dl>
      </div>
    </div>
  </div>
</template>
