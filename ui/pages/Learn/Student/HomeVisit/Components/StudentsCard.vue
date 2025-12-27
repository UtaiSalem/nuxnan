
<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
  formatDateForInput, 
  formatDateThai, 
  calculateAge 
} from '@/utils/dateUtils'

const props = defineProps({
  student: {
    type: Object,
    required: true
  },
  studentCard: {
    type: Object,
    default: () => ({})
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

// ใช้ Date Utilities แทน Helper Functions ที่เขียนเอง
// ไม่ต้องเขียน formatDateForInput, formatBirthDate, calculateAge อีกแล้ว

// Simplified: Use direct data from database since we already populated the students table

// Create reactive form data using direct database values (already populated)
const form = reactive({
  citizen_id: props.student?.citizen_id || props.studentCard?.national_id || '',
  student_id: props.student?.student_id || props.studentCard?.student_number || '',
  title_prefix_th: props.student?.title_prefix_th || props.studentCard?.title_name || '',
  first_name_th: props.student?.first_name_th || props.studentCard?.first_name_thai || '',
  last_name_th: props.student?.last_name_th || props.studentCard?.last_name_thai || '',
  middle_name_th: props.student?.middle_name_th || '',
  title_prefix_en: props.student?.title_prefix_en || '',
  first_name_en: props.student?.first_name_en || '',
  last_name_en: props.student?.last_name_en || '',
  middle_name_en: props.student?.middle_name_en || '',
  nickname: props.student?.nickname || '',
  gender: props.student?.gender !== undefined && props.student?.gender !== null ? props.student.gender : null, // 0, 1, or null
  date_of_birth: formatDateForInput(props.student?.date_of_birth || props.studentCard?.birth_date) || '',
  nationality: props.student?.nationality || 'ไทย',
  religion: props.student?.religion || '',
  profile_image: props.student?.profile_image || props.studentCard?.profile_image || '',
  status: props.student?.status || 'active',
  enrollment_date: formatDateForInput(props.student?.enrollment_date) || '',
  class_level: props.student?.class_level || props.studentCard?.class_level || '',
  class_section: props.student?.class_section || props.studentCard?.class_section || ''
})



// Computed property for current gender display
const currentGenderText = computed(() => {
  if (form.gender === null || form.gender === undefined) return ''
  
  // แปลงจากตัวเลขเป็นข้อความ: 0=หญิง, 1=ชาย
  const genderMap = {
    0: 'หญิง',
    1: 'ชาย'
  }
  
  return genderMap[form.gender] || 'ไม่ระบุ'
})

// Computed property for class display
const classDisplayText = computed(() => {
  if (!form.class_level || !form.class_section) return ''
  return `ม.${form.class_level}/${form.class_section}`
})

// Save functionality
const isSaving = ref(false)
const saveStatus = ref(null)

// Student photo management
const studentPhoto = ref(null)
const photoInput = ref(null)
const showPhotoModal = ref(false)

// Computed property to get student photo URL
const getStudentPhotoUrl = computed(() => {
  if (form.profile_image) {
    // Priority order: students table profile_image, then studentCard profile_image
    const possiblePaths = []
    
    // If profile_image from students table exists
    if (props.student?.profile_image) {
      possiblePaths.push(`/storage/images/students/${props.student?.class_level}/${props.student?.class_section}/${props.student.profile_image}`)
    }
    
    // If profile_image from studentCard exists
    if (props.studentCard?.profile_image && props.studentCard?.class_level && props.studentCard?.class_section) {
      possiblePaths.push(`/storage/images/students/${props.studentCard.class_level}/${props.studentCard.class_section}/${props.studentCard.profile_image}`)
    }
    
    return possiblePaths
  }
  return null
})

// Load student photo on component mount
onMounted(() => {
  loadStudentPhoto()
})

// Function to load student photo
const loadStudentPhoto = async () => {
  if (getStudentPhotoUrl.value) {
    for (const photoPath of getStudentPhotoUrl.value) {
      try {
        const response = await fetch(photoPath, { method: 'HEAD' })
        if (response.ok) {
          studentPhoto.value = photoPath
          break
        }
      } catch (error) {
        // Continue to next path
        continue
      }
    }
  }
}

// Function to open photo modal
const openPhotoModal = () => {
  if (studentPhoto.value) {
    showPhotoModal.value = true
  }
}

// Function to close photo modal
const closePhotoModal = () => {
  showPhotoModal.value = false
}

// Handle photo upload
const handlePhotoUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('กรุณาเลือกไฟล์รูปภาพเท่านั้น')
      return
    }
    
    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      alert('ขนาดไฟล์ต้องไม่เกิน 5MB')
      return
    }
    
    // Create preview URL
    const reader = new FileReader()
    reader.onload = (e) => {
      studentPhoto.value = e.target.result
    }
    reader.readAsDataURL(file)
    
    // Here you would typically upload the file to the server
    // uploadStudentPhoto(file)
  }
  
  // Reset input
  event.target.value = ''
}

