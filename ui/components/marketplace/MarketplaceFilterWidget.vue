<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'
import FilterSection from './FilterSection.vue'

interface Filters {
  search: string
  category: string
  level: string
  language: string
  semester: string
  academic_year: string
  credit_units_min: number | null
  credit_units_max: number | null
  hours_per_week_min: number | null
  hours_per_week_max: number | null
  price_min: number | null
  price_max: number | null
  is_free: boolean
  rating_min: number | null
  sort: string
}

const props = defineProps<{
  modelValue: Filters
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: Filters): void
  (e: 'apply'): void
  (e: 'reset'): void
}>()

const filters = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

// Collapsible sections
const openSections = ref({
  basic: true,
  academic: true,
  schedule: false,
  price: true,
  quality: false,
})

const toggleSection = (key: keyof typeof openSections.value) => {
  openSections.value[key] = !openSections.value[key]
}

// Options
const categories = [
  'การเขียนโปรแกรม', 'การออกแบบ', 'ธุรกิจ', 'ภาษา', 
  'ดนตรี', 'สุขภาพ', 'คณิตศาสตร์', 'วิทยาศาสตร์', 'ศิลปะ'
]
const levels = [
  { value: 'beginner', label: 'พื้นฐาน' },
  { value: 'intermediate', label: 'ปานกลาง' },
  { value: 'advanced', label: 'ขั้นสูง' },
  { value: 'expert', label: 'เชี่ยวชาญ' },
]
const languages = [
  { value: 'th', label: 'ไทย' },
  { value: 'en', label: 'อังกฤษ' },
  { value: 'zh', label: 'จีน' },
  { value: 'ja', label: 'ญี่ปุ่น' },
]
const semesters = [
  { value: '1', label: 'ภาคต้น (เทอม 1)' },
  { value: '2', label: 'ภาคปลาย (เทอม 2)' },
  { value: '3', label: 'ภาคฤดูร้อน' },
]
const academicYears = computed(() => {
  const currentYear = new Date().getFullYear() + 543 // BE
  return Array.from({ length: 5 }, (_, i) => String(currentYear - i))
})

const reset = () => {
  emit('reset')
}

const apply = () => {
  emit('apply')
}

