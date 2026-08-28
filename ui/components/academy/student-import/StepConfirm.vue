<template>
  <div class="space-y-6">
    <div class="text-center max-w-2xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ยืนยันการนำเข้า</h2>
      <p class="mt-2 text-gray-500 dark:text-gray-400">
        ระบบพร้อมนำเข้านักเรียนจำนวน <span class="font-bold text-primary-600">{{ currentBatch?.valid_rows || 0 }}</span> คน
      </p>
    </div>

    <!-- Confirm Screen -->
    <div v-if="!isProcessing && !isFinished" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-8 border border-gray-200 dark:border-gray-700 max-w-lg mx-auto text-center">
      <Icon icon="fluent:checkmark-circle-24-regular" class="w-16 h-16 text-primary-500 mx-auto mb-4" />
      <h3 class="text-xl font-bold text-gray-900 dark:text-white">ยืนยันนำเข้าข้อมูล</h3>
      <p class="text-gray-500 dark:text-gray-400 mt-2 mb-6">
        เมื่อยืนยันแล้ว ข้อมูลจะถูกบันทึกลงในระบบทันที ไม่สามารถยกเลิกได้
      </p>
      
      <div class="flex justify-center gap-3">
        <button @click="emit('back')" class="btn-ghost" :disabled="isConfirming">
          ย้อนกลับ
        </button>
        <button @click="handleConfirm" class="btn-primary" :disabled="isConfirming">
          <Icon v-if="isConfirming" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin mr-2" />
          ยืนยันและนำเข้าทันที
        </button>
      </div>
    </div>

    <!-- Processing Screen -->
    <div v-else-if="isProcessing" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-12 border border-gray-200 dark:border-gray-700 text-center">
      <div class="max-w-md mx-auto">
        <Icon icon="fluent:settings-24-regular" class="w-12 h-12 text-primary-500 animate-spin mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">กำลังนำเข้าข้อมูล...</h3>
        
        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 mb-2 overflow-hidden">
          <div class="bg-primary-600 h-4 rounded-full transition-all duration-500" :style="{ width: `${progressPercent}%` }"></div>
        </div>
        <div class="flex justify-between text-sm font-medium text-gray-500 dark:text-gray-400">
          <span>{{ currentBatch?.imported_rows || 0 }} รายการ</span>
          <span>{{ currentBatch?.valid_rows || 0 }} รายการ</span>
        </div>
        <p class="text-sm text-gray-400 mt-4">กรุณาอย่าปิดหน้านี้จนกว่าการดำเนินการจะเสร็จสิ้น</p>
      </div>
    </div>

    <!-- Finished Screen -->
    <div v-else-if="isFinished" class="bg-white dark:bg-gray-800 rounded-xl p-4 sm:p-8 border border-gray-200 dark:border-gray-700">
      <div class="text-center mb-8">
        <Icon 
          :icon="currentBatch?.status === 'completed' ? 'fluent:checkmark-circle-24-regular' : (currentBatch?.status === 'partial' ? 'fluent:warning-24-regular' : 'fluent:dismiss-circle-24-regular')" 
          :class="[
            'w-16 h-16 mx-auto mb-4',
            currentBatch?.status === 'completed' ? 'text-green-500' : (currentBatch?.status === 'partial' ? 'text-amber-500' : 'text-red-500')
          ]"
        />
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ currentBatch?.status === 'completed' ? 'นำเข้าสำเร็จเรียบร้อย' : (currentBatch?.status === 'partial' ? 'นำเข้าสำเร็จบางส่วน' : 'นำเข้าล้มเหลว') }}
        </h3>
        <p class="text-gray-500 dark:text-gray-400 mt-2">
          ดำเนินการเสร็จสิ้นเมื่อ {{ new Date(currentBatch?.completed_at || '').toLocaleString('th-TH') }}
        </p>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto mb-8 text-center">
        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">ทั้งหมด</div>
          <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currentBatch?.total_rows }}</div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/10 p-4 rounded-lg border border-green-100 dark:border-green-800/30">
          <div class="text-sm text-green-600 dark:text-green-400 mb-1">นำเข้าสำเร็จ</div>
          <div class="text-xl font-bold text-green-700 dark:text-green-300">{{ currentBatch?.imported_rows }}</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
          <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">ข้าม</div>
          <div class="text-xl font-bold text-gray-900 dark:text-white">{{ currentBatch?.skipped_rows }}</div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/10 p-4 rounded-lg border border-red-100 dark:border-red-800/30">
          <div class="text-sm text-red-600 dark:text-red-400 mb-1">ผิดพลาด</div>
          <div class="text-xl font-bold text-red-700 dark:text-red-300">{{ (currentBatch?.invalid_rows || 0) + ((currentBatch?.valid_rows || 0) - (currentBatch?.imported_rows || 0)) }}</div>
        </div>
      </div>

      <div class="flex justify-center gap-4">
        <button 
          v-if="currentBatch?.status !== 'completed'"
          @click="handleDownloadErrorCsv"
          class="btn-secondary flex items-center gap-2"
        >
          <Icon icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
          ดาวน์โหลดรายงานข้อผิดพลาด
        </button>
        <button @click="emit('done')" class="btn-primary flex items-center gap-2">
          กลับสู่หน้าทะเบียนนักเรียน
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useStudentImport } from '../../../composables/useStudentImport'
import { useStudentImportService } from '../../../services/studentImportService'

const emit = defineEmits(['back', 'done'])
const route = useRoute()
const academyName = route.params.name as string

const { currentBatch, isConfirming, confirmImport } = useStudentImport(academyName)
const { downloadErrorCsv } = useStudentImportService()

const isProcessing = computed(() => currentBatch.value?.status === 'processing')
const isFinished = computed(() => {
  return ['completed', 'partial', 'failed'].includes(currentBatch.value?.status || '')
})

const progressPercent = computed(() => {
  if (!currentBatch.value || !currentBatch.value.valid_rows) return 0
  return Math.min(100, Math.round((currentBatch.value.imported_rows / currentBatch.value.valid_rows) * 100))
})

const handleConfirm = async () => {
  try {
    await confirmImport()
  } catch (e) {
    alert('เกิดข้อผิดพลาดในการยืนยันนำเข้า')
  }
}

const handleDownloadErrorCsv = async () => {
  if (!currentBatch.value) return
  try {
    await downloadErrorCsv(academyName, currentBatch.value.id)
  } catch (e) {
    alert('ไม่สามารถดาวน์โหลดไฟล์รายงานได้')
  }
}
</script>
