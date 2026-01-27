<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-500 to-pink-600 px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex items-center">
        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
          </svg>
        </div>
        <div class="ml-2 sm:ml-3 min-w-0 flex-1">
          <h3 class="text-base sm:text-lg font-semibold text-white truncate">
            ข้อมูลสุขภาพ
          </h3>
          <p class="text-red-100 text-xs sm:text-sm">ข้อมูลสุขภาพและการแพทย์ของนักเรียน</p>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 sm:p-6 bg-gradient-to-br from-red-50 to-pink-50">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Height -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 17l-4 4m0 0l-4-4m4 4V3" />
            </svg>
            <span class="truncate">ส่วนสูง (ซม.)</span>
          </label>
          <input
            type="number"
            v-model="form.height"
            step="0.1"
            min="0"
            max="250"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
            placeholder="150.5"
          >
        </div>

        <!-- Weight -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l3-1m-3 1l-3-1" />
            </svg>
            <span class="truncate">น้ำหนัก (กก.)</span>
          </label>
          <input
            type="number"
            v-model="form.weight"
            step="0.1"
            min="0"
            max="200"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
            placeholder="45.0"
          >
        </div>

        <!-- BMI (Calculated) -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <span class="truncate">BMI</span>
          </label>
          <div class="w-full px-3 py-2.5 sm:py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm sm:text-base text-gray-600">
            {{ calculateBMI() }}
          </div>
        </div>

        <!-- Allergies -->
        <div class="space-y-2 sm:col-span-2 lg:col-span-1">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="truncate">ประวัติแพ้ยา/อาหาร</span>
          </label>
          <textarea
            v-model="form.allergies"
            rows="3"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation resize-none placeholder-gray-400"
            placeholder="ระบุประวัติการแพ้ยาหรืออาหาร"
          ></textarea>
        </div>

        <!-- Chronic Diseases -->
        <div class="space-y-2 sm:col-span-2 lg:col-span-1">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
            <span class="truncate">โรคประจำตัว</span>
          </label>
          <textarea
            v-model="form.chronic_diseases"
            rows="3"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation resize-none placeholder-gray-400"
            placeholder="ระบุโรคประจำตัว (ถ้ามี)"
          ></textarea>
        </div>

        <!-- Medications -->
        <div class="space-y-2 sm:col-span-2 lg:col-span-1">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span class="truncate">ยาที่ใช้ประจำ</span>
          </label>
          <textarea
            v-model="form.medications"
            rows="3"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation resize-none placeholder-gray-400"
            placeholder="ระบุยาที่ใช้ประจำ (ถ้ามี)"
          ></textarea>
        </div>

        <!-- Blood Type -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span class="truncate">หมู่เลือด</span>
          </label>
          <select
            v-model="form.blood_type"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation"
          >
            <option value="">เลือกหมู่เลือด</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="AB">AB</option>
            <option value="O">O</option>
          </select>
        </div>

        <!-- RH Factor -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="truncate">RH</span>
          </label>
          <select
            v-model="form.rh_factor"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors text-sm sm:text-base touch-manipulation"
          >
            <option value="">เลือก RH</option>
            <option value="+">บวก (+)</option>
            <option value="-">ลบ (-)</option>
          </select>
        </div>
      </div>
      
      <!-- Save Button Section -->
      <div class="px-4 py-4 sm:px-6 sm:py-6 bg-gray-50 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div class="text-xs sm:text-sm text-gray-500">
              <!-- Spinner footer removed: spinner now only at save button -->
          </div>
          
          <button
            type="button"
            @click="saveHealthData"
            :disabled="isSaving || isLoading"
            class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-2.5 bg-red-600 text-white text-sm sm:text-base font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors touch-manipulation"
          >
            <svg v-if="!isSaving" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m0 0h8m0 0h2a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4 0V5a2 2 0 011-1h4a2 2 0 011 1v2M8 7V5a2 2 0 011-1h4a2 2 0 011 1v2m0 0v4m0 0h-4m4 0V9H8v4z"></path>
            </svg>
            <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSaving ? 'กำลังบันทึก...' : (hasHealthData ? 'อัปเดตข้อมูลสุขภาพ' : 'บันทึกข้อมูลสุขภาพ') }}
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
  context: {
    type: String,
    default: 'student' // 'student' or 'teacher'
  }
})

const emit = defineEmits(['save'])

// Reactive data
const healthData = ref(null)
const isSaving = ref(false)
const isLoading = ref(false)
const error = ref(null)

// Form data
const form = ref({
  height: '',
  weight: '',
  allergies: '',
  chronic_diseases: '',
  medications: '',
  blood_type: '',
  rh_factor: ''
})