const activeFilterCount = computed(() => {
  let count = 0
  const f = filters.value
  if (f.search) count++
  if (f.category) count++
  if (f.level) count++
  if (f.language) count++
  if (f.semester) count++
  if (f.academic_year) count++
  if (f.credit_units_min || f.credit_units_max) count++
  if (f.hours_per_week_min || f.hours_per_week_max) count++
  if (f.price_min || f.price_max) count++
  if (f.is_free) count++
  if (f.rating_min) count++
  return count
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-2xl shadow-sm border border-gray-100 dark:border-vikinger-dark-100 overflow-hidden sticky top-24 max-h-[calc(100vh-7rem)] flex flex-col">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-vikinger-dark-100">
      <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2 text-sm">
        <Icon icon="fluent:filter-24-filled" class="w-5 h-5 text-vikinger-purple" />
        ตัวกรองขั้นสูง
        <span v-if="activeFilterCount > 0" class="bg-vikinger-purple text-white text-[10px] px-1.5 py-0.5 rounded-full font-black">
          {{ activeFilterCount }}
        </span>
      </h3>
      <button 
        v-if="activeFilterCount > 0"
        @click="reset" 
        class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1"
      >
        <Icon icon="fluent:dismiss-circle-24-regular" class="w-3.5 h-3.5" />
        ล้าง
      </button>
    </div>

    <!-- Scrollable filter body -->
    <div class="flex-1 overflow-y-auto custom-scrollbar">
      
      <!-- SECTION: Basic Search -->
      <FilterSection title="ค้นหาทั่วไป" icon="fluent:search-24-filled" :open="openSections.basic" @toggle="toggleSection('basic')">
        <div class="space-y-3">
          <div class="relative">
            <input v-model="filters.search" type="text" placeholder="ชื่อวิชา, รหัสวิชา..." 
              class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-vikinger-purple text-gray-800 dark:text-white"
              @keyup.enter="apply" />
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-2.5 text-gray-400 w-4 h-4" />
          </div>

          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">หมวดหมู่</label>
            <select v-model="filters.category" class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white">
              <option value="">ทั้งหมด</option>
              <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
          </div>

          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">ภาษา</label>
            <div class="flex flex-wrap gap-1.5">
              <button v-for="lang in languages" :key="lang.value"
                @click="filters.language = filters.language === lang.value ? '' : lang.value"
                class="px-2.5 py-1 text-xs rounded-full font-medium transition-all"
                :class="filters.language === lang.value ? 'bg-vikinger-purple text-white' : 'bg-gray-100 dark:bg-vikinger-dark-100 text-gray-600 dark:text-gray-300 hover:bg-gray-200'">
                {{ lang.label }}
              </button>
            </div>
          </div>
        </div>
      </FilterSection>

      <!-- SECTION: Academic -->
      <FilterSection title="ข้อมูลวิชาการ" icon="fluent:hat-graduation-24-filled" :open="openSections.academic" @toggle="toggleSection('academic')">
        <div class="space-y-3">
          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">ระดับชั้น</label>
            <div class="grid grid-cols-2 gap-1.5">
              <button v-for="lv in levels" :key="lv.value"
                @click="filters.level = filters.level === lv.value ? '' : lv.value"
                class="px-2 py-1.5 text-xs rounded-lg font-medium transition-all"
                :class="filters.level === lv.value ? 'bg-vikinger-purple text-white' : 'bg-gray-100 dark:bg-vikinger-dark-100 text-gray-600 dark:text-gray-300 hover:bg-gray-200'">
                {{ lv.label }}
              </button>
            </div>
          </div>

          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">ภาคเรียน</label>
            <select v-model="filters.semester" class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white">
              <option value="">ทั้งหมด</option>
              <option v-for="s in semesters" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>

          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">ปีการศึกษา</label>
            <select v-model="filters.academic_year" class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white">
              <option value="">ทั้งหมด</option>
              <option v-for="year in academicYears" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>

          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">หน่วยกิต</label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model.number="filters.credit_units_min" type="number" min="0" placeholder="ต่ำสุด" 
                class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white" />
              <input v-model.number="filters.credit_units_max" type="number" min="0" placeholder="สูงสุด" 
                class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white" />
            </div>
          </div>
        </div>
      </FilterSection>

      <!-- SECTION: Schedule / Duration -->
      <FilterSection title="ตารางเรียน" icon="fluent:calendar-clock-24-filled" :open="openSections.schedule" @toggle="toggleSection('schedule')">
        <div class="space-y-3">
          <div>
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">ชั่วโมง/สัปดาห์</label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model.number="filters.hours_per_week_min" type="number" min="0" placeholder="ต่ำสุด" 
                class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white" />
              <input v-model.number="filters.hours_per_week_max" type="number" min="0" placeholder="สูงสุด" 
                class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white" />
            </div>
          </div>
        </div>
      </FilterSection>

      <!-- SECTION: Price -->
      <FilterSection title="ราคา" icon="fluent:money-24-filled" :open="openSections.price" @toggle="toggleSection('price')">
        <div class="space-y-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="filters.is_free" type="checkbox" class="rounded text-vikinger-purple focus:ring-vikinger-purple" />
            <span class="text-sm text-gray-700 dark:text-gray-300">เฉพาะวิชาฟรี</span>
          </label>
          <div v-if="!filters.is_free">
            <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">ช่วงราคา (THB)</label>
            <div class="grid grid-cols-2 gap-2">
              <input v-model.number="filters.price_min" type="number" min="0" placeholder="฿ ต่ำสุด" 
                class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white" />
              <input v-model.number="filters.price_max" type="number" min="0" placeholder="฿ สูงสุด" 
                class="w-full bg-gray-50 dark:bg-vikinger-dark-100 border-0 rounded-lg py-2 px-3 text-sm focus:ring-2 focus:ring-vikinger-purple dark:text-white" />
            </div>
          </div>
        </div>
      </FilterSection>

      <!-- SECTION: Quality -->
      <FilterSection title="คุณภาพ" icon="fluent:star-24-filled" :open="openSections.quality" @toggle="toggleSection('quality')">
        <div>
          <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5 block">เรตติ้งขั้นต่ำ</label>
          <div class="flex gap-1">
            <button v-for="r in [1, 2, 3, 4, 5]" :key="r"
              @click="filters.rating_min = filters.rating_min === r ? null : r"
              class="flex-1 py-1.5 text-xs rounded-lg font-bold transition-all flex items-center justify-center gap-0.5"
              :class="filters.rating_min && filters.rating_min >= r ? 'bg-amber-400 text-white' : 'bg-gray-100 dark:bg-vikinger-dark-100 text-gray-400'">
              <Icon icon="fluent:star-24-filled" class="w-3 h-3" />
              {{ r }}
            </button>
          </div>
        </div>
      </FilterSection>
    </div>

    <!-- Footer Apply Button -->
    <div class="px-4 py-3 border-t border-gray-100 dark:border-vikinger-dark-100 bg-gray-50/50 dark:bg-vikinger-dark-100/50">
      <button @click="apply" :disabled="loading"
        class="w-full bg-gradient-vikinger hover:shadow-vikinger text-white font-bold py-2.5 rounded-lg transition-all flex items-center justify-center gap-2 disabled:opacity-50">
        <Icon v-if="loading" icon="svg-spinners:ring-resize" class="w-4 h-4" />
        <Icon v-else icon="fluent:filter-sync-24-filled" class="w-4 h-4" />
        ใช้ตัวกรอง
      </button>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(139, 92, 246, 0.3); border-radius: 4px; }
.bg-gradient-vikinger { background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%); }
.shadow-vikinger { box-shadow: 0 10px 25px -5px rgba(111, 66, 193, 0.3); }
</style>
