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
      <!-- Stepper header -->
      <nav class="mb-8">
        <ol class="flex items-center">
          <li
            v-for="(step, i) in steps"
            :key="step.value"
            class="flex items-center"
            :class="i < steps.length - 1 ? 'flex-1' : ''"
          >
            <div class="flex items-center gap-2 shrink-0">
              <div
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold transition-colors"
                :class="stepCircleClass(step.value)"
              >
                <Icon v-if="isCompleted(step.value)" icon="fluent:checkmark-24-filled" class="w-4 h-4" />
                <span v-else>{{ i + 1 }}</span>
              </div>
              <span
                class="hidden sm:block text-sm font-medium"
                :class="activeStep === step.value ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400'"
              >
                {{ step.label }}
              </span>
            </div>
            <div
              v-if="i < steps.length - 1"
              class="mx-2 h-0.5 flex-1 rounded transition-colors"
              :class="isCompleted(step.value) ? 'bg-primary-500' : 'bg-gray-200 dark:bg-gray-700'"
            />
          </li>
        </ol>
      </nav>

      <!-- Panels (v-show keeps step state; linear flow enforced via next/back only) -->
      <div v-show="activeStep === '1'">
        <StepUpload @next="handleNext('2')" />
      </div>
      <div v-show="activeStep === '2'">
        <StepPreview @back="handleBack('1')" @next="handleNext('3')" />
      </div>
      <div v-show="activeStep === '3'">
        <StepConfirm @back="handleBack('2')" @done="handleDone" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onBeforeUnmount } from 'vue'
import { Icon } from '@iconify/vue'
import { useRouter, useRoute } from 'vue-router'
import { useStudentImport } from '../../../composables/useStudentImport'
import StepUpload from './StepUpload.vue'
import StepPreview from './StepPreview.vue'
import StepConfirm from './StepConfirm.vue'

const steps = [
  { value: '1', label: '1. อัปโหลดไฟล์' },
  { value: '2', label: '2. ตรวจสอบข้อมูล' },
  { value: '3', label: '3. ยืนยันการนำเข้า' },
]

const activeStep = ref('1')
const router = useRouter()
const route = useRoute()
const academyName = route.params.name as string
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const { resetState, currentBatch } = useStudentImport(String(academyId.value || ''))

const isCompleted = (value: string) => Number(activeStep.value) > Number(value)

const stepCircleClass = (value: string) => {
  if (activeStep.value === value) return 'bg-primary-600 text-white'
  if (isCompleted(value)) return 'bg-primary-500 text-white'
  return 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
}

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
