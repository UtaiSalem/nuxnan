<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  attachableType: 'lesson' | 'topic'
  attachableId: number
}>()

const emit = defineEmits(['uploaded'])

const swal = useSweetAlert()
const api = useApi()

const fileInput = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)
const selectedFiles = ref<File[]>([])

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files) return

  const files = Array.from(target.files)
  
  if (files.length > 5) {
    swal.toast('อัปโหลดได้สูงสุด 5 ไฟล์ต่อครั้ง', 'error')
    return
  }
  
  const invalidSize = files.find(f => f.size > 20 * 1024 * 1024)
  if (invalidSize) {
    swal.toast(`ไฟล์ ${invalidSize.name} มีขนาดเกิน 20MB`, 'error')
    return
  }

  selectedFiles.value = files
}

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
  // Reset input if empty
  if (selectedFiles.value.length === 0 && fileInput.value) {
    fileInput.value.value = ''
  }
}

const uploadFiles = async () => {
  if (selectedFiles.value.length === 0) return

  isUploading.value = true
  const formData = new FormData()
  selectedFiles.value.forEach((file) => {
    formData.append('files[]', file)
  })

  try {
    const endpoint = props.attachableType === 'lesson' 
      ? `/lessons/${props.attachableId}/attachments`
      : `/topics/${props.attachableId}/attachments`

    const response = await api.post(endpoint, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.success) {
      swal.toast('อัปโหลดไฟล์แนบสำเร็จ', 'success')
      selectedFiles.value = []
      if (fileInput.value) fileInput.value.value = ''
      emit('uploaded', response.attachments)
    }
  } catch (error: any) {
    swal.toast(error.message || 'ไม่สามารถอัปโหลดไฟล์ได้', 'error')
  } finally {
    isUploading.value = false
  }
}
</script>

<template>
  <div class="bg-gray-50 dark:bg-gray-800 p-3 sm:p-4 rounded-xl border border-gray-200 dark:border-gray-700 mt-4">
    <h3 class="text-sm sm:text-base font-semibold text-gray-800 dark:text-gray-100 mb-3">อัปโหลดไฟล์แนบเพิ่มเติม</h3>
    
    <div class="flex flex-col gap-3">
      <div 
        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-4 text-center cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors min-h-[44px]"
        @click="fileInput?.click()"
      >
        <Icon icon="heroicons:arrow-up-tray" class="text-2xl text-gray-400 mb-2 mx-auto" />
        <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">คลิกเพื่อเลือกไฟล์</p>
        <p class="text-xs text-gray-400 mt-1">รองรับ PDF, Word, Excel, PPT, TXT, ZIP (สูงสุด 5 ไฟล์, ไฟล์ละไม่เกิน 20MB)</p>
        
        <input 
          type="file" 
          ref="fileInput" 
          multiple 
          class="hidden" 
          accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
          @change="handleFileSelect"
        >
      </div>

      <div v-if="selectedFiles.length > 0" class="flex flex-col gap-2">
        <div v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between bg-white dark:bg-gray-900 p-2 rounded border border-gray-200 dark:border-gray-700 text-sm sm:text-base">
          <span class="min-w-0 flex-1 break-words pr-4 text-gray-700 dark:text-gray-300">{{ file.name }}</span>
          <button @click="removeFile(index)" class="text-red-500 hover:text-red-700 p-1 flex-shrink-0 min-h-[44px] min-w-[44px] sm:min-h-0 sm:min-w-0 flex items-center justify-center" title="ลบออก">
            <Icon icon="heroicons:x-mark" class="w-5 h-5" />
          </button>
        </div>
        
        <div class="flex justify-end mt-2">
          <button 
            @click="uploadFiles" 
            :disabled="isUploading"
            class="flex items-center justify-center gap-2 px-4 py-2 min-h-[44px] sm:min-h-0 bg-primary-600 text-white rounded-lg text-sm sm:text-base font-medium hover:bg-primary-700 disabled:opacity-50 transition-colors"
          >
            <Icon v-if="isUploading" icon="heroicons:arrow-path" class="w-5 h-5 animate-spin" />
            {{ isUploading ? 'กำลังอัปโหลด...' : 'อัปโหลดไฟล์' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
