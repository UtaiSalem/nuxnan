<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/Common/RichTextViewer.vue'
import { useDocxToHtml, DOCX_MAX_BYTES } from '~/composables/useDocxToHtml'
import { useLessonImportService } from '~/services/lessonImportService'
import type {
  LessonImportLesson,
  LessonImportPreview,
  LessonImportSection,
  LessonPublicationStatus,
} from '~/types/lessonImport'

const ATTACHMENT_MAX_BYTES = 20 * 1024 * 1024
const ATTACHMENT_MAX_COUNT = 15
const ATTACHMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'csv', 'zip']

const templateUrl = '/templates/lesson-import-template.docx'

const courseStore = useCourseStore()
const { currentCourse: course, isCourseAdmin } = storeToRefs(courseStore)

const route = useRoute()
const router = useRouter()
const swal = useSweetAlert()
const service = useLessonImportService()
const { convert } = useDocxToHtml()

const courseId = route.params.id as string
const isCourseLoaded = ref(false)

onMounted(async () => {
  if (!course.value || String(course.value.id) !== String(courseId)) {
    try {
      await courseStore.fetchCourse(courseId)
    } catch (err) {
      console.error('Failed to fetch course:', err)
      router.push('/Learn/Courses')
      return
    }
  }
  isCourseLoaded.value = true
})

const courseName = computed(() => course?.value?.name || 'รายวิชา')

useHead({ title: 'นำเข้าบทเรียนจากไฟล์' })

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------
const step = ref<1 | 2 | 3>(1)
const docxFile = ref<File | null>(null)
const attachmentFiles = ref<File[]>([])
const isConverting = ref(false)
const isPreviewing = ref(false)
const isSaving = ref(false)
const isUploadingAttachments = ref(false)
const preview = ref<LessonImportPreview | null>(null)
const form = reactive<{ lesson: LessonImportLesson; topics: LessonImportSection[] }>({
  lesson: { title: '', description: null, content: '', youtube_url: null, min_read: 0 },
  topics: [],
})
const publicationStatus = ref<LessonPublicationStatus>('draft')
const clientWarnings = ref<string[]>([])
const errorText = ref<string | null>(null)
const expandedTopic = ref<number | null>(null)
const showLessonContent = ref(false)

const steps = [
  { id: 1, label: 'เลือกไฟล์', icon: 'fluent:document-arrow-up-24-regular' },
  { id: 2, label: 'ตรวจทาน', icon: 'fluent:clipboard-task-24-regular' },
  { id: 3, label: 'บันทึก', icon: 'fluent:checkmark-circle-24-regular' },
] as const

const isBusy = computed(() => isConverting.value || isPreviewing.value)

const totalMinRead = computed(() =>
  (Number(form.lesson.min_read) || 0) + form.topics.reduce((sum, t) => sum + (Number(t.min_read) || 0), 0),
)

const allWarnings = computed(() => [...(preview.value?.warnings ?? []), ...clientWarnings.value])

const canSave = computed(() =>
  !!form.lesson.title.trim()
  && form.topics.every(t => !!t.title.trim())
  && !isSaving.value,
)

