<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, ref, watch } from 'vue'

import type { AvailableFilters, CourseFilters } from '~/types/allocation'

interface Props {
  modelValue: CourseFilters
  availableFilters: AvailableFilters | null
  /** จำนวนคอร์สที่ผ่านตัวกรองปัจจุบัน */
  resultCount?: number
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  resultCount: 0,
  loading: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: CourseFilters]
  change: []
}>()

const isPanelOpen = ref(false)
const searchInput = ref(props.modelValue.search)
let searchTimer: any = null

// ปรับค่าในช่องค้นหาให้ตรงกับ state ภายนอก (เช่น ตอนกดล้างตัวกรอง)
watch(() => props.modelValue.search, (v) => {
  if (v !== searchInput.value) searchInput.value = v
})

const patch = (partial: Partial<CourseFilters>) => {
  emit('update:modelValue', { ...props.modelValue, ...partial })
  emit('change')
}

const onSearchInput = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => patch({ search: searchInput.value }), 350)
}

const clearSearch = () => {
  searchInput.value = ''
  if (searchTimer) clearTimeout(searchTimer)
  patch({ search: '' })
}

const selectGroups = computed(() => [
  { key: 'education_level' as const, label: 'ระดับชั้น', placeholder: 'ทุกระดับชั้น', options: props.availableFilters?.education_levels || [] },
  { key: 'education_year' as const, label: 'ชั้นปี', placeholder: 'ทุกชั้นปี', options: props.availableFilters?.education_years || [] },
  { key: 'semester' as const, label: 'ภาคเรียน', placeholder: 'ทุกภาคเรียน', options: props.availableFilters?.semesters || [] },
  { key: 'academic_year' as const, label: 'ปีการศึกษา', placeholder: 'ทุกปีการศึกษา', options: props.availableFilters?.academic_years || [] },
].filter(g => g.options.length > 0))

/** ค่าเริ่มต้นคือเทอมปัจจุบัน — ใช้ตัดสินว่าตัวกรองถูกปรับจากค่าเริ่มต้นหรือยัง */
const defaults = computed(() => ({
  academic_year: props.availableFilters?.current_term?.academic_year || '',
  semester: props.availableFilters?.current_term?.semester || '',
}))

const activeChips = computed(() => {
  const chips: Array<{ key: keyof CourseFilters; label: string; text: string }> = []
  for (const group of selectGroups.value) {
    const value = props.modelValue[group.key]
    if (!value) continue
    const option = group.options.find(o => o.value === value)
    chips.push({ key: group.key, label: group.label, text: option?.label || value })
  }
  if (props.modelValue.search.trim()) {
    chips.push({ key: 'search', label: 'ค้นหา', text: props.modelValue.search.trim() })
  }
  return chips
})

const isDirty = computed(() =>
  Boolean(
    props.modelValue.education_level
    || props.modelValue.education_year
    || props.modelValue.search.trim()
    || props.modelValue.academic_year !== defaults.value.academic_year
    || props.modelValue.semester !== defaults.value.semester,
  ),
)

const removeChip = (key: keyof CourseFilters) => {
  if (key === 'search') return clearSearch()
  patch({ [key]: '' } as Partial<CourseFilters>)
}

const resetToDefaults = () => {
  searchInput.value = ''
  if (searchTimer) clearTimeout(searchTimer)
  emit('update:modelValue', {
    education_level: '',
    education_year: '',
    semester: defaults.value.semester,
    academic_year: defaults.value.academic_year,
    search: '',
  })
  emit('change')
}

/** ล้างทุกอย่างรวมถึงเทอมปัจจุบัน เพื่อดูคอร์สทั้งหมดของโรงเรียน */
const showAllTerms = () => {
  searchInput.value = ''
  if (searchTimer) clearTimeout(searchTimer)
  emit('update:modelValue', {
    education_level: '',
    education_year: '',
    semester: '',
    academic_year: '',
    search: '',
  })
  emit('change')
}
</script>

