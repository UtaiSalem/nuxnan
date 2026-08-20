<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-5">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div class="min-w-0 flex-1">
        <h3 class="text-base sm:text-lg font-bold">หัวข้อคะแนนของรายวิชา</h3>
        <p class="text-xs sm:text-sm text-gray-500">ใช้กับนักเรียนทุกกลุ่มเรียนในรายวิชานี้</p>
      </div>
      <div v-if="isCourseAdmin" class="flex-shrink-0">
        <button
          @click="emit('create')"
          class="w-full sm:w-auto min-h-[44px] inline-flex items-center justify-center gap-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium"
        >
          + สร้างหัวข้อคะแนน
        </button>
      </div>
    </div>

    <div v-if="columns.length === 0" class="flex flex-col items-center justify-center py-8 text-center mt-3">
      <Icon icon="mdi:clipboard-text-off-outline" class="w-12 h-12 text-gray-400 mb-2" />
      <p class="text-gray-600 dark:text-gray-400 font-medium">ยังไม่มีหัวข้อคะแนน</p>
      <p class="text-xs text-gray-500 mt-1 mb-4">สร้างหัวข้อเพื่อเริ่มบันทึกคะแนนจากการสอบหรือกิจกรรมนอกระบบ</p>
      <button
        v-if="isCourseAdmin"
        @click="emit('create')"
        class="w-full sm:w-auto min-h-[44px] inline-flex items-center justify-center gap-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium"
      >
        + สร้างหัวข้อคะแนน
      </button>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3 mt-3 sm:mt-4">
      <div
        v-for="col in columns"
        :key="col.id"
        class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 flex flex-col"
      >
        <div class="flex items-start gap-2">
          <Icon icon="mdi:folder-outline" class="flex-shrink-0 w-5 h-5 text-gray-400" />
          <div class="min-w-0 flex-1 break-words font-semibold text-sm">{{ col.title }}</div>
        </div>
        <div class="flex flex-wrap gap-1.5 mt-1.5 text-xs text-gray-500">
          <span class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">เต็ม {{ col.max_score }}</span>
          <span class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">บันทึกแล้ว {{ col.entries_count || 0 }}/{{ memberTotal }} คน</span>
          <span v-if="col.scored_at" class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ col.scored_at }}</span>
        </div>
        <div v-if="isCourseAdmin" class="flex flex-col gap-1.5 sm:flex-row mt-auto pt-2">
          <div class="flex flex-col gap-1.5 sm:flex-row flex-1">
            <button
              @click="emit('download', col)"
              class="flex-1 flex items-center justify-center gap-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px] sm:min-h-0 sm:py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300"
            >
              <Icon icon="mdi:file-download-outline" class="w-4 h-4" />
              ดาวน์โหลดรายชื่อ
            </button>
            <button
              @click="emit('upload', col)"
              class="flex-1 flex items-center justify-center gap-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px] sm:min-h-0 sm:py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300"
            >
              <Icon icon="mdi:file-upload-outline" class="w-4 h-4" />
              อัปโหลดคะแนน
            </button>
            <button
              @click="emit('edit', col)"
              class="flex-1 flex items-center justify-center gap-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px] sm:min-h-0 sm:py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300"
            >
              <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
              แก้ไข
            </button>
          </div>
        </div>
        <div v-if="isCourseAdmin" class="text-[11px] text-gray-500 mt-1.5 text-center sm:text-left">
          สำหรับ {{ groupLabel }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'

export interface ScoreColumn {
  id: number
  title: string
  description: string | null
  category: string
  max_score: number
  scored_at: string | null
  entries_count: number
}

const props = defineProps<{
  columns: ScoreColumn[]
  isCourseAdmin: boolean
  memberTotal: number
  groupLabel: string
}>()

const emit = defineEmits<{
  (e: 'create'): void
  (e: 'edit', col: ScoreColumn): void
  (e: 'download', col: ScoreColumn): void
  (e: 'upload', col: ScoreColumn): void
}>()
</script>
