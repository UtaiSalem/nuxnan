<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useStudentIntake } from '~/composables/useStudentIntake'

const emit = defineEmits(['submit', 'back'])
const route = useRoute()
const academyName = computed(() => route.params.name as string)
const { payload, isSubmitting } = useStudentIntake(academyName.value)

const fullName = computed(() => {
  const p = payload.personal
  return [p.title_prefix_th, p.first_name_th, p.last_name_th].filter(Boolean).join(' ')
})

const guardianTypes: Record<string, string> = {
  father: 'บิดา',
  mother: 'มารดา',
  guardian: 'ผู้ปกครอง',
  relative: 'ญาติ',
  other: 'อื่นๆ'
}
</script>

<template>
  <div class="py-6 max-w-3xl mx-auto space-y-8">
    <div class="text-center">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">ตรวจสอบข้อมูล</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        กรุณาตรวจสอบความถูกต้องของข้อมูลก่อนกดยืนยัน
      </p>
    </div>

    <div class="space-y-6">
      <!-- Identity & Personal -->
      <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
          ข้อมูลประจำตัวและข้อมูลส่วนตัว
        </h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">รหัสนักเรียน</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ payload.identity.student_id || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">เลขประจำตัวประชาชน</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ payload.identity.citizen_id || '-' }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อ-นามสกุล</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ fullName }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อเล่น</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ payload.personal.nickname || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">เพศ</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ payload.personal.gender === 1 ? 'ชาย' : payload.personal.gender === 0 ? 'หญิง' : '-' }}</dd>
          </div>
        </dl>
      </div>

      <!-- Admission -->
      <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
          ข้อมูลการเรียน
        </h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">วันที่รับเข้าเรียน</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ payload.admission.enrollment_date || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">เลขที่</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ payload.admission.student_number || '-' }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">โรงเรียนเดิม</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
              {{ payload.previous_school.name || '-' }} 
              {{ payload.previous_school.province ? `(จ.${payload.previous_school.province})` : '' }}
            </dd>
          </div>
        </dl>
      </div>

      <!-- Guardians -->
      <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
          ข้อมูลผู้ปกครอง ({{ payload.guardians.length }} คน)
        </h3>
        
        <div v-if="payload.guardians.length === 0" class="text-sm text-gray-500 italic">
          ไม่มีข้อมูลผู้ปกครอง
        </div>
        
        <div v-else class="space-y-4">
          <div 
            v-for="(guardian, index) in payload.guardians" 
            :key="guardian.id"
            class="bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700"
          >
            <div class="flex justify-between mb-2">
              <span class="font-medium text-gray-900 dark:text-white">
                {{ guardian.title_prefix || '' }} {{ guardian.first_name }} {{ guardian.last_name }}
              </span>
              <span class="text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 rounded">
                {{ guardian.guardian_type === 'other' ? guardian.relationship : guardianTypes[guardian.guardian_type] }}
              </span>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap gap-2">
              <span v-if="guardian.is_primary_contact" class="text-green-600 dark:text-green-400 font-medium bg-green-50 dark:bg-green-900/20 px-1.5 py-0.5 rounded text-xs">ผู้ติดต่อหลัก</span>
              <span v-if="guardian.is_emergency_contact" class="text-red-600 dark:text-red-400 font-medium bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded text-xs">ติดต่อฉุกเฉิน</span>
              <span v-if="guardian.occupation">อาชีพ: {{ guardian.occupation }}</span>
            </div>
            <div v-if="guardian.contacts?.length" class="mt-2 text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
              <div v-for="(c, ci) in guardian.contacts" :key="ci">
                {{ { mobile: 'มือถือ', phone: 'โทรศัพท์', email: 'Email', line: 'LINE', other: 'อื่นๆ' }[c.contact_type] }}: {{ c.contact_value }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Account Mode -->
      <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b border-gray-200 dark:border-gray-700 pb-2">
          การสร้างบัญชีผู้ใช้
        </h3>
        <div class="space-y-3">
          <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 transition-colors" :class="payload.account.mode === 'pending_activation' ? 'ring-2 ring-primary-500/30 border-primary-400' : ''">
            <input type="radio" v-model="payload.account.mode" value="pending_activation" class="mt-0.5 w-4 h-4 text-primary-600 focus:ring-primary-500" />
            <div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">สร้างบัญชีรอ Activation</span>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">นักเรียนจะได้รับรหัสเพื่อ activate บัญชีและเข้าใช้ระบบทีหลัง</p>
            </div>
          </label>
          <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 transition-colors" :class="payload.account.mode === 'none' ? 'ring-2 ring-primary-500/30 border-primary-400' : ''">
            <input type="radio" v-model="payload.account.mode" value="none" class="mt-0.5 w-4 h-4 text-primary-600 focus:ring-primary-500" />
            <div>
              <span class="text-sm font-medium text-gray-900 dark:text-white">ไม่สร้างบัญชี</span>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">บันทึกข้อมูลทะเบียนอย่างเดียว ไม่สร้างบัญชีผู้ใช้</p>
            </div>
          </label>
        </div>
      </div>

    <!-- Actions -->
    <div class="flex justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
      <button
        type="button"
        class="inline-flex items-center gap-2 px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
        @click="emit('back')"
        :disabled="isSubmitting"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>ย้อนกลับ</span>
      </button>
      <button 
        type="button" 
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        @click="emit('submit')"
        :disabled="isSubmitting"
      >
        <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-filled" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:checkmark-24-regular" class="w-5 h-5" />
        <span>ยืนยันบันทึกข้อมูล</span>
      </button>
    </div>
  </div>
</template>
