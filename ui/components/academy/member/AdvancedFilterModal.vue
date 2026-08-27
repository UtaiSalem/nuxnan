<script setup lang="ts">
/**
 * Advanced Filter Modal Component
 * ตัวกรองขั้นสูงสำหรับสมาชิก
 */
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'

interface AcademyRole {
  id: number
  name: string
  display_name_th: string
  color: string
}

interface FilterState {
  status: number | null
  roleId: number | null
  dateFrom: string | null
  dateTo: string | null
  sortBy: string
  sortOrder: 'asc' | 'desc'
}

interface Props {
  modelValue: boolean
  roles: AcademyRole[]
  currentFilters?: Partial<FilterState>
}

const props = withDefaults(defineProps<Props>(), {
  currentFilters: () => ({})
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'apply': [filters: FilterState]
  'reset': []
}>()

const isVisible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Filter state
const filters = ref<FilterState>({
  status: null,
  roleId: null,
  dateFrom: null,
  dateTo: null,
  sortBy: 'created_at',
  sortOrder: 'desc',
})

// Initialize with current filters
watch(() => props.modelValue, (open) => {
  if (open && props.currentFilters) {
    filters.value = {
      status: props.currentFilters.status ?? null,
      roleId: props.currentFilters.roleId ?? null,
      dateFrom: props.currentFilters.dateFrom ?? null,
      dateTo: props.currentFilters.dateTo ?? null,
      sortBy: props.currentFilters.sortBy ?? 'created_at',
      sortOrder: props.currentFilters.sortOrder ?? 'desc',
    }
  }
})

const statusOptions = [
  { value: null, label: 'ทุกสถานะ', color: 'gray' },
  { value: 2, label: 'สมาชิก', color: 'green' },
  { value: 1, label: 'รอการอนุมัติ', color: 'yellow' },
  { value: 4, label: 'ได้รับเชิญ', color: 'blue' },
  { value: 5, label: 'ถูกระงับ', color: 'orange' },
  { value: 3, label: 'ถูกปฏิเสธ', color: 'red' },
]

const sortOptions = [
  { value: 'created_at', label: 'วันที่สมัคร' },
  { value: 'member_name', label: 'ชื่อ' },
  { value: 'member_code', label: 'รหัสสมาชิก' },
  { value: 'last_activity_at', label: 'เข้าใช้งานล่าสุด' },
]

const applyFilters = () => {
  emit('apply', { ...filters.value })
  isVisible.value = false
}

const resetFilters = () => {
  filters.value = {
    status: null,
    roleId: null,
    dateFrom: null,
    dateTo: null,
    sortBy: 'created_at',
    sortOrder: 'desc',
  }
  emit('reset')
}

const closeModal = () => {
  isVisible.value = false
}

const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.value.status !== null) count++
  if (filters.value.roleId !== null) count++
  if (filters.value.dateFrom) count++
  if (filters.value.dateTo) count++
  return count
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isVisible"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeModal"
      >
        <Transition
          enter-active-class="transition-all duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div 
            v-if="isVisible"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2">
                <Icon icon="fluent:filter-24-regular" class="w-5 h-5 text-gray-500" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตัวกรองขั้นสูง</h3>
                <span 
                  v-if="activeFiltersCount > 0"
                  class="px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-400 rounded-full"
                >
                  {{ activeFiltersCount }} ตัวกรอง
                </span>
              </div>
              <button 
                @click="closeModal"
                class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto">
              <!-- Status Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  สถานะสมาชิก
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                  <button class="min-h-[44px] sm:min-h-0"
                    v-for="opt in statusOptions"
                    :key="opt.value ?? 'all'"
                    @click="filters.status = opt.value"
                    :class="[
                      'px-3 py-2 rounded-lg text-sm font-medium border-2 transition-all',
                      filters.status === opt.value
                        ? `border-${opt.color}-500 bg-${opt.color}-50 text-${opt.color}-700 dark:bg-${opt.color}-900/30 dark:text-${opt.color}-400`
                        : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'
                    ]"
                  >
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <!-- Role Filter -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  บทบาท
                </label>
                <select
                  v-model="filters.roleId"
                  class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                >
                  <option :value="null">ทุกบทบาท</option>
                  <option v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.display_name_th }}
                  </option>
                </select>
              </div>

              <!-- Date Range -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  วันที่สมัครสมาชิก
                </label>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">ตั้งแต่</label>
                    <input
                      v-model="filters.dateFrom"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">ถึง</label>
                    <input
                      v-model="filters.dateTo"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    />
                  </div>
                </div>
              </div>

              <!-- Sort Options -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  เรียงตาม
                </label>
                <div class="flex gap-3">
                  <select
                    v-model="filters.sortBy"
                    class="min-w-0 flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                  >
                    <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                  <button
                    @click="filters.sortOrder = filters.sortOrder === 'asc' ? 'desc' : 'asc'"
                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    :title="filters.sortOrder === 'asc' ? 'น้อยไปมาก' : 'มากไปน้อย'"
                  >
                    <Icon 
                      :icon="filters.sortOrder === 'asc' ? 'fluent:arrow-up-24-regular' : 'fluent:arrow-down-24-regular'" 
                      class="w-5 h-5 text-gray-600 dark:text-gray-400" 
                    />
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
              <button
                @click="resetFilters"
                class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
              >
                รีเซ็ตทั้งหมด
              </button>
              <div class="flex gap-3">
                <button
                  @click="closeModal"
                  class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                  ยกเลิก
                </button>
                <button
                  @click="applyFilters"
                  class="min-h-[44px] sm:min-h-0 flex items-center gap-2 px-5 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                >
                  <Icon icon="fluent:checkmark-24-filled" class="w-4 h-4" />
                  ใช้ตัวกรอง
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
