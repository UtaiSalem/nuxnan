<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  attachments: any[]
  isAdmin?: boolean
}>()

const emit = defineEmits(['delete', 'download'])

const api = useApi()
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

const handleDownload = async (attachment: any) => {
  try {
    const url = new URL(attachment.download_url)
    const endpoint = url.pathname // useApi expects relative path for base URL
    
    const { blob, filename } = await api.getBlob(endpoint)
    
    const downloadUrl = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = downloadUrl
    link.setAttribute('download', attachment.original_name || filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(downloadUrl)
    
    emit('download', attachment)
  } catch (error: any) {
    swal.toast(error.message || 'เกิดข้อผิดพลาดในการดาวน์โหลด', 'error')
  }
}

const handleDelete = async (attachment: any) => {
  const confirmed = await swal.confirm('ยืนยันการลบ', `คุณต้องการลบไฟล์ "${attachment.original_name}" ใช่หรือไม่?`)
  if (confirmed) {
    emit('delete', attachment)
  }
}
</script>

<template>
  <div class="flex flex-col gap-3">
    <div
      v-for="attachment in attachments"
      :key="attachment.id"
      class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 sm:p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200"
    >
      <div class="flex items-start sm:items-center gap-3 w-full min-w-0">
        <div class="flex-shrink-0 whitespace-nowrap w-10 h-10 flex items-center justify-center bg-gray-50 dark:bg-gray-700 rounded-lg">
          <Icon :icon="getIconForExtension(attachment.extension)" class="text-xl" />
        </div>
        <div class="flex flex-col min-w-0 flex-1 break-words">
          <span class="text-sm sm:text-base font-medium text-gray-800 dark:text-gray-100" :title="attachment.original_name">
            {{ attachment.original_name }}
          </span>
          <span class="text-xs text-gray-500 dark:text-gray-400">{{ attachment.human_size }}</span>
        </div>
      </div>
      
      <div class="flex items-center gap-2 flex-shrink-0 whitespace-nowrap mt-3 sm:mt-0 sm:ml-4 self-end sm:self-auto">
        <button
          @click="handleDownload(attachment)"
          class="w-11 h-11 sm:w-8 sm:h-8 min-h-[44px] sm:min-h-0 flex items-center justify-center text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-full transition-colors"
          title="ดาวน์โหลด"
        >
          <Icon icon="heroicons:arrow-down-tray" class="w-5 h-5" />
        </button>
        
        <button
          v-if="isAdmin"
          @click="handleDelete(attachment)"
          class="w-11 h-11 sm:w-8 sm:h-8 min-h-[44px] sm:min-h-0 flex items-center justify-center text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-full transition-colors"
          title="ลบ"
        >
          <Icon icon="heroicons:trash" class="w-5 h-5" />
        </button>
      </div>
    </div>
    
    <div v-if="attachments.length === 0" class="text-center py-6 text-sm sm:text-base text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
      ไม่มีไฟล์แนบ
    </div>
  </div>
</template>
