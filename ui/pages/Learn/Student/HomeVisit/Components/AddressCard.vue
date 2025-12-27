<script setup>
import { ref, onMounted, nextTick } from 'vue'
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
const { addressRoutes } = useStudentRoutes(props.context)

// Reactive state
const addressRecords = ref([])
const isSaving = ref(false)
const showModal = ref(false)
const modalMode = ref('add') // 'add' or 'edit'
const editingRecord = ref(null)
const editingIndex = ref(-1)
const saveStatus = ref(null)
const error = ref(null)

// Form data for modal
const formData = ref({
  address_type: 'current',
  house_number: '',
  village_number: '',
  village_name: '',
  alley: '',
  road: '',
  subdistrict: '',
  district: '',
  province: '',
  postal_code: '',
  is_current: false
})

// Initialize address records on component mount
onMounted(async () => {
  try {
    if (props.student?.id) {
      await loadAddressesFromAPI()
    } else {
      console.warn('AddressCard: No student ID provided')
    }
  } catch (error) {
    console.error('Error loading addresses:', error)
    error.value = 'ไม่สามารถโหลดข้อมูลที่อยู่ได้: ' + error.message
  }
})

// Load addresses from API
const loadAddressesFromAPI = async () => {
  if (!props.student?.id) {
    console.warn('AddressCard: Cannot load addresses - no student ID')
    return
  }
  
  try {
    const response = await axios.get(addressRoutes.value.index(props.student.id))
    const result = response.data
    
    if (result.status === 'success' && result.data && result.data.length > 0) {
      addressRecords.value = result.data.map(record => ({
        id: record.id,
        address_type: record.address_type || 'permanent',
        house_number: record.house_number || '',
        village_number: record.village_number || '',
        village_name: record.village_name || '',
        alley: record.alley || '',
        road: record.road || '',
        subdistrict: record.subdistrict || '',
        district: record.district || '',
        province: record.province || '',
        postal_code: record.postal_code || '',
        is_current: Boolean(record.is_current),
        isFromAPI: true
      })).filter(record => {
        // Filter out records with no meaningful address data
        return record.house_number || record.subdistrict || record.district || record.province
      })
    } else {
      // No data, show empty state
      addressRecords.value = []
    }
  } catch (error) {
    console.error('API Error:', error)
    // If API fails, show empty state instead of throwing
    addressRecords.value = []
    if (error.response && error.response.status !== 404) {
      error.value = 'ไม่สามารถโหลดข้อมูลที่อยู่ได้'
    }
  }
}

