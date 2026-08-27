<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { useStudentIntake } from '~/composables/useStudentIntake'

const emit = defineEmits(['next', 'back'])
const route = useRoute()
const academyName = computed(() => route.params.name as string)
const { payload, addGuardian, removeGuardian, addGuardianContact, removeGuardianContact, setPrimaryGuardian, setEmergencyGuardian } = useStudentIntake(academyName.value)

const isNextClicked = ref(false)

const isValid = computed(() => {
  if (payload.guardians.length === 0) return true
  // Check if all guardians have first_name and last_name
  return payload.guardians.every(g => g.first_name.trim() !== '' && g.last_name.trim() !== '')
})

const handleNext = () => {
  isNextClicked.value = true
  if (isValid.value) {
    emit('next')
  }
}
</script>

<template>
  <div class="py-6 max-w-4xl mx-auto space-y-8">
    <div class="flex justify-between items-end">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">ข้อมูลผู้ปกครอง</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          ระบุข้อมูลบิดา มารดา หรือผู้ปกครอง (เพิ่มได้สูงสุด 4 คน)
        </p>
      </div>
      <button 
        type="button"
        @click="addGuardian"
        v-if="payload.guardians.length < 4"
        class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 font-medium rounded-lg hover:bg-primary-100 dark:hover:bg-primary-800/50 transition-colors text-sm"
      >
        <Icon icon="fluent:add-24-regular" class="w-4 h-4" />
        เพิ่มผู้ปกครอง
      </button>
    </div>

    <!-- Empty State -->
    <div 
      v-if="payload.guardians.length === 0" 
      class="bg-gray-50 dark:bg-gray-800/50 p-12 rounded-xl border border-dashed border-gray-300 dark:border-gray-600 flex flex-col items-center justify-center text-center"
    >
      <Icon icon="fluent:people-community-24-regular" class="w-12 h-12 text-gray-400 mb-3" />
      <h3 class="text-gray-900 dark:text-white font-medium mb-1">ยังไม่มีข้อมูลผู้ปกครอง</h3>
      <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">คลิกปุ่มด้านบนเพื่อเพิ่มข้อมูลผู้ปกครอง (ไม่บังคับ)</p>
    </div>

    <!-- Guardians List -->
    <div v-else class="space-y-6">
      <div 
        v-for="(guardian, index) in payload.guardians" 
        :key="guardian.id"
        class="bg-white dark:bg-gray-800 p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm relative overflow-hidden"
      >
        <!-- Guardian Header -->
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-semibold">
              {{ index + 1 }}
            </div>
            <div>
              <h3 class="font-medium text-gray-900 dark:text-white">ผู้ปกครองคนที่ {{ index + 1 }}</h3>
              <div class="flex gap-2 mt-1">
                <span v-if="guardian.is_primary_contact" class="text-xs px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full font-medium">ผู้ติดต่อหลัก</span>
                <span v-if="guardian.is_emergency_contact" class="text-xs px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full font-medium">ติดต่อฉุกเฉิน</span>
              </div>
            </div>
          </div>
          <button 
            type="button" 
            @click="removeGuardian(guardian.id!)"
            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
          >
            <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
          </button>
        </div>

        <!-- Guardian Form -->
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
          <div class="sm:col-span-3 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เกี่ยวข้องเป็น</label>
            <select 
              v-model="guardian.guardian_type"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            >
              <option value="father">บิดา</option>
              <option value="mother">มารดา</option>
              <option value="guardian">ผู้ปกครอง</option>
              <option value="relative">ญาติ</option>
              <option value="other">อื่นๆ</option>
            </select>
          </div>
          <div class="sm:col-span-3 space-y-1.5" v-if="guardian.guardian_type === 'other'">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ระบุความสัมพันธ์</label>
            <input 
              v-model="guardian.relationship"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 mt-4">
          <div class="sm:col-span-3 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">คำนำหน้า</label>
            <input 
              v-model="guardian.title_prefix"
              type="text" 
              placeholder="นาย / นาง / นางสาว"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="sm:col-span-4 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              ชื่อ <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="guardian.first_name"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="{'border-red-500': isNextClicked && !guardian.first_name}"
            />
          </div>
          <div class="sm:col-span-5 space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              นามสกุล <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="guardian.last_name"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
              :class="{'border-red-500': isNextClicked && !guardian.last_name}"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">เลขประจำตัวประชาชน</label>
            <input 
              v-model="guardian.citizen_id"
              type="text" 
              maxlength="13"
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">อาชีพ</label>
            <input 
              v-model="guardian.occupation"
              type="text" 
              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-shadow"
            />
          </div>
        </div>

        <!-- Guardian Contacts -->
        <div class="mt-6">
          <div class="flex justify-between items-center mb-3">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
              <Icon icon="fluent:call-24-regular" class="w-4 h-4 text-gray-400" />
              ช่องทางติดต่อ
            </h4>
            <button
              type="button"
              v-if="(guardian.contacts?.length ?? 0) < 3"
              @click="addGuardianContact(guardian.id!)"
              class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1"
            >
              <Icon icon="fluent:add-12-regular" class="w-3 h-3" />
              เพิ่มช่องทาง
            </button>
          </div>
          <div v-if="!guardian.contacts || guardian.contacts.length === 0" class="text-sm text-gray-400 italic">
            ยังไม่มีช่องทางติดต่อ
          </div>
          <div v-else class="space-y-2">
            <div
              v-for="(contact, cIdx) in guardian.contacts"
              :key="cIdx"
              class="flex items-center gap-2"
            >
              <select
                v-model="contact.contact_type"
                class="w-32 flex-shrink-0 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
              >
                <option value="mobile">มือถือ</option>
                <option value="phone">โทรศัพท์</option>
                <option value="email">Email</option>
                <option value="line">LINE</option>
                <option value="other">อื่นๆ</option>
              </select>
              <input
                v-model="contact.contact_value"
                type="text"
                :placeholder="contact.contact_type === 'email' ? 'email@example.com' : contact.contact_type === 'line' ? 'LINE ID' : '0xx-xxx-xxxx'"
                class="min-w-0 flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700"
              />
              <button
                type="button"
                @click="removeGuardianContact(guardian.id!, cIdx)"
                class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 transition-colors"
              >
                <Icon icon="fluent:dismiss-12-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Contact Preferences -->
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-wrap gap-6">
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              type="radio" 
              name="primary_contact" 
              :checked="guardian.is_primary_contact"
              @change="setPrimaryGuardian(guardian.id!)"
              class="w-4 h-4 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ตั้งเป็นผู้ติดต่อหลัก</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input 
              type="radio" 
              name="emergency_contact" 
              :checked="guardian.is_emergency_contact"
              @change="setEmergencyGuardian(guardian.id!)"
              class="w-4 h-4 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">ตั้งเป็นผู้ติดต่อฉุกเฉิน</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
      <button 
        type="button" 
        class="inline-flex items-center gap-2 px-6 py-2.5 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
        @click="emit('back')"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>ย้อนกลับ</span>
      </button>
      <button 
        type="button" 
        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
        @click="handleNext"
      >
        <span>ถัดไป</span>
        <Icon icon="fluent:arrow-right-24-regular" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>
