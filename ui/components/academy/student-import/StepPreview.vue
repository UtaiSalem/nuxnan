<template>
  <div class="space-y-6">
    <div class="text-center max-w-2xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ตรวจสอบข้อมูล (Preview)</h2>
      <p class="mt-2 text-gray-500 dark:text-gray-400">ระบบกำลังตรวจสอบความถูกต้องของข้อมูล กรุณารอสักครู่</p>
    </div>

    <div v-if="isValidating" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-12 border border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-12 h-12 text-primary-500 animate-spin mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">กำลังประมวลผลไฟล์...</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">อาจใช้เวลาสักครู่ขึ้นอยู่กับจำนวนข้อมูล</p>
    </div>

    <div v-else-if="currentBatch" class="space-y-6">
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center">
            <Icon icon="fluent:document-table-24-regular" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">จำนวนแถวทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ currentBatch.total_rows }}</p>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center">
            <Icon icon="fluent:checkmark-circle-24-regular" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ข้อมูลที่ถูกต้อง</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ currentBatch.valid_rows }}</p>
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center gap-4">
          <div class="w-12 h-12 rounded-full bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 flex items-center justify-center">
            <Icon icon="fluent:dismiss-circle-24-regular" class="w-6 h-6" />
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">พบข้อผิดพลาด</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ currentBatch.invalid_rows }}</p>
          </div>
        </div>
      </div>

      <div v-if="currentBatch.invalid_rows > 0" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded-xl p-4 flex gap-3 items-start">
        <Icon icon="fluent:error-circle-24-regular" class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 shrink-0" />
        <div>
          <h4 class="text-sm font-medium text-red-800 dark:text-red-300">พบข้อผิดพลาดในข้อมูล</h4>
          <p class="text-sm text-red-700 dark:text-red-400 mt-1">
            มีข้อมูล {{ currentBatch.invalid_rows }} แถวที่ไม่ผ่านการตรวจสอบ ระบบจะไม่นำเข้าข้อมูลเหล่านี้ คุณสามารถแก้ไขไฟล์และอัปโหลดใหม่ หรือกดยืนยันเพื่อนำเข้าเฉพาะแถวที่ถูกต้อง
          </p>
        </div>
      </div>

      <ImportRowTable :batchId="currentBatch.id" />

      <div class="flex justify-between items-center mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <button @click="handleCancel" class="btn-ghost flex items-center gap-2">
          <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
          ยกเลิก
        </button>
        <div class="flex gap-3">
          <button @click="emit('back')" class="btn-secondary flex items-center gap-2">
            <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
            อัปโหลดไฟล์ใหม่
          </button>
          <button 
            @click="emit('next')" 
            :disabled="currentBatch.valid_rows === 0"
            class="btn-primary flex items-center gap-2"
          >
            ไปหน้ายืนยัน
            <Icon icon="fluent:arrow-right-24-regular" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useStudentImport } from '../../../composables/useStudentImport'
import ImportRowTable from './ImportRowTable.vue'
import { useStudentImportService } from '../../../services/studentImportService'

const emit = defineEmits(['next', 'back'])
const route = useRoute()
const academyName = route.params.name as string
const { currentBatch, resetState } = useStudentImport(academyName)
const { cancelBatch } = useStudentImportService()

const isValidating = computed(() => {
  return !currentBatch.value || currentBatch.value.status === 'uploaded' || currentBatch.value.status === 'validating'
})

const handleCancel = async () => {
  if (confirm('คุณต้องการยกเลิกการนำเข้านี้ใช่หรือไม่?')) {
    if (currentBatch.value) {
      await cancelBatch(academyName, currentBatch.value.id)
    }
    resetState()
    emit('back')
  }
}
</script>
