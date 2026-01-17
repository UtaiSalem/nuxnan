<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import RichTextEditor from '~/components/RichTextEditor.vue'

interface Props {
  assignments: any[]
  lessonId: number
  courseId?: number
  isCreator?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  submit: [assignmentId: number, answer: any]
  close: []
  edit: [assignment: any]
  delete: [assignment: any]
  'view-submissions': [assignment: any]
}>()

const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

// State
const activeAssignmentId = ref<number | null>(props.assignments[0]?.id || null)
const isSubmitting = ref(false)
const answerContent = ref('')
const answerFiles = ref<File[]>([])
const allAnswers = ref<any[]>([])
const isFetchingAnswers = ref(false)
const existingImages = ref<any[]>([])
const deletedImageIds = ref<number[]>([])
const isEditing = ref(false)
const userAvatar = (user: any) => getAvatarUrl(user)

// Computed
const activeAssignment = computed(() => {
  return props.assignments.find(a => a.id === activeAssignmentId.value) || null
})

const userAnswer = computed(() => {
  if (!activeAssignment.value) return null
  return activeAssignment.value.answers?.[0] || null
})

const assignmentStatus = computed(() => {
  if (!activeAssignment.value) return 'not_started'
  if (userAnswer.value?.graded_score !== undefined && userAnswer.value?.graded_score !== null) {
    return 'graded'
  }
  if (userAnswer.value) {
    return 'submitted'
  }
  return 'not_started'
})

const statusConfig = computed(() => {
  const configs: Record<string, any> = {
    not_started: {
      icon: 'fluent:circle-24-regular',
      text: 'ยังไม่ได้ทำ',
      color: 'text-gray-700 dark:text-gray-300',
      bgColor: 'bg-gray-50 dark:bg-gray-800 ring-1 ring-inset ring-gray-600/20 dark:ring-gray-500/30',
    },
    submitted: {
      icon: 'fluent:checkmark-circle-24-filled',
      text: 'ส่งแล้ว - รอตรวจ',
      color: 'text-blue-700 dark:text-blue-400',
      bgColor: 'bg-blue-50 dark:bg-blue-900/30 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-500/30',
    },
    graded: {
      icon: 'fluent:trophy-24-filled',
      text: 'ตรวจแล้ว',
      color: 'text-amber-700 dark:text-amber-400',
      bgColor: 'bg-amber-50 dark:bg-amber-900/30 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/30',
    },
  }
  return configs[assignmentStatus.value] || configs.not_started
})

// Methods
const fetchAnswers = async () => {
  if (!activeAssignment.value || !props.isCreator) return
  
  isFetchingAnswers.value = true
  try {
    const response = await api.get(`/api/assignments/${activeAssignment.value.id}/answers`) as { answers: any[] }
    allAnswers.value = (response.answers || []).map(a => ({
      ...a,
      points: a.points,
      originalPoints: a.points,
      isUpdating: false
    }))
  } catch (error) {
    console.error('Failed to fetch answers:', error)
  } finally {
    isFetchingAnswers.value = false
  }
}

// Watch for active assignment change to fetch answers if creator
watch(activeAssignmentId, () => {
    if (props.isCreator) {
        fetchAnswers()
    }
}, { immediate: true })

const updateGrade = async (answer: any) => {
  if (!activeAssignment.value) return
  
  try {
    answer.isUpdating = true

    await api.post(`/api/assignments/${activeAssignment.value.id}/answers/${answer.id}/set-points`, {
      points: answer.points,
      feedback: answer.feedback,
      course_id: props.courseId || activeAssignment.value.course_id || activeAssignment.value.lesson?.course_id 
    })
    
    // Update local state properly
    answer.status = 'graded'
    answer.originalPoints = answer.points // Update original points on save
    answer.isUpdating = false
    swal.toast('บันทึกคะแนนเรียบร้อย', 'success')
  } catch (error) {
    console.error('Failed to grade:', error)
    swal.toast('ไม่สามารถบันทึกคะแนนได้', 'error')
    answer.isUpdating = false
  }
}

const cancelGrade = (answer: any) => {
  answer.points = answer.originalPoints
}

