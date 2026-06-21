<script setup lang="ts">
import { computed } from 'vue'
import type { ClassroomOptionDTO, RolloverPreviewDTO } from '~/types/enrollment'

const props = defineProps<{
  preview: RolloverPreviewDTO | null
  targetClassrooms: ClassroomOptionDTO[]
  targetYearName: string
  isCreating: boolean
}>()

const emit = defineEmits<{
  createGrade: [gradeLevel: string]
  createAll: []
  back: []
  next: []
}>()

const rows = computed(() => {
  const missing = props.preview?.missing_targets ?? []
  return missing.map((gradeLevel) => {
    const classrooms = props.targetClassrooms.filter((classroom) => classroom.grade_level === gradeLevel)
    return {
      gradeLevel,
      classrooms,
    }
  })
})

const hasMissingTargets = computed(() => rows.value.length > 0)
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">ตรวจห้องเรียนปี {{ targetYearName }}</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          ระบบจะเตรียมห้องปลายทางให้พอกับระดับชั้นที่ต้องใช้ก่อนเข้าสู่การจัดนักเรียน
        </p>
      </div>
      <button
        v-if="hasMissingTargets"
        type="button"
        :disabled="isCreating"
        class="rounded-xl border border-primary-200 bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 transition-colors hover:bg-primary-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-primary-800 dark:bg-primary-900/20 dark:text-primary-300"
        @click="emit('createAll')"
      >
        {{ isCreating ? 'กำลังสร้างห้อง...' : 'สร้างห้องที่ขาดทั้งหมด' }}
      </button>
    </div>

    <div v-if="preview" class="mb-5 grid gap-3 md:grid-cols-4">
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Promote</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ preview.totals.promote }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Graduate</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ preview.totals.graduate }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">New intake</div>
        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ preview.totals.new_intake }}</div>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">Missing targets</div>
        <div class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ preview.missing_targets.length }}</div>
      </div>
    </div>

    <div v-if="!hasMissingTargets" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800 dark:bg-emerald-900/20">
      <div class="text-lg font-semibold text-emerald-800 dark:text-emerald-300">ห้องปลายทางพร้อมแล้ว</div>
      <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">
        ไม่พบระดับชั้นที่ขาดในปี {{ targetYearName }} สามารถไปจัดกลุ่มนักเรียนต่อได้เลย
      </p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="row in rows"
        :key="row.gradeLevel"
        class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700"
      >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <div class="text-base font-semibold text-gray-900 dark:text-white">{{ row.gradeLevel }}</div>
            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
              ยังไม่มีห้องปลายทางสำหรับระดับชั้นนี้ในปี {{ targetYearName }}
            </div>
            <div v-if="row.classrooms.length" class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="classroom in row.classrooms"
                :key="classroom.id"
                class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
              >
                {{ classroom.display_name }}
              </span>
            </div>
          </div>
          <button
            type="button"
            :disabled="isCreating"
            class="rounded-xl bg-primary-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
            @click="emit('createGrade', row.gradeLevel)"
          >
            {{ isCreating ? 'กำลังสร้าง...' : `สร้าง ${row.gradeLevel}/1` }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="preview?.warnings?.length" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
      <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">Warnings จาก preview</div>
      <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-400">
        <li v-for="warning in preview.warnings.slice(0, 6)" :key="warning">• {{ warning }}</li>
      </ul>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
      <button
        type="button"
        class="rounded-xl border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="emit('back')"
      >
        ย้อนกลับ
      </button>
      <button
        type="button"
        :disabled="hasMissingTargets"
        class="rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
        @click="emit('next')"
      >
        ถัดไป: จัดกลุ่มนักเรียน
      </button>
    </div>
  </div>
</template>
