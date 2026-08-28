<script setup lang="ts">
import { Icon } from '@iconify/vue'
// import { VueDatePicker } from '@vuepic/vue-datepicker';
// import '@vuepic/vue-datepicker/dist/main.css'
import Swal from 'sweetalert2'
import QuizRewardForm from '~/components/learn/course/points/QuizRewardForm.vue'
import QuestionImportModal from '~/components/learn/course/questions/QuestionImportModal.vue'

const route = useRoute()
const courseId = route.params.id
const quizId = route.params.quizId
const api = useApi()
const { account, fetchAccount } = useCoursePoints(courseId)

const showImportModal = ref(false)
const importScope = computed(() => ({ type: 'quiz' as const, courseId, quizId }))
const onQuestionsImported = async (count: number) => {
  showImportModal.value = false
  await fetchData()
  Swal.fire('สำเร็จ', `นำเข้าข้อสอบ ${count} ข้อเรียบร้อยแล้ว`, 'success')
}

definePageMeta({
  middleware: ['auth', async (to) => {
      const courseStore = useCourseStore()
      if (!courseStore.currentCourse || courseStore.currentCourse.id != to.params.id) {
          try {
             await courseStore.fetchCourse(to.params.id as string)
          } catch (e) {
             console.error('Middleware fetch course error', e)
             return abortNavigation('Course not found')
          }
      }
      
      if (!courseStore.isCourseAdmin) {
          return navigateTo(`/courses/${to.params.id}`)
      }
  }]
})

const activeTab = ref('settings')
const isLoading = ref(false)
const isSaving = ref(false)
const errors = ref<string[]>([])
const hasAttemptedSubmit = ref(false)

// Form Data
const form = reactive({
    title: '',
    description: '',
    start_date: null as Date | null,
    end_date: null as Date | null,
    time_limit: 60,
    passing_score: 50,
    is_active: true,
    shuffle_questions: false
})

// Snapshot of original values for dirty check
const originalForm = ref<typeof form | null>(null)

const quiz = ref<any>(null)
const questions = ref<any[]>([])

const snapshotForm = () => {
    originalForm.value = { ...toRaw(form) }
}

const isDirty = computed(() => {
    if (!originalForm.value) return false
    const orig = originalForm.value
    return form.title !== orig.title ||
        form.description !== orig.description ||
        form.time_limit !== orig.time_limit ||
        form.passing_score !== orig.passing_score ||
        form.is_active !== orig.is_active ||
        form.shuffle_questions !== orig.shuffle_questions ||
        form.start_date?.getTime() !== orig.start_date?.getTime() ||
        form.end_date?.getTime() !== orig.end_date?.getTime()
})

// Fetch Data
const fetchData = async () => {
    isLoading.value = true
    try {
        const res = await api.get(`/api/courses/${courseId}/quizzes/${quizId}`)
        quiz.value = res.quiz

        // Populate Form
        form.title = quiz.value.title || ''
        form.description = quiz.value.description || ''
        form.start_date = quiz.value.start_date ? new Date(quiz.value.start_date) : null
        form.end_date = quiz.value.end_date ? new Date(quiz.value.end_date) : null
        form.time_limit = quiz.value.time_limit
        form.passing_score = quiz.value.passing_score
        form.is_active = !!quiz.value.is_active
        form.shuffle_questions = !!quiz.value.shuffle_questions

        // Questions
        questions.value = quiz.value.questions || []

        // Take snapshot after populating
        snapshotForm()
        hasAttemptedSubmit.value = false
    } catch (err) {
        console.error(err)
        Swal.fire('Error', 'ไม่สามารถโหลดข้อมูลแบบทดสอบได้', 'error')
    } finally {
        isLoading.value = false
    }
}

onMounted(() => {
    fetchData()
    fetchAccount()
})

// Validation with per-field errors
const validationErrors = computed(() => {
    const errs: string[] = []
    if ((form.title || '').trim() === '') errs.push('กรุณากรอกชื่อแบบทดสอบ')
    if (!form.time_limit || form.time_limit <= 0) errs.push('เวลาต้องมากกว่า 0 นาที')
    if (form.passing_score < 0 || form.passing_score > 100) errs.push('เกณฑ์ผ่านต้องอยู่ระหว่าง 0-100%')
    if (form.start_date && form.end_date && new Date(form.end_date) <= new Date(form.start_date)) errs.push('วันสิ้นสุดต้องอยู่หลังวันเริ่มต้น')
    return errs
})

