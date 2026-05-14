<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref } from 'vue'

// Props
interface Props {
  searchQuery: string
  selectedCategory: string
  selectedLevel: string
  selectedSemester: string
  selectedYear: string
  sortBy: string
  
  // New props for integrated marketplace
  marketplaceOnly: boolean
  enrollableOnly: boolean
  isFree: boolean

  categories: Array<{ value: string; label: string }>
  levels: Array<{ value: string; label: string }>
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
  'update:selectedSemester': [value: string]
  'update:selectedYear': [value: string]
  'update:sortBy': [value: string]
  
  // New emits
  'update:marketplaceOnly': [value: boolean]
  'update:enrollableOnly': [value: boolean]
  'update:isFree': [value: boolean]
  
  'handleSearch': []
}>()

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
    <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
      <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <Icon icon="fluent:filter-24-filled" class="w-5 h-5 text-blue-600" />
        ค้นหาและกรอง
      </h3>
    </div>

    <!-- Content -->
    <div class="p-4 space-y-6">
      <!-- Search Input -->
      <div class="relative">
        <Icon
          icon="fluent:search-24-regular"
          class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"
        />
        <input
          :value="searchQuery"
          @input="onSearchInput"
          type="text"
          placeholder="ค้นหารายวิชา..."
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg pl-10 pr-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
        />
      </div>

      <!-- Basic Filters -->
      <div class="space-y-4">
        <!-- Category -->
        <div>
          <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5">
            หมวดหมู่
          </label>
          <select
            :value="selectedCategory"
            @change="$emit('update:selectedCategory', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option v-for="cat in categories" :key="cat.value" :value="cat.value">
              {{ cat.label }}
            </option>
          </select>
        </div>

        <!-- Level -->
        <div>
          <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5">
            ระดับ
          </label>
          <select
            :value="selectedLevel"
            @change="$emit('update:selectedLevel', ($event.target as HTMLSelectElement).value)"
            class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-sm text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option v-for="level in levels" :key="level.value" :value="level.value">
              {{ level.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Marketplace Section -->
      <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
        <button 
          @click="showMarketplaceFilters = !showMarketplaceFilters"
          class="flex items-center justify-between w-full mb-3 group"
        >
          <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider group-hover:text-blue-500 transition-colors">
            ประเภทการเข้าถึง
          </span>
          <Icon 
            :icon="showMarketplaceFilters ? 'fluent:chevron-up-12-filled' : 'fluent:chevron-down-12-filled'" 
            class="w-3 h-3 text-gray-400"
          />
        </button>
        
        <div v-if="showMarketplaceFilters" class="space-y-3">
          <label class="flex items-center gap-3 cursor-pointer group">
            <div class="relative flex items-center">
              <input 
                type="checkbox" 
                :checked="enrollableOnly"
                @change="$emit('update:enrollableOnly', ($event.target as HTMLInputElement).checked)"
                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" 
              />
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-bold text-gray-700 dark:text-gray-200 group-hover:text-blue-600 transition-colors">เปิดให้สมัครเรียน</span>
              <span class="text-[9px] text-gray-400 uppercase">Enrollable</span>
            </div>
          </label>

          <label class="flex items-center gap-3 cursor-pointer group">
            <div class="relative flex items-center">
              <input 
                type="checkbox" 
                :checked="marketplaceOnly"
                @change="$emit('update:marketplaceOnly', ($event.target as HTMLInputElement).checked)"
                class="w-4 h-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500 dark:bg-gray-700 dark:border-gray-600" 
              />
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-bold text-gray-700 dark:text-gray-200 group-hover:text-violet-600 transition-colors">มี Master Copy</span>
              <span class="text-[9px] text-gray-400 uppercase">Marketplace (Clone)</span>
            </div>
          </label>

          <label class="flex items-center gap-3 cursor-pointer group pt-2 border-t border-dashed border-gray-100 dark:border-gray-700">
            <div class="relative flex items-center">
              <input 
                type="checkbox" 
                :checked="isFree"
                @change="$emit('update:isFree', ($event.target as HTMLInputElement).checked)"
                class="w-4 h-4 rounded border-gray-300 text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600" 
              />
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-bold text-gray-700 dark:text-gray-200 group-hover:text-green-600 transition-colors">เฉพาะรายวิชาฟรี</span>
              <span class="text-[9px] text-gray-400 uppercase">Free Courses</span>
            </div>
          </label>
        </div>
      </div>

      <!-- Academic Info Section -->
      <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
         <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-3">
            ข้อมูลปีการศึกษา
          </label>
          <div class="grid grid-cols-1 gap-3">
             <div>
                <select
                  :value="selectedSemester"
                  @change="$emit('update:selectedSemester', ($event.target as HTMLSelectElement).value)"
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option v-for="sem in semesters" :key="sem.value" :value="sem.value">
                    {{ sem.label }}
                  </option>
                </select>
             </div>
             <div>
                <select
                  :value="selectedYear"
                  @change="$emit('update:selectedYear', ($event.target as HTMLSelectElement).value)"
                  class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option v-for="y in years" :key="y.value" :value="y.value">
                    {{ y.label }}
                  </option>
                </select>
             </div>
          </div>
      </div>

      <!-- Sort -->
      <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
        <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase mb-1.5">
          จัดเรียงตาม
        </label>
        <select
          :value="sortBy"
          @change="$emit('update:sortBy', ($event.target as HTMLSelectElement).value)"
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-3 py-2 text-xs text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold"
        >
          <option v-for="option in sortOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
