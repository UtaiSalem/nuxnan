<script setup lang="ts">
/**
 * Academy Admin - Import Student Cards
 * หน้านำเข้าข้อมูลนักเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const isLoading = ref(true)
const isUploading = ref(false)
const uploadProgress = ref(0)
const uploadResult = ref<any>(null)
const selectedFile = ref<File | null>(null)
const fileError = ref('')

// Import settings
const updateExisting = ref(false)
const selectedLevel = ref('')

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('students.manage')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  // Validate file type
  const allowedTypes = [
    'text/csv',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  ]
  
  if (!allowedTypes.includes(file.type) && !file.name.match(/\.(csv|xlsx|xls)$/i)) {
    fileError.value = 'กรุณาเลือกไฟล์ CSV หรือ Excel เท่านั้น'
    selectedFile.value = null
    return
  }
  
  // Validate file size (max 10MB)
  if (file.size > 10 * 1024 * 1024) {
    fileError.value = 'ขนาดไฟล์ต้องไม่เกิน 10 MB'
    selectedFile.value = null
    return
  }
  
  fileError.value = ''
  selectedFile.value = file
  uploadResult.value = null
}

const uploadFile = async () => {
  if (!selectedFile.value) return
  
  isUploading.value = true
  uploadProgress.value = 0
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('update_existing', updateExisting.value ? '1' : '0')
    if (selectedLevel.value) {
      formData.append('class_level', selectedLevel.value)
    }
    
    // Simulate progress
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10
      }
    }, 200)
    
    const response: any = await api.post(`/api/academies/${academyId.value}/student-cards/admin/import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    clearInterval(progressInterval)
    uploadProgress.value = 100
    
    uploadResult.value = response
  } catch (err: any) {
    uploadResult.value = {
      success: false,
      message: err.message || 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล'
    }
  } finally {
    isUploading.value = false
  }
}

const downloadTemplate = async () => {
  window.open(`/api/academies/${academyId.value}/student-cards/admin/export?format=template`, '_blank')
}

const formatFileSize = (bytes: number) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const resetForm = () => {
  selectedFile.value = null
  uploadResult.value = null
  uploadProgress.value = 0
  fileError.value = ''
}

const levels = ['1', '2', '3', '4', '5', '6']
</script>

<template>
  <div>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <NuxtLink 
          :to="`/academies/${academyName}/admin/student-cards`"
          class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
        >
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        </NuxtLink>
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">นำเข้าข้อมูลนักเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400">อัปโหลดไฟล์ข้อมูลนักเรียนเข้าสู่ระบบ</p>
        </div>
      </div>

      <!-- Instructions -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
        <h3 class="font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-2 mb-2">
          <Icon icon="fluent:info-24-regular" class="w-5 h-5" />
          คำแนะนำ
        </h3>
        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1 ml-7 list-disc">
          <li>รองรับไฟล์ CSV และ Excel (.xlsx, .xls)</li>
          <li>ขนาดไฟล์ไม่เกิน 10 MB</li>
          <li>ข้อมูลต้องมีคอลัมน์: รหัสนักเรียน, ชื่อ, นามสกุล, ระดับชั้น, ห้อง</li>
          <li>ดาวน์โหลดเทมเพลตเพื่อดูรูปแบบที่ถูกต้อง</li>
        </ul>
        
        <button
          @click="downloadTemplate"
          class="mt-3 ml-7 inline-flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400 hover:underline"
        >
          <Icon icon="fluent:document-arrow-down-24-regular" class="w-4 h-4" />
          ดาวน์โหลดเทมเพลต
        </button>
      </div>

      <!-- Upload Form -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="p-6 space-y-6">
          <!-- File Drop Zone -->
          <div
            class="border-2 border-dashed rounded-xl p-8 text-center transition-colors"
            :class="[
              selectedFile 
                ? 'border-primary-300 bg-primary-50 dark:bg-primary-900/20' 
                : 'border-gray-300 dark:border-gray-600 hover:border-gray-400'
            ]"
          >
            <template v-if="!selectedFile">
              <Icon icon="fluent:cloud-arrow-up-24-regular" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
              <p class="text-gray-600 dark:text-gray-400 mb-2">ลากไฟล์มาวางที่นี่ หรือ</p>
              <label class="inline-block">
                <input
                  type="file"
                  accept=".csv,.xlsx,.xls"
                  @change="handleFileSelect"
                  class="hidden"
                />
                <span class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 cursor-pointer">
                  เลือกไฟล์
                </span>
              </label>
              <p class="text-sm text-gray-500 mt-2">CSV, Excel (สูงสุด 10 MB)</p>
              
              <p v-if="fileError" class="text-red-500 text-sm mt-2">{{ fileError }}</p>
            </template>
            
            <template v-else>
              <Icon icon="fluent:document-checkmark-24-regular" class="w-12 h-12 mx-auto text-primary-500 mb-3" />
              <p class="font-medium text-gray-900 dark:text-white">{{ selectedFile.name }}</p>
              <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
              <button
                @click="resetForm"
                class="mt-3 text-sm text-red-600 hover:underline"
              >
                เปลี่ยนไฟล์
              </button>
            </template>
          </div>

          <!-- Options -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ระดับชั้น (ไม่บังคับ)
              </label>
              <select
                v-model="selectedLevel"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              >
                <option value="">ใช้ค่าจากไฟล์</option>
                <option v-for="level in levels" :key="level" :value="level">ม.{{ level }}</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">ถ้าระบุ จะใช้ค่านี้แทนค่าในไฟล์</p>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ตัวเลือก
              </label>
              <label class="flex items-center gap-3 p-3 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                <input
                  type="checkbox"
                  v-model="updateExisting"
                  class="w-4 h-4 text-primary-600 rounded"
                />
                <div>
                  <span class="text-gray-900 dark:text-white">อัปเดตข้อมูลที่มีอยู่</span>
                  <p class="text-xs text-gray-500">ถ้ารหัสนักเรียนซ้ำ จะอัปเดตข้อมูล</p>
                </div>
              </label>
            </div>
          </div>

          <!-- Upload Progress -->
          <div v-if="isUploading" class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400">กำลังนำเข้า...</span>
              <span class="text-gray-900 dark:text-white">{{ uploadProgress }}%</span>
            </div>
            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
              <div
                class="h-full bg-primary-600 transition-all duration-300"
                :style="{ width: uploadProgress + '%' }"
              ></div>
            </div>
          </div>

          <!-- Upload Result -->
          <div v-if="uploadResult" class="p-4 rounded-lg" :class="uploadResult.success ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20'">
            <div class="flex items-start gap-3">
              <Icon
                :icon="uploadResult.success ? 'fluent:checkmark-circle-24-regular' : 'fluent:error-circle-24-regular'"
                class="w-6 h-6 shrink-0"
                :class="uploadResult.success ? 'text-green-500' : 'text-red-500'"
              />
              <div>
                <p class="font-medium" :class="uploadResult.success ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                  {{ uploadResult.message }}
                </p>
                
                <div v-if="uploadResult.success && uploadResult.summary" class="mt-2 text-sm text-green-700 dark:text-green-300">
                  <p>• นำเข้าใหม่: {{ uploadResult.summary.created || 0 }} รายการ</p>
                  <p>• อัปเดต: {{ uploadResult.summary.updated || 0 }} รายการ</p>
                  <p v-if="uploadResult.summary.skipped">• ข้าม: {{ uploadResult.summary.skipped }} รายการ</p>
                </div>
                
                <div v-if="uploadResult.errors && uploadResult.errors.length > 0" class="mt-2 text-sm text-red-700 dark:text-red-300">
                  <p class="font-medium">ข้อผิดพลาด:</p>
                  <ul class="list-disc ml-4">
                    <li v-for="(error, i) in uploadResult.errors.slice(0, 5)" :key="i">
                      {{ error }}
                    </li>
                    <li v-if="uploadResult.errors.length > 5">
                      และอีก {{ uploadResult.errors.length - 5 }} รายการ...
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <NuxtLink
              :to="`/academies/${academyName}/admin/student-cards`"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              ยกเลิก
            </NuxtLink>
            <button
              @click="uploadFile"
              :disabled="!selectedFile || isUploading"
              class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <Icon v-if="isUploading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
              <Icon v-else icon="fluent:arrow-upload-24-regular" class="w-5 h-5" />
              <span>{{ isUploading ? 'กำลังนำเข้า...' : 'นำเข้าข้อมูล' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
