<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref, onMounted, onUnmounted } from 'vue'
import {
  StudentsCard,
  AcademicInfoCard,
  AddressCard,
  ContactCard,
  HealthInfoCard,
  GuardianCard,
  DocumentsCard
} from '../Components'

import ShowStudentCard from '../../Card/StudentCard.vue'

// Define props with validation
const props = defineProps({
  student: {
    type: Object,
    required: true,
    validator: (value) => value && typeof value === 'object'
  },
  studentCard: {
    type: Object,
    default: () => ({})
  },
  homeVisits: {
    type: Array,
    default: () => []
  }
})

// Reactive states
const isSubmitting = ref(false)
const lastSaved = ref(null)
const saveMessage = ref('')

// Computed properties for cleaner data access
const studentData = computed(() => props.student || {})
const primaryAddress = computed(() => studentData.value.addresses?.[0] || {})
const primaryContact = computed(() => studentData.value.contacts?.[0] || {})
const primaryGuardian = computed(() => studentData.value.guardians?.[0] || {})
const guardianContact = computed(() => primaryGuardian.value.contacts?.[0] || {})
const academicInfo = computed(() => studentData.value.academic_info || {})
const healthInfo = computed(() => studentData.value.health_info || {})

// This component serves as a coordinator/container for individual card components
// Each card component has its own form and handles its own data management
// This parent component only handles:
// 1. Passing student data to child components
// 2. Receiving save events from child components  
// 3. Showing feedback messages
// 4. Managing loading states