<template>
  <div class="mb-3 space-y-2.5">
    <!-- แถวบน: ค้นหา + ปุ่มเปิดตัวกรอง -->
    <div class="flex gap-2">
      <div class="relative flex-1">
        <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
        <input
          v-model="searchInput"
          type="search"
          placeholder="ค้นหาชื่อคอร์ส..."
          class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-9 text-sm outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-indigo-950"
          @input="onSearchInput"
        />
        <button
          v-if="searchInput"
          type="button"
          class="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800"
          aria-label="ล้างคำค้นหา"
          @click="clearSearch"
        >
          <Icon icon="fluent:dismiss-24-regular" class="h-4 w-4" />
        </button>
      </div>

      <button
        v-if="selectGroups.length"
        type="button"
        class="min-h-[44px] sm:min-h-0 relative inline-flex items-center gap-1.5 rounded-xl border px-3.5 py-2.5 text-sm font-semibold transition"
        :class="isPanelOpen || activeChips.length
          ? 'border-indigo-500 bg-indigo-50 text-indigo-600 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300'
          : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
        @click="isPanelOpen = !isPanelOpen"
      >
        <Icon icon="fluent:filter-24-regular" class="h-5 w-5" />
        <span class="hidden sm:inline">ตัวกรอง</span>
        <span
          v-if="activeChips.length"
          class="ml-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-500 px-1.5 text-[11px] font-bold text-white"
        >
          {{ activeChips.length }}
        </span>
        <Icon :icon="isPanelOpen ? 'fluent:chevron-up-24-regular' : 'fluent:chevron-down-24-regular'" class="h-4 w-4" />
      </button>
    </div>

    <!-- แผงตัวกรอง -->
    <div
      v-if="isPanelOpen && selectGroups.length"
      class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 dark:border-gray-700 dark:bg-gray-800/40"
    >
      <div class="grid gap-2.5 sm:grid-cols-2">
        <label v-for="group in selectGroups" :key="group.key" class="block">
          <span class="mb-1 block text-xs font-semibold text-gray-500 dark:text-gray-400">{{ group.label }}</span>
          <select
            :value="modelValue[group.key]"
            class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            @change="patch({ [group.key]: ($event.target as HTMLSelectElement).value } as any)"
          >
            <option value="">{{ group.placeholder }}</option>
            <option v-for="opt in group.options" :key="opt.value" :value="opt.value">
              {{ opt.label }} ({{ opt.count }})
            </option>
          </select>
        </label>
      </div>

      <div class="mt-3 flex flex-wrap gap-2 border-t border-gray-200 pt-3 dark:border-gray-700">
        <button
          type="button"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
          @click="showAllTerms"
        >
          <Icon icon="fluent:apps-list-24-regular" class="h-4 w-4" />
          ดูคอร์สทุกปีการศึกษา
        </button>
        <button
          v-if="isDirty"
          type="button"
          class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:border-rose-300 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
          @click="resetToDefaults"
        >
          <Icon icon="fluent:arrow-reset-24-regular" class="h-4 w-4" />
          กลับเป็นเทอมปัจจุบัน
        </button>
      </div>
    </div>

    <!-- ชิปตัวกรองที่ใช้อยู่ + จำนวนผลลัพธ์ -->
    <div v-if="activeChips.length || resultCount >= 0" class="flex flex-wrap items-center gap-2">
      <span
        v-for="chip in activeChips"
        :key="chip.key"
        class="inline-flex items-center gap-1 rounded-full bg-indigo-100 py-1 pl-2.5 pr-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300"
      >
        <span class="opacity-60">{{ chip.label }}:</span>
        <span class="max-w-[10rem] truncate">{{ chip.text }}</span>
        <button
          type="button"
          class="rounded-full p-0.5 transition hover:bg-indigo-200 dark:hover:bg-indigo-900"
          :aria-label="`ลบตัวกรอง ${chip.label}`"
          @click="removeChip(chip.key)"
        >
          <Icon icon="fluent:dismiss-24-regular" class="h-3 w-3" />
        </button>
      </span>

      <span class="ml-auto inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
        <Icon v-if="loading" icon="svg-spinners:ring-resize" class="h-3.5 w-3.5" />
        <template v-else>พบ {{ resultCount.toLocaleString('th-TH') }} คอร์ส</template>
      </span>
    </div>
  </div>
</template>
