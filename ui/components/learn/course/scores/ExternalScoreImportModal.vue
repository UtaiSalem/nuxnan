<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { Icon } from '@iconify/vue'
import { useExternalScoreImportService } from '~/services/externalScoreImportService'
import type { ExternalScoreImportPreview, ExternalScoreImportTopic } from '~/types/externalScoreImport'

interface Props {
  show: boolean
  courseId: number
  topics: ExternalScoreImportTopic[]
  initialTopicId?: number | null
  groupId: number | null
  groupName: string | null
}
const props = defineProps<Props>()
const emit = defineEmits(['close', 'imported'])

const swal = useSweetAlert()
const service = useExternalScoreImportService()

const step = ref<1 | 2>(1)
const selectedTopicId = ref<number | null>(null)
const isDownloading = ref(false)
const isUploading = ref(false)
const isSaving = ref(false)

const file = ref<File | null>(null)
const preview = ref<ExternalScoreImportPreview | null>(null)
const serverError = ref<string | null>(null)

const selectedTopic = computed(() => props.topics.find(t => t.id === selectedTopicId.value) ?? null)
const committableRows = computed(() => preview.value?.rows.filter(r => r.errors.length === 0 && r.action !== 'skip') ?? [])

watch(() => props.show, (isOpen) => {
  if (isOpen) {
    step.value = 1
    file.value = null
    preview.value = null
    serverError.value = null
    selectedTopicId.value = props.initialTopicId ?? (props.topics.length === 1 ? props.topics[0].id : null)
  }
})

