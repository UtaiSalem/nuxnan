<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'

// Props
interface Props {
  searchQuery: string
  selectedCategory: string
  selectedLevel: string
  selectedEducationLevel: string
  selectedSemester: string
  selectedYear: string
  sortBy: string
  
  // New props for integrated marketplace
  marketplaceOnly?: boolean
  enrollableOnly?: boolean
  isFree?: boolean

  categories: Array<{ value: string; label: string }>
  levels: Array<{ value: string; label: string }>
  educationLevels: Array<{ value: string; label: string }>
  semesters: Array<{ value: string; label: string }>
  years: Array<{ value: string; label: string }>
  sortOptions: Array<{ value: string; label: string }>
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  'update:searchQuery': [value: string]
  'update:selectedCategory': [value: string]
  'update:selectedLevel': [value: string]
  'update:selectedEducationLevel': [value: string]
  'update:selectedSemester': [value: string]
  'update:selectedYear': [value: string]
  'update:sortBy': [value: string]
  
  // New emits
  'update:marketplaceOnly': [value: boolean]
  'update:enrollableOnly': [value: boolean]
  'update:isFree': [value: boolean]
  
  'handleSearch': []
  'clearFilters': []
}>()

// Active filters count
const activeFiltersCount = computed(() => {
  let count = 0
  if (props.selectedCategory !== 'all') count++
  if (props.selectedLevel !== 'all') count++
  if (props.selectedEducationLevel !== 'all') count++
  if (props.selectedSemester !== 'all') count++
  if (props.selectedYear !== 'all') count++
  if (props.marketplaceOnly) count++
  if (props.enrollableOnly) count++
  if (props.isFree) count++
  return count
})

// Sections toggle
const showMarketplaceFilters = ref(true)

// Search handler with debounce
let searchTimeout: ReturnType<typeof setTimeout>
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    emit('handleSearch')
  }, 300)
}

