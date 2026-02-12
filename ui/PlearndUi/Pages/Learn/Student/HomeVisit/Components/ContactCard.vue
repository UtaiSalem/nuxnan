<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
          </div>
          <div class="ml-2 sm:ml-3 min-w-0 flex-1">
            <h3 class="text-base sm:text-lg font-semibold text-white truncate">
              ข้อมูลติดต่อ ({{ contactRecords.length }} รายการ)
            </h3>
            <p class="text-teal-100 text-xs sm:text-sm">ข้อมูลการติดต่อของนักเรียน</p>
          </div>
        </div>
        
        <!-- Add Button -->
        <button
          @click="openAddModal"
          :disabled="isSaving"
          class="inline-flex items-center px-3 py-2 bg-white bg-opacity-20 text-white text-sm font-medium rounded-lg hover:bg-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-teal-600 disabled:opacity-50 transition-all"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          เพิ่มข้อมูล
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="bg-gradient-to-br from-teal-50 to-cyan-50">
      <!-- Desktop Table -->
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
          <thead class="bg-white border-b border-gray-200">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ข้อมูลติดต่อ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(record, index) in contactRecords" :key="record.id || index"
                :class="record.isNew ? 'bg-teal-50' : 'hover:bg-gray-50'">
              <td class="px-4 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                      :class="{
                        'bg-blue-100 text-blue-800': record.contact_type === 'phone',
                        'bg-green-100 text-green-800': record.contact_type === 'email',
                        'bg-yellow-100 text-yellow-800': record.contact_type === 'line',
                        'bg-purple-100 text-purple-800': record.contact_type === 'facebook',
                        'bg-gray-100 text-gray-800': !record.contact_type
                      }">
                  {{ getContactTypeText(record.contact_type) || '-' }}
                </span>
              </td>
              <td class="px-4 py-4">
                <div class="text-sm text-gray-900 font-medium">
                  {{ record.contact_value || '-' }}
                </div>
              </td>
              <td class="px-4 py-4 whitespace-nowrap">
                <span v-if="record.is_primary" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  หลัก
                </span>
              </td>
              <td class="px-4 py-4 text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <!-- Edit Button -->
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

                  <!-- Set as Primary Button -->
                  <button
                    v-if="!record.is_primary && !record.isNew"
                    @click="setPrimary(record, index)"
                    :disabled="isSaving"
                    class="text-teal-600 hover:text-teal-800 disabled:opacity-50"
                    title="ตั้งเป็นหลัก"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>

                  <!-- Delete Button -->
                  <button
                    v-if="!record.isNew"
                    @click="deleteRecord(index)"
                    :disabled="isSaving"
                    class="text-red-600 hover:text-red-800 disabled:opacity-50"
                    title="ลบ"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            
            <!-- Empty State -->
            <tr v-if="contactRecords.length === 0">
              <td colspan="4" class="px-4 py-12 text-center">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <p class="text-sm text-gray-500">ยังไม่มีข้อมูลการติดต่อ</p>
                <p class="text-xs text-gray-400 mt-1">คลิกปุ่ม "เพิ่มข้อมูล" เพื่อเริ่มต้น</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Cards -->
      <div class="md:hidden space-y-4 p-4">
        <div v-for="(record, index) in contactRecords" :key="record.id || index"
             :class="['bg-white rounded-lg border', record.isNew ? 'border-teal-300 bg-teal-50' : 'border-gray-200']">
          <div class="p-4 space-y-3">
            <!-- Contact Type -->
            <div class="flex items-center justify-between">
              <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                    :class="{
                      'bg-blue-100 text-blue-800': record.contact_type === 'phone',
                      'bg-green-100 text-green-800': record.contact_type === 'email',
                      'bg-yellow-100 text-yellow-800': record.contact_type === 'line',
                      'bg-purple-100 text-purple-800': record.contact_type === 'facebook',
                      'bg-gray-100 text-gray-800': !record.contact_type
                    }">
                {{ getContactTypeText(record.contact_type) || '-' }}
              </span>
              <span v-if="record.is_primary" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                หลัก
              </span>
            </div>
            
            <!-- Contact Information -->
            <div class="space-y-2">
              <div v-if="record.contact_value" class="text-sm">
                <span class="text-gray-500 font-medium">{{ getContactTypeText(record.contact_type) }}: </span>
                <span class="text-gray-900">{{ record.contact_value }}</span>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center justify-end pt-2 border-t border-gray-200">
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

                <!-- Set as Primary Button -->
                <button
                  v-if="!record.is_primary && !record.isNew"
                  @click="setPrimary(record, index)"
                  :disabled="isSaving"
                  class="inline-flex items-center px-3 py-1.5 bg-teal-600 text-white text-xs font-medium rounded-md hover:bg-teal-700 disabled:opacity-50"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  ตั้งหลัก
                </button>
                
                <!-- Delete Button -->
                <button
                  v-if="!record.isNew"
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
        <div v-if="contactRecords.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
          </svg>
          <p class="text-sm text-gray-500">ยังไม่มีข้อมูลการติดต่อ</p>
          <p class="text-xs text-gray-400 mt-1">คลิกปุ่ม "เพิ่มข้อมูล" เพื่อเริ่มต้น</p>
        </div>
      </div>
    </div>
    
    <!-- Status Footer -->
      <!-- Spinner footer removed: spinner now only at save button -->

    <!-- Modal for Add/Edit -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="closeModal">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <!-- Modal Header -->
          <div class="flex items-center justify-between pb-3">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ modalMode === 'add' ? 'เพิ่มข้อมูลการติดต่อ' : 'แก้ไขข้อมูลการติดต่อ' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <!-- Modal Content -->
          <div class="space-y-4">
            <!-- Contact Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">ประเภทการติดต่อ</label>
              <select
                v-model="formData.contact_type"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
              >
                <option value="phone">โทรศัพท์</option>
                <option value="email">อีเมล</option>
                <option value="line">Line</option>
                <option value="facebook">Facebook</option>
              </select>
            </div>

            <!-- Contact Value -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">ข้อมูลการติดต่อ</label>
              <input
                type="text"
                v-model="formData.contact_value"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-teal-500 focus:border-teal-500"
                :placeholder="formData.contact_type === 'phone' ? '0XX-XXX-XXXX' : 
                              formData.contact_type === 'email' ? 'email@example.com' :
                              formData.contact_type === 'line' ? 'Line ID' : 'ข้อมูลการติดต่อ'"
              >
            </div>

            <!-- Is Primary -->
            <div class="flex items-center">
              <input
                type="checkbox"
                id="is_primary"
                v-model="formData.is_primary"
                class="rounded border-gray-300 text-teal-600 shadow-sm focus:border-teal-300 focus:ring focus:ring-teal-200 focus:ring-opacity-50"
              >
              <label for="is_primary" class="ml-2 text-sm text-gray-700">ช่องทางการติดต่อหลัก</label>
            </div>
          </div>
          
          <!-- Modal Actions -->
          <div class="flex items-center justify-end pt-6 space-x-2">
            <button
              @click="closeModal"
              type="button"
              class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300"
            >
              ยกเลิก
            </button>
            <button
              @click="submitForm"
              type="button"
              :disabled="isSaving"
              class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50"
            >
              {{ isSaving ? 'กำลังบันทึก...' : (modalMode === 'add' ? 'เพิ่มข้อมูล' : 'บันทึกการแก้ไข') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
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
const contactRecords = ref([])
const isSaving = ref(false)
const saveStatus = ref(null)
const error = ref(null)

// Modal state
const showModal = ref(false)
const modalMode = ref('add') // 'add' or 'edit'
const editingRecord = ref(null)
const editingIndex = ref(-1)

// Form data
const formData = ref({
  contact_type: 'phone',
  contact_value: '',
  is_primary: false
})

// Load contact data on mount
onMounted(async () => {
  try {
    if (props.student?.id) {
      await loadContactsFromAPI()
    } else {
      console.warn('ContactCard: No student ID provided')
    }
  } catch (error) {
    console.error('Error loading contacts:', error)
    error.value = 'ไม่สามารถโหลดข้อมูลการติดต่อได้: ' + error.message
  }
})

// Load contacts from API
const loadContactsFromAPI = async () => {
  if (!props.student?.id) {
    console.warn('ContactCard: Cannot load contacts - no student ID')
    return
  }
  
  try {
    // Use absolute URL if route helper fails
    let url
    try {
      url = route('homevisit.student.contacts.index', props.student.id)
    } catch (routeError) {
      url = `/home-visit/student/${props.student.id}/contacts`
    }
    
    const response = await axios.get(url)
    const result = response.data
    
    if (result.status === 'success' && result.data) {
      if (result.data.length > 0) {
        contactRecords.value = result.data.map(record => ({
          id: record.id,
          contact_type: record.contact_type || 'phone',
          contact_value: record.contact_value || '',
          is_primary: Boolean(record.is_primary),
          isFromAPI: true
        })).filter(record => {
          // Only filter out completely empty contact values
          return record.contact_value && record.contact_value.trim() !== ''
        })
      } else {
        // API returned success but no data
        contactRecords.value = []
      }
    } else {
      // API returned error or no success status
      contactRecords.value = []
    }
  } catch (error) {
    console.error('API Error:', error)
    // If API fails, show empty state instead of throwing
    contactRecords.value = []
    if (error.response && error.response.status !== 404) {
      error.value = 'ไม่สามารถโหลดข้อมูลการติดต่อได้'
    }
  }
}

// Delete record using API
const deleteRecord = async (index) => {
  const record = contactRecords.value[index]
  
  if (!record) return
  
  // If it's a new record (not saved), just remove from array
  if (record.isNew) {
    contactRecords.value.splice(index, 1)
    return
  }
  
  // Confirm deletion
  const result = await Swal.fire({
    title: 'ยืนยันการลบ',
    text: 'คุณต้องการลบข้อมูลการติดต่อนี้หรือไม่?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'ลบ',
    cancelButtonText: 'ยกเลิก'
  })
  
  if (!result.isConfirmed) return
  
  isSaving.value = true
  saveStatus.value = null
  
  try {
    let url
    try {
      url = route('homevisit.student.contacts.destroy', [props.student.id, record.id])
    } catch (routeError) {
      url = `/home-visit/student/${props.student.id}/contacts/${record.id}`
    }
    
    const response = await axios.delete(url)
    const result = response.data

    if (result.status === 'success' || result.success === true) {
      // 1. Remove from local array first
      contactRecords.value.splice(index, 1)

      // 2. Emit delete event to parent component
      emit('save', {
        success: true,
        type: 'contact_deleted',
        data: record
      })

      // 3. Wait for DOM update
      await nextTick()

      // 4. Reset loading state BEFORE showing alert
      isSaving.value = false

      // 5. Show success message AFTER all operations are completed
      await Swal.fire({
        title: 'ลบสำเร็จ!',
        text: 'ลบข้อมูลการติดต่อเรียบร้อยแล้ว',
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
      type: 'contact_deleted',
      message: error.message
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}

// Set record as primary
const setPrimary = async (record, index) => {
  if (!record.id) return
  
  isSaving.value = true
  saveStatus.value = null
  
  try {
    let url
    try {
      url = route('homevisit.student.contacts.set-primary', [props.student.id, record.id])
    } catch (routeError) {
      url = `/home-visit/student/${props.student.id}/contacts/${record.id}/set-primary`
    }
    
    const response = await axios.put(url)
    const result = response.data

    if (result.status === 'success' || result.success === true) {
      // 1. Update local state first
      contactRecords.value.forEach(r => r.is_primary = false)
      record.is_primary = true

      // 2. Emit success event
      emit('save', {
        success: true,
        type: 'contact_set_primary',
        data: result.data
      })

      // 3. Wait for DOM update
      await nextTick()

      // 4. Reset loading state BEFORE showing alert
      isSaving.value = false

      // 5. Show success message AFTER all operations are completed
      await Swal.fire({
        title: 'สำเร็จ!',
        text: 'ตั้งค่าเป็นการติดต่อหลักเรียบร้อยแล้ว',
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
    console.error('Set primary error:', error)
    
    await Swal.fire({
      title: 'เกิดข้อผิดพลาด!',
      text: error.message,
      icon: 'error',
      confirmButtonText: 'ตกลง'
    })

    // Emit error event
    emit('save', {
      success: false,
      type: 'contact_set_primary',
      message: error.message
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}

// Contact type text mapping
const getContactTypeText = (type) => {
  const typeMap = {
    phone: 'โทรศัพท์',
    email: 'อีเมล',
    line: 'Line',
    facebook: 'Facebook'
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
    contact_type: 'phone',
    contact_value: '',
    is_primary: false
  }
}

const populateFormData = (record) => {
  // Use Object.assign to force reactivity
  Object.assign(formData.value, {
    contact_type: record.contact_type || 'phone',
    contact_value: record.contact_value || '',
    is_primary: record.is_primary || false
  })
}

const submitForm = async () => {
  if (!formData.value.contact_value) {
    await Swal.fire({
      title: 'ข้อมูลไม่ครบถ้วน',
      text: 'กรุณากรอกข้อมูลการติดต่อ',
      icon: 'warning',
      confirmButtonText: 'ตกลง'
    })
    return
  }

  isSaving.value = true
  
  try {
    const apiData = {
      contact_type: formData.value.contact_type || 'phone',
      contact_value: formData.value.contact_value || null,
      is_primary: Boolean(formData.value.is_primary)
    }

    let response
    if (modalMode.value === 'edit' && editingRecord.value?.id) {
      // PUT request for update
      let updateUrl
      try {
        updateUrl = route('homevisit.student.contacts.update', [props.student.id, editingRecord.value.id])
      } catch (routeError) {
        updateUrl = `/home-visit/student/${props.student.id}/contacts/${editingRecord.value.id}`
      }
      
      response = await axios.put(updateUrl, apiData)
    } else {
      // POST request for create
      let storeUrl
      try {
        storeUrl = route('homevisit.student.contacts.store', props.student.id)
      } catch (routeError) {
        storeUrl = `/home-visit/student/${props.student.id}/contacts`
      }
      
      response = await axios.post(storeUrl, apiData)
    }

    const result = response.data

    // Check for different success response formats
    if (result.status === 'success' || result.success === true || response.status === 200) {
      // 1. Update local data from API response (avoid unnecessary reload)
      if (modalMode.value === 'add' && result.data) {
        // If new record is primary, unset other primary records
        if (Boolean(result.data.is_primary || formData.value.is_primary)) {
          contactRecords.value.forEach(record => record.is_primary = false)
        }
        
        // Add new record to local array
        const newRecord = {
          id: result.data.id,
          contact_type: result.data.contact_type || formData.value.contact_type,
          contact_value: result.data.contact_value || formData.value.contact_value,
          is_primary: Boolean(result.data.is_primary || formData.value.is_primary),
          isFromAPI: true
        }
        contactRecords.value.push(newRecord)
      } else if (modalMode.value === 'edit' && editingIndex.value >= 0 && result.data) {
        // If updated record is primary, unset other primary records
        if (Boolean(result.data.is_primary || formData.value.is_primary)) {
          contactRecords.value.forEach(record => record.is_primary = false)
        }
        
        // Update existing record in local array
        const updatedRecord = {
          ...contactRecords.value[editingIndex.value],
          contact_type: result.data.contact_type || formData.value.contact_type,
          contact_value: result.data.contact_value || formData.value.contact_value,
          is_primary: Boolean(result.data.is_primary || formData.value.is_primary)
        }
        contactRecords.value[editingIndex.value] = updatedRecord
      }
      
      // Fallback: If no API response data, reload once to ensure consistency
      if (!result.data) {
        await loadContactsFromAPI()
      }
      
      // 2. Wait for DOM update before closing modal
      await nextTick()
      
      // 3. Close modal
      closeModal()

      // 4. Emit success event
      emit('save', {
        success: true,
        type: modalMode.value === 'add' ? 'contact_added' : 'contact_updated',
        data: result.data
      })

      // 5. Wait for all UI updates to complete
      await nextTick()
      
      // 6. Reset loading state BEFORE showing alert
      isSaving.value = false
      
      // 7. Show success alert after all operations are completed
      await Swal.fire({
        title: 'สำเร็จ!',
        text: result.message || (modalMode.value === 'add' ? 'เพิ่มข้อมูลการติดต่อเรียบร้อยแล้ว' : 'แก้ไขข้อมูลการติดต่อเรียบร้อยแล้ว'),
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
      type: modalMode.value === 'add' ? 'contact_added' : 'contact_updated',
      message: error.message
    })
    
    // Reset loading state for error case only (success case already handled)
    isSaving.value = false
  }
}
</script>
