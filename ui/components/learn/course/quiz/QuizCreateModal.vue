<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps<{
  courseId: number | string
  show: boolean
}>()

const emit = defineEmits<{
  close: []
  created: [quiz: any]
}>()

const api = useApi()

// State
const isLoading = ref(false)
const errors = ref<string[]>([])

// Form Data
const form = reactive({
  title: '',
  description: '',
  start_date: new Date(),
  end_date: new Date(Date.now() + 60 * 60 * 1000),
  time_limit: 60,
  passing_score: 50,
  is_active: true,
  shuffle_questions: false
})

// Quiz Search/Suggestions
const quizSuggestions = ref<any[]>([])
const isSearchingQuizzes = ref(false)
const isTitleFocused = ref(false)
const isDuplicating = ref(false)

const showSuggestions = computed(() => isTitleFocused.value && quizSuggestions.value.length > 0)

// Search quizzes when title changes
let searchTimeout: ReturnType<typeof setTimeout> | null = null
watch(() => form.title, (newTitle) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  if (newTitle && newTitle.length >= 2) {
    searchTimeout = setTimeout(searchQuizzes, 300)
  } else {
    quizSuggestions.value = []
  }
})

const searchQuizzes = async () => {
  if (!form.title || form.title.length < 2) return
  
  isSearchingQuizzes.value = true
  try {
    const res = await api.get(`/api/quizzes/search?q=${encodeURIComponent(form.title)}`) as any
    if (Array.isArray(res)) {
      quizSuggestions.value = res
    } else if (res.quizzes) {
      quizSuggestions.value = res.quizzes
    } else if (res.data) {
      quizSuggestions.value = Array.isArray(res.data) ? res.data : []
    } else {
      quizSuggestions.value = []
    }
  } catch (err) {
    console.error('Error searching quizzes:', err)
    quizSuggestions.value = []
  } finally {
    isSearchingQuizzes.value = false
  }
}

const onTitleFocus = () => {
  isTitleFocused.value = true
  if (form.title && form.title.length >= 2 && quizSuggestions.value.length === 0) {
    searchQuizzes()
  }
}

const onTitleBlur = () => {
  setTimeout(() => {
    isTitleFocused.value = false
  }, 300)
}

// Apply quiz values to form
const applyQuizValues = (quiz: any) => {
  form.title = quiz.title || ''
  form.description = quiz.description || ''
  form.time_limit = quiz.time_limit || 60
  form.passing_score = quiz.passing_score || 50
  form.shuffle_questions = Boolean(quiz.shuffle_questions)
  form.is_active = Boolean(quiz.is_active)
  
  isTitleFocused.value = false
  
  Swal.fire({
    icon: 'info',
    title: 'นำค่ามาใช้แล้ว',
    text: 'ข้อมูลถูกนำมาใส่ในฟอร์มแล้ว',
    timer: 2000,
    showConfirmButton: false
  })
}

const removeFromSuggestions = (index: number) => {
  quizSuggestions.value = quizSuggestions.value.filter((_, i) => i !== index)
}

