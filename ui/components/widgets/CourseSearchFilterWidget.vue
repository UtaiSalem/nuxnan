<script setup lang="ts">
import { Icon } from '@iconify/vue'

// Props
interface Props {
  searchQuery: string
  selectedCategory: string
  selectedLevel: string
  selectedSemester: string
  selectedYear: string
  sortBy: string
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
  'handleSearch': []
}>()

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

const onCategoryChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:selectedCategory', target.value)
}

const onLevelChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:selectedLevel', target.value)
}

const onSemesterChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:selectedSemester', target.value)
}

const onYearChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:selectedYear', target.value)
}

const onSortChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:sortBy', target.value)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
      <h3 class="font-bold text-gray-800 dark:text-white">ค้นหาและกรอง</h3>
    </div>

    <!-- Content -->
    <div class="p-4 space-y-4">
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
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg pl-10 pr-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>

      <!-- Category -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          หมวดหมู่
        </label>
        <select
          :value="selectedCategory"
          @change="onCategoryChange"
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option v-for="cat in categories" :key="cat.value" :value="cat.value">
            {{ cat.label }}
          </option>
        </select>
      </div>

      <!-- Level -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          ระดับ
        </label>
        <select
          :value="selectedLevel"
          @change="onLevelChange"
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option v-for="level in levels" :key="level.value" :value="level.value">
            {{ level.label }}
          </option>
        </select>
      </div>

      <!-- Semester -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          ภาคเรียน
        </label>
        <select
          :value="selectedSemester"
          @change="onSemesterChange"
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option v-for="sem in semesters" :key="sem.value" :value="sem.value">
            {{ sem.label }}
          </option>
        </select>
      </div>

      <!-- Year -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          ปีการศึกษา
        </label>
        <select
          :value="selectedYear"
          @change="onYearChange"
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option v-for="y in years" :key="y.value" :value="y.value">
            {{ y.label }}
          </option>
        </select>
      </div>

      <!-- Sort -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          จัดเรียง
        </label>
        <select
          :value="sortBy"
          @change="onSortChange"
          class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option v-for="option in sortOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
