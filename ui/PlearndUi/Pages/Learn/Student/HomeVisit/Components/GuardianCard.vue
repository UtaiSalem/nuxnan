<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex items-center">
        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div class="ml-2 sm:ml-3 min-w-0 flex-1">
          <h3 class="text-base sm:text-lg font-semibold text-white truncate">
            ข้อมูลผู้ปกครอง
          </h3>
          <p class="text-purple-100 text-xs sm:text-sm">ข้อมูลผู้ปกครองและช่องทางติดต่อ</p>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 sm:p-6 bg-gradient-to-br from-purple-50 to-indigo-50">
      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-8">
        <div class="flex items-center space-x-2">
          <svg class="animate-spin h-5 w-5 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-purple-600 text-sm">กำลังโหลดข้อมูลผู้ปกครอง...</span>
        </div>
      </div>

      <!-- Error State -->
  <div v-else-if="error && !isSaving" class="rounded-lg bg-red-50 p-4 mb-6">
          <div v-if="!isSaving" class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">เกิดข้อผิดพลาด</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ error }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Guardian Form -->
      <div v-else class="space-y-6">
        <!-- Guardian Basic Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          <!-- Guardian Type -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a2 2 0 012-2z" />
              </svg>
              <span class="truncate">ประเภทผู้ปกครอง</span>
            </label>
            <select
              v-model="form.guardian.guardian_type"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
            >
              <option value="father">บิดา</option>
              <option value="mother">มารดา</option>
              <option value="guardian">ผู้ปกครอง</option>
              <option value="relative">ญาติ</option>
            </select>
          </div>

          <!-- Title Prefix -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="truncate">คำนำหน้า</span>
            </label>
            <input
              type="text"
              v-model="form.guardian.title_prefix"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="นาย/นาง/นางสาว"
            >
          </div>

          <!-- First Name -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="truncate">ชื่อ</span>
            </label>
            <input
              type="text"
              v-model="form.guardian.first_name"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="ชื่อ"
            >
          </div>

          <!-- Last Name -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span class="truncate">นามสกุล</span>
            </label>
            <input
              type="text"
              v-model="form.guardian.last_name"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="นามสกุล"
            >
          </div>

          <!-- Citizen ID -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
              </svg>
              <span class="truncate">เลขบัตรประชาชน</span>
            </label>
            <input
              type="text"
              v-model="form.guardian.citizen_id"
              maxlength="13"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="1234567890123"
            >
          </div>

          <!-- Occupation -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V4a2 2 0 00-2-2H8a2 2 0 00-2 2v2m8 0v2a2 2 0 002 2h2a2 2 0 002-2V8h-2m0 0V6a2 2 0 00-2-2h-4a2 2 0 00-2 2v2H8V6z" />
              </svg>
              <span class="truncate">อาชีพ</span>
            </label>
            <input
              type="text"
              v-model="form.guardian.occupation"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="อาชีพ"
            >
          </div>

          <!-- Monthly Income -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
              <span class="truncate">รายได้ต่อเดือน (บาท)</span>
            </label>
            <input
              type="number"
              v-model="form.guardian.monthly_income"
              min="0"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="25000"
            >
          </div>

          <!-- Relationship -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
              <span class="truncate">ความสัมพันธ์</span>
            </label>
            <input
              type="text"
              v-model="form.guardian.relationship"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="บิดา/มารดา/ผู้ปกครอง"
            >
          </div>

          <!-- Is Primary Guardian -->
          <div class="flex items-center justify-center">
            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="form.guardian.is_primary"
                class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50 w-4 h-4"
              >
              <span class="ml-2 text-xs sm:text-sm text-gray-700">ผู้ปกครองหลัก</span>
            </label>
          </div>
        </div>

        <!-- Guardian Contact Information -->
        <div class="border-t border-purple-200 pt-6">
          <h4 class="text-sm sm:text-base font-medium text-gray-700 mb-4 flex items-center">
            <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            ช่องทางติดต่อผู้ปกครอง
          </h4>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Contact Type -->
            <div class="space-y-2">
              <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a2 2 0 012-2z" />
                </svg>
                <span class="truncate">ประเภทการติดต่อ</span>
              </label>
              <select
                v-model="form.guardianContact.contact_type"
                class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              >
                <option value="phone">โทรศัพท์</option>
                <option value="email">อีเมล</option>
                <option value="line">Line</option>
                <option value="facebook">Facebook</option>
              </select>
            </div>

            <!-- Contact Value -->
            <div class="space-y-2">
              <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span class="truncate">ข้อมูลติดต่อ</span>
              </label>
              <input
                type="text"
                v-model="form.guardianContact.contact_value"
                class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
                :placeholder="form.guardianContact.contact_type === 'phone' ? '0XX-XXX-XXXX' : 
                              form.guardianContact.contact_type === 'email' ? 'email@example.com' :
                              form.guardianContact.contact_type === 'line' ? 'Line ID' : 'ข้อมูลติดต่อ'"
              >
            </div>

            <!-- Is Primary Contact -->
            <div class="flex items-center justify-center">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="form.guardianContact.is_primary"
                  class="rounded border-gray-300 text-purple-600 shadow-sm focus:border-purple-300 focus:ring focus:ring-purple-200 focus:ring-opacity-50 w-4 h-4"
                >
                <span class="ml-2 text-xs sm:text-sm text-gray-700">ช่องทางหลัก</span>
              </label>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Save Button Section -->
      <div class="px-4 py-4 sm:px-6 sm:py-6 bg-gray-50 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div class="text-xs sm:text-sm text-gray-500">
            <span v-if="isSaving" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              กำลังบันทึก...
            </span>
            <span v-else-if="saveStatus === 'success'" class="flex items-center text-green-600">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              บันทึกสำเร็จ
            </span>
            <span v-else-if="saveStatus === 'error'" class="flex items-center text-red-600">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              เกิดข้อผิดพลาด
            </span>
            <span v-else>ข้อมูลผู้ปกครองและการติดต่อ</span>
          </div>
          
          <button
            type="button"
            @click="saveGuardianData"
            :disabled="isSaving"
            class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-2.5 bg-purple-600 text-white text-sm sm:text-base font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors touch-manipulation placeholder-gray-400"
          >
            <svg v-if="!isSaving" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m0 0h8m0 0h2a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4 0V5a2 2 0 011-1h4a2 2 0 011 1v2M8 7V5a2 2 0 011-1h4a2 2 0 011 1v2m0 0v4m0 0h-4m4 0V9H8v4z"></path>
            </svg>
            <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSaving ? 'กำลังบันทึก...' : (hasGuardianData ? 'อัปเดตข้อมูลผู้ปกครอง' : 'บันทึกข้อมูลผู้ปกครอง') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const props = defineProps({
  student: {
    type: Object,
    required: true
  },
  isLoading: {
    type: Boolean,
    default: false
  },
  context: {
    type: String,
    default: 'student' // 'student' or 'teacher'
  }
})

