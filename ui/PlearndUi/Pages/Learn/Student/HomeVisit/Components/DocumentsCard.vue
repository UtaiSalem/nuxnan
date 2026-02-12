<template>
  <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-amber-500 to-yellow-600 px-4 py-3 sm:px-6 sm:py-4">
      <div class="flex items-center">
        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div class="ml-2 sm:ml-3 min-w-0 flex-1">
          <h3 class="text-base sm:text-lg font-semibold text-white truncate">
            เอกสารประกอบ (ตาราง student_documents)
          </h3>
          <p class="text-amber-100 text-xs sm:text-sm">เอกสารและไฟล์แนบของนักเรียน</p>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 sm:p-6 bg-gradient-to-br from-amber-50 to-yellow-50">
      <div class="space-y-6">
        <!-- Document Upload Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
          <!-- Document Type -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a2 2 0 012-2z" />
              </svg>
              <span class="truncate">ประเภทเอกสาร</span>
            </label>
            <select
              v-model="form.documents.document_type"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
            >
              <option value="birth_certificate">สูติบัตร</option>
              <option value="id_card">สำเนาบัตรประชาชน</option>
              <option value="house_registration">สำเนาทะเบียนบ้าน</option>
              <option value="transcript">ใบรายงานผลการเรียน</option>
              <option value="medical_certificate">ใบรับรองแพทย์</option>
              <option value="vaccination_record">บันทึกการฉีดวัคซีน</option>
              <option value="guardian_id">สำเนาบัตรประชาชนผู้ปกครอง</option>
              <option value="other">อื่นๆ</option>
            </select>
          </div>

          <!-- Document Name -->
          <div class="space-y-2">
            <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span class="truncate">ชื่อเอกสาร</span>
            </label>
            <input
              type="text"
              v-model="form.documents.document_name"
              class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors text-sm sm:text-base touch-manipulation placeholder-gray-400"
              placeholder="กรอกชื่อเอกสาร"
            >
          </div>
        </div>

        <!-- File Upload -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <span class="truncate">อัพโหลดไฟล์</span>
          </label>
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 sm:p-6 text-center hover:border-amber-400 transition-colors">
            <input
              type="file"
              @change="handleFileUpload"
              multiple
              accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
              class="hidden"
              ref="fileInput"
            >
            <div class="space-y-2">
              <svg class="mx-auto h-8 w-8 sm:h-12 sm:w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <div class="text-sm sm:text-base text-gray-600">
                <button
                  type="button"
                  @click="$refs.fileInput.click()"
                  class="font-medium text-amber-600 hover:text-amber-500 transition-colors"
                >
                  คลิกเพื่อเลือกไฟล์
                </button>
                <span class="hidden sm:inline"> หรือลากไฟล์มาวางที่นี่</span>
              </div>
              <p class="text-xs sm:text-sm text-gray-500">
                PDF, JPG, PNG, DOC, DOCX (สูงสุด 10MB)
              </p>
            </div>
          </div>
        </div>

        <!-- Uploaded Files List -->
        <div v-if="uploadedFiles.length > 0" class="space-y-3">
          <h4 class="text-sm sm:text-base font-medium text-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            ไฟล์ที่อัพโหลด
          </h4>
          
          <div class="space-y-2">
            <div
              v-for="(file, index) in uploadedFiles"
              :key="index"
              class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg"
            >
              <div class="flex items-center min-w-0 flex-1">
                <div class="flex-shrink-0">
                  <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="ml-3 min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                  <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-2 ml-3">
                <!-- View Button -->
                <button
                  type="button"
                  @click="viewFile(file)"
                  class="p-1.5 text-blue-600 hover:text-blue-500 transition-colors"
                  title="ดูไฟล์"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
                <!-- Download Button -->
                <button
                  type="button"
                  @click="downloadFile(file)"
                  class="p-1.5 text-green-600 hover:text-green-500 transition-colors"
                  title="ดาวน์โหลด"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </button>
                <!-- Delete Button -->
                <button
                  type="button"
                  @click="removeFile(index)"
                  class="p-1.5 text-red-600 hover:text-red-500 transition-colors"
                  title="ลบไฟล์"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Document Notes -->
        <div class="space-y-2">
          <label class="flex items-center text-xs sm:text-sm font-medium text-gray-700">
            <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            <span class="truncate">หมายเหตุ</span>
          </label>
          <textarea
            v-model="form.documents.notes"
            rows="3"
            class="w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors text-sm sm:text-base touch-manipulation resize-none"
            placeholder="หมายเหตุเพิ่มเติมเกี่ยวกับเอกสาร"
          ></textarea>
        </div>
      </div>
      
      <!-- Save Button Section -->
      <div class="px-4 py-4 sm:px-6 sm:py-6 bg-gray-50 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div class="text-xs sm:text-sm text-gray-500">
            <span v-if="isSaving" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
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
            <span v-else>เอกสารและไฟล์แนบ ({{ uploadedFiles.length }} ไฟล์)</span>
          </div>
          
          <button
            type="button"
            @click="saveDocumentData"
            :disabled="isSaving"
            class="inline-flex items-center px-4 py-2 sm:px-6 sm:py-2.5 bg-amber-600 text-white text-sm sm:text-base font-medium rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors touch-manipulation placeholder-gray-400"
          >
            <svg v-if="!isSaving" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v6a2 2 0 002 2h2m0 0h8m0 0h2a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4 0V5a2 2 0 011-1h4a2 2 0 011 1v2M8 7V5a2 2 0 011-1h4a2 2 0 011 1v2m0 0v4m0 0h-4m4 0V9H8v4z"></path>
            </svg>
            <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกเอกสาร' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  student: {
    type: Object,
    required: true
  },
  isLoading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['save', 'update'])