const formatMb = (bytes: number) =>
  bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} KB`
    : `${(bytes / 1024 / 1024).toFixed(1)} MB`

// ---------------------------------------------------------------------------
// Step 1 — file selection
// ---------------------------------------------------------------------------
const selectDocx = (file: File) => {
  errorText.value = null
  if (!file.name.toLowerCase().endsWith('.docx')) {
    errorText.value = 'รองรับเฉพาะไฟล์ .docx'
    return
  }
  if (file.size > DOCX_MAX_BYTES) {
    errorText.value = 'ไฟล์ใหญ่เกิน 15MB'
    return
  }
  docxFile.value = file
}

const handleDocxChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) selectDocx(file)
  input.value = ''
}

const handleDocxDrop = (event: DragEvent) => {
  const file = event.dataTransfer?.files?.[0]
  if (file) selectDocx(file)
}

const clearDocx = () => {
  docxFile.value = null
  errorText.value = null
}

const handleAttachmentChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  input.value = ''
  for (const file of files) {
    const ext = file.name.split('.').pop()?.toLowerCase() || ''
    if (!ATTACHMENT_EXTENSIONS.includes(ext)) {
      swal.toast(`ไม่รองรับไฟล์ชนิดนี้: ${file.name}`, 'error')
      continue
    }
    if (file.size > ATTACHMENT_MAX_BYTES) {
      swal.toast(`ไฟล์ ${file.name} ใหญ่เกิน 20MB`, 'error')
      continue
    }
    if (attachmentFiles.value.length >= ATTACHMENT_MAX_COUNT) {
      swal.toast(`แนบไฟล์ประกอบได้สูงสุด ${ATTACHMENT_MAX_COUNT} ไฟล์`, 'error')
      break
    }
    attachmentFiles.value.push(file)
  }
}

const removeAttachment = (index: number) => {
  attachmentFiles.value.splice(index, 1)
}

const startPreview = async () => {
  if (!docxFile.value || isBusy.value) return
  const file = docxFile.value
  errorText.value = null
  try {
    isConverting.value = true
    const { html, messages } = await convert(file)
    clientWarnings.value = messages.slice(0, 10)
    isConverting.value = false

    isPreviewing.value = true
    const res = await service.preview(courseId, html, file.name)
    preview.value = res

    form.lesson = { ...res.lesson }
    form.topics = res.topics.map(t => ({ ...t }))
    expandedTopic.value = null
    showLessonContent.value = false
    step.value = 2
  } catch (err: any) {
    errorText.value = service.errorMessage(err, 'ไม่สามารถอ่านไฟล์ได้')
  } finally {
    isConverting.value = false
    isPreviewing.value = false
  }
}

// ---------------------------------------------------------------------------
// Step 2 — review
// ---------------------------------------------------------------------------
const toggleTopic = (index: number) => {
  expandedTopic.value = expandedTopic.value === index ? null : index
}

const moveTopic = (index: number, direction: -1 | 1) => {
  const target = index + direction
  if (target < 0 || target >= form.topics.length) return
  const list = form.topics
  const tmp = list[index]
  list[index] = list[target]
  list[target] = tmp
  if (expandedTopic.value === index) expandedTopic.value = target
  else if (expandedTopic.value === target) expandedTopic.value = index
}

const removeTopic = (index: number) => {
  form.topics.splice(index, 1)
  if (expandedTopic.value === index) expandedTopic.value = null
  else if (expandedTopic.value !== null && expandedTopic.value > index) expandedTopic.value -= 1
}

const resetToStep1 = () => {
  step.value = 1
  preview.value = null
  docxFile.value = null
  clientWarnings.value = []
  errorText.value = null
  expandedTopic.value = null
  showLessonContent.value = false
}

const save = async () => {
  if (!preview.value || !canSave.value) return
  isSaving.value = true
  errorText.value = null
  step.value = 3
  try {
    const res = await service.commit(courseId, {
      import_id: preview.value.import_id,
      publication_status: publicationStatus.value,
      lesson: { ...form.lesson, youtube_url: form.lesson.youtube_url?.trim() || null },
      topics: form.topics,
    })

    if (attachmentFiles.value.length) {
      isUploadingAttachments.value = true
      try {
        await service.uploadAttachments(res.newLesson.id, attachmentFiles.value)
      } catch {
        swal.toast('บันทึกบทเรียนแล้ว แต่แนบไฟล์ประกอบไม่สำเร็จ — แนบเพิ่มได้ในหน้าแก้ไขบทเรียน', 'error')
      } finally {
        isUploadingAttachments.value = false
      }
    }

    swal.toast(`นำเข้าบทเรียนสำเร็จ (${res.topics_count} หัวข้อย่อย)`, 'success')
    router.push({
      path: `/Learn/Courses/${courseId}/lessons/${res.newLesson.id}`,
      query: { created: Date.now().toString() },
    })
  } catch (err: any) {
    step.value = 2
    errorText.value = service.errorMessage(err, 'บันทึกบทเรียนไม่สำเร็จ')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-0 sm:px-4 lg:px-6 pb-32 sm:pb-12 space-y-4 sm:space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex flex-wrap items-center gap-2 px-3 sm:px-0 text-sm mb-6">
      <NuxtLink
        :to="`/Learn/Courses/${courseId}`"
        class="min-w-0 break-words text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        {{ courseName }}
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 flex-shrink-0 text-gray-400 dark:text-gray-500" />
      <NuxtLink
        :to="`/Learn/Courses/${courseId}/lessons`"
        class="whitespace-nowrap text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
      >
        บทเรียน
      </NuxtLink>
      <Icon icon="fluent:chevron-right-16-regular" class="w-4 h-4 flex-shrink-0 text-gray-400 dark:text-gray-500" />
      <span class="min-w-0 break-words text-gray-900 dark:text-white font-medium">นำเข้าบทเรียนจากไฟล์</span>
    </nav>

    <!-- Loading course -->
    <div
      v-if="!isCourseLoaded"
      class="flex items-center justify-center gap-3 rounded-2xl bg-white dark:bg-gray-800 p-6 sm:p-10 text-sm sm:text-base text-gray-500 dark:text-gray-400 shadow-sm"
    >
      <Icon icon="eos-icons:loading" class="w-6 h-6 flex-shrink-0 animate-spin text-blue-600 dark:text-blue-400" />
      <span>กำลังโหลดข้อมูลรายวิชา...</span>
    </div>

    <!-- Not admin -->
    <div
      v-else-if="!isCourseAdmin"
      class="rounded-2xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20 p-4 sm:p-8 text-center"
    >
      <Icon icon="fluent:lock-closed-24-regular" class="w-12 h-12 mx-auto mb-3 text-amber-500 dark:text-amber-400" />
      <h2 class="text-base sm:text-lg font-bold text-amber-800 dark:text-amber-300 mb-4">เฉพาะผู้ดูแลรายวิชาเท่านั้น</h2>
      <NuxtLink
        :to="`/Learn/Courses/${courseId}/lessons`"
        class="inline-flex w-full sm:w-auto min-h-[44px] items-center justify-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-5 py-2 font-medium text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5 flex-shrink-0" />
        <span>กลับไปหน้าบทเรียน</span>
      </NuxtLink>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="rounded-2xl bg-white dark:bg-gray-800 p-3 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex items-start gap-3">
          <div class="w-11 h-11 sm:w-12 sm:h-12 flex-shrink-0 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
            <Icon icon="fluent:document-arrow-up-24-regular" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
          <div class="min-w-0 flex-1">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white break-words">นำเข้าบทเรียนจากไฟล์</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 break-words">สร้างบทเรียนและหัวข้อย่อยจากไฟล์ Word (.docx) ในครั้งเดียว</p>
          </div>
        </div>

        <!-- Step indicator -->
        <ol class="mt-4 grid grid-cols-3 gap-2 sm:gap-3">
          <li
            v-for="s in steps"
            :key="s.id"
            class="flex flex-col items-center gap-1 rounded-xl p-2 text-center sm:flex-row sm:gap-3 sm:p-3 sm:text-left transition-colors"
            :class="step === s.id
              ? 'bg-blue-600 text-white dark:bg-blue-500'
              : step > s.id
                ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                : 'bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400'"
          >
            <span
              class="w-8 h-8 sm:w-10 sm:h-10 flex-shrink-0 rounded-full flex items-center justify-center"
              :class="step === s.id
                ? 'bg-white/20'
                : step > s.id
                  ? 'bg-green-500 text-white dark:bg-green-600'
                  : 'bg-white text-gray-400 dark:bg-gray-800 dark:text-gray-500'"
            >
              <Icon :icon="step > s.id ? 'fluent:checkmark-24-filled' : s.icon" class="w-4 h-4 sm:w-5 sm:h-5" />
            </span>
            <span class="min-w-0 text-xs sm:text-sm font-medium whitespace-nowrap">{{ s.label }}</span>
          </li>
        </ol>
      </div>

      <!-- STEP 1: เลือกไฟล์ -->
      <div
        v-if="step === 1"
        class="rounded-2xl bg-white dark:bg-gray-800 p-3 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4 sm:space-y-6"
      >
        <div class="rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50 dark:bg-blue-900/20 p-3 sm:p-4 text-sm text-blue-800 dark:text-blue-300">
          <h2 class="mb-2 flex items-center gap-2 font-bold">
            <Icon icon="fluent:info-24-regular" class="w-5 h-5 flex-shrink-0" />
            <span class="min-w-0 break-words">วิธีจัดรูปแบบไฟล์</span>
          </h2>
          <ul class="list-disc pl-5 space-y-1 break-words">
            <li>Heading 1 = ชื่อบทเรียน (ใช้ครั้งเดียว)</li>
            <li>Heading 2 = หัวข้อย่อย (ไม่เกิน 10 หัวข้อ)</li>
            <li>ลิงก์ YouTube ที่อยู่บรรทัดเดี่ยว = วิดีโอของบทเรียน/หัวข้อนั้น</li>
            <li>รูปภาพไม่เกิน 15 รูป และไฟล์ไม่เกิน 15MB</li>
          </ul>
          <a
            :href="templateUrl"
            download
            class="mt-3 inline-flex w-full sm:w-auto min-h-[44px] items-center justify-center gap-2 rounded-lg bg-white dark:bg-gray-800 px-4 py-2 font-medium text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-gray-700 transition-colors"
          >
            <Icon icon="fluent:arrow-download-24-filled" class="w-5 h-5 flex-shrink-0" />
            <span>ดาวน์โหลดแม่แบบ (.docx)</span>
          </a>
        </div>

        <!-- DOCX picker -->
        <div>
          <input
            id="lesson-docx-input"
            type="file"
            class="sr-only"
            accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            @change="handleDocxChange"
          />
          <label
            for="lesson-docx-input"
            class="flex min-h-[44px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-5 sm:p-8 text-center hover:border-blue-400 dark:hover:border-blue-500 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
            @dragover.prevent
            @drop.prevent="handleDocxDrop"
          >
            <template v-if="!docxFile">
              <Icon icon="fluent:document-arrow-up-24-regular" class="w-12 h-12 text-gray-400 dark:text-gray-500" />
              <span class="text-sm sm:text-base text-gray-700 dark:text-gray-200 font-medium">แตะเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</span>
              <span class="text-xs text-gray-500 dark:text-gray-400">รองรับเฉพาะ .docx ไม่เกิน 15MB</span>
            </template>
            <template v-else>
              <Icon icon="fluent:document-text-24-filled" class="w-12 h-12 text-green-500 dark:text-green-400" />
              <span class="max-w-full break-all text-sm sm:text-base font-medium text-gray-900 dark:text-white">{{ docxFile.name }}</span>
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatMb(docxFile.size) }} · แตะเพื่อเปลี่ยนไฟล์</span>
            </template>
          </label>
          <div v-if="docxFile" class="mt-2 flex justify-end">
            <button
              type="button"
              class="inline-flex min-h-[44px] items-center gap-1 rounded-lg px-3 text-sm text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
              @click="clearDocx"
            >
              <Icon icon="fluent:delete-24-regular" class="w-5 h-5 flex-shrink-0" />
              <span>ลบไฟล์</span>
            </button>
          </div>
        </div>

        <!-- Attachments -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 sm:p-4 space-y-3">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 flex-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white break-words">ไฟล์ประกอบ (ไม่บังคับ)</h3>
              <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 break-words">
                จะแนบเข้าบทเรียนหลังนำเข้าสำเร็จ · ไม่เกิน 20MB ต่อไฟล์ · สูงสุด {{ ATTACHMENT_MAX_COUNT }} ไฟล์
              </p>
            </div>
            <input
              id="lesson-attachment-input"
              type="file"
              multiple
              class="sr-only"
              accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip"
              @change="handleAttachmentChange"
            />
            <label
              for="lesson-attachment-input"
              class="inline-flex w-full sm:w-auto min-h-[44px] flex-shrink-0 cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-gray-100 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
            >
              <Icon icon="fluent:attach-24-regular" class="w-5 h-5 flex-shrink-0" />
              <span>เลือกไฟล์ประกอบ</span>
            </label>
          </div>

          <ul v-if="attachmentFiles.length" class="divide-y divide-gray-100 dark:divide-gray-700">
            <li
              v-for="(file, index) in attachmentFiles"
              :key="`${file.name}-${index}`"
              class="flex items-center gap-2 py-1"
            >
              <Icon icon="fluent:document-24-regular" class="w-5 h-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
              <div class="min-w-0 flex-1">
                <p class="break-all text-sm text-gray-900 dark:text-white">{{ file.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatMb(file.size) }}</p>
              </div>
              <button
                type="button"
                class="inline-flex min-h-[44px] min-w-[44px] flex-shrink-0 items-center justify-center rounded-lg text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                :aria-label="`ลบ ${file.name}`"
                @click="removeAttachment(index)"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </li>
          </ul>
        </div>

        <div
          v-if="errorText"
          class="flex items-start gap-2 rounded-lg border border-red-100 dark:border-red-900/30 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400"
        >
          <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
          <span class="min-w-0 flex-1 break-words">{{ errorText }}</span>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 dark:border-gray-700 pt-4 sm:flex-row sm:justify-end">
          <button
            type="button"
            :disabled="!docxFile || isBusy"
            class="inline-flex w-full sm:w-auto min-h-[44px] items-center justify-center gap-2 rounded-lg bg-blue-600 dark:bg-blue-500 px-6 py-2 font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            @click="startPreview"
          >
            <Icon v-if="isBusy" icon="eos-icons:loading" class="w-5 h-5 flex-shrink-0 animate-spin" />
            <Icon v-else icon="fluent:clipboard-task-24-regular" class="w-5 h-5 flex-shrink-0" />
            <span v-if="isConverting">กำลังแปลงไฟล์...</span>
            <span v-else-if="isPreviewing">กำลังวิเคราะห์...</span>
            <span v-else>ตรวจทานเนื้อหา</span>
          </button>
        </div>
      </div>

      <!-- STEP 2: ตรวจทาน -->
      <div v-if="step === 2 && preview" class="space-y-4 sm:space-y-6">
        <!-- Summary -->
        <div class="grid grid-cols-3 gap-2 sm:gap-3">
          <div class="rounded-xl bg-white dark:bg-gray-800 p-3 sm:p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-lg sm:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ form.topics.length }}</p>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">หัวข้อย่อย</p>
          </div>
          <div class="rounded-xl bg-white dark:bg-gray-800 p-3 sm:p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-lg sm:text-2xl font-bold text-green-600 dark:text-green-400">{{ preview.images }}</p>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">รูปภาพ</p>
          </div>
          <div class="rounded-xl bg-white dark:bg-gray-800 p-3 sm:p-4 text-center shadow-sm border border-gray-100 dark:border-gray-700">
            <p class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">{{ totalMinRead }}</p>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">นาทีอ่าน</p>
          </div>
        </div>

        <!-- Warnings -->
        <div
          v-if="allWarnings.length"
          class="rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-900/20 p-3 sm:p-4 text-sm text-amber-800 dark:text-amber-300"
        >
          <h3 class="mb-2 flex items-center gap-2 font-bold">
            <Icon icon="fluent:warning-24-filled" class="w-5 h-5 flex-shrink-0" />
            <span class="min-w-0 break-words">ข้อควรตรวจสอบ</span>
          </h3>
          <ul class="list-disc pl-5 space-y-1">
            <li v-for="(w, i) in allWarnings" :key="`w-${i}`" class="break-words">{{ w }}</li>
          </ul>
        </div>

        <!-- Lesson card -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-3 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
          <h2 class="flex items-center gap-2 text-base sm:text-lg font-bold text-gray-900 dark:text-white">
            <Icon icon="fluent:book-24-regular" class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
            <span>บทเรียน</span>
          </h2>

          <div>
            <label for="import-lesson-title" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
              ชื่อบทเรียน <span class="text-red-500 dark:text-red-400">*</span>
            </label>
            <input
              id="import-lesson-title"
              v-model="form.lesson.title"
              type="text"
              required
              maxlength="255"
              class="w-full min-h-[44px] rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm sm:text-base text-gray-900 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 outline-none"
            />
          </div>

          <div>
            <label for="import-lesson-description" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">คำอธิบาย</label>
            <textarea
              id="import-lesson-description"
              v-model="form.lesson.description"
              rows="3"
              maxlength="5000"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm sm:text-base text-gray-900 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 outline-none"
            />
          </div>

          <div>
            <label for="import-lesson-youtube" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">ลิงก์ YouTube</label>
            <input
              id="import-lesson-youtube"
              v-model="form.lesson.youtube_url"
              type="url"
              placeholder="https://www.youtube.com/watch?v=..."
              class="w-full min-h-[44px] rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm sm:text-base text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30 outline-none"
            />
          </div>

          <div>
            <button
              type="button"
              class="inline-flex min-h-[44px] items-center gap-2 rounded-lg px-3 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
              @click="showLessonContent = !showLessonContent"
            >
              <Icon :icon="showLessonContent ? 'fluent:eye-off-24-regular' : 'fluent:eye-24-regular'" class="w-5 h-5 flex-shrink-0" />
              <span>{{ showLessonContent ? 'ซ่อนเนื้อหา' : 'ดูเนื้อหา' }}</span>
            </button>
            <div
              v-if="showLessonContent"
              class="mt-2 max-h-96 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3"
            >
              <RichTextViewer :content="form.lesson.content" />
            </div>
          </div>
        </div>

        <!-- Topics -->
        <div class="space-y-3">
          <h2 class="flex items-center gap-2 text-base sm:text-lg font-bold text-gray-900 dark:text-white">
            <Icon icon="fluent:text-bullet-list-ltr-24-regular" class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
            <span class="min-w-0 flex-1 break-words">หัวข้อย่อย ({{ form.topics.length }})</span>
          </h2>

          <p
            v-if="!form.topics.length"
            class="rounded-xl bg-white dark:bg-gray-800 p-3 sm:p-4 text-sm text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700"
          >
            ไม่มีหัวข้อย่อย — จะสร้างเฉพาะบทเรียน
          </p>

          <div
            v-for="(topic, index) in form.topics"
            :key="index"
            class="rounded-xl bg-white dark:bg-gray-800 p-3 sm:p-4 shadow-sm border border-gray-100 dark:border-gray-700 space-y-2"
          >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
              <div class="flex min-w-0 flex-1 items-center gap-2">
                <span class="w-8 h-8 flex-shrink-0 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-bold flex items-center justify-center">
                  {{ index + 1 }}
                </span>
                <input
                  v-model="topic.title"
                  type="text"
                  maxlength="255"
                  :aria-label="`ชื่อหัวข้อที่ ${index + 1}`"
                  class="min-w-0 flex-1 min-h-[44px] rounded-lg border bg-white dark:bg-gray-700 px-3 py-2 text-sm sm:text-base text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/30 outline-none"
                  :class="topic.title.trim() ? 'border-gray-300 dark:border-gray-600 focus:border-blue-500' : 'border-red-400 dark:border-red-500'"
                />
              </div>
              <div class="flex flex-shrink-0 items-center justify-end gap-1">
                <button
                  type="button"
                  :disabled="index === 0"
                  class="inline-flex min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                  aria-label="เลื่อนขึ้น"
                  @click="moveTopic(index, -1)"
                >
                  <Icon icon="fluent:arrow-up-24-regular" class="w-5 h-5" />
                </button>
                <button
                  type="button"
                  :disabled="index === form.topics.length - 1"
                  class="inline-flex min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
                  aria-label="เลื่อนลง"
                  @click="moveTopic(index, 1)"
                >
                  <Icon icon="fluent:arrow-down-24-regular" class="w-5 h-5" />
                </button>
                <button
                  type="button"
                  class="inline-flex min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] items-center justify-center rounded-lg text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                  aria-label="ลบหัวข้อ"
                  @click="removeTopic(index)"
                >
                  <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                </button>
              </div>
            </div>

            <p v-if="topic.youtube_url" class="flex items-start gap-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300">
              <Icon icon="logos:youtube-icon" class="w-4 h-4 flex-shrink-0 mt-0.5" />
              <span class="min-w-0 flex-1 break-all">{{ topic.youtube_url }}</span>
            </p>

            <button
              type="button"
              class="inline-flex min-h-[44px] items-center gap-2 rounded-lg px-3 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
              @click="toggleTopic(index)"
            >
              <Icon :icon="expandedTopic === index ? 'fluent:eye-off-24-regular' : 'fluent:eye-24-regular'" class="w-5 h-5 flex-shrink-0" />
              <span>{{ expandedTopic === index ? 'ซ่อนเนื้อหา' : 'ดูเนื้อหา' }}</span>
            </button>
            <div
              v-if="expandedTopic === index"
              class="max-h-96 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3"
            >
              <RichTextViewer :content="topic.content" />
            </div>
          </div>
        </div>

        <!-- Publication status -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-3 sm:p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-3">
          <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white">สถานะเมื่อบันทึก</h2>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3">
            <label
              class="flex min-h-[44px] cursor-pointer items-start gap-3 rounded-xl border-2 p-3 transition-colors"
              :class="publicationStatus === 'draft'
                ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40'"
            >
              <input v-model="publicationStatus" type="radio" value="draft" class="sr-only" />
              <Icon
                :icon="publicationStatus === 'draft' ? 'fluent:radio-button-24-filled' : 'fluent:circle-24-regular'"
                class="w-5 h-5 flex-shrink-0 mt-0.5"
                :class="publicationStatus === 'draft' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500'"
              />
              <span class="min-w-0 flex-1 break-words text-sm sm:text-base text-gray-900 dark:text-white">
                ฉบับร่าง — ยังไม่แสดงให้นักเรียนเห็น
              </span>
            </label>
            <label
              class="flex min-h-[44px] cursor-pointer items-start gap-3 rounded-xl border-2 p-3 transition-colors"
              :class="publicationStatus === 'published'
                ? 'border-green-500 bg-green-50 dark:border-green-400 dark:bg-green-900/20'
                : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40'"
            >
              <input v-model="publicationStatus" type="radio" value="published" class="sr-only" />
              <Icon
                :icon="publicationStatus === 'published' ? 'fluent:radio-button-24-filled' : 'fluent:circle-24-regular'"
                class="w-5 h-5 flex-shrink-0 mt-0.5"
                :class="publicationStatus === 'published' ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500'"
              />
              <span class="min-w-0 flex-1 break-words text-sm sm:text-base text-gray-900 dark:text-white">เผยแพร่ทันที</span>
            </label>
          </div>
        </div>

        <div
          v-if="errorText"
          class="flex items-start gap-2 rounded-lg border border-red-100 dark:border-red-900/30 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-600 dark:text-red-400"
        >
          <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 flex-shrink-0 mt-0.5" />
          <span class="min-w-0 flex-1 break-words">{{ errorText }}</span>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            :disabled="isSaving"
            class="inline-flex w-full sm:w-auto min-h-[44px] items-center justify-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-5 py-2 font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
            @click="resetToStep1"
          >
            <Icon icon="fluent:arrow-counterclockwise-24-regular" class="w-5 h-5 flex-shrink-0" />
            <span>เลือกไฟล์ใหม่</span>
          </button>
          <button
            type="button"
            :disabled="!canSave"
            class="inline-flex w-full sm:w-auto min-h-[44px] items-center justify-center gap-2 rounded-lg bg-blue-600 dark:bg-blue-500 px-6 py-2 font-medium text-white hover:bg-blue-700 dark:hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            @click="save"
          >
            <Icon icon="fluent:save-24-regular" class="w-5 h-5 flex-shrink-0" />
            <span>บันทึกบทเรียน</span>
          </button>
        </div>
      </div>

      <!-- STEP 3: กำลังบันทึก -->
      <div
        v-if="step === 3"
        class="flex flex-col items-center justify-center gap-3 rounded-2xl bg-white dark:bg-gray-800 p-6 sm:p-12 text-center shadow-sm border border-gray-100 dark:border-gray-700"
      >
        <Icon icon="eos-icons:loading" class="w-12 h-12 animate-spin text-blue-600 dark:text-blue-400" />
        <p class="text-sm sm:text-base font-medium text-gray-900 dark:text-white">
          {{ isUploadingAttachments ? 'กำลังแนบไฟล์ประกอบ...' : 'กำลังบันทึกบทเรียน...' }}
        </p>
      </div>
    </template>
  </div>
</template>