const duplicateQuiz = async (quiz: any) => {
  const result = await Swal.fire({
    title: 'คัดลอกแบบทดสอบ',
    html: `
      <p class="mb-4">คุณต้องการคัดลอก "${quiz.title}" มาใช้ในคอร์สนี้หรือไม่?</p>
      <input id="swal-new-title" class="swal2-input" placeholder="ชื่อแบบทดสอบใหม่" value="${quiz.title} (สำเนา)">
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'คัดลอก',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#8b5cf6',
    preConfirm: () => {
      const input = document.getElementById('swal-new-title') as HTMLInputElement
      if (!input.value.trim()) {
        Swal.showValidationMessage('กรุณาระบุชื่อแบบทดสอบ')
        return false
      }
      return input.value.trim()
    }
  })

  if (result.isConfirmed && result.value) {
    isDuplicating.value = true
    try {
      const res = await api.post(`/api/quizzes/${quiz.id}/duplicate`, {
        course_id: Number(props.courseId),
        title: result.value
      })

      if (res.success || res.quiz) {
        await Swal.fire({
          icon: 'success',
          title: 'คัดลอกสำเร็จ!',
          text: 'แบบทดสอบถูกคัดลอกมายังคอร์สนี้แล้ว',
          timer: 2000,
          showConfirmButton: false
        })
        emit('created', res.quiz)
        emit('close')
        // Navigate to edit the duplicated quiz
        navigateTo(`/courses/${props.courseId}/quizzes/${res.quiz.id}/edit`)
      }
    } catch (err: any) {
      console.error('Error duplicating quiz:', err)
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        text: err?.data?.message || 'ไม่สามารถคัดลอกแบบทดสอบได้'
      })
    } finally {
      isDuplicating.value = false
    }
  }
}

// Validation
const isFormValid = computed(() => {
  return form.title.trim() !== '' && 
         form.time_limit > 0 && 
         form.passing_score >= 0 && 
         form.passing_score <= 100 &&
         (!form.start_date || !form.end_date || new Date(form.end_date) > new Date(form.start_date))
})

// Submit Handler
const handleSubmit = async () => {
  if (!isFormValid.value || isLoading.value) return
  
  isLoading.value = true
  errors.value = []

  try {
    const payload = {
      title: form.title,
      description: form.description,
      time_limit: form.time_limit,
      passing_score: form.passing_score,
      is_active: form.is_active,
      shuffle_questions: form.shuffle_questions,
      start_date: form.start_date,
      end_date: form.end_date,
    }

    const res = await api.post(`/api/courses/${props.courseId}/quizzes`, payload) as any
    
    if (res.success || res.quiz) {
      Swal.fire({
        icon: 'success',
        title: 'สร้างแบบทดสอบสำเร็จ',
        timer: 1500,
        showConfirmButton: false
      })
      emit('created', res.quiz || res.data)
      emit('close')
      // Navigate to edit the new quiz to add questions
      if (res.quiz?.id) {
        navigateTo(`/courses/${props.courseId}/quizzes/${res.quiz.id}/edit`)
      }
    }
  } catch (err: any) {
    console.error(err)
    if (err.data?.errors) {
      errors.value = Object.values(err.data.errors).flat() as string[]
    } else {
      errors.value = ['เกิดข้อผิดพลาดในการสร้างแบบทดสอบ']
    }
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: errors.value[0]
    })
  } finally {
    isLoading.value = false
  }
}

// Reset form when modal closes
watch(() => props.show, (newVal) => {
  if (!newVal) {
    form.title = ''
    form.description = ''
    form.time_limit = 60
    form.passing_score = 50
    form.is_active = true
    form.shuffle_questions = false
    form.start_date = new Date()
    form.end_date = new Date(Date.now() + 60 * 60 * 1000)
    errors.value = []
    quizSuggestions.value = []
  }
})

const handleClose = () => {
  if (!isLoading.value && !isDuplicating.value) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-8 bg-black/50 backdrop-blur-sm"
        @click.self="handleClose"
      >
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="show"
            class="relative w-full max-w-2xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl mx-4"
            @click.stop
          >
            <!-- Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center">
                  <Icon icon="fluent:quiz-new-24-filled" class="w-5 h-5 text-white" />
                </div>
                <div>
                  <h2 class="text-lg font-bold text-gray-900 dark:text-white">สร้างแบบทดสอบใหม่</h2>
                  <p class="text-sm text-gray-500">กรอกข้อมูลเพื่อสร้างแบบทดสอบ</p>
                </div>
              </div>
              <button
                @click="handleClose"
                :disabled="isLoading || isDuplicating"
                class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
              >
                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
              </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-5 max-h-[calc(100vh-240px)] overflow-y-auto">
              <!-- Title with suggestions -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  ชื่อแบบทดสอบ <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                  <input 
                    v-model="form.title"
                    type="text"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-shadow"
                    placeholder="เช่น แบบทดสอบบทที่ 1"
                    @focus="onTitleFocus"
                    @blur="onTitleBlur"
                  />
                  <Icon 
                    v-if="isSearchingQuizzes"
                    icon="svg-spinners:ring-resize"
                    class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-purple-500"
                  />
                </div>

                <!-- Quiz Suggestions -->
                <Transition
                  enter-active-class="transition ease-out duration-150"
                  enter-from-class="opacity-0 -translate-y-2"
                  enter-to-class="opacity-100 translate-y-0"
                  leave-active-class="transition ease-in duration-100"
                  leave-from-class="opacity-100 translate-y-0"
                  leave-to-class="opacity-0 -translate-y-2"
                >
                  <div
                    v-if="showSuggestions"
                    class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden"
                  >
                    <div class="px-4 py-2 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 border-b border-gray-100 dark:border-gray-700">
                      <p class="text-xs font-semibold text-purple-700 dark:text-purple-300 flex items-center gap-1.5">
                        <Icon icon="fluent:copy-24-regular" class="w-3.5 h-3.5" />
                        แบบทดสอบที่คุณเคยสร้าง
                      </p>
                    </div>
                    <div class="max-h-48 overflow-y-auto">
                      <div
                        v-for="(quiz, index) in quizSuggestions"
                        :key="quiz.id"
                        class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                      >
                        <button
                          type="button"
                          @mousedown.prevent="applyQuizValues(quiz)"
                          class="flex items-center gap-3 flex-1 text-left"
                        >
                          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
                            <Icon icon="fluent:quiz-new-24-filled" class="w-4 h-4 text-white" />
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white truncate text-sm">
                              {{ quiz.title }}
                            </p>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                              <span>{{ quiz.questions_count || 0 }} ข้อ</span>
                              <span v-if="quiz.course" class="text-purple-600 dark:text-purple-400 truncate">
                                {{ quiz.course.name }}
                              </span>
                            </div>
                          </div>
                        </button>
                        <div class="flex items-center gap-1">
                          <button
                            type="button"
                            @mousedown.prevent="duplicateQuiz(quiz)"
                            class="p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg"
                            title="คัดลอกพร้อมคำถาม"
                          >
                            <Icon icon="fluent:copy-24-regular" class="w-4 h-4" />
                          </button>
                          <button
                            type="button"
                            @mousedown.prevent="removeFromSuggestions(index)"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                          >
                            <Icon icon="fluent:dismiss-16-regular" class="w-4 h-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </Transition>
              </div>

              <!-- Description -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  คำอธิบาย
                </label>
                <textarea 
                  v-model="form.description"
                  rows="2"
                  class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  placeholder="รายละเอียดหรือคำชี้แจง..."
                ></textarea>
              </div>

              <!-- Settings Grid -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    เวลา (นาที) <span class="text-red-500">*</span>
                  </label>
                  <input 
                    v-model.number="form.time_limit"
                    type="number"
                    min="1"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    เกณฑ์ผ่าน (%) <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      v-model.number="form.passing_score"
                      type="number"
                      min="0"
                      max="100"
                      class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-8"
                    />
                    <span class="absolute right-3 top-2.5 text-gray-500">%</span>
                  </div>
                </div>
              </div>

              <!-- Schedule -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    เริ่มทำได้ตั้งแต่
                  </label>
                  <VueDatePicker 
                    v-model="form.start_date"
                    :format="'dd/MM/yyyy HH:mm'"
                    auto-apply
                    :teleport="true"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    สิ้นสุดเมื่อ
                  </label>
                  <VueDatePicker 
                    v-model="form.end_date"
                    :format="'dd/MM/yyyy HH:mm'"
                    auto-apply
                    :teleport="true"
                  />
                </div>
              </div>

              <!-- Options -->
              <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" v-model="form.shuffle_questions" class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                  <span class="text-sm text-gray-700 dark:text-gray-300">สลับข้อคำถาม</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" v-model="form.is_active" class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                  <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
              </div>

              <!-- Errors -->
              <div v-if="errors.length" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
                <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400">
                  <li v-for="(error, index) in errors" :key="index">{{ error }}</li>
                </ul>
              </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 rounded-b-2xl">
              <button 
                @click="handleClose"
                class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                :disabled="isLoading || isDuplicating"
              >
                ยกเลิก
              </button>
              <button 
                @click="handleSubmit"
                class="px-6 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-medium hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
                :disabled="!isFormValid || isLoading || isDuplicating"
              >
                <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                <span>{{ isLoading ? 'กำลังบันทึก...' : 'สร้างแบบทดสอบ' }}</span>
              </button>
            </div>
          </div>
        </Transition>

        <!-- Loading overlay for duplicating -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="isDuplicating"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50"
          >
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 flex flex-col items-center gap-4 shadow-2xl">
              <Icon icon="svg-spinners:ring-resize" class="w-14 h-14 text-purple-600" />
              <div class="text-center">
                <p class="text-lg font-bold text-gray-900 dark:text-white">กำลังคัดลอกแบบทดสอบ...</p>
                <p class="text-sm text-gray-500 mt-1">รวมถึงคำถามและรูปภาพทั้งหมด</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
