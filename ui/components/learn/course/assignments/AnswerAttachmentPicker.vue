<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  modelValue: File[]
  existing?: any[]
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', files: File[]): void
  (e: 'remove-existing', id: number): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const swal = useSweetAlert()

const getIconForExtension = (ext: string) => {
  const iconMap: Record<string, string> = {
    pdf: 'vscode-icons:file-type-pdf2',
    doc: 'vscode-icons:file-type-word',
    docx: 'vscode-icons:file-type-word',
    xls: 'vscode-icons:file-type-excel',
    xlsx: 'vscode-icons:file-type-excel',
    ppt: 'vscode-icons:file-type-powerpoint',
    pptx: 'vscode-icons:file-type-powerpoint',
    csv: 'vscode-icons:file-type-excel',
    zip: 'vscode-icons:file-type-zip',
    txt: 'vscode-icons:default-file',
  }
  return iconMap[ext?.toLowerCase()] || 'vscode-icons:default-file'
}

const getFileExtension = (filename: string) => {
  const parts = filename.split('.')
  return parts.length > 1 ? parts.pop()?.toLowerCase() || '' : ''
}

const formatBytes = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (!input.files || input.files.length === 0) return

  const newFiles = Array.from(input.files)
  
  // Check total count
  const existingCount = props.existing?.length || 0
  const currentCount = props.modelValue.length
  
  if (existingCount + currentCount + newFiles.length > 5) {
    swal.toast('อัปโหลดได้สูงสุด 5 ไฟล์', 'error')
    input.value = ''
    return
  }
  
  // Check sizes
  const validFiles: File[] = []
  for (const file of newFiles) {
    if (file.size > 20 * 1024 * 1024) { // 20MB
      swal.toast(`ไฟล์ ${file.name} มีขนาดเกิน 20MB`, 'error')
    } else {
      validFiles.push(file)
    }
  }
  
  if (validFiles.length > 0) {
    emit('update:modelValue', [...props.modelValue, ...validFiles])
  }
  
  // Reset input
  input.value = ''
}

const removeFile = (index: number) => {
  const newFiles = [...props.modelValue]
  newFiles.splice(index, 1)
  emit('update:modelValue', newFiles)
}

const removeExisting = (id: number) => {
  emit('remove-existing', id)
}

const triggerFileInput = () => {
  fileInput.value?.click()
}
</script>

<template>
  <div class="space-y-4">
    <div 
      @click="triggerFileInput"
      class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
    >
      <Icon icon="heroicons:document-plus" class="w-8 h-8 text-gray-400 mb-2" />
      <p class="text-sm font-medium text-gray-700 dark:text-gray-300">แนบไฟล์เอกสาร</p>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
        รองรับ PDF, Word, Excel, PPT, TXT, ZIP (สูงสุด 5 ไฟล์, ไฟล์ละไม่เกิน 20MB)
      </p>
      <input
        ref="fileInput"
        type="file"
        class="hidden"
        accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip"
        multiple
        @change="handleFileSelect"
      />
    </div>

    <!-- Existing Files -->
    <div v-if="(existing && existing.length > 0) || modelValue.length > 0" class="flex flex-col gap-3">
      <div
        v-for="attachment in existing"
        :key="'existing-' + attachment.id"
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 sm:p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm"
      >
        <div class="flex items-start sm:items-center gap-3 w-full min-w-0">
          <div class="flex-shrink-0 whitespace-nowrap w-10 h-10 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg">
            <Icon :icon="getIconForExtension(attachment.extension)" class="text-xl" />
          </div>
          <div class="flex flex-col min-w-0 flex-1 break-words">
            <span class="text-sm sm:text-base font-medium text-gray-800 dark:text-gray-100" :title="attachment.original_name">
              {{ attachment.original_name }}
            </span>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ attachment.human_size }}</span>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                อัปโหลดแล้ว
              </span>
            </div>
          </div>
        </div>
        
        <div class="flex items-center flex-shrink-0 whitespace-nowrap mt-3 sm:mt-0 sm:ml-4 self-end sm:self-auto">
          <button
            @click="removeExisting(attachment.id)"
            class="w-11 h-11 sm:w-8 sm:h-8 min-h-[44px] sm:min-h-0 flex items-center justify-center text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors"
            title="ลบ"
          >
            <Icon icon="heroicons:x-mark" class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- New Files -->
      <div
        v-for="(file, index) in modelValue"
        :key="'new-' + index"
        class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 sm:p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm"
      >
        <div class="flex items-start sm:items-center gap-3 w-full min-w-0">
          <div class="flex-shrink-0 whitespace-nowrap w-10 h-10 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg">
            <Icon :icon="getIconForExtension(getFileExtension(file.name))" class="text-xl" />
          </div>
          <div class="flex flex-col min-w-0 flex-1 break-words">
            <span class="text-sm sm:text-base font-medium text-gray-800 dark:text-gray-100" :title="file.name">
              {{ file.name }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatBytes(file.size) }}</span>
          </div>
        </div>
        
        <div class="flex items-center flex-shrink-0 whitespace-nowrap mt-3 sm:mt-0 sm:ml-4 self-end sm:self-auto">
          <button
            @click="removeFile(index)"
            class="w-11 h-11 sm:w-8 sm:h-8 min-h-[44px] sm:min-h-0 flex items-center justify-center text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-full transition-colors"
            title="ลบ"
          >
            <Icon icon="heroicons:x-mark" class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