// Create reactive form data from student prop
const form = ref({
  documents: {
    document_type: 'birth_certificate',
    document_name: '',
    notes: '',
    is_required: false
  }
})

// Save functionality
const isSaving = ref(false)
const saveStatus = ref(null)

const uploadedFiles = ref([])

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    if (file.size <= 10 * 1024 * 1024) { // 10MB limit
      uploadedFiles.value.push(file)
    } else {
      alert(`ไฟล์ ${file.name} มีขนาดเกิน 10MB`)
    }
  })
  event.target.value = '' // Reset input
}

const removeFile = (index) => {
  uploadedFiles.value.splice(index, 1)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const viewFile = (file) => {
  const url = URL.createObjectURL(file)
  window.open(url, '_blank')
}

const downloadFile = (file) => {
  const url = URL.createObjectURL(file)
  const a = document.createElement('a')
  a.href = url
  a.download = file.name
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

// Save document data function
const saveDocumentData = async () => {
  isSaving.value = true
  saveStatus.value = null
  
  try {
    // Create FormData for file upload
    const formData = new FormData()
    
    // Add student ID and document info
    formData.append('student_id', props.student?.student_id || props.student?.id)
    formData.append('document_type', form.value.documents.document_type || '')
    formData.append('document_name', form.value.documents.document_name || '')
    formData.append('notes', form.value.documents.notes || '')
    formData.append('is_required', form.value.documents.is_required || false)
    
    // Add files
    uploadedFiles.value.forEach((file, index) => {
      formData.append(`files[${index}]`, file)
    })
    
    // Use Inertia.js to save data with files
    await router.post(route('homevisit.student.update.documents'), formData, {
      preserveScroll: true,
      forceFormData: true,
      onSuccess: () => {
        saveStatus.value = 'success'
        // Emit save event to parent component
        emit('save', {
          success: true,
          type: 'documents',
          data: {
            document_type: form.value.documents.document_type,
            document_name: form.value.documents.document_name,
            notes: form.value.documents.notes,
            is_required: form.value.documents.is_required,
            files: uploadedFiles.value
          }
        })
        uploadedFiles.value = [] // Clear uploaded files after successful save
        setTimeout(() => {
          saveStatus.value = null
        }, 3000)
      },
      onError: (errors) => {
        saveStatus.value = 'error'
        // Emit save event with error to parent component
        emit('save', {
          success: false,
          type: 'documents',
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
