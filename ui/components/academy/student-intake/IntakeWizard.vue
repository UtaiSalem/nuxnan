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
const { submit, resetForm } = useStudentIntake(props.academyName)

const activeStep = ref('1')

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
    <Stepper v-model:value="activeStep">
      <StepList>
        <Step value="1">ข้อมูลเบื้องต้น</Step>
        <Step value="2">ข้อมูลส่วนตัว</Step>
        <Step value="3">ข้อมูลการเรียน</Step>
        <Step value="4">ผู้ปกครอง</Step>
        <Step value="5">ยืนยัน</Step>
      </StepList>
      <StepPanels>
        <StepPanel value="1">
          <StepIdentity @next="handleNext('2')" />
        </StepPanel>
        <StepPanel value="2">
          <StepPersonal @back="handleBack('1')" @next="handleNext('3')" />
        </StepPanel>
        <StepPanel value="3">
          <StepAdmission @back="handleBack('2')" @next="handleNext('4')" />
        </StepPanel>
        <StepPanel value="4">
          <StepGuardian @back="handleBack('3')" @next="handleNext('5')" />
        </StepPanel>
        <StepPanel value="5">
          <StepReview @back="handleBack('4')" @submit="handleSubmit" />
        </StepPanel>
      </StepPanels>
    </Stepper>
  </div>
</template>
