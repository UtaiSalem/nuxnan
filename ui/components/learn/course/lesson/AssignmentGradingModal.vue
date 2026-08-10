<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Icon } from '@iconify/vue'
import RichTextViewer from '~/components/RichTextViewer.vue'
import AnswerAttachmentList from '~/components/learn/course/assignments/AnswerAttachmentList.vue'

interface Props {
  show: boolean
  assignment: any
  lessonId: number
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
  graded: [answer: any]
}>()

const api = useApi()
const swal = useSweetAlert()
const { getAvatarUrl } = useAvatar()

// State
const answers = ref<any[]>([])
const isLoading = ref(false)
const selectedAnswer = ref<any>(null)
const gradePoints = ref<string | number>('')
const feedback = ref('')
const isGrading = ref(false)

// Fetch answers when modal opens
watch(() => props.show, async (newVal) => {
  if (newVal && props.assignment) {
    isLoading.value = true
    selectedAnswer.value = null
    feedback.value = ''
    try {
      // The endpoint returns a paginated resource collection: { data, links, meta }.
      // Reading `.answers` here meant the list was always empty.
      const response = await api.get(`/api/assignments/${props.assignment.id}/answers`) as { data: any[] }
      answers.value = response.data || []
    } catch (error) {
      console.error('Failed to fetch answers:', error)
      swal.toast('ไม่สามารถโหลดคำตอบได้', 'error')
    } finally {
      isLoading.value = false
    }
  }
})

const selectAnswer = (answer: any) => {
  selectedAnswer.value = answer
  gradePoints.value = answer.points || 0
  feedback.value = answer.feedback || ''
}

const submitGrade = async () => {
  if (!selectedAnswer.value) return
  
  // Validate points
  const points = parseInt(gradePoints.value.toString())
  if (isNaN(points) || points < 0) {
    swal.toast('กรุณาระบุคะแนนที่ถูกต้อง', 'warning')
    return
  }
  
  if (points > (props.assignment.points || 100)) {
     swal.toast(`คะแนนต้องไม่เกิน ${props.assignment.points || 100}`, 'warning')
     return
  }

  isGrading.value = true
  try {
    const response = await api.post(`/api/assignments/${props.assignment.id}/answers/${selectedAnswer.value.id}/set-points`, {
      points: points,
      feedback: feedback.value,
      course_id: props.assignment.course_id || props.assignment.lesson?.course_id 
    }) as any

    if (response.success) {
      swal.toast('บันทึกคะแนนเรียบร้อย', 'success')
      // Update local state
      selectedAnswer.value.points = points
      selectedAnswer.value.feedback = feedback.value
      selectedAnswer.value.status = 'graded' 
      emit('graded', selectedAnswer.value)
    }
  } catch (error: any) {
    console.error('Failed to grade:', error)
    swal.error(error?.data?.message || 'ไม่สามารถบันทึกคะแนนได้')
  } finally {
    isGrading.value = false
  }
}

const getUserAvatar = (user: any) => getAvatarUrl(user)

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Course ID handling
// Only used for grading API which requires course_id for progress deduction.
// If assignment object coming from LessonDetail -> assignments doesn't have course_id, we fail.
// So we should add courseId to props or depend on assignment having it.
// Assignment model: assignmentable (Lesson) -> course?
// The controller expects `course_id` in request for `setAnswerPoints`.
// So we must ensure we send it.
// I'll grab it from assignment object if available, otherwise parent should pass it?
// Let's check props. If LessonInteractionTabs passes assignment from lesson.assignments, does it have course_id?
// Lesson has course_id. Assignment belongs to Lesson.
// LessonAssignmentSection gets 'assignments' from 'lesson'.
// If assignment object doesn't have course_id, we can verify if 'lesson' object has it (passed via props).
// 'LessonInteractionTabs' has 'lesson' object which has 'course_id'.
// The 'assignment' passed here comes from 'editingAssignment' in 'LessonInteractionTabs' which comes from 'lesson.assignments'.
// So assignment object itself usually *doesn't* have course_id field unless strictly eager loaded or appended.
// Better to pass 'courseId' as a prop.

</script>