const emit = defineEmits(['save', 'update'])

// Reactive data
const isLoading = ref(false)
const isSaving = ref(false)
const saveStatus = ref(null)
const error = ref('')
const hasGuardianData = ref(false)

// Form data
const form = ref({
  guardian: {
    guardian_type: 'father',
    title_prefix: '',
    first_name: '',
    last_name: '',
    citizen_id: '',
    occupation: '',
    workplace: '',
    monthly_income: '',
    relationship: '',
    is_primary_contact: false,
    is_emergency_contact: false
  },
  guardianContact: {
    contact_type: 'phone',
    contact_value: '',
    is_primary: true
  }
})

// API endpoints
const getApiUrl = (endpoint) => {
  const studentId = props.student?.id
  return `/home-visit/student/${studentId}/guardian${endpoint ? '/' + endpoint : ''}`
}

// Load guardian data from API
const loadGuardianData = async () => {
  isLoading.value = true
  error.value = ''
  saveStatus.value = null
  
  try {
    const response = await axios.get(getApiUrl())
    
    if (response.data.success && response.data.data) {
      const { guardian, contact } = response.data.data
      
      // Update form with guardian data
      if (guardian) {
        Object.assign(form.value.guardian, {
          guardian_type: guardian.guardian_type || 'father',
          title_prefix: guardian.title_prefix || '',
          first_name: guardian.first_name || '',
          last_name: guardian.last_name || '',
          citizen_id: guardian.citizen_id || '',
          occupation: guardian.occupation || '',
          workplace: guardian.workplace || '',
          monthly_income: guardian.monthly_income || '',
          relationship: guardian.relationship || '',
          is_primary_contact: guardian.is_primary_contact || false,
          is_emergency_contact: guardian.is_emergency_contact || false
        })
      }
      
      // Update form with contact data
      if (contact) {
        Object.assign(form.value.guardianContact, {
          contact_type: contact.contact_type || 'phone',
          contact_value: contact.contact_value || '',
          is_primary: contact.is_primary !== undefined ? contact.is_primary : true
        })
      }
      
      hasGuardianData.value = !!guardian
    }
  } catch (err) {
    console.error('Error loading guardian data:', err)
    
    let errorMessage = 'ไม่สามารถโหลดข้อมูลผู้ปกครองได้'
    
    if (err.response?.status === 404) {
      errorMessage = 'ไม่พบข้อมูลนักเรียน'
    } else if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.message) {
      errorMessage = err.message
    }
    
    error.value = errorMessage
  } finally {
    isLoading.value = false
  }
}