const downloadTemplate = async () => {
  if (!selectedTopicId.value) return
  isDownloading.value = true
  try {
    await service.downloadTemplate(props.courseId, selectedTopicId.value, props.groupId)
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
    input.value = ''
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
  if (!file.value || !selectedTopicId.value) return
  isUploading.value = true
  serverError.value = null
  try {
    const res = await service.previewImport(props.courseId, selectedTopicId.value, file.value, props.groupId)
    preview.value = res
    step.value = 2
  } catch (err: any) {
    serverError.value = err?.data?.message ?? err?.message ?? 'เกิดข้อผิดพลาดในการตรวจสอบไฟล์'
  } finally {
    isUploading.value = false
  }
}

const commit = async () => {
  if (committableRows.value.length === 0 || !selectedTopicId.value || !preview.value) return
  isSaving.value = true
  serverError.value = null
  try {
    await service.commitImport(props.courseId, selectedTopicId.value, preview.value.rows)
    swal.success('สำเร็จ', 'บันทึกคะแนนเรียบร้อยแล้ว')
    emit('imported')
    emit('close')
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.data?.message ?? err?.message ?? 'ไม่สามารถบันทึกคะแนนได้')
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
            <DialogPanel class="w-full max-w-lg sm:max-w-3xl transform overflow-y-auto max-h-[90vh] rounded-2xl bg-white dark:bg-gray-800 p-3 sm:p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-base sm:text-lg font-bold leading-6 text-gray-900 dark:text-white mb-4 flex items-center justify-between gap-2">
                <span class="min-w-0">บันทึกคะแนนจากไฟล์</span>
                <button @click="emit('close')" class="text-gray-400 hover:text-gray-500 p-2 -mr-2 flex-shrink-0">
                  <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                </button>
              </DialogTitle>

              <!-- STEP 1: เลือกหัวข้อ + ดาวน์โหลด + เลือกไฟล์ -->
              <div v-if="step === 1" class="space-y-4 sm:space-y-6">
                <!-- แถบบริบทด้านบน -->
                <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 p-3 sm:p-4 rounded-xl text-sm border border-blue-100 dark:border-blue-900/50">
                  <div class="font-bold flex items-center gap-2">
                    <Icon icon="fluent:people-community-24-regular" class="w-5 h-5 flex-shrink-0" />
                    กลุ่มเรียน: {{ groupName ?? 'ทุกกลุ่มเรียน' }}
                  </div>
                  <div v-if="!groupName" class="mt-1 ml-7 text-blue-600 dark:text-blue-400">
                    ไฟล์จะมีรายชื่อนักเรียนทั้งรายวิชา
                  </div>
                </div>

                <!-- เลือกหัวข้อ -->
                <div v-if="initialTopicId === null || initialTopicId === undefined" class="space-y-1">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">หัวข้อคะแนน</label>
                  <select
                    v-model="selectedTopicId"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white min-h-[44px]"
                  >
                    <option :value="null" disabled>-- เลือกหัวข้อคะแนน --</option>
                    <option v-for="t in topics" :key="t.id" :value="t.id">
                      {{ t.title }} (เต็ม {{ t.max_score }})
                    </option>
                  </select>
                </div>
                <div v-else class="space-y-1">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">หัวข้อคะแนน</label>
                  <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white font-medium min-h-[44px] flex items-center">
                    {{ selectedTopic?.title }} (เต็ม {{ selectedTopic?.max_score }})
                  </div>
                </div>

                <!-- ดาวน์โหลดแบบฟอร์ม -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 sm:p-6 text-center border border-gray-100 dark:border-gray-700">
                  <button
                    @click="downloadTemplate"
                    :disabled="isDownloading || !selectedTopicId"
                    class="inline-flex w-full sm:w-auto justify-center items-center gap-2 min-h-[44px] px-5 py-2.5 bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:hover:bg-orange-900/50 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Icon v-if="isDownloading" icon="eos-icons:loading" class="w-5 h-5 animate-spin" />
                    <Icon v-else icon="fluent:arrow-download-24-filled" class="w-5 h-5" />
                    ดาวน์โหลดแบบฟอร์ม (.xlsx)
                  </button>
                </div>

                <!-- อัปโหลดไฟล์ -->
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
                    <button @click="removeFile" class="text-gray-400 hover:text-red-500 p-2 flex-shrink-0 min-h-[44px] min-w-[44px] flex items-center justify-center">
                      <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </div>

                <div class="bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 p-4 rounded-xl text-sm border border-amber-100 dark:border-amber-900/50">
                  <h5 class="font-bold mb-2 flex items-center gap-2">
                    <Icon icon="fluent:info-24-regular" class="w-5 h-5" />
                    คำแนะนำ
                  </h5>
                  <ul class="list-disc pl-5 space-y-1">
                    <li>เว้นช่องคะแนนว่าง = ไม่เปลี่ยนแปลงคะแนนเดิม</li>
                    <li>พิมพ์เครื่องหมาย - = ล้างคะแนนของคนนั้น</li>
                  </ul>
                </div>

                <div v-if="serverError" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded-lg text-sm border border-red-100 dark:border-red-900/30 flex items-start gap-2">
                  <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                  <span>{{ serverError }}</span>
                </div>

                <div class="flex flex-col sm:flex-row sm:justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                  <button
                    @click="previewFile"
                    :disabled="!file || !selectedTopicId || isUploading"
                    class="w-full sm:w-auto min-h-[44px] px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  >
                    <Icon v-if="isUploading" icon="eos-icons:loading" class="w-5 h-5 animate-spin" />
                    ตรวจสอบไฟล์
                  </button>
                </div>
              </div>

              <!-- STEP 2: ผลตรวจ -->
              <div v-if="step === 2 && preview" class="space-y-4 sm:space-y-6">
                <!-- การ์ดสรุป -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                  <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">ทั้งหมด</div>
                    <div class="text-lg font-bold text-gray-900 dark:text-white">{{ preview.summary.total }}</div>
                  </div>
                  <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-xl border border-green-100 dark:border-green-900/30 text-center">
                    <div class="text-xs text-green-700 dark:text-green-400 mb-1">จะบันทึก</div>
                    <div class="text-lg font-bold text-green-700 dark:text-green-400">{{ preview.summary.set }}</div>
                  </div>
                  <div class="bg-orange-50 dark:bg-orange-900/20 p-3 rounded-xl border border-orange-100 dark:border-orange-900/30 text-center">
                    <div class="text-xs text-orange-700 dark:text-orange-400 mb-1">จะล้างคะแนน</div>
                    <div class="text-lg font-bold text-orange-700 dark:text-orange-400">{{ preview.summary.clear }}</div>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">ข้าม (เว้นว่าง)</div>
                    <div class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ preview.summary.skip }}</div>
                  </div>
                  <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-xl border border-red-100 dark:border-red-900/30 text-center">
                    <div class="text-xs text-red-700 dark:text-red-400 mb-1">ผิดพลาด</div>
                    <div class="text-lg font-bold text-red-700 dark:text-red-400">{{ preview.summary.invalid }}</div>
                  </div>
                  <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-xl border border-gray-100 dark:border-gray-700 text-center">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">ไม่อยู่ในไฟล์</div>
                    <div class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ preview.summary.missing }}</div>
                  </div>
                </div>

                <div v-if="preview.summary.invalid > 0" class="bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 p-3 rounded-lg text-sm border border-red-100 dark:border-red-900/30 flex items-start gap-2">
                  <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                  <span>แถวที่ผิดพลาดจะถูกข้าม ไม่ถูกบันทึก</span>
                </div>

                <!-- ตาราง -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                  <div class="max-h-[50vh] overflow-y-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                      <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300 sticky top-0 z-10 shadow-sm">
                        <tr>
                          <th class="px-3 sm:px-4 py-3">เลขที่</th>
                          <th class="px-3 sm:px-4 py-3 min-w-[150px]">ชื่อ</th>
                          <th class="px-3 sm:px-4 py-3">คะแนนเดิม</th>
                          <th class="px-3 sm:px-4 py-3">คะแนนใหม่</th>
                          <th class="px-3 sm:px-4 py-3">สถานะ</th>
                          <th class="px-3 sm:px-4 py-3">ปัญหา</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="row in preview.rows" :key="row.row_number" 
                          class="border-b dark:border-gray-700"
                          :class="[
                            row.errors.length > 0 ? 'bg-red-50 dark:bg-red-900/20' : 
                            row.action === 'clear' ? 'bg-orange-50 dark:bg-orange-900/10' : 
                            row.action === 'skip' ? 'text-gray-400 bg-gray-50 dark:bg-gray-800/50' :
                            'bg-white dark:bg-gray-800'
                          ]"
                        >
                          <td class="px-3 sm:px-4 py-3 align-top">{{ row.order_number ?? '-' }}</td>
                          <td class="px-3 sm:px-4 py-3 align-top whitespace-normal">
                            <div class="font-medium" :class="{ 'text-gray-400': row.action === 'skip' }">{{ row.name }}</div>
                            <div v-if="row.group_name" class="text-xs opacity-70">{{ row.group_name }}</div>
                            
                            <!-- Errors and Warnings under name as requested -->
                            <div v-if="row.errors.length > 0" class="mt-1 flex flex-col gap-0.5">
                              <div v-for="(err, i) in row.errors" :key="'e'+i" class="text-red-600 text-xs flex items-start gap-1">
                                <Icon icon="fluent:error-circle-12-regular" class="mt-0.5 flex-shrink-0" /> {{ err }}
                              </div>
                            </div>
                            <div v-if="row.warnings.length > 0" class="mt-1 flex flex-col gap-0.5">
                              <div v-for="(warn, i) in row.warnings" :key="'w'+i" class="text-amber-600 text-xs flex items-start gap-1">
                                <Icon icon="fluent:warning-12-regular" class="mt-0.5 flex-shrink-0" /> {{ warn }}
                              </div>
                            </div>
                          </td>
                          <td class="px-3 sm:px-4 py-3 align-top">{{ row.current_score ?? '-' }}</td>
                          <td class="px-3 sm:px-4 py-3 align-top font-bold">{{ row.new_score ?? '-' }}</td>
                          <td class="px-3 sm:px-4 py-3 align-top">
                            <span v-if="row.action === 'set'" class="text-green-600 font-medium">บันทึก</span>
                            <span v-else-if="row.action === 'clear'" class="text-orange-600 font-medium">ล้างคะแนน</span>
                            <span v-else class="text-gray-400">ข้าม</span>
                          </td>
                          <td class="px-3 sm:px-4 py-3 align-top whitespace-normal">
                             <div v-if="row.errors.length === 0 && row.warnings.length === 0" class="text-green-600 flex items-center gap-1">
                              <Icon icon="fluent:checkmark-circle-16-regular" />
                            </div>
                            <!-- They are also shown under the name, but having a column might be fine, or we can just leave it since the requirement says "แสดง errors/warnings เป็นข้อความเล็กใต้ชื่อ" -->
                            <div v-else-if="row.errors.length > 0" class="text-red-600 font-medium">
                               มีข้อผิดพลาด
                            </div>
                            <div v-else-if="row.warnings.length > 0" class="text-amber-600 font-medium">
                               มีคำเตือน
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

                <div class="flex flex-col sm:flex-row sm:justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                  <button
                    @click="step = 1"
                    :disabled="isSaving"
                    class="w-full sm:w-auto min-h-[44px] px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg font-medium transition-colors disabled:opacity-50"
                  >
                    ย้อนกลับ
                  </button>
                  <button
                    @click="commit"
                    :disabled="committableRows.length === 0 || isSaving"
                    class="w-full sm:w-auto min-h-[44px] px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                  >
                    <Icon v-if="isSaving" icon="eos-icons:loading" class="w-5 h-5 animate-spin" />
                    บันทึกคะแนน {{ committableRows.length }} รายการ
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