const deleteAnswer = async (answer: any) => {
  const userName = answer.student?.name || answer.member_name || 'นักเรียน'
  const confirmed = await swal.confirm(
    'ลบคำตอบ', 
    `คุณแน่ใจหรือไม่ที่จะลบคำตอบของ ${userName}? \nข้อมูลคะแนนที่ได้จากงานนี้จะถูกลบไปด้วย`
  )
  
  if (!confirmed || !activeAssignment.value) return

  try {
    await api.delete(`/api/assignments/${activeAssignment.value.id}/answers/${answer.id}`, {
      params: { // Use params for delete request if backend expects query params, or data for body
         course_id: props.courseId || activeAssignment.value.course_id || activeAssignment.value.lesson?.course_id 
      }
    })
    
    // Remove from local list
    allAnswers.value = allAnswers.value.filter(a => a.id !== answer.id)
    swal.toast('ลบคำตอบเรียบร้อยแล้ว', 'success')
  } catch (error) {
    console.error('Failed to delete answer:', error)
    swal.toast('ไม่สามารถลบคำตอบได้', 'error')
  }
}

const isScheduled = (dateStr: string) => {
  if (!dateStr) return false
  return new Date(dateStr) > new Date()
}



const selectAssignment = (id: number) => {
  activeAssignmentId.value = id
  answerContent.value = ''
  answerFiles.value = []
  existingImages.value = []
  deletedImageIds.value = []
  isEditing.value = false
}

const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    answerFiles.value = [...answerFiles.value, ...Array.from(input.files)]
  }
}

const removeFile = (index: number) => {
  answerFiles.value.splice(index, 1)
}

const editAnswer = () => {
  if (!userAnswer.value) return
  answerContent.value = userAnswer.value.content || ''
  existingImages.value = [...(userAnswer.value.images || [])]
  deletedImageIds.value = []
  answerFiles.value = []
  isEditing.value = true
  
  // Scroll to form
  nextTick(() => {
    const formEl = document.getElementById('answer-form')
    if (formEl) formEl.scrollIntoView({ behavior: 'smooth' })
  })
}

const cancelEdit = () => {
  isEditing.value = false
  answerContent.value = ''
  answerFiles.value = []
  existingImages.value = []
  deletedImageIds.value = []
}

const removeExistingImage = (imageId: number) => {
  const index = existingImages.value.findIndex(img => img.id === imageId)
  if (index !== -1) {
    existingImages.value.splice(index, 1)
    deletedImageIds.value.push(imageId)
  }
}

