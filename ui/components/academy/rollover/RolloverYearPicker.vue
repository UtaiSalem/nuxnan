<script setup lang="ts">
import { computed } from 'vue'
import type { AcademicYearDTO } from '~/types/enrollment'

interface YearForm {
  name: string
  start_date: string
  end_date: string
  is_current: boolean
  create_semesters: boolean
}

const props = defineProps<{
  academicYears: AcademicYearDTO[]
  fromYearId: number | null
  toYearId: number | null
  toMode: 'existing' | 'create'
  newYearForm: YearForm
  isCreatingYear: boolean
}>()

const emit = defineEmits<{
  'update:fromYearId': [value: number | null]
  'update:toYearId': [value: number | null]
  'update:toMode': [value: 'existing' | 'create']
  'update:newYearForm': [value: YearForm]
  createYear: []
  next: []
}>()

const currentYearId = computed(() => props.academicYears.find((year) => year.is_current)?.id ?? null)

const availableTargetYears = computed(() => {
  return props.academicYears.filter((year) => year.id !== props.fromYearId)
})

const canProceed = computed(() => {
  if (!props.fromYearId) return false
  if (props.toMode === 'existing') {
    return !!props.toYearId
  }

  return !!props.toYearId
})

function updateForm<K extends keyof YearForm>(key: K, value: YearForm[K]) {
  emit('update:newYearForm', {
    ...props.newYearForm,
    [key]: value,
  })
}
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">เลือกปีต้นทางและปีปลายทาง</h2>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        เลือกปีปัจจุบันที่ต้องการปิดรอบ และปีใหม่ที่จะรับนักเรียน/ห้องเรียนปลายทาง
      </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-sm font-semibold text-gray-900 dark:text-white">ปีต้นทาง</div>
        <select
          :value="fromYearId ?? ''"
          class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          @change="emit('update:fromYearId', Number(($event.target as HTMLSelectElement).value) || null)"
        >
          <option value="">เลือกปีต้นทาง</option>
          <option v-for="year in academicYears" :key="year.id" :value="year.id">
            {{ year.name }} {{ year.is_current ? '(ปัจจุบัน)' : '' }}
          </option>
        </select>
        <p v-if="currentYearId && fromYearId !== currentYearId" class="text-xs text-amber-600 dark:text-amber-400">
          ปีที่เลือกไม่ใช่ปีปัจจุบัน ตรวจสอบอีกครั้งก่อนทำ rollover
        </p>
      </div>

      <div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-sm font-semibold text-gray-900 dark:text-white">ปีปลายทาง</div>
        <div class="flex flex-wrap gap-3">
          <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input
              :checked="toMode === 'existing'"
              type="radio"
              name="target-year-mode"
              class="h-4 w-4 text-primary-500"
              @change="emit('update:toMode', 'existing')"
            />
            ใช้ปีที่มีอยู่แล้ว
          </label>
          <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input
              :checked="toMode === 'create'"
              type="radio"
              name="target-year-mode"
              class="h-4 w-4 text-primary-500"
              @change="emit('update:toMode', 'create')"
            />
            สร้างปีใหม่
          </label>
        </div>

        <div v-if="toMode === 'existing'" class="space-y-2">
          <select
            :value="toYearId ?? ''"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            @change="emit('update:toYearId', Number(($event.target as HTMLSelectElement).value) || null)"
          >
            <option value="">เลือกปีปลายทาง</option>
            <option v-for="year in availableTargetYears" :key="year.id" :value="year.id">
              {{ year.name }}
            </option>
          </select>
          <p class="text-xs text-gray-500 dark:text-gray-400">ใช้ปีที่สร้างไว้แล้วและข้ามขั้นตอนสร้างปีใหม่</p>
        </div>

        <div v-else class="space-y-3">
          <input
            :value="newYearForm.name"
            type="text"
            placeholder="เช่น 2569"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            @input="updateForm('name', ($event.target as HTMLInputElement).value)"
          />
          <div class="grid gap-3 sm:grid-cols-2">
            <input
              :value="newYearForm.start_date"
              type="date"
              class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
              @input="updateForm('start_date', ($event.target as HTMLInputElement).value)"
            />
            <input
              :value="newYearForm.end_date"
              type="date"
              class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
              @input="updateForm('end_date', ($event.target as HTMLInputElement).value)"
            />
          </div>
          <div class="flex flex-wrap gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input
                :checked="newYearForm.is_current"
                type="checkbox"
                class="h-4 w-4 rounded text-primary-500"
                @change="updateForm('is_current', ($event.target as HTMLInputElement).checked)"
              />
              ตั้งเป็นปีปัจจุบัน
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
              <input
                :checked="newYearForm.create_semesters"
                type="checkbox"
                class="h-4 w-4 rounded text-primary-500"
                @change="updateForm('create_semesters', ($event.target as HTMLInputElement).checked)"
              />
              สร้างภาคเรียนอัตโนมัติ
            </label>
          </div>
          <button
            type="button"
            :disabled="isCreatingYear || !newYearForm.name || !newYearForm.start_date || !newYearForm.end_date"
            class="inline-flex items-center gap-2 rounded-xl bg-primary-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
            @click="emit('createYear')"
          >
            {{ isCreatingYear ? 'กำลังสร้างปีใหม่...' : 'สร้างปีใหม่แล้วใช้เป็นปลายทาง' }}
          </button>
        </div>
      </div>
    </div>

    <div class="mt-6 flex justify-end">
      <button
        type="button"
        :disabled="!canProceed"
        class="rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
        @click="emit('next')"
      >
        ถัดไป: ตรวจห้องปลายทาง
      </button>
    </div>
  </div>
</template>