const isFormValid = computed(() => validationErrors.value.length === 0)

// Update Settings
const handleUpdate = async () => {
  hasAttemptedSubmit.value = true

  if (!isFormValid.value) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาตรวจสอบข้อมูล',
      html: validationErrors.value.map(e => `• ${e}`).join('<br>'),
    })
    return
  }

  if (!isDirty.value) {
    Swal.fire({
      icon: 'info',
      title: 'ไม่มีการเปลี่ยนแปลง',
      text: 'ข้อมูลยังเหมือนเดิม ไม่จำเป็นต้องบันทึก',
      timer: 2500,
      showConfirmButton: false
    })
    return
  }

  if (isSaving.value) return

  isSaving.value = true
  errors.value = []

  try {
    const payload = {
        title: form.title,
        description: form.description,
        time_limit: form.time_limit,
        passing_score: form.passing_score,
        is_active: form.is_active,
        shuffle_questions: form.shuffle_questions,
        start_date: form.start_date ? form.start_date.toISOString() : null,
        end_date: form.end_date ? form.end_date.toISOString() : null,
    }

    const res = await api.put(`/api/courses/${courseId}/quizzes/${quizId}`, payload)

    // Refresh data
    quiz.value = res.quiz || res.data?.quiz
    snapshotForm()
    hasAttemptedSubmit.value = false
    Swal.fire({
        icon: 'success',
        title: form.is_active ? 'บันทึกสำเร็จ — เผยแพร่แล้ว' : 'บันทึกสำเร็จ — ปิดเผยแพร่แล้ว',
        timer: 2000,
        showConfirmButton: false
    })
  } catch (err: any) {
    console.error(err)
    if (err.data?.errors) {
      errors.value = Object.values(err.data.errors).flat() as string[]
    } else {
      errors.value = [err.data?.message || 'เกิดข้อผิดพลาดในการบันทึก กรุณาลองใหม่อีกครั้ง']
    }
    Swal.fire({
      icon: 'error',
      title: 'บันทึกไม่สำเร็จ',
      html: errors.value.map(e => `• ${e}`).join('<br>'),
    })
  } finally {
    isSaving.value = false
  }
}

// Question Management
const questionModal = ref(false)
const editingQuestion = ref<any>(null)
const isSavingQuestion = ref(false)
const questionMediaFile = ref<File | null>(null)
const questionMediaPreview = ref<string | null>(null)
const optionMediaFiles = ref<Record<number, File | null>>({})
const optionMediaPreviews = ref<Record<number, string | null>>({})
const questionForm = reactive({
    text: '',
    points: 1,
    pp_fine: 0,
    media_url: null as string | null,
    options: [
        { text: '', is_correct: false, media_url: null as string | null },
        { text: '', is_correct: false, media_url: null as string | null }
    ]
})

// File upload handlers
const handleQuestionMediaChange = (event: Event) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        questionMediaFile.value = file
        questionMediaPreview.value = URL.createObjectURL(file)
    }
}

const removeQuestionMedia = () => {
    questionMediaFile.value = null
    questionMediaPreview.value = null
    questionForm.media_url = null
}

const handleOptionMediaChange = (event: Event, index: number) => {
    const target = event.target as HTMLInputElement
    const file = target.files?.[0]
    if (file) {
        optionMediaFiles.value[index] = file
        optionMediaPreviews.value[index] = URL.createObjectURL(file)
    }
}

const removeOptionMedia = (index: number) => {
    optionMediaFiles.value[index] = null
    optionMediaPreviews.value[index] = null
    questionForm.options[index].media_url = null
}

// Add/Remove options
const addOption = () => {
    questionForm.options.push({ text: '', is_correct: false, media_url: null })
}

