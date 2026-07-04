<template>
  <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex justify-between items-center">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400">
          <Icon icon="fluent:cloud-arrow-up-24-regular" class="w-5 h-5" />
        </div>
        <div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">นำเข้าข้อมูลนักเรียน (Bulk Import)</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">อัปโหลดไฟล์ CSV เพื่อเพิ่มข้อมูลนักเรียนหลายคนพร้อมกัน</p>
        </div>
      </div>
    </div>

    <!-- Stepper Content -->
    <div class="p-6">
      <Stepper v-model:value="activeStep" :linear="true">
        <StepList>
          <Step value="1">1. อัปโหลดไฟล์</Step>
          <Step value="2">2. ตรวจสอบข้อมูล</Step>
          <Step value="3">3. ยืนยันการนำเข้า</Step>
        </StepList>
        <StepPanels>
          <StepPanel value="1">
            <StepUpload @next="handleNext('2')" />
          </StepPanel>
          <StepPanel value="2">
            <StepPreview @back="handleBack('1')" @next="handleNext('3')" />
          </StepPanel>
          <StepPanel value="3">
            <StepConfirm @back="handleBack('2')" @done="handleDone" />
          </StepPanel>
        </StepPanels>
      </Stepper>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useStudentImport } from '../../../composables/useStudentImport'
import StepUpload from './StepUpload.vue'
import StepPreview from './StepPreview.vue'
import StepConfirm from './StepConfirm.vue'

const activeStep = ref('1')
const router = useRouter()
const route = useRoute()
const academyName = route.params.name as string
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const { resetState, currentBatch } = useStudentImport(String(academyId.value || ''))

const handleNext = (nextStep: string) => {
  activeStep.value = nextStep
}

const handleBack = (prevStep: string) => {
  // Disable back if we are already processing or finished
  if (currentBatch.value?.status && ['processing', 'completed', 'partial', 'failed'].includes(currentBatch.value.status)) {
    return
  }
  activeStep.value = prevStep
}

const handleDone = () => {
  resetState()
  router.push(`/academies/${academyName}/admin/students`)
}

// Ensure state is reset when leaving the wizard
onBeforeUnmount(() => {
  resetState()
})
</script>
