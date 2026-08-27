<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import type { QuestionImportScope, QuestionImportPreview } from '~/types/questionImport'
import { useQuestionImportService } from '~/services/questionImportService'

interface Props {
  show: boolean
  scope: QuestionImportScope
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'imported'])

const swal = useSweetAlert()
const service = useQuestionImportService()

const step = ref<1 | 2 | 3>(1)
const isDownloading = ref(false)
const isUploading = ref(false)
const isSaving = ref(false)

const file = ref<File | null>(null)
const preview = ref<QuestionImportPreview | null>(null)
const serverError = ref<string | null>(null)

const validRows = computed(() => preview.value?.rows.filter(r => r.errors.length === 0) ?? [])

watch(() => props.show, (isOpen) => {
  if (isOpen) {
    step.value = 1
    file.value = null
    preview.value = null
    serverError.value = null
  }
})

const downloadTemplate = async () => {
  isDownloading.value = true
  try {
    await service.downloadTemplate(props.scope)
  } catch (err: any) {
    swal.toast('ดาวน์โหลดแบบฟอร์มไม่สำเร็จ', 'error')
  } finally {
    isDownloading.value = false
  }
}

const handleFileDrop = (event: DragEvent) => {
  const droppedFiles = event.dataTransfer?.files
  if (droppedFiles && droppedFiles.length > 0) {
    handleFileSelection(droppedFiles[0])
  }
}

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files.length > 0) {
    handleFileSelection(input.files[0])
    input.value = '' // Reset
  }
}

const handleFileSelection = (selectedFile: File) => {
  serverError.value = null
  const ext = selectedFile.name.split('.').pop()?.toLowerCase()
  if (!['xlsx', 'xls', 'csv'].includes(ext || '')) {
    serverError.value = 'รองรับเฉพาะไฟล์ .xlsx และ .csv'
    file.value = null
    return
  }
  if (selectedFile.size > 2 * 1024 * 1024) {
    serverError.value = 'ไฟล์ต้องมีขนาดไม่เกิน 2 MB'
    file.value = null
    return
  }
  file.value = selectedFile
}

const removeFile = () => {
  file.value = null
  serverError.value = null
}

const formatSize = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const previewFile = async () => {
  if (!file.value) return
  isUploading.value = true
  serverError.value = null
  try {
    const res = await service.previewImport(props.scope, file.value)
    preview.value = res
    step.value = 3
  } catch (err: any) {
    serverError.value = err?.data?.message ?? err?.message ?? 'เกิดข้อผิดพลาดในการตรวจสอบไฟล์'
  } finally {
    isUploading.value = false
  }
}

