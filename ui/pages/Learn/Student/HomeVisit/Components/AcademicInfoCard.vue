<script setup>
import { ref, onMounted, nextTick, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'
import { useStudentRoutes } from '../Composables/useStudentRoutes'

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

// Use route composable for dynamic routes  
const { academicInfoRoutes } = useStudentRoutes(props.context)

// Reactive state
const academicInfo = ref(null)
const academicRecords = ref([])
const isSaving = ref(false)
const showModal = ref(false)
const modalMode = ref('add') // 'add' or 'edit'
const editingRecord = ref(null)
const editingIndex = ref(-1)
const saveStatus = ref(null)
const error = ref(null)
let saveTimeout = null

// Form data for modal
const formData = ref({
  academic_year: '',
  class_level: '',
  education_level: 2, // ค่าเริ่มต้นเป็นมัธยมศึกษา (2)
  class_section: '',
  school_name: 'โรงเรียนจริยธรรมศึกษามูลนิธิ',
  school_address: 'หมู่ 8 ตำบลสะกอม อำเภอจะนะ',
  school_province: 'สงขลา',
  enrollment_date: '',
  graduation_date: '',
  status: 'studying',
  transfer_reason: '',
  notes: '',
  isCurrent: false
})



// Initialize academic records on component mount
onMounted(async () => {
  try {
    if (props.student?.id) {
      await loadAcademicInfoFromAPI()
    } else {
      console.warn('AcademicInfoCard: No student ID provided')
    }
  } catch (error) {
    if (error.message.includes('CSRF') || error.message.includes('token')) {
      error.value = 'ไม่พบ CSRF token กรุณาโหลดหน้าใหม่'
    } else {
      error.value = 'ไม่สามารถโหลดข้อมูลได้: ' + error.message
    }
  }
})

// Watch for changes in formData.class_level
watch(() => formData.value.class_level, (newValue, oldValue) => {
  // กำหนดระดับการศึกษาอัตโนมัติ
  if (newValue && newValue !== oldValue) {
    const detectedLevel = detectEducationLevel(newValue)
    if (formData.value.education_level !== detectedLevel) {
      formData.value.education_level = detectedLevel
    }
  }
})

// Load academic info from API
const loadAcademicInfoFromAPI = async () => {
  if (!props.student?.id) {
    console.warn('AcademicInfoCard: Cannot load academic info - no student ID')
    return
  }
  
  try {
    // ใช้ axios แทน fetch เพราะมี CSRF token setup แล้ว
    const response = await axios.get(route('homevisit.student.academic-info.index', props.student.id))
    const result = response.data
    
    if (result.success && result.data && result.data.length > 0) {
      // Convert API data to component format - multiple records
      academicRecords.value = result.data.map(record => ({
        id: record.id,
        academic_year: record.academic_year || getCurrentAcademicYear(),
        class_level: record.current_grade || '',
        education_level: record.education_level || '',
        class_section: record.current_class || '',
        student_number: extractStudentNumber(props.student.student_id),
        enrollment_date: formatDateForInput(record.enrollment_date) || formatDateForInput(props.student.enrollment_date),
        graduation_date: formatDateForInput(record.graduation_date),
        school_name: record.school_name || 'โรงเรียนจริยธรรมศึกษามูลนิธิ',
        school_address: record.school_address || 'หมู่ 8 ตำบลสะกอม อำเภอจะนะ',
        school_province: record.school_province || 'สงขลา',
        status: record.study_status || 'studying',
        isCurrent: record.is_current || false,
        transfer_reason: record.transfer_reason || '',
        notes: record.notes || '',
        isFromAPI: true
      }))
      
      // Set current academic info (for backward compatibility)
      const currentRecord = result.data.find(r => r.is_current) || result.data[0]
      academicInfo.value = currentRecord
    } else {
      // No data, show empty state
      academicRecords.value = []
    }
  } catch (error) {
    throw error
  }
}

// Helper functions
const getCurrentAcademicYear = () => {
  const now = new Date()
  const buddhistYear = now.getFullYear() + 543
  
  // ปีการศึกษาเริ่ม พ.ค. ของปีก่อนหน้า
  if (now.getMonth() < 4) { // มกราคม-เมษายน = ภาคเรียนที่ 2
    return String(buddhistYear - 1)
  }
  return String(buddhistYear) // พฤษภาคม-ธันวาคม = ภาคเรียนที่ 1 ปีใหม่
}

const extractStudentNumber = (studentId) => {
  // สกัดเลขที่นักเรียนจาก student_id (เช่น ถ้า student_id = "2567001234" ให้ดึง "34")
  if (!studentId) return ''
  
  const idStr = studentId.toString()
  // ดึง 2 หลักสุดท้าย หรือหลักสุดท้ายถ้าเป็นเลขเดียว
  if (idStr.length >= 2) {
    const lastTwo = idStr.slice(-2)
    return parseInt(lastTwo).toString() // ลบ leading zero
  }
  return idStr
}

const formatDateForInput = (dateValue) => {
  if (!dateValue) return ''
  
  try {
    const date = new Date(dateValue)
    if (isNaN(date.getTime())) return ''
    
    // แปลงเป็นรูปแบบ yyyy-MM-dd สำหรับ HTML date input
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    
    return `${year}-${month}-${day}`
  } catch (error) {
    return ''
  }
}



// Delete record using API
const deleteRecord = async (index) => {
  const record = academicRecords.value[index]
  
  if (!record) return
  
  // If it's a new record (not saved), just remove from array
  if (record.isNew || !record.id) {
    academicRecords.value.splice(index, 1)
    return
  }
  
  // Confirm deletion with SweetAlert2
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: 'คุณต้องการลบข้อมูลประวัติการศึกษานี้หรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก'
  })

  if (!result.isConfirmed) {
    return
  }
  
  isSaving.value = true
  saveStatus.value = null
  
  try {
    const response = await axios.delete(route('homevisit.student.academic-info.destroy', [props.student.id, record.id]))
    const result = response.data

    if (result.success) {
      // Remove from local array
      academicRecords.value.splice(index, 1)
      academicInfo.value = null

      // Show success message
      await Swal.fire({
        title: 'ลบสำเร็จ!',
        text: 'ลบข้อมูลประวัติการศึกษาเรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      // Emit delete event to parent component
      emit('save', {
        success: true,
        type: 'academic_info_deleted',
        data: record
      })
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล')
    }

  } catch (error) {
    // ตรวจสอบว่าเป็น CSRF token mismatch หรือไม่
    if (error.message.includes('CSRF') || error.message.includes('token')) {
      await Swal.fire({
        title: 'CSRF Token หมดอายุ!',
        text: 'กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง',
        icon: 'warning',
        confirmButtonText: 'ตกลง'
      })
    } else {
      // Show error message
      await Swal.fire({
        title: 'เกิดข้อผิดพลาด!',
        text: error.message,
        icon: 'error',
        confirmButtonText: 'ตกลง'
      })
    }

    // Emit error event to parent component
    emit('save', {
      success: false,
      type: 'academic_info_deleted',
      message: error.message
    })
  } finally {
    isSaving.value = false
  }
}

// Set record as current
const setAsCurrent = async (record, index) => {
  if (!record.id) return
  
  isSaving.value = true
  saveStatus.value = null
  
  try {
    const response = await axios.put(route('homevisit.student.academic-info.set-current', [props.student.id, record.id]))
    const result = response.data

    if (result.success) {
      // Update local state
      academicRecords.value.forEach(r => r.isCurrent = false)
      record.isCurrent = true
      academicInfo.value = result.data

      // Show success message
      await Swal.fire({
        title: 'สำเร็จ!',
        text: 'ตั้งค่าเป็นข้อมูลปัจจุบันเรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      // Emit success event
      emit('save', {
        success: true,
        type: 'academic_info_set_current',
        data: result.data
      })
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการตั้งค่า')
    }

  } catch (error) {
    // ตรวจสอบว่าเป็น CSRF token mismatch หรือไม่
    if (error.message.includes('CSRF') || error.message.includes('token')) {
      await Swal.fire({
        title: 'CSRF Token หมดอายุ!',
        text: 'กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง',
        icon: 'warning',
        confirmButtonText: 'โหลดหน้าใหม่'
      }).then(() => window.location.reload())
    } else {
      // Show error message
      await Swal.fire({
        title: 'เกิดข้อผิดพลาด!',
        text: error.message,
        icon: 'error',
        confirmButtonText: 'ตกลง'
      })
    }

    // Emit error event
    emit('save', {
      success: false,
      type: 'academic_info_set_current',
      message: error.message
    })
  } finally {
    isSaving.value = false
  }
}

// Status text mapping
const getStatusText = (status) => {
  const statusMap = {
    studying: 'กำลังศึกษา',
    graduated: 'จบการศึกษา',
    transferred: 'ย้ายโรงเรียน',
    dropped: 'ออกจากระบบ'
  }
  return statusMap[status] || status
}

// Education level text mapping
const getEducationLevelText = (level) => {
  const levelMap = {
    0: 'อนุบาล',
    1: 'ประถม',
    2: 'มัธยม'
  }
  return levelMap[level] || 'ไม่ระบุ'
}

// Auto-detect education level from class level
const detectEducationLevel = (classLevel) => {
  if (!classLevel) return 2 // ค่าเริ่มต้น (มัธยม)
  
  const level = classLevel.toString().toLowerCase()
  
  // อนุบาล
  if (level.includes('อ.') || level.includes('ก.')) {
    return 0
  }
  
  // ประถม
  if (level.includes('ป.')) {
    return 1
  }
  
  // มัธยม
  if (level.includes('ม.')) {
    return 2
  }
  
  // ค่าเริ่มต้น (มัธยม)
  return 2
}

// Modal methods
const openAddModal = () => {
  modalMode.value = 'add'
  editingRecord.value = null
  editingIndex.value = -1
  resetFormData()
  showModal.value = true
}

const openEditModal = async (record, index) => {
  modalMode.value = 'edit'
  editingRecord.value = record
  editingIndex.value = index
  
  // Reset form first, then populate
  resetFormData()
  
  // Wait for reset to complete
  await nextTick()
  
  // Populate with record data
  populateFormData(record)
  
  // Show modal after data is ready
  showModal.value = true
  
  // Wait for DOM to update
  await nextTick()
  
  // Force reactive update
  const correctClassLevel = record.class_level || record.current_grade || ''
  if (correctClassLevel) {
    formData.value.class_level = correctClassLevel
  }
}

const closeModal = () => {
  showModal.value = false
  resetFormData()
}

const resetFormData = () => {
  formData.value = {
    academic_year: getCurrentAcademicYear(), // getCurrentAcademicYear() ตอนนี้ return string แล้ว
    class_level: '',
    education_level: 2, // ค่าเริ่มต้นเป็นมัธยมศึกษา (2)
    class_section: '',
    school_name: 'โรงเรียนจริยธรรมศึกษามูลนิธิ',
    school_address: 'หมู่ 8 ตำบลสะกอม อำเภอจะนะ',
    school_province: 'สงขลา',
    enrollment_date: formatDateForInput(props.student.enrollment_date) || '', // ดึงจากตาราง students
    graduation_date: '',
    status: 'studying',
    transfer_reason: '',
    notes: '',
    isCurrent: academicRecords.value.length === 0 // ถ้าไม่มีข้อมูลให้ตั้งเป็น current
  }
}

const populateFormData = (record) => {
  // Ensure class_level is populated from the record's class_level (which comes from current_grade)
  const classLevel = record.class_level || record.current_grade || ''
  
  // Use Object.assign to force reactivity
  Object.assign(formData.value, {
    academic_year: record.academic_year || '',
    class_level: classLevel,
    education_level: record.education_level || '',
    class_section: record.class_section || '',
    school_name: record.school_name || '',
    school_address: record.school_address || '',
    school_province: record.school_province || '',
    enrollment_date: record.enrollment_date || formatDateForInput(props.student.enrollment_date) || '',
    graduation_date: record.graduation_date || '',
    status: record.status || 'studying',
    transfer_reason: record.transfer_reason || '',
    notes: record.notes || '',
    isCurrent: record.isCurrent || false
  })
}

const submitForm = async () => {
  if (!formData.value.class_level || !formData.value.class_section) {
    await Swal.fire({
      title: 'ข้อมูลไม่ครบถ้วน',
      text: 'กรุณากรอกระดับชั้นและห้องเรียน',
      icon: 'warning',
      confirmButtonText: 'ตกลง'
    })
    return
  }

  isSaving.value = true
  
  try {
    // Prepare and validate data
    const enrollmentDate = formData.value.enrollment_date || null
    const graduationDate = formData.value.graduation_date || null
    
    // Validate graduation date is after enrollment date
    if (enrollmentDate && graduationDate && new Date(graduationDate) < new Date(enrollmentDate)) {
      await Swal.fire({
        title: 'ข้อมูลไม่ถูกต้อง',
        text: 'วันที่จบการศึกษาต้องไม่เก่ากว่าวันที่เข้าเรียน',
        icon: 'warning',
        confirmButtonText: 'ตกลง'
      })
      return
    }
    
    const apiData = {
      current_grade: formData.value.class_level || null,
      education_level: formData.value.education_level ? parseInt(formData.value.education_level) : null,
      current_class: formData.value.class_section || null,
      academic_year: formData.value.academic_year ? String(formData.value.academic_year) : null,
      semester: 1, // Default semester
      school_name: formData.value.school_name || null,
      school_address: formData.value.school_address || null,
      school_province: formData.value.school_province || null,
      enrollment_date: enrollmentDate,
      graduation_date: graduationDate,
      study_status: formData.value.status || 'studying',
      is_current: Boolean(formData.value.isCurrent),
      transfer_reason: formData.value.transfer_reason || null,
      notes: formData.value.notes || null
    }

    let response
    let requestUrl
    
    if (modalMode.value === 'edit' && editingRecord.value?.id) {
      // PUT request for update
      requestUrl = route('homevisit.student.academic-info.update', [props.student.id, editingRecord.value.id])
      response = await axios.put(requestUrl, apiData)
    } else {
      // POST request for create
      requestUrl = route('homevisit.student.academic-info.store', props.student.id)
      response = await axios.post(requestUrl, apiData)
    }

    const result = response.data

    if (result.success) {
      // Reload data from API
      await loadAcademicInfoFromAPI()

      // Close modal
      closeModal()

      // Emit success event
      emit('save', {
        success: true,
        type: modalMode.value === 'add' ? 'academic_info_added' : 'academic_info_updated',
        data: result.data
      })

      // ปิดสปินเนอร์ก่อนแสดง SweetAlert (isSaving จะถูก set false ใน finally)
      await nextTick()
      await Swal.fire({
        title: 'สำเร็จ!',
        text: modalMode.value === 'add' ? 'เพิ่มข้อมูลประวัติการศึกษาเรียบร้อยแล้ว' : 'อัปเดตข้อมูลประวัติการศึกษาเรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล')
    }
  } catch (error) {
    let errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
    
    // ตรวจสอบว่าเป็น axios error หรือไม่
    if (error.response) {
      // มี response จาก server
      const { status, data } = error.response
      
      if (status === 422) {
        // Validation errors
        
        if (data.errors) {
          // Show specific field errors
          const fieldErrors = []
          Object.entries(data.errors).forEach(([field, messages]) => {
            fieldErrors.push(`${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
          })
          errorMessage = `Validation Errors:\n${fieldErrors.join('\n')}`
        } else if (data.message) {
          errorMessage = data.message
        } else {
          errorMessage = 'ข้อมูลที่กรอกไม่ถูกต้อง กรุณาตรวจสอบและลองใหม่อีกครั้ง'
        }
      } else if (status === 419) {
        // CSRF token mismatch
        await Swal.fire({
          title: 'CSRF Token หมดอายุ!',
          text: 'กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง',
          icon: 'warning',
          confirmButtonText: 'โหลดหน้าใหม่',
          cancelButtonText: 'ยกเลิก',
          showCancelButton: true
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.reload()
          }
        })
        return
      } else if (data.message) {
        errorMessage = data.message
      }
    } else if (error.message.includes('CSRF') || error.message.includes('token')) {
      // CSRF token error (fallback)
      await Swal.fire({
        title: 'CSRF Token หมดอายุ!',
        text: 'กรุณาโหลดหน้าเว็บใหม่แล้วลองอีกครั้ง',
        icon: 'warning',
        confirmButtonText: 'ตกลง'
      })
      return
    } else if (error.message) {
      errorMessage = error.message
    }
    
    // แสดง error message
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด!',
      text: errorMessage,
      icon: 'error',
      confirmButtonText: 'ตกลง'
    })

    emit('save', {
      success: false,
      type: modalMode.value === 'add' ? 'academic_info_added' : 'academic_info_updated',
      message: error.message
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
          <div class="ml-2 sm:ml-3 min-w-0 flex-1">
            <h3 class="text-base sm:text-lg font-semibold text-white truncate">
              ประวัติการศึกษา ({{ academicRecords.length }} รายการ)
            </h3>
            <p class="text-blue-100 text-xs sm:text-sm">ข้อมูลประวัติการศึกษาของนักเรียน</p>
          </div>
        </div>
        
        <!-- Add New Record Button -->
        <button
          @click="openAddModal"
          class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white text-xs sm:text-sm font-medium rounded-lg transition-all duration-200"
        >
          <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          เพิ่มข้อมูล
        </button>
      </div>
    </div>

  <!-- Error State -->
  <div v-if="error && !isSaving" class="p-4 bg-red-50 border-l-4 border-red-400">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Table Content -->
    <div class="overflow-hidden">
      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ปีการศึกษา</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ระดับการศึกษา</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ระดับชั้น</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ห้อง</th>
              <!-- <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขที่</th> -->
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่เข้าศึกษา</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">โรงเรียน</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จังหวัด</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">จัดการ</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(record, index) in academicRecords" :key="record.id || index" 
                :class="{
                  'bg-blue-50': record.isNew, 
                  'bg-green-50': record.isCurrent,
                  'bg-gray-50': record.isFromAPI && !record.isCurrent,
                  'hover:bg-gray-50': !record.isNew && !record.isCurrent
                }">
              <!-- Academic Year -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center">
                  <input
                    v-model="record.academic_year"
                    @input="updateRecord(record, index)"
                    type="text"
                    :readonly="record.isFromAPI && !record.isCurrent"
                    :class="[
                      'w-full border-0 bg-transparent focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded px-2 py-1 text-sm placeholder-gray-400',
                      record.isFromAPI && !record.isCurrent ? 'text-gray-600 cursor-default' : ''
                    ]"
                    placeholder="2567"
                  />
                  <span v-if="record.isCurrent" class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    ปัจจุบัน
                  </span>
                </div>
              </td>
              <!-- Education Level -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ getEducationLevelText(record.education_level) || '-' }}</span>
              </td>
              <!-- Class Level -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.class_level || '-' }}</span>
              </td>
              <!-- Class Section -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.class_section || '-' }}</span>
              </td>
              <!-- Student Number -->
              <!-- <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.student_number || '-' }}</span>
              </td> -->
              <!-- Enrollment Date -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.enrollment_date || '-' }}</span>
              </td>
              <!-- School Name -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.school_name || '-' }}</span>
              </td>
              <!-- Province -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.school_province || '-' }}</span>
              </td>
              <!-- Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="{
                        'bg-green-100 text-green-800': record.status === 'studying',
                        'bg-blue-100 text-blue-800': record.status === 'graduated',
                        'bg-yellow-100 text-yellow-800': record.status === 'transferred',
                        'bg-red-100 text-red-800': record.status === 'dropped',
                        'bg-gray-100 text-gray-800': !record.status
                      }">
                  {{ getStatusText(record.status) || '-' }}
                </span>

              </td>
              <!-- Actions -->
              <td class="px-4 py-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center space-x-2">
                  <!-- Show API source indicator -->
                  <!-- <span v-if="record.isFromAPI" class="text-xs text-gray-500 mr-2" title="ข้อมูลจาก API">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                  </span> -->
                  
                  <!-- Edit Button for existing records -->
                  <button
                    v-if="!record.isNew"
                    @click="openEditModal(record, index)"
                    :disabled="isSaving"
                    class="text-blue-600 hover:text-blue-800 disabled:opacity-50"
                    title="แก้ไข"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  
                  <!-- Only allow delete for current record or new records -->
                  <button
                    v-if="record.isCurrent || record.isNew"
                    @click="deleteRecord(index)"
                    :disabled="isSaving"
                    class="text-red-600 hover:text-red-800 disabled:opacity-50"
                    title="ลบ"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                  
                  <!-- Set as current button for historical records -->
                  <template v-else>
                    <button
                      v-if="record.isFromAPI"
                      @click="setAsCurrent(record, index)"
                      :disabled="isSaving"
                      class="text-blue-600 hover:text-blue-800 disabled:opacity-50 mr-2"
                      title="ตั้งเป็นปัจจุบัน"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 713.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                      </svg>
                    </button>
                    
                    <!-- View only indicator for historical records -->
                    <span v-else class="text-xs text-gray-400" title="ข้อมูลประวัติ (ดูเท่านั้น)">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </span>
                  </template>
                </div>
              </td>
            </tr>
            
            <!-- Empty State -->
            <tr v-if="academicRecords.length === 0">
              <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                  <p class="text-sm">ยังไม่มีข้อมูลประวัติการศึกษา</p>
                  <p class="text-xs text-gray-400 mt-1">คลิกปุ่ม "เพิ่มข้อมูล" เพื่อเริ่มต้น</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-4 p-4">
        <div v-for="(record, index) in academicRecords" :key="record.id || index"
             :class="['bg-white rounded-lg border', record.isNew ? 'border-blue-300 bg-blue-50' : 'border-gray-200']">
          <div class="p-4 space-y-3">
            <!-- Academic Year -->
            <div class="flex items-center space-x-3">
              <label class="text-xs font-medium text-gray-500 w-20">ปีการศึกษา:</label>
              <span class="flex-1 text-sm text-gray-900">{{ record.academic_year || '-' }}</span>
            </div>
            
            <!-- Education Level -->
            <div class="flex items-center space-x-3">
              <label class="text-xs font-medium text-gray-500 w-20">ระดับการศึกษา:</label>
              <span class="flex-1 text-sm text-gray-900">{{ getEducationLevelText(record.education_level) || '-' }}</span>
            </div>
            
            <!-- Class Level & Section -->
            <div class="grid grid-cols-2 gap-3">
              <div class="flex items-center space-x-2">
                <label class="text-xs font-medium text-gray-500">ระดับชั้น:</label>
                <span class="flex-1 text-sm text-gray-900">{{ record.class_level || '-' }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <label class="text-xs font-medium text-gray-500">ห้อง:</label>
                <span class="flex-1 text-sm text-gray-900">{{ record.class_section || '-' }}</span>
              </div>
            </div>
            
            <!-- School Name -->
            <div class="flex items-center space-x-3">
              <label class="text-xs font-medium text-gray-500 w-20">โรงเรียน:</label>
              <span class="flex-1 text-sm text-gray-900">{{ record.school_name || '-' }}</span>
            </div>
            
            <!-- Province -->
            <div class="flex items-center space-x-3">
              <label class="text-xs font-medium text-gray-500 w-20">จังหวัด:</label>
              <span class="flex-1 text-sm text-gray-900">{{ record.school_province || '-' }}</span>
            </div>
            
            <!-- Status -->
            <div class="flex items-center space-x-3">
              <label class="text-xs font-medium text-gray-500 w-20">สถานะ:</label>
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-green-100 text-green-800': record.status === 'studying',
                      'bg-blue-100 text-blue-800': record.status === 'graduated',
                      'bg-yellow-100 text-yellow-800': record.status === 'transferred',
                      'bg-red-100 text-red-800': record.status === 'dropped',
                      'bg-gray-100 text-gray-800': !record.status
                    }">
                {{ getStatusText(record.status) || '-' }}
              </span>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
              <div class="flex items-center space-x-2">
                <!-- <span v-if="record.isCurrent" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  ปัจจุบัน
                </span> -->
                <!-- <span v-if="record.isFromAPI" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                  </svg>
                  API
                </span> -->
              </div>
              
              <div class="flex space-x-2">
                <!-- Edit Button for all existing records -->
                <button
                  v-if="!record.isNew"
                  @click="openEditModal(record, index)"
                  :disabled="isSaving"
                  class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  แก้ไข
                </button>

                <!-- Set as Current Button for non-current records -->
                <button
                  v-if="!record.isCurrent && !record.isNew"
                  @click="setAsCurrent(record, index)"
                  :disabled="isSaving"
                  class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-md hover:bg-orange-700 disabled:opacity-50"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 713.138-3.138z" />
                  </svg>
                  ตั้งปัจจุบัน
                </button>
                
                <!-- Delete Button (only for current record) -->
                <button
                  v-if="record.isCurrent"
                  @click="deleteRecord(index)"
                  :disabled="isSaving"
                  class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 disabled:opacity-50"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                  ลบ
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Mobile Empty State -->
        <div v-if="academicRecords.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
          </svg>
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลประวัติการศึกษา</p>
          <p class="text-xs text-gray-400 mt-1">คลิกปุ่ม "เพิ่มข้อมูล" เพื่อเริ่มต้น</p>
        </div>
      </div>
    </div>
    

    <!-- Modal Form -->
    <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
      
      <!-- Modal Content -->
      <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ modalMode === 'add' ? 'เพิ่มประวัติการศึกษา' : 'แก้ไขประวัติการศึกษา' }}
            </h3>
            <button 
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Modal Body -->
          <div class="p-6 space-y-6">
            <!-- Academic Year & Current Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ปีการศึกษา</label>
                <input
                  v-model="formData.academic_year"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                  placeholder="2567"
                />
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <input
                    v-model="formData.isCurrent"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                  />
                  <span class="ml-2 text-sm text-gray-700">ประวัติปัจจุบัน</span>
                </label>
              </div>
            </div>

            <!-- Education Level -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ระดับการศึกษา *</label>
              <select
                v-model="formData.education_level"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              >
                <option value="">เลือกระดับการศึกษา</option>
                <option :value="0">อนุบาล</option>
                <option :value="1">ประถมศึกษา</option>
                <option :value="2">มัธยมศึกษา</option>
              </select>
            </div>

            <!-- Class Level & Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ระดับชั้น *</label>
                <input
                  v-model="formData.class_level"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                  placeholder="เช่น 1, 2"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ห้อง *</label>
                <input
                  v-model="formData.class_section"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                  placeholder="1, 2, 3..."
                  required
                />
              </div>
            </div>

            <!-- School Information -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อโรงเรียน</label>
              <input
                v-model="formData.school_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                placeholder="โรงเรียนจริยธรรมศึกษามูลนิธิ"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่โรงเรียน</label>
                <textarea
                  v-model="formData.school_address"
                  rows="2"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                  placeholder="หมู่ 8 ตำบลสะกอม อำเภอจะนะ"
                ></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด</label>
                <input
                  v-model="formData.school_province"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                  placeholder="สงขลา"
                />
              </div>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">วันที่เข้าเรียน</label>
                <input
                  v-model="formData.enrollment_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">วันที่จบการศึกษา</label>
                <input
                  v-model="formData.graduation_date"
                  type="date"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>

            <!-- Status -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">สถานะการศึกษา</label>
              <select
                v-model="formData.status"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="studying">กำลังศึกษา</option>
                <option value="graduated">จบการศึกษา</option>
                <option value="transferred">ย้ายโรงเรียน</option>
                <option value="dropped">ออกจากระบบ</option>
                <option value="suspended">พักการเรียน</option>
              </select>
            </div>

            <!-- Transfer Reason (show only when transferred) -->
            <div v-if="formData.status === 'transferred'">
              <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผลการย้าย</label>
              <textarea
                v-model="formData.transfer_reason"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                placeholder="ระบุเหตุผลการย้ายโรงเรียน"
              ></textarea>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ</label>
              <textarea
                v-model="formData.notes"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400"
                placeholder="หมายเหตุเพิ่มเติม"
              ></textarea>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
            <button
              @click="closeModal"
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              ยกเลิก
            </button>
            <button
              @click="submitForm"
              :disabled="isSaving"
              type="button"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isSaving" class="flex items-center">
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                กำลังบันทึก...
              </span>
              <span v-else>
                {{ modalMode === 'add' ? 'เพิ่มข้อมูล' : 'บันทึกการแก้ไข' }}
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>



