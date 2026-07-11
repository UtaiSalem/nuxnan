<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useStudentIntake } from '~/composables/useStudentIntake'
import DuplicateWarning from './DuplicateWarning.vue'

const emit = defineEmits(['next'])
// The API binds {academy} by id, so the duplicate check must use the id (not the name).
const academyId = inject<Ref<number | null>>('academyId', ref(null))

const { payload, isCheckingDuplicate, duplicateMatches, checkDuplicate } = useStudentIntake(academyId)
const isNextClicked = ref(false)
const overrideDuplicate = ref(false)

const isValid = computed(() => {
  return payload.identity.student_id.trim() !== ''
})

const handleNext = async () => {
  isNextClicked.value = true
  if (!isValid.value) return

  if (overrideDuplicate.value) {
    emit('next')
    return
  }

  const hasDuplicate = await checkDuplicate()
  if (!hasDuplicate) {
    emit('next')
  }
}
</script>

<template>
  <div class="py-6 max-w-2xl mx-auto space-y-8">
    <div class="text-center">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลรหัสประจำตัว</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        กรุณาระบุรหัสนักเรียนและเลขประจำตัวประชาชน (ถ้ามี) ระบบจะตรวจสอบข้อมูลซ้ำให้อัตโนมัติ
      </p>
    </div>

    <!-- Form -->
    <div class="space-y-5 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          รหัสนักเรียน <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <Icon icon="fluent:number-symbol-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input 
            v-model="payload.identity.student_id"
            type="text" 
            placeholder="เช่น 69-001 หรือ STD001"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            :class="{'border-red-500 focus:border-red-500 focus:ring-red-500/20': isNextClicked && !payload.identity.student_id}"
            @input="overrideDuplicate = false"
          />
        </div>
        <p v-if="isNextClicked && !payload.identity.student_id" class="text-sm text-red-500">กรุณาระบุรหัสนักเรียน</p>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          เลขประจำตัวประชาชน <span class="text-gray-400 font-normal">(ไม่บังคับ)</span>
        </label>
        <div class="relative">
          <Icon icon="fluent:card-ui-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input 
            v-model="payload.identity.citizen_id"
            type="text" 
            placeholder="เลข 13 หลัก"
            maxlength="13"
            class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            @input="overrideDuplicate = false"
          />
        </div>
      </div>
    </div>

    <!-- Duplicate Warning -->
    <DuplicateWarning 
      v-if="duplicateMatches.length > 0 && !overrideDuplicate" 
      :matches="duplicateMatches" 
      @cancel="overrideDuplicate = false"
      @proceed="overrideDuplicate = true; handleNext()"
    />

    <!-- Actions -->
    <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
      <button 
        type="button" 
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        @click="handleNext"
        :disabled="isCheckingDuplicate"
      >
        <Icon v-if="isCheckingDuplicate" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
        <span>ถัดไป</span>
        <Icon v-if="!isCheckingDuplicate" icon="fluent:arrow-right-24-regular" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>