// Computed properties
const hasHealthData = computed(() => {
  return healthData.value && Object.keys(healthData.value).length > 0
})

// Load health data on mount
onMounted(async () => {
  try {
    if (props.student?.id) {
      await loadHealthData()
    } else {
      console.warn('HealthInfoCard: No student ID provided')
    }
  } catch (error) {
    console.error('Error loading health data:', error)
    error.value = 'ไม่สามารถโหลดข้อมูลสุขภาพได้: ' + error.message
  }
})

// Load health data from API
const loadHealthData = async () => {
  if (!props.student?.id) {
    console.warn('HealthInfoCard: Cannot load health data - no student ID')
    return
  }
  
  try {
    isLoading.value = true
    
    // Try to get health data for this student
    let url
    try {
      url = route('homevisit.student.health.show', props.student.id)
    } catch (routeError) {
      url = `/home-visit/student/${props.student.id}/health`
    }
    
    const response = await axios.get(url)
    const result = response.data
    
    if (result.status === 'success' && result.data) {
      healthData.value = result.data
      
      // Populate form with existing data  
      form.value = {
        height: result.data.height_cm || '',
        weight: result.data.weight_kg || '',
        allergies: result.data.allergies || '',
        chronic_diseases: result.data.chronic_diseases || '',
        medications: result.data.medications || '',
        blood_type: result.data.blood_type || '',
        rh_factor: result.data.rh_factor || ''
      }
    } else {
      // No health data exists yet
      healthData.value = null
    }
  } catch (error) {
    console.error('API Error:', error)
    // If 404, no health data exists yet (not an error)
    if (error.response && error.response.status === 404) {
      healthData.value = null
    } else {
      error.value = 'ไม่สามารถโหลดข้อมูลสุขภาพได้'
    }
  } finally {
    isLoading.value = false
  }
}

const calculateBMI = () => {
  const weight = parseFloat(form.value.weight)
  const height = parseFloat(form.value.height)
  
  if (!weight || !height || weight <= 0 || height <= 0) {
    return '-'
  }
  
  const heightInMeters = height / 100
  const bmi = weight / (heightInMeters * heightInMeters)
  
  return bmi.toFixed(1)
}

// Save health data function
const saveHealthData = async () => {
  isSaving.value = true
  
  try {
    const apiData = {
      height: form.value.height || null,
      weight: form.value.weight || null,
      allergies: form.value.allergies || null,
      chronic_diseases: form.value.chronic_diseases || null,
      medications: form.value.medications || null,
      blood_type: form.value.blood_type || null,
      rh_factor: form.value.rh_factor || null
    }

    let response
    let url
    
    if (healthData.value && healthData.value.id) {
      // Update existing health data
      try {
        url = route('homevisit.student.health.update', [props.student.id, healthData.value.id])
      } catch (routeError) {
        url = `/home-visit/student/${props.student.id}/health/${healthData.value.id}`
      }
      response = await axios.put(url, apiData)
    } else {
      // Create new health data
      try {
        url = route('homevisit.student.health.store', props.student.id)
      } catch (routeError) {
        url = `/home-visit/student/${props.student.id}/health`
      }
      response = await axios.post(url, apiData)
    }

    const result = response.data

    if (result.status === 'success' || result.success === true || response.status === 200) {
      // 1. Update local health data
      healthData.value = result.data
      
      // 2. Emit success event
      emit('save', {
        success: true,
        type: 'health_info',
        data: result.data
      })

      // 3. Wait for DOM update
      await nextTick()
      
      // 4. Reset loading state BEFORE showing alert
      isSaving.value = false
      
      // 5. Show success alert after all operations are completed
      await Swal.fire({
        title: 'สำเร็จ!',
        text: result.message || 'บันทึกข้อมูลสุขภาพเรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      return
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล')
    }
  } catch (error) {
    console.error('Save error:', error)
    
    let errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
    
    if (error.response && error.response.status === 422) {
      // Validation errors
      const data = error.response.data
      if (data.errors) {
        const fieldErrors = []
        Object.entries(data.errors).forEach(([field, messages]) => {
          fieldErrors.push(`${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
        })
        errorMessage = `Validation Errors:\n${fieldErrors.join('\n')}`
      } else if (data.message) {
        errorMessage = data.message
      }
    } else if (error.response && error.response.data) {
      errorMessage = error.response.data.message || error.response.data.error || errorMessage
    } else if (error.message) {
      errorMessage = error.message
    }
    
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด!',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'ตกลง'
    })

    emit('save', {
      success: false,
      type: 'health_info',
      message: errorMessage
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}
</script>
