<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import StepIdentity from './StepIdentity.vue'
import StepPersonal from './StepPersonal.vue'
import StepAdmission from './StepAdmission.vue'
import StepGuardian from './StepGuardian.vue'
import StepReview from './StepReview.vue'
import { useStudentIntake } from '~/composables/useStudentIntake'

const props = defineProps<{
  academyName: string
}>()

const router = useRouter()
// The API binds {academy} by id, so intake calls must use the id (not the name).
// Pass the ref so the id is resolved lazily (the parent provides it async).
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const { submit, resetForm } = useStudentIntake(academyId)

const steps = [
  { value: '1', label: 'ข้อมูลเบื้องต้น' },
  { value: '2', label: 'ข้อมูลส่วนตัว' },
  { value: '3', label: 'ข้อมูลการเรียน' },
  { value: '4', label: 'ผู้ปกครอง' },
  { value: '5', label: 'ยืนยัน' },
]

const activeStep = ref('1')

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
  activeStep.value = prevStep
}

const handleSubmit = async () => {
  try {
    const result = await submit()
    if (result.success) {
      Swal.fire({
        title: 'สำเร็จ!',
        text: 'รับนักเรียนใหม่เข้าสู่ระบบเรียบร้อยแล้ว',
        icon: 'success',
        confirmButtonText: 'กลับไปหน้าทะเบียน',
        confirmButtonColor: '#10b981'
      }).then(() => {
        resetForm()
        router.push(`/academies/${props.academyName}/admin/students`)
      })
    }
  } catch (error: any) {
    Swal.fire({
      title: 'เกิดข้อผิดพลาด',
      text: error.response?.data?.message || 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง',
      icon: 'error'
    })
  }
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
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
              class="hidden md:block text-sm font-medium"
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

    <!-- Panels (v-show keeps step state when navigating back) -->
    <div v-show="activeStep === '1'">
      <StepIdentity @next="handleNext('2')" />
    </div>
    <div v-show="activeStep === '2'">
      <StepPersonal @back="handleBack('1')" @next="handleNext('3')" />
    </div>
    <div v-show="activeStep === '3'">
      <StepAdmission @back="handleBack('2')" @next="handleNext('4')" />
    </div>
    <div v-show="activeStep === '4'">
      <StepGuardian @back="handleBack('3')" @next="handleNext('5')" />
    </div>
    <div v-show="activeStep === '5'">
      <StepReview @back="handleBack('4')" @submit="handleSubmit" />
    </div>
  </div>
</template>
