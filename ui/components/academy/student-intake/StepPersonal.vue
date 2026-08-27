<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useStudentIntake } from '~/composables/useStudentIntake'

const emit = defineEmits(['next', 'back'])
const route = useRoute()
const academyName = computed(() => route.params.name as string)
const { payload } = useStudentIntake(academyName.value)

const isNextClicked = ref(false)

const isValid = computed(() => {
  return payload.personal.first_name_th.trim() !== '' && 
         payload.personal.last_name_th.trim() !== ''
})

const handleNext = () => {
  isNextClicked.value = true
  if (isValid.value) {
    emit('next')
  }
}
</script>

<template>
  <div class="py-6 max-w-3xl mx-auto space-y-8">
    <div class="text-center">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลส่วนตัว</h2>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        ระบุข้อมูลส่วนตัวเบื้องต้นของนักเรียน
      </p>
    </div>

    <div class="space-y-6">
      <!-- Thai Name -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:local-language-24-regular" class="w-5 h-5 text-gray-400" />
          ชื่อ-นามสกุล (ภาษาไทย)
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
          <div class="sm:col-span-3 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">คำนำหน้า</label>
            <input 
              v-model="payload.personal.title_prefix_th"
              type="text" 
              placeholder="ด.ช. / ด.ญ. / นาย / นางสาว"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="sm:col-span-4 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              ชื่อ <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="payload.personal.first_name_th"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="{'border-red-500': isNextClicked && !payload.personal.first_name_th}"
            />
            <p v-if="isNextClicked && !payload.personal.first_name_th" class="text-xs text-red-500">กรุณาระบุชื่อ</p>
          </div>
          <div class="sm:col-span-5 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              นามสกุล <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="payload.personal.last_name_th"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="{'border-red-500': isNextClicked && !payload.personal.last_name_th}"
            />
            <p v-if="isNextClicked && !payload.personal.last_name_th" class="text-xs text-red-500">กรุณาระบุนามสกุล</p>
          </div>
        </div>
      </div>

      <!-- English Name -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:text-grammar-options-24-regular" class="w-5 h-5 text-gray-400" />
          ชื่อ-นามสกุล (ภาษาอังกฤษ) <span class="font-normal text-gray-500 text-xs">- ไม่บังคับ</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
          <div class="sm:col-span-3 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
            <input 
              v-model="payload.personal.title_prefix_en"
              type="text" 
              placeholder="Mr. / Ms."
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="sm:col-span-4 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">First Name</label>
            <input 
              v-model="payload.personal.first_name_en"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="sm:col-span-5 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Last Name</label>
            <input 
              v-model="payload.personal.last_name_en"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
        </div>
      </div>

      <!-- Details -->
      <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="fluent:person-info-24-regular" class="w-5 h-5 text-gray-400" />
          ข้อมูลอื่นๆ
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ชื่อเล่น</label>
            <input 
              v-model="payload.personal.nickname"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เพศ</label>
            <select 
              v-model="payload.personal.gender"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            >
              <option :value="null">ไม่ระบุ</option>
              <option :value="1">ชาย (Male)</option>
              <option :value="0">หญิง (Female)</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">วันเกิด</label>
            <input 
              v-model="payload.personal.date_of_birth"
              type="date" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">สัญชาติ</label>
            <input 
              v-model="payload.personal.nationality"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ศาสนา</label>
            <input 
              v-model="payload.personal.religion"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
      <button 
        type="button" 
        class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
        @click="emit('back')"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>ย้อนกลับ</span>
      </button>
      <button 
        type="button" 
        class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        @click="handleNext"
      >
        <span>ถัดไป</span>
        <Icon icon="fluent:arrow-right-24-regular" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>