// Handle image load error
const handleImageError = () => {
  studentPhoto.value = null
}

// Handle successful image load
const onImageLoad = () => {
  // Image loaded successfully
}

// Function to upload photo to server (placeholder)
const uploadStudentPhoto = async (file) => {
  const formData = new FormData()
  formData.append('photo', file)
  formData.append('student_id', form.student_id)
  
  try {
    // Replace with actual upload endpoint
    // const response = await fetch('/api/students/upload-photo', {
    //   method: 'POST',
    //   body: formData
    // })
    
    // Photo upload functionality would be implemented here
  } catch (error) {
    console.error('Photo upload error:', error)
    alert('เกิดข้อผิดพลาดในการอัพโหลดรูปภาพ')
  }
}

// Helper function to format citizen ID
const formatCitizenId = (citizenId) => {
  if (!citizenId) return '-'
  
  // Remove all non-digits
  const digits = citizenId.replace(/\D/g, '')
  
  // If not 13 digits, return as is
  if (digits.length !== 13) return citizenId
  
  // Format as X-XXXX-XXXXX-XX-X
  return `${digits.substring(0, 1)}-${digits.substring(1, 5)}-${digits.substring(5, 10)}-${digits.substring(10, 12)}-${digits.substring(12, 13)}`
}

// Save student data function
const saveStudentData = async () => {
  isSaving.value = true
  saveStatus.value = null
  
  try {
    // Extract only student table data with correct field names
    const studentData = {
      citizen_id: form.citizen_id,
      student_id: form.student_id,
      title_prefix_th: form.title_prefix_th,
      first_name_th: form.first_name_th,
      last_name_th: form.last_name_th,
      middle_name_th: form.middle_name_th,
      title_prefix_en: form.title_prefix_en,
      first_name_en: form.first_name_en,
      last_name_en: form.last_name_en,
      middle_name_en: form.middle_name_en,
      nickname: form.nickname,
      gender: form.gender,
      date_of_birth: form.date_of_birth,
      nationality: form.nationality,
      religion: form.religion,
      profile_image: form.profile_image,
      status: form.status,
      enrollment_date: form.enrollment_date,
      class_level: form.class_level,
      class_section: form.class_section
    }
    
    // Use Inertia.js to save data
    await router.post(route('homevisit.student.update.info'), studentData, {
      preserveScroll: true,
      onSuccess: () => {
        saveStatus.value = 'success'
        // Emit save event to parent component
        emit('save', {
          success: true,
          type: 'students',
          data: studentData
        })
        setTimeout(() => {
          saveStatus.value = null
        }, 3000)
      },
      onError: (errors) => {
        saveStatus.value = 'error'
        // Emit save event with error to parent component
        emit('save', {
          success: false,
          type: 'students',
          message: Object.values(errors).join(', ')
        })
        console.error('Save errors:', errors)
      }
    })
    
  } catch (error) {
    saveStatus.value = 'error'
    console.error('Save error:', error)
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-2xl animate-fade-in">
    <!-- Modern Gradient Header -->
    <div class="bg-gradient-to-br from-emerald-500 via-green-500 to-teal-600 px-6 py-5 relative overflow-hidden">
      <!-- Animated Background Pattern -->
      <div class="absolute inset-0 opacity-10">
        <div class="absolute -top-4 -right-4 w-24 h-24 bg-white rounded-full animate-blob"></div>
        <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-white rounded-full animate-blob animation-delay-2000"></div>
      </div>
      
      <div class="relative z-10 flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-12 h-12 bg-white bg-opacity-20 backdrop-blur-sm rounded-xl flex items-center justify-center flex-shrink-0 animate-pulse-soft">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-xl font-bold text-white">
              ข้อมูลพื้นฐานนักเรียน
            </h3>
            <p class="text-emerald-100 text-sm mt-0.5">ข้อมูลส่วนตัวและประวัติ</p>
          </div>
        </div>
        <div class="hidden md:flex items-center space-x-2">
          <span class="px-3 py-1.5 bg-white bg-opacity-20 backdrop-blur-sm rounded-lg text-white text-sm font-medium">
            <i class="fas fa-user-graduate mr-1.5"></i>
            นักเรียน
          </span>
        </div>
      </div>
    </div>

    <!-- Form Content -->
    <form @submit.prevent="saveStudentData" class="p-6 space-y-8">
      
      <!-- Student Photo Section with Enhanced Design -->
      <div class="bg-gradient-to-br from-gray-50 to-emerald-50/30 rounded-2xl p-6 border-2 border-emerald-100 transition-all duration-300 hover:border-emerald-300">
        <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
          <!-- Photo Display -->
          <div class="flex-shrink-0 relative group">
            <div class="w-32 h-40 md:w-36 md:h-48 bg-gradient-to-br from-gray-200 to-gray-300 rounded-2xl overflow-hidden border-4 border-white shadow-2xl transition-all duration-300 group-hover:shadow-emerald-500/50 group-hover:scale-105" 
              :class="{ 'cursor-pointer': studentPhoto }" 
              @click="openPhotoModal">
              <img
                v-if="studentPhoto"
                :src="studentPhoto"
                :alt="`${form.first_name_th} ${form.last_name_th}`"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                @load="onImageLoad"
                @error="handleImageError"
              />
              <div v-else class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200">
                <svg class="w-16 h-16 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-xs text-gray-500 font-medium">ไม่มีรูปภาพ</span>
              </div>
              <!-- Overlay on hover -->
              <div v-if="studentPhoto" class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-300 flex items-center justify-center">
                <i class="fas fa-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
              </div>
            </div>
            <!-- Photo Badge -->
            <div class="absolute -bottom-2 -right-2 bg-gradient-to-r from-emerald-500 to-green-500 text-white rounded-full p-2 shadow-lg">
              <i class="fas fa-camera text-sm"></i>
            </div>
          </div>

          <!-- Student Info -->
          <div class="flex-1 space-y-4 text-center md:text-left">
            <div>
              <h4 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-green-600 bg-clip-text text-transparent">
                {{ form.title_prefix_th }} {{ form.first_name_th }} {{ form.last_name_th }}
              </h4>
              <div class="flex flex-wrap items-center justify-center md:justify-start gap-3 mt-3">
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                  <i class="fas fa-id-card mr-2"></i>
                  {{ form.student_id }}
                </span>
                <span v-if="classDisplayText" class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800 border border-green-200">
                  <i class="fas fa-school mr-2"></i>
                  ชั้น {{ classDisplayText }}
                </span>
                <span v-if="form.nickname" class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 border border-blue-200">
                  <i class="fas fa-smile mr-2"></i>
                  {{ form.nickname }}
                </span>
              </div>
            </div>

            <!-- Upload Photo Button -->
            <div class="pt-2">
              <input
                ref="photoInput"
                type="file"
                accept="image/*"
                @change="handlePhotoUpload"
                class="hidden"
              />
              <button
                type="button"
                @click="$refs.photoInput.click()"
                class="group inline-flex items-center px-5 py-2.5 border-2 border-emerald-300 text-sm font-semibold rounded-xl text-emerald-700 bg-white hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200 hover:scale-105 hover:shadow-lg"
              >
                <i class="fas fa-camera mr-2 group-hover:rotate-12 transition-transform"></i>
                เปลี่ยนรูปภาพ
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Names Section with Enhanced Design -->
      <div class="space-y-6">
        <div class="flex items-center space-x-3 mb-6">
          <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-green-500 rounded-xl flex items-center justify-center shadow-lg">
            <i class="fas fa-signature text-white text-lg"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900">ชื่อ-นามสกุล</h4>
            <p class="text-sm text-gray-500">กรอกข้อมูลชื่อภาษาไทยและอังกฤษ</p>
          </div>
        </div>

        <!-- Thai Names Section -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border-2 border-blue-100 transition-all duration-300 hover:border-blue-300">
          <div class="flex items-center mb-5">
            <div class="flex items-center space-x-2">
              <span class="bg-blue-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md">TH</span>
              <span class="text-base font-semibold text-blue-900">ชื่อภาษาไทย</span>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Title Prefix Thai -->
            <div class="form-group">
              <label class="form-label group">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user-tag text-blue-500 group-hover:scale-110 transition-transform"></i>
                  <span>คำนำหน้า</span>
                </div>
              </label>
              <select
                v-model="form.title_prefix_th"
                class="form-select"
                required
              >
                <option value="">-- เลือกคำนำหน้า --</option>
                <option value="เด็กชาย">เด็กชาย</option>
                <option value="เด็กหญิง">เด็กหญิง</option>
                <option value="นาย">นาย</option>
                <option value="นางสาว">นางสาว</option>
                <option value="นาง">นาง</option>
              </select>
            </div>

            <!-- First Name Thai -->
            <div class="form-group">
              <label class="form-label group">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user text-blue-500 group-hover:scale-110 transition-transform"></i>
                  <span>ชื่อ</span>
                </div>
              </label>
              <input
                type="text"
                v-model="form.first_name_th"
                class="form-input"
                placeholder="ชื่อภาษาไทย"
                required
              >
            </div>

            <!-- Last Name Thai -->
            <div class="form-group">
              <label class="form-label group">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user text-blue-500 group-hover:scale-110 transition-transform"></i>
                  <span>นามสกุล</span>
                </div>
              </label>
              <input
                type="text"
                v-model="form.last_name_th"
                class="form-input"
                placeholder="นามสกุลภาษาไทย"
                required
              >
            </div>
          </div>
        </div>

        <!-- English Names Section -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border-2 border-green-100 transition-all duration-300 hover:border-green-300">
          <div class="flex items-center mb-5">
            <div class="flex items-center space-x-2">
              <span class="bg-green-500 text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-md">EN</span>
              <span class="text-base font-semibold text-green-900">ชื่อภาษาอังกฤษ</span>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Title Prefix English -->
            <div class="form-group">
              <label class="form-label group">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user-tag text-green-500 group-hover:scale-110 transition-transform"></i>
                  <span>Title</span>
                </div>
              </label>
              <select
                v-model="form.title_prefix_en"
                class="form-select"
              >
                <option value="">-- Select Title --</option>
                <option value="Master">Master</option>
                <option value="Miss">Miss</option>
                <option value="Mr.">Mr.</option>
                <option value="Mrs.">Mrs.</option>
                <option value="Ms.">Ms.</option>
              </select>
            </div>

            <!-- First Name English -->
            <div class="form-group">
              <label class="form-label group">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user text-green-500 group-hover:scale-110 transition-transform"></i>
                  <span>First Name</span>
                </div>
              </label>
              <input
                type="text"
                v-model="form.first_name_en"
                class="form-input"
                placeholder="First Name"
              >
            </div>

            <!-- Last Name English -->
            <div class="form-group">
              <label class="form-label group">
                <div class="flex items-center space-x-2">
                  <i class="fas fa-user text-green-500 group-hover:scale-110 transition-transform"></i>
                  <span>Last Name</span>
                </div>
              </label>
              <input
                type="text"
                v-model="form.last_name_en"
                class="form-input"
                placeholder="Last Name"
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Personal Information Section -->
      <div class="space-y-6">
        <div class="flex items-center space-x-3 mb-6">
          <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
            <i class="fas fa-info-circle text-white text-lg"></i>
          </div>
          <div>
            <h4 class="text-lg font-bold text-gray-900">ข้อมูลส่วนตัว</h4>
            <p class="text-sm text-gray-500">รายละเอียดเพิ่มเติมของนักเรียน</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
          <!-- Citizen ID Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-id-card text-indigo-500 group-hover:scale-110 transition-transform"></i>
                <span>เลขประจำตัวประชาชน</span>
              </div>
            </label>
            <input
              type="text"
              :value="formatCitizenId(form.citizen_id)"
              class="form-input bg-gray-50 cursor-not-allowed"
              readonly
            >
          </div>

          <!-- Student ID Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-barcode text-purple-500 group-hover:scale-110 transition-transform"></i>
                <span>รหัสนักเรียน</span>
              </div>
            </label>
            <input
              type="text"
              v-model="form.student_id"
              class="form-input bg-gray-50 cursor-not-allowed"
              readonly
            >
          </div>

          <!-- Nickname Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-smile text-yellow-500 group-hover:scale-110 transition-transform"></i>
                <span>ชื่อเล่น</span>
              </div>
            </label>
            <input
              type="text"
              v-model="form.nickname"
              class="form-input"
              placeholder="กรอกชื่อเล่น"
            >
          </div>

          <!-- Gender Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-venus-mars text-pink-500 group-hover:scale-110 transition-transform"></i>
                <span>เพศ</span>
              </div>
            </label>
            <select v-model.number="form.gender" class="form-select" required>
              <option :value="null" disabled>-- เลือกเพศ --</option>
              <option :value="1">ชาย</option>
              <option :value="0">หญิง</option>
            </select>
          </div>

          <!-- Date of Birth Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-birthday-cake text-red-500 group-hover:scale-110 transition-transform"></i>
                <span>วันเกิด</span>
              </div>
            </label>
            <input
              type="date"
              v-model="form.date_of_birth"
              class="form-input"
              required
            >
            <div v-if="form.date_of_birth" class="mt-2 flex items-center justify-between text-xs bg-gradient-to-r from-blue-50 to-purple-50 px-3 py-2 rounded-lg border border-blue-100">
              <span class="flex items-center text-blue-700">
                <i class="far fa-calendar-alt mr-1.5"></i>
                {{ formatDateThai(form.date_of_birth) }}
              </span>
              <span class="flex items-center text-purple-700 font-semibold">
                <i class="fas fa-hourglass-half mr-1.5"></i>
                {{ calculateAge(form.date_of_birth) }} ปี
              </span>
            </div>
          </div>

          <!-- Nationality Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-flag text-blue-500 group-hover:scale-110 transition-transform"></i>
                <span>สัญชาติ</span>
              </div>
            </label>
            <input
              type="text"
              v-model="form.nationality"
              class="form-input"
              placeholder="กรอกสัญชาติ"
            >
          </div>

          <!-- Religion Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-praying-hands text-orange-500 group-hover:scale-110 transition-transform"></i>
                <span>ศาสนา</span>
              </div>
            </label>
            <input
              type="text"
              v-model="form.religion"
              class="form-input"
              placeholder="กรอกศาสนา"
            >
          </div>

          <!-- Class Level Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-graduation-cap text-emerald-500 group-hover:scale-110 transition-transform"></i>
                <span>ระดับชั้น (ม.)</span>
              </div>
            </label>
            <input
              type="text"
              v-model="form.class_level"
              class="form-input bg-gray-50 cursor-not-allowed"
              readonly
            >
          </div>

          <!-- Class Section Field -->
          <div class="form-group">
            <label class="form-label group">
              <div class="flex items-center space-x-2">
                <i class="fas fa-door-open text-teal-500 group-hover:scale-110 transition-transform"></i>
                <span>ห้องเรียน (/)</span>
              </div>
            </label>
            <input
              type="text"
              v-model="form.class_section"
              class="form-input bg-gray-50 cursor-not-allowed"
              readonly
            >
          </div>
        </div>
      </div>

      <!-- Meta Information -->
      <div class="pt-6 border-t-2 border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap gap-2 text-xs">
            <div class="flex items-center bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-2 rounded-lg border border-gray-200 shadow-sm">
              <i class="fas fa-clock text-gray-500 mr-2"></i>
              <span class="text-gray-700">อัปเดต: {{ new Date().toLocaleDateString('th-TH') }} {{ new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="pt-6 border-t-2 border-gray-200">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
          <!-- Status Messages -->
          <div class="flex-1">
            <transition name="fade">
              <div v-if="isSaving" class="flex items-center text-sm text-blue-600 bg-blue-50 px-4 py-3 rounded-xl border border-blue-200 animate-pulse">
                <svg class="animate-spin h-5 w-5 mr-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="font-medium">กำลังบันทึกข้อมูล...</span>
              </div>
              <div v-else-if="saveStatus === 'success'" class="flex items-center text-sm text-green-700 bg-gradient-to-r from-green-50 to-emerald-50 px-4 py-3 rounded-xl border border-green-200 shadow-sm">
                <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center mr-3">
                  <i class="fas fa-check text-white text-xs"></i>
                </div>
                <span class="font-semibold">บันทึกข้อมูลสำเร็จ!</span>
              </div>
              <div v-else-if="saveStatus === 'error'" class="flex items-center text-sm text-red-700 bg-gradient-to-r from-red-50 to-pink-50 px-4 py-3 rounded-xl border border-red-200 shadow-sm">
                <div class="w-5 h-5 bg-red-500 rounded-full flex items-center justify-center mr-3">
                  <i class="fas fa-times text-white text-xs"></i>
                </div>
                <span class="font-semibold">เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง</span>
              </div>
              <div v-else class="text-sm text-gray-500 px-4 py-3">
                <i class="fas fa-info-circle mr-2"></i>
                กรุณาตรวจสอบข้อมูลก่อนบันทึก
              </div>
            </transition>
          </div>

          <!-- Save Button -->
          <button
            type="submit"
            :disabled="isSaving"
            class="group relative inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-emerald-600 to-green-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-green-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 hover:scale-105 hover:shadow-2xl overflow-hidden"
          >
            <!-- Animated Background -->
            <div class="absolute inset-0 bg-gradient-to-r from-green-600 to-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            
            <!-- Button Content -->
            <div class="relative flex items-center">
              <svg v-if="!isSaving" class="w-5 h-5 mr-3 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m0 0h8m0 0h2a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4 0V5a2 2 0 011-1h4a2 2 0 011 1v2M8 7V5a2 2 0 011-1h4a2 2 0 011 1v2m0 0v4m0 0h-4m4 0V9H8v4z"></path>
              </svg>
              <svg v-else class="animate-spin h-5 w-5 mr-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-base">{{ isSaving ? 'กำลังบันทึก...' : 'บันทึกข้อมูล' }}</span>
            </div>
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Photo Modal -->
  <div v-if="showPhotoModal" class="fixed inset-0 z-50 overflow-y-auto" @click="closePhotoModal">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-black bg-opacity-75" @click="closePhotoModal"></div>

      <!-- Modal content -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full" @click.stop>
        <!-- Header -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex items-center justify-between border-b">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            รูปภาพ {{ form.title_prefix_th }} {{ form.first_name_th }} {{ form.last_name_th }}
          </h3>
          <button @click="closePhotoModal" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition ease-in-out duration-150">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Photo content -->
        <div class="bg-white px-4 py-6 sm:px-6">
          <div class="flex justify-center">
            <img
              :src="studentPhoto"
              :alt="`${form.first_name_th} ${form.last_name_th}`"
              class="max-w-full max-h-96 object-contain rounded-lg shadow-lg"
            />
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t">
          <button
            @click="closePhotoModal"
            type="button"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-600 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
          >
            ปิด
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes blob {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25% { transform: translate(20px, -20px) scale(1.1); }
  50% { transform: translate(-20px, 20px) scale(0.9); }
  75% { transform: translate(20px, 20px) scale(1.05); }
}

@keyframes pulse-soft {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.8; }
}

.animate-fade-in {
  animation: fadeIn 0.6s ease-out;
}

.animate-blob {
  animation: blob 7s infinite;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animate-pulse-soft {
  animation: pulse-soft 3s ease-in-out infinite;
}

/* Form Elements */
.form-group {
  @apply space-y-2;
}

.form-label {
  @apply text-sm font-semibold text-gray-700 flex items-center transition-colors duration-200;
}

.form-label:hover {
  @apply text-emerald-600;
}

.form-input {
  @apply w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-sm placeholder-gray-400 hover:border-emerald-300;
}

.form-input:focus {
  @apply shadow-lg shadow-emerald-100;
}

.form-select {
  @apply w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-sm hover:border-emerald-300 bg-white cursor-pointer;
}

.form-select:focus {
  @apply shadow-lg shadow-emerald-100;
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateX(-10px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb {
  background: linear-gradient(to bottom, #10b981, #059669);
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(to bottom, #059669, #047857);
}
</style>