const commit = async () => {
  if (validRows.value.length === 0) return
  isSaving.value = true
  serverError.value = null
  try {
    const rowsData = validRows.value.map(r => r.data)
    const res = await service.commitImport(props.scope, rowsData)
    emit('imported', res.imported)
    swal.toast(`บันทึกสำเร็จ ${res.imported} ข้อ`, 'success')
  } catch (err: any) {
    serverError.value = err?.data?.message ?? err?.message ?? 'เกิดข้อผิดพลาดในการบันทึกข้อสอบ'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <TransitionRoot appear :show="show" as="template">
    <Dialog as="div" @close="emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-4xl transform overflow-y-auto max-h-[92vh] rounded-2xl bg-white dark:bg-gray-800 p-4 sm:p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-base sm:text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between gap-2">
                <span class="min-w-0">อัปโหลดข้อสอบจากไฟล์</span>
                <button @click="emit('close')" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 p-2 -mr-2 flex-shrink-0">
                  <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                </button>
              </DialogTitle>

              <!-- STEP 1: ดาวน์โหลดแบบฟอร์ม -->
              <div v-if="step === 1" class="space-y-4 sm:space-y-6">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 sm:p-6 text-center border border-gray-100 dark:border-gray-700">
                  <Icon icon="fluent:document-arrow-down-24-regular" class="w-12 h-12 text-orange-500 mx-auto mb-3" />
                  <h4 class="font-medium text-gray-900 dark:text-white mb-1">ดาวน์โหลดแบบฟอร์มตัวอย่าง</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">เพื่อความถูกต้อง กรุณาใช้ไฟล์แบบฟอร์มของเรา</p>
                  <button
                    @click="downloadTemplate"
                    :disabled="isDownloading"
                    class="inline-flex w-full sm:w-auto justify-center items-center gap-2 min-h-[44px] px-5 py-2.5 bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:hover:bg-orange-900/50 rounded-lg font-medium transition-colors"
                  >
                    <Icon v-if="isDownloading" icon="eos-icons:loading" class="w-5 h-5 animate-spin" />
                    <Icon v-else icon="fluent:arrow-download-24-filled" class="w-5 h-5" />
                    ดาวน์โหลดแบบฟอร์ม (.xlsx)
                  </button>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 p-4 rounded-xl text-sm border border-blue-100 dark:border-blue-900/50">
                  <h5 class="font-bold mb-2 flex items-center gap-2">
                    <Icon icon="fluent:info-24-regular" class="w-5 h-5" />
                    ข้อกำหนดของการอัปโหลด
                  </h5>
                  <ul class="list-disc pl-5 space-y-1">
                    <li>ตัวเลือกได้สูงสุด 6 ช่อง ต่อ 1 ข้อ</li>
                    <li>เฉลยได้ข้อเดียวต่อ 1 ข้อ</li>
                    <li>การอัปโหลด "เพิ่มต่อท้าย" ข้อสอบเดิมเสมอ ไม่ลบและไม่ทับของเดิม</li>
                    <li>ยังไม่รองรับรูปภาพ ให้เพิ่มรูปทีหลัง</li>
                    <li>สูงสุด 200 ข้อต่อไฟล์, ไฟล์ไม่เกิน 2 MB, รองรับ .xlsx และ .csv</li>
                  </ul>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                  <button
                    @click="step = 2"
                    class="w-full sm:w-auto min-h-[44px] px-6 py-2 bg-gray-900 text-white dark:bg-white dark:text-gray-900 rounded-lg hover:bg-gray-800 dark:hover:bg-gray-100 font-medium transition-colors"
                  >
                    ถัดไป
                  </button>
                </div>
              </div>

              <!-- STEP 2: เลือกไฟล์ -->
              <div v-if="step === 2" class="space-y-4 sm:space-y-6">
                <div
                  class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-5 sm:p-8 text-center transition-colors relative"
                  @dragover.prevent
                  @drop.prevent="handleFileDrop"
                >
                  <input type="file" class="hidden" accept=".xlsx,.xls,.csv" id="file-upload" @change="handleFileSelect" />
                  
                  <div v-if="!file">
                    <Icon icon="fluent:document-arrow-up-24-regular" class="w-12 h-12 text-gray-400 dark:text-gray-500 mx-auto mb-3" />
                    <p class="text-gray-600 dark:text-gray-400 mb-2">ลากไฟล์มาวางที่นี่ หรือ</p>
                    <label for="file-upload" class="cursor-pointer inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-colors">
                      เลือกไฟล์
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-3">รองรับ .xlsx และ .csv (ไม่เกิน 2 MB)</p>
                  </div>
                  <div v-else class="flex items-center justify-between bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg border border-gray-200 dark:border-gray-600 text-left">
                    <div class="flex items-center gap-3 overflow-hidden">
                      <Icon icon="fluent:document-text-24-filled" class="w-8 h-8 text-green-500 flex-shrink-0" />
                      <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ file.name }}</p>
                        <p class="text-xs text-gray-500">{{ formatSize(file.size) }}</p>
                      </div>
                    </div>
                    <button @click="removeFile" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center text-gray-400 hover:text-red-500 p-2 flex-shrink-0">
                      <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </div>

                <div v-if="serverError" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm border border-red-100 dark:border-red-900/30 flex items-start gap-2">
                  <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                  <span>{{ serverError }}</span>
                </div>

                <div class="flex justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                  <button
                    @click="step = 1"
                    class="min-h-[44px] px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg font-medium transition-colors"
                  >
                    ย้อนกลับ
                  </button>
                  <button
                    @click="previewFile"
                    :disabled="!file || isUploading"
                    class="min-h-[44px] px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  >
                    <Icon v-if="isUploading" icon="eos-icons:loading" class="w-5 h-5 animate-spin" />
                    ตรวจสอบไฟล์
                  </button>
                </div>
              </div>

              <!-- STEP 3: ตรวจสอบและยืนยัน -->
              <div v-if="step === 3 && preview" class="space-y-4">
                <div class="flex flex-wrap gap-3 bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700">
                  <div class="px-3 py-1 rounded bg-white dark:bg-gray-800 text-sm shadow-sm">
                    ทั้งหมด <span class="font-bold">{{ preview.summary.total }}</span> ข้อ
                  </div>
                  <div class="px-3 py-1 rounded bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm shadow-sm">
                    ใช้ได้ <span class="font-bold">{{ preview.summary.valid }}</span> ข้อ
                  </div>
                  <div class="px-3 py-1 rounded bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm shadow-sm">
                    มีปัญหา <span class="font-bold">{{ preview.summary.invalid }}</span> ข้อ
                  </div>
                  <div class="px-3 py-1 rounded bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm shadow-sm">
                    คำเตือน <span class="font-bold">{{ preview.summary.warnings }}</span> ข้อ
                  </div>
                </div>

                <div v-if="preview.summary.invalid > 0" class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-3 rounded-lg text-sm border border-red-100 dark:border-red-900/30 flex items-start gap-2">
                  <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                  <span>ระบบจะบันทึกเฉพาะข้อที่ใช้ได้ ข้อที่มีปัญหาให้แก้ไขในไฟล์แล้วอัปโหลดใหม่</span>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                  <div class="max-h-[50vh] overflow-y-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                      <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300 sticky top-0 z-10 shadow-sm">
                        <tr>
                          <th class="px-4 py-3">แถวที่</th>
                          <th class="px-4 py-3 min-w-[200px]">คำถาม</th>
                          <th class="px-4 py-3">ตัวเลือก</th>
                          <th class="px-4 py-3">เฉลย</th>
                          <th class="px-4 py-3">คะแนน</th>
                          <th class="px-4 py-3">สถานะ</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="row in preview.rows" :key="row.row_number" 
                          class="border-b dark:border-gray-700"
                          :class="[
                            row.errors.length > 0 ? 'bg-red-50 dark:bg-red-900/20' : 
                            row.warnings.length > 0 ? 'bg-amber-50 dark:bg-amber-900/10' : 
                            'bg-white dark:bg-gray-800'
                          ]"
                        >
                          <td class="px-4 py-3 align-top">{{ row.row_number }}</td>
                          <td class="px-4 py-3 align-top whitespace-normal break-words max-w-xs">{{ row.data.text }}</td>
                          <td class="px-4 py-3 align-top">{{ row.data.options?.length || 0 }}</td>
                          <td class="px-4 py-3 align-top whitespace-normal break-words max-w-[150px]">
                            <span v-if="row.data.options && row.data.options[row.data.correct] !== undefined">
                              {{ row.data.options[row.data.correct] }}
                            </span>
                          </td>
                          <td class="px-4 py-3 align-top">{{ row.data.points }}</td>
                          <td class="px-4 py-3 align-top whitespace-normal">
                            <div v-if="row.errors.length === 0 && row.warnings.length === 0" class="text-green-600 flex items-center gap-1">
                              <Icon icon="fluent:checkmark-circle-16-regular" /> ปกติ
                            </div>
                            <div class="flex flex-col gap-1">
                              <div v-for="(err, i) in row.errors" :key="'e'+i" class="text-red-600 text-xs flex items-start gap-1">
                                <Icon icon="fluent:error-circle-12-regular" class="mt-0.5 flex-shrink-0" /> {{ err }}
                              </div>
                              <div v-for="(warn, i) in row.warnings" :key="'w'+i" class="text-amber-600 text-xs flex items-start gap-1">
                                <Icon icon="fluent:warning-12-regular" class="mt-0.5 flex-shrink-0" /> {{ warn }}
                              </div>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div v-if="serverError" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm border border-red-100 dark:border-red-900/30 flex items-start gap-2">
                  <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                  <span>{{ serverError }}</span>
                </div>

                <div class="flex justify-between pt-4 border-t border-gray-100 dark:border-gray-700 mt-4">
                  <button
                    @click="step = 2"
                    :disabled="isSaving"
                    class="min-h-[44px] px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg font-medium transition-colors disabled:opacity-50"
                  >
                    ย้อนกลับ
                  </button>
                  <button
                    @click="commit"
                    :disabled="validRows.length === 0 || isSaving"
                    class="min-h-[44px] px-4 sm:px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm sm:text-base"
                  >
                    <Icon v-if="isSaving" icon="eos-icons:loading" class="w-5 h-5 animate-spin" />
                    ยืนยันบันทึก {{ validRows.length }} ข้อ
                  </button>
                </div>
              </div>

            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