const removeOption = (index: number) => {
    if (questionForm.options.length > 2) {
        questionForm.options.splice(index, 1)
        // Clean up media for removed option
        delete optionMediaFiles.value[index]
        delete optionMediaPreviews.value[index]
        // Reindex remaining media
        const newFiles: Record<number, File | null> = {}
        const newPreviews: Record<number, string | null> = {}
        Object.keys(optionMediaFiles.value).forEach(key => {
            const oldIndex = parseInt(key)
            if (oldIndex > index) {
                newFiles[oldIndex - 1] = optionMediaFiles.value[oldIndex]
                newPreviews[oldIndex - 1] = optionMediaPreviews.value[oldIndex]
            } else {
                newFiles[oldIndex] = optionMediaFiles.value[oldIndex]
                newPreviews[oldIndex] = optionMediaPreviews.value[oldIndex]
            }
        })
        optionMediaFiles.value = newFiles
        optionMediaPreviews.value = newPreviews
    }
}

const openAddQuestion = () => {
    editingQuestion.value = null
    questionForm.text = ''
    questionForm.points = 1
    questionForm.pp_fine = 0
    questionForm.media_url = null
    questionForm.options = [
        { text: '', is_correct: false, media_url: null },
        { text: '', is_correct: false, media_url: null }
    ]
    // Reset media files
    questionMediaFile.value = null
    questionMediaPreview.value = null
    optionMediaFiles.value = {}
    optionMediaPreviews.value = {}
    questionModal.value = true
}

const getImageUrl = (item: any): string | null => {
    if (item.media_url) return item.media_url
    if (item.images && item.images.length > 0) {
        return item.images[0].full_url || item.images[0].url || null
    }
    return null
}

const openEditQuestion = (q: any) => {
    editingQuestion.value = q
    questionForm.text = q.text || ''
    questionForm.points = q.points
    questionForm.pp_fine = q.pp_fine || 0
    questionForm.media_url = getImageUrl(q)
    // Reset media files
    questionMediaFile.value = null
    questionMediaPreview.value = getImageUrl(q)
    optionMediaFiles.value = {}
    optionMediaPreviews.value = {}
    // Mapping options if available, else default
    if (q.options && q.options.length) {
        questionForm.options = q.options.map((opt: any, idx: number) => {
            const optImgUrl = getImageUrl(opt)
            if (optImgUrl) {
                optionMediaPreviews.value[idx] = optImgUrl
            }
            return {
                id: opt.id,
                text: opt.text || '',
                is_correct: !!opt.is_correct,
                media_url: optImgUrl,
                images: opt.images || []
            }
        })
    } else {
        questionForm.options = [
            { text: '', is_correct: false, media_url: null },
            { text: '', is_correct: false, media_url: null }
        ]
    }
    questionModal.value = true
}

const toggleCorrectOption = (index: number) => {
    // Multiple choice support - toggle the selected option
    questionForm.options[index].is_correct = !questionForm.options[index].is_correct
}

const buildQuestionFormData = (fields: Record<string, any>, imageFile?: File | null): FormData => {
    const fd = new FormData()
    for (const [key, value] of Object.entries(fields)) {
        if (value !== null && value !== undefined) fd.append(key, String(value))
    }
    if (imageFile) {
        fd.append('images[]', imageFile)
    }
    return fd
}

const buildOptionFormData = (fields: Record<string, any>, imageFile?: File | null): FormData => {
    const fd = new FormData()
    for (const [key, value] of Object.entries(fields)) {
        if (value !== null && value !== undefined) fd.append(key, String(value))
    }
    if (imageFile) {
        fd.append('images[]', imageFile)
    }
    return fd
}