// Delete record using API
const deleteRecord = async (index) => {
  const record = addressRecords.value[index]
  
  if (!record) return
  
  // If it's a new record (not saved), just remove from array
  if (record.isNew || !record.id) {
    addressRecords.value.splice(index, 1)
    return
  }
  
  // Confirm deletion with SweetAlert2
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: 'คุณต้องการลบข้อมูลที่อยู่นี้หรือไม่?',
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
    const response = await axios.delete(addressRoutes.value.destroy(props.student.id, record.id))
    const result = response.data

    if (result.status === 'success' || result.success === true) {
      // 1. Remove from local array first
      addressRecords.value.splice(index, 1)

      // 2. Emit delete event to parent component
      emit('save', {
        success: true,
        type: 'address_deleted',
        data: record
      })

      // 3. Wait for DOM update
      await nextTick()

      // 4. Reset loading state BEFORE showing alert
      isSaving.value = false

      // 5. Show success message AFTER all operations are completed
      await Swal.fire({
        title: 'ลบสำเร็จ!',
        text: 'ลบข้อมูลที่อยู่เรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      // Exit function successfully
      return
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล')
    }

  } catch (error) {
    console.error('Delete error:', error)
    
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด!',
      text: error.message,
      icon: 'error',
      confirmButtonText: 'ตกลง'
    })

    // Emit error event to parent component
    emit('save', {
      success: false,
      type: 'address_deleted',
      message: error.message
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}

// Set record as current
const setAsCurrent = async (record, index) => {
  if (!record.id) return
  
  isSaving.value = true
  saveStatus.value = null
  
  try {
    const response = await axios.put(addressRoutes.value.setCurrent(props.student.id, record.id))
    const result = response.data

    if (result.status === 'success' || result.success === true) {
      // 1. Update local state first
      addressRecords.value.forEach(r => r.is_current = false)
      record.is_current = true

      // 2. Emit success event
      emit('save', {
        success: true,
        type: 'address_set_current',
        data: result.data
      })

      // 3. Wait for DOM update
      await nextTick()

      // 4. Reset loading state BEFORE showing alert
      isSaving.value = false

      // 5. Show success message AFTER all operations are completed
      await Swal.fire({
        title: 'สำเร็จ!',
        text: 'ตั้งค่าเป็นที่อยู่ปัจจุบันเรียบร้อยแล้ว',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      // Exit function successfully
      return
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการตั้งค่า')
    }

  } catch (error) {
    console.error('Set current error:', error)
    
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด!',
      text: error.message,
      icon: 'error',
      confirmButtonText: 'ตกลง'
    })

    // Emit error event
    emit('save', {
      success: false,
      type: 'address_set_current',
      message: error.message
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}

// Address type text mapping
const getAddressTypeText = (type) => {
  const typeMap = {
    current: 'ปัจจุบัน',
    permanent: 'ตามทะเบียนบ้าน',
    temporary: 'ชั่วคราว'
  }
  return typeMap[type] || type
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
}

const closeModal = () => {
  showModal.value = false
  resetFormData()
}

const resetFormData = () => {
  formData.value = {
    address_type: 'current',
    house_number: '',
    village_number: '',
    village_name: '',
    alley: '',
    road: '',
    subdistrict: '',
    district: '',
    province: '',
    postal_code: '',
    is_current: addressRecords.value.length === 0 // ถ้าไม่มีข้อมูลให้ตั้งเป็น current
  }
}

const populateFormData = (record) => {
  // Use Object.assign to force reactivity
  Object.assign(formData.value, {
    address_type: record.address_type || 'current',
    house_number: record.house_number || '',
    village_number: record.village_number || '',
    village_name: record.village_name || '',
    alley: record.alley || '',
    road: record.road || '',
    subdistrict: record.subdistrict || '',
    district: record.district || '',
    province: record.province || '',
    postal_code: record.postal_code || '',
    is_current: record.is_current || false
  })
}

const submitForm = async () => {
  if (!formData.value.house_number || !formData.value.subdistrict || !formData.value.district || !formData.value.province) {
    await Swal.fire({
      title: 'ข้อมูลไม่ครบถ้วน',
      text: 'กรุณากรอกบ้านเลขที่ ตำบล อำเภอ และจังหวัด',
      icon: 'warning',
      confirmButtonText: 'ตกลง'
    })
    return
  }

  isSaving.value = true
  
  try {
    const apiData = {
      address_type: formData.value.address_type || 'current',
      house_number: formData.value.house_number || null,
      village_number: formData.value.village_number || null,
      village_name: formData.value.village_name || null,
      alley: formData.value.alley || null,
      road: formData.value.road || null,
      subdistrict: formData.value.subdistrict || null,
      district: formData.value.district || null,
      province: formData.value.province || null,
      postal_code: formData.value.postal_code || null,
      is_current: Boolean(formData.value.is_current)
    }



    let response
    if (modalMode.value === 'edit' && editingRecord.value?.id) {
      // PUT request for update
      response = await axios.put(
        addressRoutes.value.update(props.student.id, editingRecord.value.id),
        apiData
      )
    } else {
      // POST request for create
      response = await axios.post(
        addressRoutes.value.store(props.student.id),
        apiData
      )
    }

    const result = response.data

    // Check for different success response formats
    if (result.status === 'success' || result.success === true || response.status === 200) {
      // 1. Update local data from API response (avoid unnecessary reload)
      if (modalMode.value === 'add' && result.data) {
        // If new record is current, unset other current records
        if (Boolean(result.data.is_current || formData.value.is_current)) {
          addressRecords.value.forEach(record => record.is_current = false)
        }
        
        // Add new record to local array
        const newRecord = {
          id: result.data.id,
          address_type: result.data.address_type || formData.value.address_type,
          house_number: result.data.house_number || formData.value.house_number,
          village_number: result.data.village_number || formData.value.village_number,
          village_name: result.data.village_name || formData.value.village_name,
          alley: result.data.alley || formData.value.alley,
          road: result.data.road || formData.value.road,
          subdistrict: result.data.subdistrict || formData.value.subdistrict,
          district: result.data.district || formData.value.district,
          province: result.data.province || formData.value.province,
          postal_code: result.data.postal_code || formData.value.postal_code,
          is_current: Boolean(result.data.is_current || formData.value.is_current),
          isFromAPI: true
        }
        addressRecords.value.push(newRecord)
      } else if (modalMode.value === 'edit' && editingIndex.value >= 0 && result.data) {
        // If updated record is current, unset other current records
        if (Boolean(result.data.is_current || formData.value.is_current)) {
          addressRecords.value.forEach(record => record.is_current = false)
        }
        
        // Update existing record in local array
        const updatedRecord = {
          ...addressRecords.value[editingIndex.value],
          address_type: result.data.address_type || formData.value.address_type,
          house_number: result.data.house_number || formData.value.house_number,
          village_number: result.data.village_number || formData.value.village_number,
          village_name: result.data.village_name || formData.value.village_name,
          alley: result.data.alley || formData.value.alley,
          road: result.data.road || formData.value.road,
          subdistrict: result.data.subdistrict || formData.value.subdistrict,
          district: result.data.district || formData.value.district,
          province: result.data.province || formData.value.province,
          postal_code: result.data.postal_code || formData.value.postal_code,
          is_current: Boolean(result.data.is_current || formData.value.is_current)
        }
        addressRecords.value[editingIndex.value] = updatedRecord
      }
      
      // Fallback: If no API response data, reload once to ensure consistency
      if (!result.data) {
        await loadAddressesFromAPI()
      }
      
      // 2. Wait for DOM update before closing modal
      await nextTick()
      
      // 3. Close modal
      closeModal()

      // 4. Emit success event
      emit('save', {
        success: true,
        type: modalMode.value === 'add' ? 'address_added' : 'address_updated',
        data: result.data
      })

      // 5. Wait for all UI updates to complete
      await nextTick()
      
      // 6. Reset loading state BEFORE showing alert
      isSaving.value = false
      
      // 7. Show success alert after all operations are completed
      await Swal.fire({
        title: 'สำเร็จ!',
        text: result.message || (modalMode.value === 'add' ? 'เพิ่มข้อมูลที่อยู่เรียบร้อยแล้ว' : 'แก้ไขข้อมูลที่อยู่เรียบร้อยแล้ว'),
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
      })

      // Exit function successfully - don't continue to catch block
      return
    } else {
      throw new Error(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล')
    }
  } catch (error) {
    console.error('Submit error:', error)
    
    let errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'
    
    if (error.response && error.response.status === 403) {
      // Forbidden error
      const data = error.response.data
      errorMessage = data.message || 'ไม่มีสิทธิ์ในการดำเนินการนี้'
      console.error('403 Forbidden:', data)
    } else if (error.response && error.response.status === 422) {
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
      // API error response
      errorMessage = error.response.data.message || error.response.data.error || errorMessage
    } else if (error.message && !error.message.includes('เรียบร้อยแล้ว')) {
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
      type: modalMode.value === 'add' ? 'address_added' : 'address_updated',
      message: error.message
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}
</script>

<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-500 to-red-600 px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="ml-2 sm:ml-3 min-w-0 flex-1">
            <h3 class="text-base sm:text-lg font-semibold text-white truncate">
              ที่อยู่ ({{ addressRecords.length }} รายการ)
            </h3>
            <p class="text-orange-100 text-xs sm:text-sm">ข้อมูลที่อยู่ปัจจุบันและตามทะเบียนบ้าน</p>
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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ที่อยู่</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ตำบล</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">อำเภอ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จังหวัด</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รหัสไปรษณีย์</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">จัดการ</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(record, index) in addressRecords" :key="record.id || index" 
                :class="{
                  'bg-blue-50': record.isNew, 
                  'bg-green-50': record.is_current,
                  'bg-gray-50': record.isFromAPI && !record.is_current,
                  'hover:bg-gray-50': !record.isNew && !record.is_current
                }">
              <!-- Address Type -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center">
                  <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                        :class="{
                          'bg-green-100 text-green-800': record.address_type === 'current',
                          'bg-blue-100 text-blue-800': record.address_type === 'permanent',
                          'bg-yellow-100 text-yellow-800': record.address_type === 'temporary',
                          'bg-gray-100 text-gray-800': !record.address_type
                        }">
                    {{ getAddressTypeText(record.address_type) || '-' }}
                  </span>
                  <span v-if="record.is_current" class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    ปัจจุบัน
                  </span>
                </div>
              </td>
              <!-- Address -->
              <td class="px-4 py-3">
                <div class="text-sm text-gray-900 max-w-xs truncate">
                  {{ [record.house_number, record.village_number ? `หมู่ ${record.village_number}` : '', record.village_name, record.alley, record.road].filter(Boolean).join(' ') || '-' }}
                </div>
              </td>
              <!-- Subdistrict -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.subdistrict || '-' }}</span>
              </td>
              <!-- District -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.district || '-' }}</span>
              </td>
              <!-- Province -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.province || '-' }}</span>
              </td>
              <!-- Postal Code -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="text-sm text-gray-900">{{ record.postal_code || '-' }}</span>
              </td>
              <!-- Actions -->
              <td class="px-4 py-3 whitespace-nowrap text-center">
                <div class="flex items-center justify-center space-x-2">
                  <!-- Edit Button for existing records -->
                  <button
                    v-if="!record.isNew"
                    @click="openEditModal(record, index)"
                    :disabled="isSaving"
                    class="text-orange-600 hover:text-orange-800 disabled:opacity-50"
                    title="แก้ไข"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  
                  <!-- Only allow delete for current record or new records -->
                  <button
                    v-if="record.is_current || record.isNew"
                    @click="deleteRecord(index)"
                    :disabled="isSaving"
                    class="text-red-600 hover:text-red-800 disabled:opacity-50"
                    title="ลบ"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                  
                  <!-- Set as current button for non-current records -->
                  <button
                    v-if="!record.is_current && !record.isNew && record.isFromAPI"
                    @click="setAsCurrent(record, index)"
                    :disabled="isSaving"
                    class="text-orange-600 hover:text-orange-800 disabled:opacity-50"
                    title="ตั้งเป็นปัจจุบัน"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            
            <!-- Empty State -->
            <tr v-if="addressRecords.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <p class="text-sm">ยังไม่มีข้อมูลที่อยู่</p>
                  <p class="text-xs text-gray-400 mt-1">คลิกปุ่ม "เพิ่มข้อมูล" เพื่อเริ่มต้น</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-4 p-4">
        <div v-for="(record, index) in addressRecords" :key="record.id || index"
             :class="['bg-white rounded-lg border', record.isNew ? 'border-orange-300 bg-orange-50' : 'border-gray-200']">
          <div class="p-4 space-y-3">
            <!-- Address Type -->
            <div class="flex items-center justify-between">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-green-100 text-green-800': record.address_type === 'current',
                      'bg-blue-100 text-blue-800': record.address_type === 'permanent',
                      'bg-yellow-100 text-yellow-800': record.address_type === 'temporary',
                      'bg-gray-100 text-gray-800': !record.address_type
                    }">
                {{ getAddressTypeText(record.address_type) || '-' }}
              </span>
              <span v-if="record.is_current" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                ปัจจุบัน
              </span>
            </div>
            
            <!-- Full Address -->
            <div class="space-y-2">
              <!-- Address Details -->
              <div v-if="record.house_number || record.village_number || record.village_name || record.alley || record.road" class="text-sm">
                <span class="text-gray-500 font-medium">ที่อยู่: </span>
                <span class="text-gray-900">{{ [record.house_number, record.village_number ? `หมู่ ${record.village_number}` : '', record.village_name, record.alley, record.road].filter(Boolean).join(' ') || '-' }}</span>
              </div>
              
              <!-- Administrative Division -->
              <div class="space-y-1">
                <div v-if="record.subdistrict" class="text-sm">
                  <span class="text-gray-500 font-medium">ตำบล: </span>
                  <span class="text-gray-900">{{ record.subdistrict }}</span>
                </div>
                <div v-if="record.district" class="text-sm">
                  <span class="text-gray-500 font-medium">อำเภอ: </span>
                  <span class="text-gray-900">{{ record.district }}</span>
                </div>
                <div v-if="record.province" class="text-sm">
                  <span class="text-gray-500 font-medium">จังหวัด: </span>
                  <span class="text-gray-900">{{ record.province }}</span>
                </div>
                <div v-if="record.postal_code" class="text-sm">
                  <span class="text-gray-500 font-medium">รหัสไปรษณีย์: </span>
                  <span class="text-gray-900">{{ record.postal_code }}</span>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
              <div class="flex items-center space-x-2">
                <!-- <span v-if="record.isFromAPI" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                  </svg>
                  API
                </span> -->
              </div>
              
              <div class="flex space-x-2">
                <!-- Edit Button -->
                <button
                  v-if="!record.isNew"
                  @click="openEditModal(record, index)"
                  :disabled="isSaving"
                  class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white text-xs font-medium rounded-md hover:bg-orange-700 disabled:opacity-50"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  แก้ไข
                </button>

                <!-- Set as Current Button -->
                <button
                  v-if="!record.is_current && !record.isNew"
                  @click="setAsCurrent(record, index)"
                  :disabled="isSaving"
                  class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  ตั้งปัจจุบัน
                </button>
                
                <!-- Delete Button -->
                <button
                  v-if="record.is_current"
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
        <div v-if="addressRecords.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลที่อยู่</p>
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
              {{ modalMode === 'add' ? 'เพิ่มที่อยู่' : 'แก้ไขที่อยู่' }}
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
            <!-- Address Type & Current Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทที่อยู่ *</label>
                <select
                  v-model="formData.address_type"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                  required
                >
                  <option value="current">ปัจจุบัน</option>
                  <option value="permanent">ตามทะเบียนบ้าน</option>
                  <option value="temporary">ชั่วคราว</option>
                </select>
              </div>
              <div class="flex items-center">
                <label class="flex items-center">
                  <input
                    v-model="formData.is_current"
                    type="checkbox"
                    class="rounded border-gray-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                  />
                  <span class="ml-2 text-sm text-gray-700">ที่อยู่ปัจจุบัน</span>
                </label>
              </div>
            </div>

            <!-- House Number & Village Number -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">บ้านเลขที่ *</label>
                <input
                  v-model="formData.house_number"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="เลขที่บ้าน"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">หมู่ที่</label>
                <input
                  v-model="formData.village_number"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="หมู่ที่"
                />
              </div>
            </div>

            <!-- Village Name & Alley -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อหมู่บ้าน</label>
                <input
                  v-model="formData.village_name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="ชื่อหมู่บ้าน"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ซอย/ตรอก</label>
                <input
                  v-model="formData.alley"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="ซอย/ตรอก"
                />
              </div>
            </div>

            <!-- Road -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ถนน</label>
              <input
                v-model="formData.road"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                placeholder="ถนน"
              />
            </div>

            <!-- Subdistrict, District, Province -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ตำบล/แขวง *</label>
                <input
                  v-model="formData.subdistrict"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="ตำบล/แขวง"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">อำเภอ/เขต *</label>
                <input
                  v-model="formData.district"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="อำเภอ/เขต"
                  required
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">จังหวัด *</label>
                <input
                  v-model="formData.province"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="จังหวัด"
                  required
                />
              </div>
            </div>

            <!-- Postal Code -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสไปรษณีย์</label>
                <input
                  v-model="formData.postal_code"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 placeholder-gray-400"
                  placeholder="รหัสไปรษณีย์"
                />
              </div>
            </div>
          </div>

          <!-- Modal Footer -->
          <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
            <button
              @click="closeModal"
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
              ยกเลิก
            </button>
            <button
              @click="submitForm"
              :disabled="isSaving"
              type="button"
              class="px-4 py-2 text-sm font-medium text-white bg-orange-600 border border-transparent rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 disabled:opacity-50 disabled:cursor-not-allowed"
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



