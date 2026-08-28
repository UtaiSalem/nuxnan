<script setup lang="ts">
/**
 * At-Risk Student Dashboard
 * สำหรับผู้บริหารและครูฝ่ายปกครองติดตามนักเรียนกลุ่มเสี่ยง
 */

import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main',
  middleware: ['auth']
})

const route = useRoute()
const schoolApi = useSchoolManagement()
const academyName = computed(() => route.params.name as string)
const academyId = ref<number | null>(null)

const students = ref<any[]>([])
const isLoading = ref(true)
const threshold = ref(80)

onMounted(async () => {
  try {
    const response: any = await useApi().get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchAtRiskStudents()
    }
  } catch (err) {
    console.error('Failed to load academy:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchAtRiskStudents = async () => {
  if (!academyId.value) return
  isLoading.value = true
  try {
    const response: any = await schoolApi.getAtRiskStudents(academyId.value, { 
      attendance_threshold: threshold.value 
    })
    if (response.success) {
      students.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to fetch at-risk students:', err)
  } finally {
    isLoading.value = false
  }
}

const getRiskLevel = (student: any) => {
  if (student.risk_factors.length >= 2) return 'critical'
  return 'warning'
}

const getRiskClass = (level: string) => {
  if (level === 'critical') return 'border-red-500 bg-red-50 dark:bg-red-900/10'
  return 'border-amber-500 bg-amber-50 dark:bg-amber-900/10'
}

const getStatusBadge = (level: string) => {
  if (level === 'critical') return 'bg-red-500 text-white'
  return 'bg-amber-500 text-white'
}

const getFactorLabel = (factor: string) => {
  switch (factor) {
    case 'low_attendance': return 'อัตราเข้าเรียนต่ำ'
    case 'overdue_fees': return 'ค้างชำระค่าเทอม'
    case 'poor_grades': return 'ผลการเรียนวิกฤต'
    default: return factor
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 pb-20">
    <div class="max-w-7xl mx-auto px-0 sm:px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
            <Icon icon="fluent:person-warning-24-filled" class="w-10 h-10 text-red-500" />
            นักเรียนกลุ่มเสี่ยง (At-Risk)
          </h1>
          <p class="text-gray-500 dark:text-gray-400 mt-1">ติดตามและให้ความช่วยเหลือนักเรียนที่ต้องการความดูแลเป็นพิเศษ</p>
        </div>
        
        <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-2 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
          <label class="text-xs font-bold text-gray-400 uppercase ml-2">เกณฑ์เข้าเรียน (%)</label>
          <input 
            v-model.number="threshold" 
            type="number" 
            min="0" max="100"
            @change="fetchAtRiskStudents"
            class="w-20 px-3 py-2 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl font-bold outline-none focus:ring-2 focus:ring-primary-500/20"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="h-48 bg-white dark:bg-gray-800 rounded-3xl animate-pulse"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="students.length === 0" class="bg-white dark:bg-gray-800 rounded-[2.5rem] p-20 text-center shadow-sm">
        <div class="w-32 h-32 bg-green-50 dark:bg-green-900/20 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500">
          <Icon icon="fluent:checkmark-starburst-24-filled" class="w-16 h-16" />
        </div>
        <h2 class="text-2xl font-black text-gray-900 dark:text-white">เยี่ยมมาก!</h2>
        <p class="text-gray-500 mt-2">ไม่มีนักเรียนที่เข้าข่ายกลุ่มเสี่ยงตามเกณฑ์ที่กำหนด</p>
      </div>

      <!-- Student Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="student in students" 
          :key="student.id"
          :class="['p-6 rounded-[2rem] border-2 shadow-sm transition-all hover:shadow-md', getRiskClass(getRiskLevel(student))]"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-4">
              <CircleAvatar :src="student.profile_photo_path" :name="student.name" size="md" />
              <div>
                <h3 class="font-black text-gray-900 dark:text-white leading-none">{{ student.name }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ student.academy_member?.classroom?.name || 'ไม่ระบุห้อง' }}</p>
              </div>
            </div>
            <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', getStatusBadge(getRiskLevel(student))]">
              {{ getRiskLevel(student) }}
            </span>
          </div>

          <div class="space-y-2 mb-6">
            <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest">สาเหตุที่ติดกลุ่มเสี่ยง</p>
            <div class="flex flex-wrap gap-2">
              <span 
                v-for="factor in student.risk_factors" 
                :key="factor"
                class="px-3 py-1 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-sm"
              >
                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                {{ getFactorLabel(factor) }}
              </span>
            </div>
          </div>

          <div class="flex gap-2">
            <button class="flex-1 px-4 py-3 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl text-xs font-black uppercase tracking-tight hover:bg-gray-50 transition-colors">
              ติดต่อผู้ปกครอง
            </button>
            <button class="flex-1 px-4 py-3 bg-primary-500 text-white rounded-2xl text-xs font-black uppercase tracking-tight hover:bg-primary-600 transition-colors">
              ดูประวัติเชิงลึก
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