const saveQuestion = async () => {
    // Basic validation
    if (!questionForm.text || !questionForm.options.some(o => o.is_correct)) {
        Swal.fire('กรุณากรอกข้อมูล', 'ต้องมีคำถามและเฉลยอย่างน้อย 1 ข้อ', 'warning')
        return
    }

    isSavingQuestion.value = true
    try {
        if (editingQuestion.value) {
            // Update existing question
            const qId = editingQuestion.value.id
            const qFormData = buildQuestionFormData({
                text: questionForm.text,
                points: questionForm.points,
                pp_fine: questionForm.pp_fine,
                _method: 'PATCH'
            }, questionMediaFile.value)

            await api.post(`/api/courses/${courseId}/quizzes/${quizId}/questions/${qId}`, qFormData)

            // Update options
            const validOptions = questionForm.options.filter(o => (o.text ?? '').trim() !== '' || o.media_url || optionMediaFiles.value[questionForm.options.indexOf(o)])
            
            // Delete removed options
            if (editingQuestion.value.options) {
                const currentOptionIds = validOptions.filter(o => o.id).map(o => o.id)
                for (const oldOpt of editingQuestion.value.options) {
                    if (!currentOptionIds.includes(oldOpt.id)) {
                        try {
                            await api.delete(`/api/questions/${qId}/options/${oldOpt.id}`)
                        } catch (e) {
                            console.error('Failed to delete option', e)
                        }
                    }
                }
            }
            
            for (let i = 0; i < validOptions.length; i++) {
                const opt = validOptions[i]
                const origIdx = questionForm.options.indexOf(opt)
                const optFile = optionMediaFiles.value[origIdx] || null
                
                if (opt.id) {
                    if (optFile) {
                        // Has new image — use FormData POST with _method PATCH
                        const optFd = buildOptionFormData({
                            text: opt.text,
                            is_correct: opt.is_correct ? 1 : 0,
                            _method: 'PATCH'
                        }, optFile)
                        await api.post(`/api/questions/${qId}/options/${opt.id}`, optFd)
                    } else {
                        // No new image — simple JSON patch
                        await api.patch(`/api/questions/${qId}/options/${opt.id}`, {
                            text: opt.text,
                            is_correct: opt.is_correct ? 1 : 0
                        })
                    }
                } else {
                    // Create new option with FormData
                    const optFd = buildOptionFormData({
                        text: opt.text,
                        is_correct: opt.is_correct ? 1 : 0
                    }, optFile)
                    await api.post(`/api/questions/${qId}/options`, optFd)
                }
            }

            // Reload Data
            await fetchData()
            questionModal.value = false
            editingQuestion.value = null
            Swal.fire('Success', 'แก้ไขคำถามเรียบร้อย', 'success')
        } else {
            // Create Question with FormData
            const qFormData = buildQuestionFormData({
                text: questionForm.text,
                points: questionForm.points,
                pp_fine: questionForm.pp_fine
            }, questionMediaFile.value)

            const qRes = await api.post(`/api/courses/${courseId}/quizzes/${quizId}/questions`, qFormData)

            if (qRes.success && qRes.question) {
                const newQ = qRes.question
                
                // Create Options
                const validOptions = questionForm.options.filter((o, i) => (o.text ?? '').trim() !== '' || o.media_url || optionMediaFiles.value[i])
                
                for (let i = 0; i < validOptions.length; i++) {
                    const opt = validOptions[i]
                    const origIdx = questionForm.options.indexOf(opt)
                    const optFile = optionMediaFiles.value[origIdx] || null
                    
                    const optFd = buildOptionFormData({
                        text: opt.text,
                        is_correct: opt.is_correct ? 1 : 0
                    }, optFile)
                    
                    await api.post(`/api/questions/${newQ.id}/options`, optFd)
                }

                // Reload Data
                await fetchData()
                questionModal.value = false
                Swal.fire('Success', 'เพิ่มคำถามเรียบร้อย', 'success')
            }
        }
    } catch (err) {
        console.error(err)
        Swal.fire('Error', 'ไม่สามารถบันทึกคำถามได้', 'error')
    } finally {
        isSavingQuestion.value = false
    }
}