// Component event handlers - Handle individual card saves
const handleCardSave = async (cardType, result) => {
  try {
    isSubmitting.value = true
    
    // Get card name for display
    const cardNames = {
      student: 'ข้อมูลนักเรียน',
      academic: 'ข้อมูลการศึกษา', 
      address: 'ที่อยู่',
      contact: 'ข้อมูลติดต่อ',
      health: 'ข้อมูลสุขภาพ',
      guardian: 'ข้อมูลผู้ปกครอง'
      // documents: 'เอกสาร' // Disabled for future development
    }
    
    const cardName = cardNames[cardType] || cardType
    
    if (result.success) {
      lastSaved.value = new Date()
      saveMessage.value = `บันทึก${cardName}สำเร็จ`
      setTimeout(() => { saveMessage.value = '' }, 3000)
    } else {
      saveMessage.value = `เกิดข้อผิดพลาดในการบันทึก${cardName}: ${result.message || 'ไม่ทราบสาเหตุ'}`
    }
  } catch (error) {
    saveMessage.value = `เกิดข้อผิดพลาดที่ไม่คาดคิด: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}

// Handle bulk save (if needed) - triggers save on all components
const saveAllData = async () => {
  if (isSubmitting.value) return
  
  try {
    isSubmitting.value = true
    saveMessage.value = 'กำลังบันทึกข้อมูลทั้งหมด...'
    
    // This would trigger save events on all child components
    // Implementation depends on how child components expose their save methods
    // For now, show message that individual saves should be used
    
    setTimeout(() => {
      saveMessage.value = 'กรุณาใช้ปุ่มบันทึกในแต่ละการ์ดเพื่อบันทึกข้อมูล'
      setTimeout(() => { saveMessage.value = '' }, 3000)
    }, 1000)
    
  } catch (error) {
    saveMessage.value = `เกิดข้อผิดพลาดที่ไม่คาดคิด: ${error.message}`
  } finally {
    isSubmitting.value = false
  }
}

// Enhanced logout handler
const logout = async () => {
  try {
    await router.post(route('homevisit.general.logout'))
  } catch (error) {
    console.error('Logout error:', error)
    // Fallback: force redirect to login
    window.location.href = route('homevisit.login')
  }
}

// Computed properties for better performance
const fullName = computed(() => {
  const first = studentData.value.first_name_th || ''
  const last = studentData.value.last_name_th || ''
  return `${first} ${last}`.trim()
})

const userInitial = computed(() => {
  return studentData.value.first_name_th?.charAt(0) || 'S'
})

const hasHomeVisits = computed(() => {
  return props.homeVisits && props.homeVisits.length > 0
})

const canSubmit = computed(() => {
  return !isSubmitting.value
})

// Memoized helper functions
const statusTextMap = new Map([
  ['pending', 'รอดำเนินการ'],
  ['completed', 'เสร็จสิ้น'],
  ['cancelled', 'ยกเลิก']
])

const getStatusText = (status) => {
  return statusTextMap.get(status) || status
}

const formatDate = (date) => {
  if (!date) return ''
  
  // Use cached formatter for better performance
  if (!formatDate._formatter) {
    formatDate._formatter = new Intl.DateTimeFormat('th-TH', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
  
  try {
    return formatDate._formatter.format(new Date(date))
  } catch (error) {
    console.warn('Date formatting error:', error)
    return date
  }
}

// Lifecycle hooks for cleanup
onMounted(() => {
  // Initialize any required data or listeners
  if (lastSaved.value) {
    saveMessage.value = `ข้อมูลล่าสุดถูกบันทึกเมื่อ ${formatDate(lastSaved.value)}`
  }
})

onUnmounted(() => {
  // Cleanup any pending operations
  isSubmitting.value = false
  saveMessage.value = ''
})
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
    <Head title="ข้อมูลส่วนตัว - ระบบเยี่ยมบ้านนักเรียน" />
    
    <!-- Navigation Header - Fully Responsive -->
    <nav class="bg-white shadow-lg border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-8">
        <div class="flex justify-between items-center h-14 sm:h-16">
          <!-- Left Section -->
          <div class="flex items-center min-w-0 flex-1">
            <!-- Logo/Title -->
            <div class="flex items-center">
              <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2v0a2 2 0 002-2h10" />
                </svg>
              </div>
              <div class="ml-2 sm:ml-3 min-w-0 flex-1">
                <h1 class="text-sm sm:text-base lg:text-xl font-bold text-gray-900 truncate">
                  ระบบเยี่ยมบ้าน
                </h1>
                <p class="text-xs sm:text-sm text-gray-500 truncate sm:hidden">
                  ข้อมูลส่วนตัว
                </p>
              </div>
            </div>
          </div>

          <!-- Right Section -->
          <div class="flex items-center space-x-2 sm:space-x-4">
            <!-- User Info -->
            <div class="flex items-center space-x-2">
              <div class="w-6 h-6 sm:w-8 sm:h-8 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs sm:text-sm font-medium">
                  {{ userInitial }}
                </span>
              </div>
              <div class="hidden sm:block text-right min-w-0">
                <p class="text-xs sm:text-sm font-medium text-gray-900 truncate max-w-24 sm:max-w-32 lg:max-w-none">
                  {{ fullName }}
                </p>
                <p class="text-xs text-gray-500">นักเรียน</p>
              </div>
            </div>
            
            <!-- Logout Button -->
            <button
              @click="logout"
              class="inline-flex items-center px-2 py-1.5 sm:px-4 sm:py-2 border border-transparent text-xs sm:text-sm font-medium rounded-lg text-white bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-md hover:shadow-lg"
            >
              <svg class="w-3 h-3 sm:w-4 sm:h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span class="hidden sm:inline">ออกจากระบบ</span>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Container - Fully Responsive -->
    <div class="max-w-7xl mx-auto pb-4 sm:pb-6 lg:py-8 px-3 sm:px-4 lg:px-8">



      <!-- Page Header - Mobile Optimized -->
      <div class="mb-6 sm:mb-8">
        <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-6 sm:px-6 sm:py-8">
            <div class="flex flex-col sm:flex-row sm:items-center space-y-4 sm:space-y-0">
              <!-- Icon -->
              <div class="w-12 h-12 sm:w-16 sm:h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 sm:w-8 sm:h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <!-- Content -->
              <div class="sm:ml-6">
                <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-white">
                  ข้อมูลส่วนตัวนักเรียน
                </h1>
                <p class="text-blue-100 mt-1 text-sm sm:text-base">
                  จัดการและแก้ไขข้อมูลส่วนตัวของคุณ
                </p>
                <!-- Mobile Breadcrumb -->
                <div class="mt-3 sm:hidden">
                  <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1">
                      <li class="inline-flex items-center">
                        <span class="text-xs font-medium text-blue-200">หน้าหลัก</span>
                      </li>
                      <li>
                        <div class="flex items-center">
                          <svg class="w-3 h-3 text-blue-200" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                          </svg>
                          <span class="ml-1 text-xs font-medium text-white">ข้อมูลส่วนตัว</span>
                        </div>
                      </li>
                    </ol>
                  </nav>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success/Error Message Display -->
      <div v-if="saveMessage" 
           class="mb-6 p-4 rounded-lg border-l-4 transition-all duration-300"
           :class="{
             'bg-green-50 border-green-400 text-green-700': saveMessage.includes('สำเร็จ'),
             'bg-red-50 border-red-400 text-red-700': saveMessage.includes('ข้อผิดพลาด'),
             'bg-blue-50 border-blue-400 text-blue-700': saveMessage.includes('กำลัง')
           }">
        <div class="flex items-center">
          <svg v-if="saveMessage.includes('สำเร็จ')" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <svg v-else-if="saveMessage.includes('ข้อผิดพลาด')" class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <svg v-else class="w-5 h-5 mr-2 animate-spin" fill="currentColor" viewBox="0 0 20 20">
            <path d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
          </svg>
          <span class="font-medium">{{ saveMessage }}</span>
        </div>
      </div>

      <!-- Main Content - Card Components without parent form -->
      <div class="space-y-4 sm:space-y-6">
        
        <!-- Students Card Component - Has its own form -->
        <StudentsCard 
          :student="student"
          :student-card="studentCard"
          @save="(result) => handleCardSave('student', result)"
          :is-loading="isSubmitting"
        />
        
        <!-- Academic Info Card Component - Uses API from student_academic_info -->
        <AcademicInfoCard 
          :student="student"
          @save="(result) => handleCardSave('academic_info', result)"
          :is-loading="isSubmitting"
        />
        
        <!-- Address Card Component - Has its own form -->
        <AddressCard 
          :student="student"
          @save="(result) => handleCardSave('address', result)"
          :is-loading="isSubmitting"
        />
        
        <!-- Contact Card Component - Has its own form -->
        <ContactCard 
          :student="student"
          @save="(result) => handleCardSave('contact', result)"
          :is-loading="isSubmitting"
        />
        
        <!-- Health Info Card Component - Has its own form -->
        <HealthInfoCard 
          :student="student"
          @save="(result) => handleCardSave('health', result)"
          :is-loading="isSubmitting"
        />
        
        <!-- Guardian Card Component - Has its own form -->
        <GuardianCard 
          :student="student"
          @save="(result) => handleCardSave('guardian', result)"
          :is-loading="isSubmitting"
        />
        
        <!-- Documents Card Component - Temporarily disabled for future development -->
        <!-- 
        <DocumentsCard 
          :student="student"
          @save="(result) => handleCardSave('documents', result)"
          :is-loading="isSubmitting"
        />
        -->

        <!-- Optional Bulk Save Button - Triggers individual component saves -->
        <!-- <div class="flex justify-center pt-4 sm:pt-6">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-4">
              ใช้ปุ่ม "บันทึก" ในแต่ละการ์ดเพื่อบันทึกข้อมูลแยกตามส่วน
            </p>
            <button
              @click="saveAllData"
              :disabled="!canSubmit"
              class="inline-flex items-center justify-center px-6 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md"
            >
              <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              <span class="truncate">
                {{ isSubmitting ? 'กำลังดำเนินการ...' : 'คำแนะนำการบันทึก' }}
              </span>
            </button>
          </div>
        </div> -->
      </div>

      <!-- Home Visits History - Enhanced with lazy loading -->
      <div v-if="hasHomeVisits" class="mt-8 sm:mt-12 bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-4 py-3 sm:px-6 sm:py-4">
          <div class="flex items-center">
            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
            <div class="ml-2 sm:ml-3 min-w-0 flex-1">
              <h3 class="text-base sm:text-lg font-semibold text-white truncate">
                ประวัติการเยี่ยมบ้าน
              </h3>
              <p class="text-indigo-100 text-xs sm:text-sm truncate">รายการการเยี่ยมบ้านของนักเรียน</p>
            </div>
          </div>
        </div>
        <div class="p-4 sm:p-6">
          <div class="space-y-4">
            <div
              v-for="visit in homeVisits"
              :key="visit.id"
              class="bg-gray-50 rounded-lg p-4 border border-gray-200"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="font-medium text-gray-900">{{ formatDate(visit.visit_date) }}</h4>
                  <p class="text-sm text-gray-600">{{ visit.purpose || 'เยี่ยมบ้าน' }}</p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  {{ getStatusText(visit.status) }}
                </span>
              </div>
              <div v-if="visit.notes" class="mt-2">
                <p class="text-sm text-gray-700">{{ visit.notes }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