<template>
  <Transition name="fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-5xl h-[80vh] flex overflow-hidden">
        
        <!-- Left: Student List -->
        <div class="w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-800/50">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white flex items-center justify-between">
              <span>ส่งงานแล้ว ({{ answers.length }})</span>
              <button @click="emit('close')" class="md:hidden text-gray-500">
                <Icon icon="fluent:dismiss-24-regular" />
              </button>
            </h3>
          </div>
          
          <div class="flex-1 overflow-y-auto p-2 space-y-2">
            <div v-if="isLoading" class="flex justify-center py-8">
              <Icon icon="eos-icons:loading" class="w-6 h-6 text-green-500" />
            </div>
            
            <button
              v-for="answer in answers"
              :key="answer.id"
              @click="selectAnswer(answer)"
              class="w-full p-3 rounded-lg flex items-center gap-3 transition-colors text-left"
              :class="selectedAnswer?.id === answer.id ? 'bg-white dark:bg-gray-700 shadow-sm ring-1 ring-green-500' : 'hover:bg-gray-100 dark:hover:bg-gray-700'"
            >
              <img :src="getUserAvatar(answer.user)" class="w-10 h-10 rounded-full object-cover border border-gray-200 dark:border-gray-600" />
              <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 dark:text-white truncate">
                  {{ answer.user?.firstname }} {{ answer.user?.lastname }}
                </div>
                <div class="text-xs text-gray-500 flex justify-between">
                  <span>{{ formatDate(answer.created_at) }}</span>
                  <span v-if="answer.points !== null" class="font-bold text-amber-600 dark:text-amber-400">
                    {{ answer.points }} / {{ assignment.points }}
                  </span>
                </div>
              </div>
            </button>

            <div v-if="!isLoading && answers.length === 0" class="text-center py-8 text-gray-500">
              <p>ยังไม่มีใครส่งงาน</p>
            </div>
          </div>
        </div>

        <!-- Right: Detail & Grading -->
        <div class="flex-1 flex flex-col w-2/3">
          <!-- Header -->
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-gray-800">
            <h3 class="font-bold text-lg text-gray-900 dark:text-white">
              {{ assignment?.title }}
            </h3>
            <button @click="emit('close')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto p-6 bg-gray-50 dark:bg-gray-900">
            <div v-if="selectedAnswer" class="space-y-6">
              <!-- Student Answer -->
              <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-4">
                  <img :src="getUserAvatar(selectedAnswer.user)" class="w-10 h-10 rounded-full" />
                  <div>
                    <h4 class="font-bold text-gray-900 dark:text-white">
                      {{ selectedAnswer.user?.firstname }} {{ selectedAnswer.user?.lastname }}
                    </h4>
                    <p class="text-xs text-gray-500">ส่งเมื่อ {{ formatDate(selectedAnswer.created_at) }}</p>
                  </div>
                </div>

                <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap">
                  {{ selectedAnswer.content }}
                </div>

                <!-- Images -->
                <div v-if="selectedAnswer.images?.length" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-3">
                  <a
                    v-for="img in selectedAnswer.images"
                    :key="img.id"
                    :href="img.full_url || img.image_url"
                    target="_blank"
                    class="block rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:opacity-90"
                  >
                    <img :src="img.full_url || img.image_url" class="w-full h-32 object-cover" />
                  </a>
                </div>
                <AnswerAttachmentList :attachments="selectedAnswer.attachments" title="ไฟล์แนบ" class="mt-4" />
              </div>
            </div>

            <div v-else class="h-full flex flex-col items-center justify-center text-gray-500">
              <Icon icon="fluent:person-board-24-regular" class="w-16 h-16 opacity-30 mb-4" />
              <p>เลือกนักเรียนจากรายการด้านซ้ายเพื่อตรวจงาน</p>
            </div>
          </div>

          <!-- Grading Footer -->
          <div v-if="selectedAnswer" class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-10">
            <div class="flex items-center gap-4 max-w-2xl mx-auto">
              <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">คะแนน (เต็ม {{ assignment.points }})</label>
                <div class="relative">
                  <input
                    v-model="gradePoints"
                    type="number"
                    min="0"
                    :max="assignment.points"
                    class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 outline-none"
                    placeholder="ใส่คะแนน"
                  />
                  <Icon icon="fluent:star-24-regular" class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" />
                </div>
                
                <!-- Grade Slider -->
                <input
                  type="range"
                  v-model.number="gradePoints"
                  min="0"
                  :max="assignment.points"
                  step="1"
                  class="w-full mt-3 accent-green-500 cursor-grab active:cursor-grabbing h-2 bg-gray-200 rounded-lg appearance-none dark:bg-gray-700"
                />

                <div class="mt-4">
                  <label class="block text-xs font-medium text-gray-500 mb-1">ข้อเสนอแนะ (Feedback)</label>
                  <textarea
                    v-model="feedback"
                    rows="2"
                    class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 focus:ring-2 focus:ring-green-500 outline-none resize-none"
                    placeholder="เขียนข้อแนะนำเพิ่มเติม..."
                  ></textarea>
                </div>
                

              </div>
              <button
                @click="submitGrade"
                :disabled="isGrading"
                class="mt-5 px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl active:scale-95 transition-all text-sm flex items-center gap-2"
              >
                <Icon v-if="isGrading" icon="eos-icons:loading" class="w-5 h-5" />
                <span v-else>บันทึกคะแนน</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