const deleteQuestion = async (qId: number) => {
    const result = await Swal.fire({
        title: 'ยืนยันการลบ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    })
    
    if (result.isConfirmed) {
        try {
            await api.delete(`/api/courses/${courseId}/quizzes/${quizId}/questions/${qId}`) // Verify route
            // The route might be different, commonly destroyed via QuestionController if generic
            // Or CourseQuizQuestionController destroy method.
            // Let's assume standard resource route: DELETE /courses/{course}/quizzes/{quiz}/questions/{question}
            // Check routes/learn/course.php: Route::resource(..., CourseQuizQuestionController::class) -> destroys at /{question}
            
            await fetchData()
        } catch (err) {
             // Fallback to generic question delete if specific route fails
             try {
                 await api.delete(`/api/questions/${qId}`)
                 await fetchData()
             } catch (e) {
                 Swal.fire('Error', 'ลบไม่สำเร็จ', 'error')
             }
        }
    }
}

</script>

<template>
  <div class="max-w-4xl mx-auto px-0 sm:px-3 sm:px-4 lg:px-6 pb-32 sm:pb-12 space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
      <button 
        @click="navigateTo(`/courses/${courseId}/quizzes/${quizId}`)"
        class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-6 h-6" />
      </button>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขแบบทดสอบ</h1>
        <p class="text-sm text-gray-500">{{ quiz?.title }}</p>
      </div>
    </div>

    <div v-if="isLoading" class="flex justify-center p-12">
      <Icon icon="svg-spinners:3-dots-fade" class="w-10 h-10 text-gray-400" />
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        
        <!-- Tabs -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button 
                @click="activeTab = 'settings'"
                :class="['px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative', activeTab === 'settings' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300']"
            >
                ตั้งค่าทั่วไป
                <div v-if="activeTab === 'settings'" class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>
            </button>
            <button @click="activeTab = 'reward'" :class="['px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative', activeTab === 'reward' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300']">
                รางวัลแต้ม
                <div v-if="activeTab === 'reward'" class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>
            </button>
            <button 
                @click="activeTab = 'questions'"
                :class="['px-6 py-3 font-medium text-sm focus:outline-none transition-colors relative', activeTab === 'questions' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300']"
            >
                ข้อสอบ ({{ questions.length }})
                <div v-if="activeTab === 'questions'" class="absolute bottom-0 left-0 w-full h-0.5 bg-purple-600 dark:bg-purple-400"></div>
            </button>
        </div>

        <!-- Settings Tab -->
        <div v-show="activeTab === 'settings'" class="p-4 sm:p-6 space-y-6">
             <!-- Same form content as create.vue -->
             <!-- Basic Info -->
            <div class="grid gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อแบบทดสอบ <span class="text-red-500">*</span></label>
                <input v-model="form.title" type="text" :class="['w-full px-4 py-2 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent', hasAttemptedSubmit && !form.title.trim() ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                <p v-if="hasAttemptedSubmit && !form.title.trim()" class="mt-1 text-xs text-red-500">กรุณากรอกชื่อแบบทดสอบ</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
            </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <Icon icon="fluent:timer-24-regular" class="w-5 h-5 text-gray-400" />
                    การตั้งค่าเวลาและคะแนน
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เวลา (นาที) <span class="text-red-500">*</span></label>
                    <input v-model.number="form.time_limit" type="number" min="1" :class="['w-full px-4 py-2 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent', hasAttemptedSubmit && (!form.time_limit || form.time_limit <= 0) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                    <p v-if="hasAttemptedSubmit && (!form.time_limit || form.time_limit <= 0)" class="mt-1 text-xs text-red-500">เวลาต้องมากกว่า 0 นาที</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เกณฑ์ผ่าน (%) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input v-model.number="form.passing_score" type="number" min="0" max="100" :class="['w-full px-4 py-2 rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent pr-8', hasAttemptedSubmit && (form.passing_score < 0 || form.passing_score > 100) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']" />
                        <span class="absolute right-3 top-2.5 text-gray-500">%</span>
                    </div>
                    <p v-if="hasAttemptedSubmit && (form.passing_score < 0 || form.passing_score > 100)" class="mt-1 text-xs text-red-500">เกณฑ์ผ่านต้องอยู่ระหว่าง 0-100%</p>
                </div>
            </div>
            <div class="space-y-4">
                <h3 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                    <Icon icon="fluent:calendar-ltr-24-regular" class="w-5 h-5 text-gray-400" />
                    กำหนดการ
                </h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เริ่ม</label>
                    <VueDatePicker v-model="form.start_date" :format="'dd/MM/yyyy HH:mm'" auto-apply :teleport="true" input-class-name="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สิ้นสุด</label>
                    <VueDatePicker v-model="form.end_date" :format="'dd/MM/yyyy HH:mm'" auto-apply :teleport="true" />
                    <p v-if="hasAttemptedSubmit && form.start_date && form.end_date && new Date(form.end_date) <= new Date(form.start_date)" class="mt-1 text-xs text-red-500">วันสิ้นสุดต้องอยู่หลังวันเริ่มต้น</p>
                </div>
            </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700" />

            <div class="flex flex-col sm:flex-row gap-6">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative flex items-center">
                    <input type="checkbox" v-model="form.shuffle_questions" class="peer sr-only">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-purple-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-300">สลับข้อคำถาม</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative flex items-center">
                    <input type="checkbox" v-model="form.is_active" class="peer sr-only">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-300">เปิดใช้งาน</span>
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button 
                  @click="$router.back()"
                  class="min-h-[44px] sm:min-h-0 px-4 sm:px-6 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                  :disabled="isSaving"
                >
                  ยกเลิก
                </button>
                <button
                  @click="handleUpdate"
                  class="min-h-[44px] sm:min-h-0 px-6 py-2 rounded-lg bg-purple-600 text-white font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors"
                  :disabled="isSaving"
                >
                  <span v-if="isSaving">กำลังบันทึก...</span>
                  <span v-else>บันทึกการเปลี่ยนแปลง</span>
                </button>
            </div>
        </div>

        <!-- Questions Tab -->
        <div v-show="activeTab === 'questions'" class="p-3 sm:p-6">
            <!-- หัวข้อ + ปุ่ม: mobile ซ้อนกันและปุ่มเต็มความกว้าง, sm+ อยู่แถวเดียวกัน -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4 sm:mb-6">
                <h3 class="font-bold text-gray-900 dark:text-white">รายการคำถาม</h3>
                <div class="grid grid-cols-2 sm:flex sm:items-center gap-2">
                  <button
                    @click="showImportModal = true"
                    class="w-full sm:w-auto min-h-[44px] px-3 sm:px-4 py-2 rounded-lg border border-purple-200 text-purple-600 hover:bg-purple-50 dark:border-purple-800 dark:text-purple-400 dark:hover:bg-purple-900/30 transition-colors flex items-center justify-center gap-2 font-medium text-sm sm:text-base"
                  >
                    <Icon icon="fluent:arrow-upload-24-regular" class="w-5 h-5 flex-shrink-0" />
                    อัปโหลดข้อสอบ
                  </button>
                  <button
                    @click="openAddQuestion"
                    class="w-full sm:w-auto min-h-[44px] px-3 sm:px-4 py-2 rounded-lg bg-purple-100 text-purple-600 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-400 dark:hover:bg-purple-900/50 transition-colors flex items-center justify-center gap-2 font-medium text-sm sm:text-base"
                  >
                    <Icon icon="fluent:add-circle-24-regular" class="w-5 h-5 flex-shrink-0" />
                    เพิ่มข้อสอบ
                  </button>
                </div>
            </div>

            <div v-if="questions.length === 0" class="text-center py-10 sm:py-12 px-4 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                <Icon icon="fluent:quiz-new-24-regular" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500">ยังไม่มีข้อสอบในชุดนี้</p>
                <button @click="openAddQuestion" class="text-purple-600 hover:underline mt-2 min-h-[44px] px-2">เพิ่มข้อสอบแรก</button>
            </div>

            <div v-else class="space-y-3 sm:space-y-4">
                <div v-for="(q, index) in questions" :key="q.id" class="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-3 sm:p-4 border border-gray-100 dark:border-gray-700">
                    <div class="flex gap-2.5 sm:gap-3">
                        <!-- เลขข้อ: อยู่นอกส่วนที่ reflow เสมอ -->
                        <div class="w-7 h-7 sm:w-8 sm:h-8 flex-shrink-0 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center font-bold text-gray-500 text-xs sm:text-sm shadow-sm">
                            {{ index + 1 }}
                        </div>

                        <!-- เนื้อหา + แถบคะแนน/ปุ่ม: mobile ซ้อนกัน, sm+ วางข้างกัน -->
                        <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2.5 sm:gap-4">
                            <div class="min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2 break-words">{{ q.text }}</h4>
                                <!-- Question Images -->
                                <div v-if="q.images && q.images.length" class="flex flex-wrap gap-2 mb-2">
                                    <img v-for="img in q.images" :key="img.id" :src="img.full_url || img.url" class="h-16 w-auto max-w-full rounded-lg object-cover border border-gray-200 dark:border-gray-600" />
                                </div>
                                <div class="space-y-1">
                                    <div v-for="opt in q.options" :key="opt.id" class="flex items-start gap-2 text-sm">
                                        <Icon
                                            :icon="opt.is_correct ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'"
                                            :class="[opt.is_correct ? 'text-green-500' : 'text-gray-400', 'w-5 h-5 flex-shrink-0 mt-0.5']"
                                        />
                                        <span :class="[opt.is_correct ? 'text-green-700 dark:text-green-400 font-medium' : 'text-gray-600 dark:text-gray-400', 'min-w-0 break-words']">
                                            {{ opt.text }}
                                        </span>
                                        <img v-if="opt.images && opt.images.length" :src="opt.images[0].full_url || opt.images[0].url" class="h-8 w-auto flex-shrink-0 rounded object-cover border border-gray-200 dark:border-gray-600" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-2 flex-shrink-0 border-t border-gray-200/70 dark:border-gray-600/50 pt-2 sm:border-0 sm:pt-0 sm:justify-end">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold px-2 py-1 whitespace-nowrap bg-blue-100 dark:bg-blue-900/30 rounded text-blue-600 dark:text-blue-300">{{ q.points }} คะแนน</span>
                                    <span class="text-xs font-bold px-2 py-1 whitespace-nowrap bg-orange-100 dark:bg-orange-900/30 rounded text-orange-600 dark:text-orange-300">{{ q.pp_fine || 0 }} แต้ม</span>
                                </div>
                                <div class="flex items-center gap-0.5 sm:ml-1">
                                    <button @click="openEditQuestion(q)" class="p-3 sm:p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-full transition-colors" title="แก้ไขคำถาม">
                                        <Icon icon="fluent:edit-20-regular" class="w-5 h-5" />
                                    </button>
                                    <button @click="deleteQuestion(q.id)" class="p-3 sm:p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors" title="ลบคำถาม">
                                        <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Mobile Save Button (Sticky Bottom) -->
    <div class="fixed bottom-16 sm:bottom-0 left-0 right-0 px-4 pt-3 pb-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur-lg border-t border-gray-200 dark:border-gray-800 lg:hidden z-40" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));">
      <div class="max-w-4xl mx-auto flex items-center gap-3">
        <button @click="$router.back()" class="flex-1 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors min-h-[48px]">ยกเลิก</button>
        <button @click="handleUpdate" :disabled="isSaving" class="flex-1 px-4 py-3 rounded-lg bg-purple-600 text-white font-medium hover:bg-purple-700 disabled:opacity-50 transition-colors min-h-[48px]">{{ isSaving ? 'กำลังบันทึก...' : 'บันทึก' }}</button>
      </div>
    </div>

    <!-- Question Modal -->
    <div v-if="questionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">
                    {{ editingQuestion ? 'แก้ไขข้อสอบ' : 'เพิ่มข้อสอบใหม่' }}
                </h3>
                <button @click="questionModal = false" class="text-gray-400 hover:text-gray-500">
                    <Icon icon="fluent:dismiss-24-regular" class="w-6 h-6" />
                </button>
            </div>
            
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-6">
                <!-- คำถาม -->
                <div>
                     <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำถาม</label>
                     <textarea v-model="questionForm.text" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                     
                     <!-- แนบไฟล์คำถาม -->
                     <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">แนบรูปภาพ/ไฟล์ประกอบคำถาม</label>
                        <div v-if="questionMediaPreview || questionForm.media_url" class="relative inline-block mb-2">
                            <img :src="questionMediaPreview || questionForm.media_url" class="h-24 w-auto rounded-lg object-cover" alt="Question media" />
                            <button 
                                @click="removeQuestionMedia" 
                                type="button"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
                            >
                                <Icon icon="fluent:dismiss-12-regular" class="w-4 h-4" />
                            </button>
                        </div>
                        <label v-else class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors w-fit">
                            <Icon icon="fluent:image-add-20-regular" class="w-5 h-5" />
                            <span class="text-sm">เลือกไฟล์</span>
                            <input type="file" accept="image/*,video/*,audio/*" class="hidden" @change="handleQuestionMediaChange" />
                        </label>
                     </div>
                </div>
                
                <!-- คะแนนและแต้มค่าปรับ -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนน</label>
                        <input v-model.number="questionForm.points" type="number" min="1" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            แต้มค่าปรับ (PP Fine)
                            <Icon icon="fluent:info-16-regular" class="inline w-4 h-4 text-gray-400 ml-1" title="จำนวนแต้มที่ผู้ใช้ต้องใช้เพื่อแก้ไขคำตอบหลังส่ง (0 = ฟรี)" />
                        </label>
                        <input v-model.number="questionForm.pp_fine" type="number" min="0" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                        <p class="text-xs text-gray-500 mt-1">0 = แก้ไขได้ฟรี</p>
                    </div>
                </div>

                <!-- ตัวเลือก -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">ตัวเลือก (คลิกเพื่อเลือกข้อที่ถูก - เลือกได้มากกว่า 1 ข้อ)</label>
                        <button 
                            @click="addOption" 
                            type="button"
                            class="text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400 flex items-center gap-1"
                        >
                            <Icon icon="fluent:add-circle-16-regular" class="w-4 h-4" />
                            เพิ่มตัวเลือก
                        </button>
                    </div>
                    <div class="space-y-3">
                        <div v-for="(opt, i) in questionForm.options" :key="i" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                            <div class="flex items-start gap-3">
                                <button @click="toggleCorrectOption(i)" class="focus:outline-none mt-2.5" type="button" title="คลิกเพื่อเลือก/ยกเลิกคำตอบที่ถูกต้อง">
                                    <div 
                                        class="w-6 h-6 rounded border-2 flex items-center justify-center transition-colors duration-200"
                                        :class="opt.is_correct ? 'bg-green-500 border-green-500 text-white' : 'border-gray-300 dark:border-gray-600 hover:border-green-400'"
                                    >
                                        <Icon v-if="opt.is_correct" icon="fluent:checkmark-16-filled" class="w-4 h-4" />
                                    </div>
                                </button>
                                <div class="flex-1 space-y-2">
                                    <input 
                                        v-model="opt.text" 
                                        type="text" 
                                        :placeholder="`ตัวเลือกที่ ${i + 1}`"
                                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                        :class="{'border-green-500 ring-1 ring-green-500': opt.is_correct}"
                                    />
                                    <!-- แนบไฟล์ตัวเลือก -->
                                    <div class="flex items-center gap-2">
                                        <div v-if="optionMediaPreviews[i] || opt.media_url" class="relative inline-block">
                                            <img :src="optionMediaPreviews[i] || opt.media_url" class="h-16 w-auto rounded object-cover" :alt="`Option ${i+1} media`" />
                                            <button 
                                                @click="removeOptionMedia(i)" 
                                                type="button"
                                                class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors"
                                            >
                                                <Icon icon="fluent:dismiss-12-regular" class="w-3 h-3" />
                                            </button>
                                        </div>
                                        <label v-else class="flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 rounded cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-500 transition-colors text-xs">
                                            <Icon icon="fluent:image-add-20-regular" class="w-4 h-4" />
                                            <span>แนบรูป</span>
                                            <input type="file" accept="image/*" class="hidden" @change="handleOptionMediaChange($event, i)" />
                                        </label>
                                    </div>
                                </div>
                                <!-- ปุ่มลบตัวเลือก -->
                                <button 
                                    v-if="questionForm.options.length > 2"
                                    @click="removeOption(i)" 
                                    type="button"
                                    class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors mt-1"
                                    title="ลบตัวเลือกนี้"
                                >
                                    <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        <Icon icon="fluent:info-16-regular" class="inline w-3.5 h-3.5 mr-1" />
                        สามารถเลือกคำตอบที่ถูกต้องได้มากกว่า 1 ข้อ
                    </p>
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 flex justify-end gap-3">
                <button @click="questionModal = false" class="min-h-[44px] sm:min-h-0 px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg" :disabled="isSavingQuestion">ยกเลิก</button>
                <button @click="saveQuestion" class="min-h-[44px] sm:min-h-0 px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium disabled:opacity-50 flex items-center gap-2" :disabled="isSavingQuestion">
                    <Icon v-if="isSavingQuestion" icon="svg-spinners:ring-resize" class="w-5 h-5" />
                    <span>{{ isSavingQuestion ? 'กำลังบันทึก...' : 'บันทึก' }}</span>
                </button>
            </div>
        </div>
    </div>

    <div v-show="activeTab === 'reward'" class="p-4 sm:p-6">
        <QuizRewardForm :course-id="courseId" :quiz-id="Number(quizId)" :available-balance="account?.available_balance || 0" />
    </div>

    <QuestionImportModal :show="showImportModal" :scope="importScope" @close="showImportModal = false" @imported="onQuestionsImported" />
  </div>
</template>