// Save guardian data to API
const saveGuardianData = async () => {
  isSaving.value = true
  error.value = ''
  saveStatus.value = null

  // Basic validation
  if (!form.value.guardian.first_name.trim() || !form.value.guardian.last_name.trim()) {
    await Swal.fire({
      title: 'ข้อมูลไม่ครบถ้วน',
      text: 'กรุณากรอกชื่อและนามสกุลผู้ปกครอง',
      icon: 'warning',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#8b5cf6'
    })
    isSaving.value = false
    return
  }

  if (!form.value.guardianContact.contact_value.trim()) {
    await Swal.fire({
      title: 'ข้อมูลไม่ครบถ้วน',
      text: 'กรุณากรอกข้อมูลการติดต่อ',
      icon: 'warning',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#8b5cf6'
    })
    isSaving.value = false
    return
  }

  try {
    const method = hasGuardianData.value ? 'put' : 'post'
    const response = await axios[method](getApiUrl(), {
      guardian: form.value.guardian,
      contact: form.value.guardianContact
    })

    if (response.data.success) {
      hasGuardianData.value = true
      saveStatus.value = 'success'
      await nextTick()
      isSaving.value = false
      await Swal.fire({
        title: 'สำเร็จ!',
        text: response.data.message || 'บันทึกข้อมูลผู้ปกครองเรียบร้อย',
        icon: 'success',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#8b5cf6',
        timer: 2000
      })
      setTimeout(() => {
        saveStatus.value = null
      }, 3000)
      emit('save', {
        success: true,
        type: 'guardian',
        data: response.data.data
      })
    }
  } catch (err) {
    console.error('Error saving guardian data:', err)
    
    let errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
    
    if (err.response?.data?.message) {
      errorMessage = err.response.data.message
    } else if (err.response?.data?.errors) {
      const errors = Object.values(err.response.data.errors).flat()
      errorMessage = errors.join(', ')
    }

    saveStatus.value = 'error'

    await Swal.fire({
      title: 'เกิดข้อผิดพลาด',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#ef4444'
    })

    // Clear saveStatus after showing error
    setTimeout(() => {
      saveStatus.value = null
    }, 3000)

    error.value = errorMessage
  } finally {
    isSaving.value = false
  }
}

// Computed properties
const guardianTypeOptions = [
  { value: 'father', label: 'บิดา' },
  { value: 'mother', label: 'มารดา' },
  { value: 'guardian', label: 'ผู้ปกครอง' },
  { value: 'relative', label: 'ญาติ' }
]

const contactTypeOptions = [
  { value: 'phone', label: 'โทรศัพท์' },
  { value: 'email', label: 'อีเมล' },
  { value: 'line', label: 'Line ID' },
  { value: 'facebook', label: 'Facebook' },
  { value: 'other', label: 'อื่นๆ' }
]

// Load data on component mount
onMounted(async () => {
  await nextTick()
  if (props.student?.id) {
    await loadGuardianData()
  } else {
    console.warn('GuardianCard: No student ID provided')
  }
})
</script>