// Helper methods for event handling
const onSearchInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:searchQuery', target.value)
  handleSearch()
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
    <!-- Header -->
    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-800/50">
      <div class="flex items-center gap-2">
        <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <Icon icon="fluent:filter-24-filled" class="w-5 h-5 text-blue-600" />
          ค้นหาและกรอง
        </h3>
        <span v-if="activeFiltersCount > 0" class="flex items-center justify-center w-5 h-5 bg-blue-600 text-white text-[10px] font-bold rounded-full animate-pulse">
          {{ activeFiltersCount }}
        </span>
      </div>
      <button 
        v-if="activeFiltersCount > 0 || searchQuery"
        @click="$emit('clearFilters')"
        class="text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors flex items-center gap-1"
      >
        <Icon icon="fluent:dismiss-24-regular" class="w-3 h-3" />
        ล้างทั้งหมด
      </button>
    </div>

    <!-- Content -->
    <div class="p-4 space-y-6">
      <!-- Search Input -->
      <div class="relative group">
        <Icon
          icon="fluent:search-24-regular"
          class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors"
        />
        <input
          :value="searchQuery"
          @input="onSearchInput"
          type="text"
          placeholder="ค้นหารายวิชา..."
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg pl-10 pr-10 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all shadow-inner"
        />
        <button 
          v-if="searchQuery"
          @click="$emit('update:searchQuery', ''); $emit('handleSearch')"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
        >
          <Icon icon="fluent:dismiss-circle-24-filled" class="w-4 h-4" />
        </button>
      </div>

      <!-- Basic Filters -->
      <div class="space-y-4">
        <!-- Category -->
        <div>
          <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 px-1">
            หมวดหมู่
          </label>
          <select
            :value="selectedCategory"
            @change="$emit('update:selectedCategory', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none cursor-pointer select-custom-icon"
          >
            <option v-for="cat in categories" :key="cat.value" :value="cat.value">
              {{ cat.label }}
            </option>
          </select>
        </div>

        <!-- Level -->
        <div>
          <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 px-1">
            ระดับ
          </label>
          <select
            :value="selectedLevel"
            @change="$emit('update:selectedLevel', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer select-custom-icon"
          >
            <option v-for="level in levels" :key="level.value" :value="level.value">
              {{ level.label }}
            </option>
          </select>
        </div>

        <!-- Education Level -->
        <div>
          <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 px-1">
            ระดับการศึกษา
          </label>
          <select
            :value="selectedEducationLevel"
            @change="$emit('update:selectedEducationLevel', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer select-custom-icon"
          >
            <option v-for="el in educationLevels" :key="el.value" :value="el.value">
              {{ el.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Marketplace Section -->
      <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
        <button 
          @click="showMarketplaceFilters = !showMarketplaceFilters"
          class="flex items-center justify-between w-full mb-4 group"
        >
          <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest group-hover:text-blue-500 transition-colors">
            ประเภทการเข้าถึง
          </span>
          <Icon 
            :icon="showMarketplaceFilters ? 'fluent:chevron-up-12-filled' : 'fluent:chevron-down-12-filled'" 
            class="w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors"
          />
        </button>
        
        <div v-if="showMarketplaceFilters" class="grid grid-cols-1 gap-2.5">
          <!-- Enrollable Pill -->
          <button 
            @click="$emit('update:enrollableOnly', !enrollableOnly)"
            class="flex items-center justify-between px-4 py-2.5 rounded-xl border transition-all text-left"
            :class="enrollableOnly 
              ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/30 dark:border-blue-800 dark:text-blue-300' 
              : 'bg-white border-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 hover:border-blue-200 dark:hover:border-blue-800'"
          >
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                :class="enrollableOnly ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                <Icon icon="fluent:person-add-24-filled" class="w-4 h-4" />
              </div>
              <div class="flex flex-col">
                <span class="text-xs font-bold">เปิดให้สมัครเรียน</span>
                <span class="text-[9px] uppercase opacity-70">Enrollable</span>
              </div>
            </div>
            <Icon v-if="enrollableOnly" icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-blue-600" />
          </button>

          <!-- Marketplace Pill -->
          <button 
            @click="$emit('update:marketplaceOnly', !marketplaceOnly)"
            class="flex items-center justify-between px-4 py-2.5 rounded-xl border transition-all text-left"
            :class="marketplaceOnly 
              ? 'bg-violet-50 border-violet-200 text-violet-700 dark:bg-violet-900/30 dark:border-violet-800 dark:text-violet-300' 
              : 'bg-white border-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 hover:border-violet-200 dark:hover:border-violet-800'"
          >
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                :class="marketplaceOnly ? 'bg-violet-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                <Icon icon="fluent:copy-24-filled" class="w-4 h-4" />
              </div>
              <div class="flex flex-col">
                <span class="text-xs font-bold">มี Master Copy</span>
                <span class="text-[9px] uppercase opacity-70">Marketplace</span>
              </div>
            </div>
            <Icon v-if="marketplaceOnly" icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-violet-600" />
          </button>

          <!-- Free Pill -->
          <button 
            @click="$emit('update:isFree', !isFree)"
            class="flex items-center justify-between px-4 py-2.5 rounded-xl border transition-all text-left"
            :class="isFree 
              ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300' 
              : 'bg-white border-gray-100 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 hover:border-green-200 dark:hover:border-green-800'"
          >
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors"
                :class="isFree ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'">
                <Icon icon="fluent:money-off-24-filled" class="w-4 h-4" />
              </div>
              <div class="flex flex-col">
                <span class="text-xs font-bold">รายวิชาฟรี</span>
                <span class="text-[9px] uppercase opacity-70">Free Courses</span>
              </div>
            </div>
            <Icon v-if="isFree" icon="fluent:checkmark-circle-24-filled" class="w-5 h-5 text-green-600" />
          </button>
        </div>
      </div>

      <!-- Academic Info Section -->
      <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
         <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3 px-1">
            ข้อมูลปีการศึกษา
          </label>
          <div class="grid grid-cols-1 gap-3">
             <div class="relative">
                <select
                  :value="selectedSemester"
                  @change="$emit('update:selectedSemester', ($event.target as HTMLSelectElement).value)"
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer select-custom-icon"
                >
                  <option v-for="sem in semesters" :key="sem.value" :value="sem.value">
                    {{ sem.label }}
                  </option>
                </select>
             </div>
             <div class="relative">
                <select
                  :value="selectedYear"
                  @change="$emit('update:selectedYear', ($event.target as HTMLSelectElement).value)"
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-3 py-2.5 text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none cursor-pointer select-custom-icon"
                >
                  <option v-for="y in years" :key="y.value" :value="y.value">
                    {{ y.label }}
                  </option>
                </select>
             </div>
          </div>
      </div>

      <!-- Sort -->
      <div class="pt-4 border-t border-gray-100 dark:border-gray-700 pb-2">
        <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3 px-1">
          จัดเรียงตาม
        </label>
        <div class="flex flex-wrap gap-2">
          <button 
            v-for="option in sortOptions" 
            :key="option.value"
            @click="$emit('update:sortBy', option.value)"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all border"
            :class="sortBy === option.value 
              ? 'bg-blue-600 border-blue-600 text-white shadow-sm' 
              : 'bg-white border-gray-100 text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 hover:border-blue-200 dark:hover:border-blue-800'"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.select-custom-icon {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239CA3AF'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  background-size: 1rem;
}
</style>