const submitAnswer = async () => {
  if (!activeAssignment.value || (!answerContent.value.trim() && answerFiles.value.length === 0 && existingImages.value.length === 0)) {
    return
  }

  isSubmitting.value = true

  try {
    const payload = {
       content: answerContent.value,
       files: answerFiles.value,
       deleted_images: deletedImageIds.value
    }
    
    // We emit 'submit' but the parent component expects (id, {content, files}).
    // We need to either update the parent or adhere to the interface.
    // The parent function handleSubmitAnswer creates FormData.
    // Let's modify the emit payload to include deleted_images, 
    // BUT the parent needs to handle it. 
    // Wait, the emit definition is: submit: [assignmentId: number, answer: any]
    // The parent uses `answerData.content` and `answerData.files`.
    // I need to update the parent component (LessonInteractionTabs) to handle `deleted_images` too.
    // However, I can just pass it in the object since `answer: any` is generic.

    emit('submit', activeAssignment.value.id, payload)
    
    // Reset form
    answerContent.value = ''
    answerFiles.value = []
    existingImages.value = []
    deletedImageIds.value = []
    isEditing.value = false
  } finally {
    isSubmitting.value = false
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h3 class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white">
        <Icon icon="fluent:clipboard-task-24-filled" class="w-6 h-6 text-green-600" />
        แบบฝึกหัดประจำบทเรียน
        <span class="px-2 py-0.5 text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
          {{ assignments.length }} ข้อ
        </span>
      </h3>
      <button
        @click="emit('close')"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
      >
        <Icon icon="fluent:chevron-up-24-regular" class="w-5 h-5" />
      </button>
    </div>

    <!-- Assignment Tabs (if multiple) -->
    <div v-if="assignments.length > 1" class="flex gap-2 mb-6 overflow-x-auto pb-2">
      <button
        v-for="(assignment, index) in assignments"
        :key="assignment.id"
        @click="selectAssignment(assignment.id)"
        class="flex-shrink-0 px-4 py-2 rounded-lg font-medium text-sm transition-all duration-200"
        :class="[
          activeAssignmentId === assignment.id
            ? 'bg-green-500 text-white shadow-lg'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600',
        ]"
      >
        ข้อที่ {{ index + 1 }}
      </button>
    </div>

    <!-- Active Assignment Content -->
    <div v-if="activeAssignment" class="space-y-6">
      <!-- Assignment Info Card -->
      <div class="p-6 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-200 dark:border-green-800">
        <!-- Title & Status -->
        <div class="flex items-start justify-between mb-4">
          <h4 class="text-lg font-bold text-gray-900 dark:text-white">
            {{ activeAssignment.title }}
          </h4>
          <div class="flex items-center gap-3">
            <!-- Admin Actions -->
            <div v-if="isCreator" class="flex items-center gap-1 bg-white dark:bg-gray-800 rounded-lg p-1 shadow-sm border border-gray-200 dark:border-gray-700">
              <button
                @click="emit('edit', activeAssignment)"
                class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-md transition-colors"
                title="แก้ไข"
              >
                <Icon icon="fluent:edit-16-regular" class="w-4 h-4" />
              </button>
              <div class="w-px h-4 bg-gray-200 dark:bg-gray-700"></div>
              <button
                @click="emit('delete', activeAssignment)"
                class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-md transition-colors"
                title="ลบ"
              >
                <Icon icon="fluent:delete-16-regular" class="w-4 h-4" />
              </button>
              
              <div class="w-px h-4 bg-gray-200 dark:bg-gray-700"></div>
              
              <button
                @click="emit('view-submissions', activeAssignment)"
                class="flex items-center gap-1 px-2 py-1 text-xs font-bold text-amber-600 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:hover:bg-amber-900/30 rounded-md transition-colors"
              >
                <Icon icon="fluent:clipboard-task-list-20-regular" class="w-4 h-4" />
                <span>ตรวจละเอียด</span>
              </button>
            </div>

            <span
              v-else
              class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium"
              :class="[statusConfig.bgColor, statusConfig.color]"
            >
              <Icon :icon="statusConfig.icon" class="w-4 h-4" />
              {{ statusConfig.text }}
            </span>
          </div>
        </div>

        <!-- Description -->
        <div v-if="activeAssignment.description" class="mb-4">
          <RichTextEditor :model-value="activeAssignment.description" disabled class="!min-h-0" />
        </div>

        <!-- Meta Info -->
        <div class="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
          <div class="flex items-center gap-1.5">
            <Icon icon="fluent:star-24-regular" class="w-4 h-4 text-amber-500" />
            <span>{{ activeAssignment.points || 0 }} คะแนน</span>
          </div>
          <div v-if="activeAssignment.due_date" class="flex items-center gap-1.5">
            <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 text-blue-500" />
            <span>กำหนดส่ง: {{ formatDate(activeAssignment.due_date) }}</span>
          </div>
        </div>

        <!-- Assignment Images -->
        <div v-if="activeAssignment.images?.length" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3">
          <img
            v-for="image in activeAssignment.images"
            :key="image.id"
            :src="image.full_url || image.image_url"
            :alt="activeAssignment.title"
            class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
          />
        </div>
      </div>

      <!-- Admin Inline Grading List -->
      <div v-if="isCreator" class="space-y-4">
        <h4 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
           <Icon icon="fluent:person-available-24-filled" class="w-5 h-5 text-green-600" />
           ส่งงานแล้ว ({{ allAnswers.length }})
        </h4>

        <div v-if="isFetchingAnswers" class="py-8 flex justify-center">
            <Icon icon="eos-icons:loading" class="w-8 h-8 text-green-500" />
        </div>
        
        <div v-else-if="allAnswers.length === 0" class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
             <p class="text-gray-500">ยังไม่มีใครส่งงาน</p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="answer in allAnswers" :key="answer.id" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 shadow-sm">
                <!-- User Info & Answer -->
                <div class="flex items-start gap-3 mb-3">
                    <img :src="userAvatar(answer.user)" class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600" />
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    {{ answer.user?.firstname }} {{ answer.user?.lastname }}
                                    <span v-if="answer.points === null || answer.points === undefined" class="px-2 py-0.5 text-[10px] bg-red-100 text-red-600 rounded-full border border-red-200">
                                      รอตรวจ
                                    </span>
                                    <span v-else class="px-2 py-0.5 text-[10px] bg-green-100 text-green-600 rounded-full border border-green-200">
                                      ตรวจแล้ว
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ส่งเมื่อ {{ formatDate(answer.created_at) }}
                                    <span v-if="answer.late_submission" class="text-red-500 ml-1">(ส่งช้า)</span>
                                </div>
                            </div>
                            <div class="text-right flex flex-col items-end gap-1">
                                <button 
                                    @click="deleteAnswer(answer)" 
                                    class="text-gray-400 hover:text-red-500 transition-colors p-1" 
                                    title="ลบคำตอบ"
                                >
                                    <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
                                </button>
                                <div>
                                    <span class="text-2xl font-bold" :class="answer.points !== null ? 'text-green-600 dark:text-green-400' : 'text-gray-400'">
                                        {{ answer.points ?? '-' }}
                                    </span>
                                    <span class="text-xs text-gray-500">/ {{ activeAssignment.points }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Short Answer Preview -->
                        <div class="mt-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-100 dark:border-gray-800">
                             {{ answer.content }}
                             <div v-if="answer.images?.length" class="mt-2 text-xs text-blue-500 flex gap-1">
                                <Icon icon="fluent:image-24-regular" class="w-4 h-4" />
                                มีรูปภาพแนบ {{ answer.images.length }} รูป
                             </div>
                        </div>
                    </div>
                </div>

                <!-- Grading Slider -->
                <div class="mt-2 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-medium text-gray-500">ให้คะแนน:</span>
                        <input 
                            type="range" 
                            v-model.number="answer.points" 
                            :min="0" 
                            :max="activeAssignment.points" 
                            step="1"
                            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-green-500"
                        >
                        <!-- Manual Save Button -->
                        <button 
                            @click="updateGrade(answer)"
                            :disabled="answer.isUpdating || answer.points === answer.originalPoints"
                            class="p-1 px-3 text-xs font-bold text-white bg-green-500 rounded-lg hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                            title="บันทึกคะแนน"
                        >
                            <Icon v-if="answer.isUpdating" icon="eos-icons:loading" class="w-4 h-4 animate-spin" />
                            <Icon v-else icon="fluent:save-24-regular" class="w-4 h-4" />
                            บันทึก
                        </button>
                        
                        <!-- Cancel Button -->
                        <button
                            v-if="answer.points !== answer.originalPoints"
                            @click="cancelGrade(answer)"
                            :disabled="answer.isUpdating"
                            class="p-1 px-3 text-xs font-bold text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors disabled:opacity-50 flex items-center gap-1"
                            title="ยกเลิก"
                        >
                            <Icon icon="fluent:dismiss-24-regular" class="w-4 h-4" />
                            ยกเลิก
                        </button>
                    </div>
                </div>
            </div>
        </div>
      </div>

      <!-- Previous Answer (if exists) -->
      <div
        v-if="userAnswer && !isEditing && !isCreator"
        class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800"
      >
        <div class="flex justify-between items-start mb-2">
          <h5 class="font-semibold text-blue-800 dark:text-blue-300 flex items-center gap-2">
            <Icon icon="fluent:document-text-24-regular" class="w-5 h-5" />
            คำตอบของคุณ
          </h5>
          <div class="flex items-center gap-2">
            <!-- Student Status Badge -->
            <span 
              v-if="userAnswer.graded_score === null || userAnswer.graded_score === undefined" 
              class="px-2 py-0.5 text-xs font-medium bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg flex items-center gap-1"
            >
              <Icon icon="fluent:clock-24-regular" class="w-3.5 h-3.5" />
              ส่งแล้ว - รอตรวจ
            </span>
             <span 
              v-else 
              class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-lg flex items-center gap-1"
            >
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-3.5 h-3.5" />
              ตรวจแล้ว
            </span>

            <button
              v-if="assignmentStatus !== 'graded'"
              @click="editAnswer"
              class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 ml-2"
            >
              <Icon icon="fluent:edit-16-filled" class="w-4 h-4" />
              แก้ไข
            </button>
          </div>
        </div>
        <div class="text-gray-700 dark:text-gray-300 mt-2">
          <RichTextViewer :content="userAnswer.content" />
        </div>
        
        <!-- Answer Images -->
        <div v-if="userAnswer.images?.length" class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
           <img 
             v-for="img in userAnswer.images" 
             :key="img.id" 
             :src="img.full_url || img.image_url" 
             class="w-full h-24 object-cover rounded-lg border border-blue-100 dark:border-blue-800"
           />
        </div>

        <!-- Score if graded -->
        <div
          v-if="userAnswer.graded_score !== undefined && userAnswer.graded_score !== null"
          class="mt-4 p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg"
        >
          <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400 font-bold">
            <Icon icon="fluent:trophy-24-filled" class="w-5 h-5" />
            คะแนนที่ได้: {{ userAnswer.graded_score }} / {{ activeAssignment.points || 100 }}
          </div>
          <p v-if="userAnswer.feedback" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ userAnswer.feedback }}
          </p>
        </div>
      </div>

      <!-- Answer Form (if not graded yet) -->
      <div
        v-if="(assignmentStatus !== 'graded' && !isCreator && !userAnswer) || isEditing"
        id="answer-form"
        class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg"
      >
        <div class="flex justify-between items-center mb-4">
          <h5 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="fluent:edit-24-regular" class="w-5 h-5 text-green-600" />
            {{ isEditing ? 'แก้ไขคำตอบ' : 'เขียนคำตอบ' }}
          </h5>
          <button 
             v-if="isEditing"
             @click="cancelEdit"
             class="text-gray-500 hover:text-gray-700 text-sm font-medium"
          >
             ยกเลิก
          </button>
        </div>

        <!-- Text Answer -->
        <RichTextEditor
          v-model="answerContent"
          placeholder="พิมพ์คำตอบของคุณที่นี่..."
          class="min-h-[120px]"
        />

        <!-- File Upload -->
        <div class="mt-4">
          <label
            class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-green-500 hover:text-green-500 cursor-pointer transition-colors"
          >
            <Icon icon="fluent:attach-24-regular" class="w-5 h-5" />
            <span>แนบไฟล์ / รูปภาพ</span>
            <input
              type="file"
              multiple
              accept="image/*,.pdf,.doc,.docx"
              class="hidden"
              @change="handleFileSelect"
            />
          </label>

          <!-- Selected Files Preview -->
          <div v-if="answerFiles.length" class="mt-3 flex flex-wrap gap-2">
            <div
              v-for="(file, index) in answerFiles"
              :key="index"
              class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm"
            >
              <Icon icon="fluent:document-24-regular" class="w-4 h-4" />
              <span class="truncate max-w-32">{{ file.name }}</span>
              <button
                @click="removeFile(index)"
                class="text-red-500 hover:text-red-700"
              >
                <Icon icon="fluent:dismiss-12-regular" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Existing Images (Edit Mode) -->
          <div v-if="existingImages.length > 0" class="mt-3">
             <p class="text-xs text-gray-500 mb-2">รูปภาพเดิม:</p>
             <div class="flex flex-wrap gap-2">
              <div
                v-for="(img) in existingImages"
                :key="img.id"
                class="relative group w-24 h-24 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"
              >
                <img :src="img.full_url || img.image_url" class="w-full h-full object-cover" />
                <button
                  @click="removeExistingImage(img.id)"
                  class="absolute top-1 right-1 p-1 bg-red-500/90 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                  title="ลบรูปภาพ"
                >
                  <Icon icon="fluent:delete-16-regular" class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <button
          @click="submitAnswer"
          :disabled="isSubmitting || (!answerContent.trim() && answerFiles.length === 0)"
          class="mt-4 w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
        >
          <Icon
            :icon="isSubmitting ? 'fluent:spinner-ios-20-regular' : 'fluent:send-24-filled'"
            class="w-5 h-5"
            :class="isSubmitting && 'animate-spin'"
          />
          {{ isSubmitting ? 'กำลังบันทึก...' : (isEditing ? 'บันทึกการแก้ไข' : 'ส่งคำตอบ') }}
        </button>
      </div>
    </div>

    <!-- No Assignments -->
    <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:clipboard-task-24-regular" class="w-12 h-12 mx-auto mb-2 opacity-50" />
      <p>ไม่มีแบบฝึกหัดในบทเรียนนี้</p>
    </div>
  </div>
</template>
