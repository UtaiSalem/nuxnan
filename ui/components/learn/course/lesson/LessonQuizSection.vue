<script setup lang="ts">
import { ref, computed, watch, nextTick, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'
import { formatScore } from '~/utils/scoreFormat'
import { useApi } from '@/composables/useApi'
import { useSweetAlert } from '@/composables/useSweetAlert'
import ImageLightbox from '~/components/play/feed/ImageLightbox.vue'
import LessonQuizCompletionCard from './LessonQuizCompletionCard.vue'
import QuestionImportModal from '~/components/learn/course/questions/QuestionImportModal.vue'
import QuestionBulkSettingsModal from '~/components/learn/course/questions/QuestionBulkSettingsModal.vue'
import { useQuestionImportService } from '~/services/questionImportService'

interface NextLesson {
  id: number
  title: string
}

interface Props {
  questions: any[]
  lessonId: number
  isCreator?: boolean
  /** นโยบาย: ทำแบบทดสอบได้เฉพาะสมาชิกรายวิชา (คนนอกอ่านบทเรียนได้อย่างเดียว) */
  canAnswer?: boolean
  /** บทเรียนนี้ถูกทำเครื่องหมาย "อ่านแล้ว" หรือยัง */
  lessonCompleted?: boolean
  hasAssignments?: boolean
  assignmentCount?: number
  /**
   * tri-state: object = มีบทถัดไป, null = รู้แน่ชัดว่าเป็นบทสุดท้าย,
   * undefined = ยังไม่รู้ (รายการบทเรียนยังไม่ถูกโหลด)
   * จงใจไม่ตั้ง default ใน withDefaults เพราะ Vue จะแปลง undefined เป็น default ทิ้งสถานะ "ยังไม่รู้"
   */
  nextLesson?: NextLesson | null
  togglingProgress?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isCreator: false,
  canAnswer: true,
  lessonCompleted: false,
  hasAssignments: false,
  assignmentCount: 0,
  togglingProgress: false,
})

const emit = defineEmits<{
  'update:questions': [questions: any[]]
  'create': []
  'edit': [question: any]
  'delete': [question: any]
  'mark-complete': []
  'go-assignments': []
  'go-next-lesson': []
  'go-progress': []
}>()

const api = useApi()
const swal = useSweetAlert()

const showImportModal = ref(false)
const showBulkSettings = ref(false)
const bulkSettingsEndpoint = computed(() => `/api/lessons/${props.lessonId}/questions/bulk-settings`)
/** หลังตั้งค่าทั้งชุด โหลดคำถามใหม่ให้ค่าบนจอตรงกับฐานข้อมูล */
const onBulkSettingsSaved = async () => {
  const res: any = await api.get(`/api/lessons/${props.lessonId}/questions`)
  emit('update:questions', res?.data ?? res ?? [])
}
const importScope = computed(() => ({ type: 'lesson' as const, lessonId: props.lessonId }))
const onQuestionsImported = async () => {
  showImportModal.value = false
  const res: any = await api.get(`/api/lessons/${props.lessonId}/questions`)
  emit('update:questions', res?.data ?? res ?? [])
}

const questionExportService = useQuestionImportService()
const isExporting = ref(false)
const exportQuestions = async () => {
  if (isExporting.value) return
  isExporting.value = true
  try {
    await questionExportService.exportQuestions(importScope.value)
  } catch (err: any) {
    swal.toast(err?.data?.message || 'ดาวน์โหลดข้อสอบไม่สำเร็จ', 'error')
  } finally {
    isExporting.value = false
  }
}

const hasQuestions = computed(() => props.questions && props.questions.length > 0)

// --- Student Logic ---
// We need a local copy of questions to allow shuffling options without mutating props
const localQuestions = ref<any[]>([])

// Track selected option ID for each question ID
const selectedAnswers = ref<Record<number, number>>({}) 
// Track result status { is_correct, points, message }
const answerResults = ref<Record<number, { is_correct: boolean, points: number, message: string }>>({})
// Track submitting state
const submitting = ref<Record<number, boolean>>({})
// เฉลยที่เปิดอ่านอยู่ (question id -> { correct_option_text, correct_option_explanation, explanation })
// ไม่จำข้ามการโหลดหน้า: ปิดแล้วเปิดใหม่ = จ่ายแต้มใหม่ (นโยบายเจ้าของ)
const revealedKeys = ref<Record<number, any>>({})
const revealing = ref<Record<number, boolean>>({})

/** แต้มที่ใช้ทั้งตอนแก้คำตอบและตอนดูเฉลย (ผู้สอนตั้งต่อข้อ, 0 = ฟรี) */
const questionFee = (question: any) => Math.max(0, Math.floor(Number(question?.pp_fine) || 0))

const closeReveal = (questionId: number) => {
    delete revealedKeys.value[questionId]
}

/**
 * สรุปผลจาก backend (quiz_summary) — อาจไม่มีถ้า API ยังไม่ deploy
 * จึง fallback ไปใช้ค่าที่คำนวณฝั่ง client เสมอ
 */
const serverSummary = ref<any | null>(null)

// กันไม่ให้โมดัลฉลองเด้งซ้ำภายใน session เดียวกัน
const celebrationShown = ref(false)

/** timer ของการสลับตัวเลือกหลังตอบผิด (question id -> handle) */
const retryTimers: Record<number, ReturnType<typeof setTimeout>> = {}

const clearRetryTimer = (questionId: number) => {
    const handle = retryTimers[questionId]
    if (handle !== undefined) {
        clearTimeout(handle)
        delete retryTimers[questionId]
    }
}

const clearAllRetryTimers = () => {
    Object.keys(retryTimers).forEach(key => clearRetryTimer(Number(key)))
}

/** ล้าง key ของคำถามที่ถูกลบไปแล้ว ไม่งั้นตัวนับจะทะลุจำนวนข้อจริง (เช่น "ข้อ 3/2") */
const pruneStaleKeys = (validIds: Set<number>) => {
    const maps: Record<number, any>[] = [
        answerResults.value,
        selectedAnswers.value,
        submitting.value,
        revealedKeys.value,
        revealing.value,
    ]
    maps.forEach(map => {
        Object.keys(map).forEach(key => {
            if (!validIds.has(Number(key))) delete map[Number(key)]
        })
    })
    Object.keys(retryTimers).forEach(key => {
        if (!validIds.has(Number(key))) clearRetryTimer(Number(key))
    })
}

// Initialize and sync questions
watch(() => props.questions, (newVal) => {
    const list = newVal || []
    localQuestions.value = JSON.parse(JSON.stringify(list))

    const validIds = new Set<number>(list.map((q: any) => Number(q.id)))
    pruneStaleKeys(validIds)

    // ชุดคำถามเปลี่ยน (ผู้สอนเพิ่ม/ลบข้อ) → สรุปผลชุดเดิมจาก server ใช้ไม่ได้แล้ว
    const summaryTotal = serverSummary.value?.total_questions
    if (typeof summaryTotal === 'number' && summaryTotal !== list.length) {
        serverSummary.value = null
    }
    if (list.length === 0) celebrationShown.value = false

    // Restore persisted answers
    list.forEach((q: any) => {
        if (q.user_answer) {
            // Restore result feedback (ถูก/ผิด)
            answerResults.value[q.id] = {
                is_correct: !!q.user_answer.is_correct,
                points: q.user_answer.points || 0,
                message: q.user_answer.is_correct ? 'ถูกต้อง!' : 'ยังไม่ถูกต้อง'
            }
            // Restore selected answer เฉพาะข้อที่ถูก (ข้อผิดให้ลองใหม่ได้)
            if (q.user_answer.is_correct) {
                selectedAnswers.value[q.id] = q.user_answer.answer_id
            }
        }
    })
}, { immediate: true, deep: true })

const isQuestionCorrect = (q: any) =>
    !!(answerResults.value[q.id]?.is_correct || q.user_answer?.is_correct)

const isQuestionAnswered = (q: any) =>
    !!answerResults.value[q.id] || !!q.user_answer

/** นับจาก props.questions เท่านั้น เพื่อไม่ให้ค่าจากคำถามที่ถูกลบไปแล้วทำให้ตัวเลขทะลุ */
const answeredCount = computed(() =>
    props.questions?.filter(q => isQuestionAnswered(q)).length || 0
)

/** คำถามที่ไม่ได้ตั้งคะแนนไว้ ให้นับเป็นข้อละ 1 คะแนน เพื่อให้ "คะแนน" กับ "เปอร์เซ็นต์" ไม่ขัดกัน */
const questionPoints = (q: any) => Number(q?.points) || 1

const earnedPoints = computed(() =>
    props.questions?.reduce((sum, q) => {
        if (!isQuestionCorrect(q)) return sum
        const awarded = Number(answerResults.value[q.id]?.points ?? q.user_answer?.points) || 0
        return sum + (awarded > 0 ? awarded : questionPoints(q))
    }, 0) || 0
)

const totalPoints = computed(() => {
    return props.questions?.reduce((sum, q) => sum + questionPoints(q), 0) || 0
})

// --- Completion state ---
const totalQuestions = computed(() => props.questions?.length || 0)
const remainingCount = computed(() => Math.max(0, totalQuestions.value - answeredCount.value))

// Progress Value
const progressPercentage = computed(() => {
    if (totalQuestions.value === 0) return 0
    return Math.min(100, Math.round((answeredCount.value / totalQuestions.value) * 100))
})

const correctCount = computed(() =>
    props.questions?.filter(q => isQuestionCorrect(q)).length || 0
)

/** ข้อที่ตอบแล้วแต่ยังไม่ถูก */
const incorrectQuestions = computed(() =>
    props.questions?.filter(q => isQuestionAnswered(q) && !isQuestionCorrect(q)) || []
)

const allAnswered = computed(() => totalQuestions.value > 0 && answeredCount.value >= totalQuestions.value)
const allCorrect = computed(() => totalQuestions.value > 0 && correctCount.value >= totalQuestions.value)

/**
 * ตัวเลขที่โชว์บนการ์ดสรุป
 *
 * หลัก: ยึด "แหล่งเดียว" ต่อคู่ตัวเลขเสมอ ห้ามผสม server กับ client ในคู่เดียวกัน
 * ไม่งั้นจะเรนเดอร์ค่าที่ขัดกันเอง เช่น "ตอบถูก 4/3 ข้อ"
 * ถ้า server ส่งมาไม่ครบคู่ (หรือส่งค่าที่ใช้ไม่ได้) ให้ถอยไปใช้ client ทั้งคู่
 */
const useServerCounts = computed(() => {
    const s = serverSummary.value
    return typeof s?.correct_count === 'number'
        && typeof s?.total_questions === 'number'
        && s.total_questions === totalQuestions.value
})

const displayCorrectCount = computed(() =>
    useServerCounts.value ? Number(serverSummary.value.correct_count) : correctCount.value
)
const displayTotalQuestions = computed(() =>
    useServerCounts.value ? Number(serverSummary.value.total_questions) : totalQuestions.value
)

/**
 * backend คิดคะแนนรวมจาก points ของคำถาม ถ้าผู้สอนตั้ง points = 0 ทุกข้อจะได้ total_points = 0
 * → ใช้ค่าจาก server ไม่ได้ (จะโชว์ "0/0" และ 0% ทั้งที่ตอบถูกหมด) ให้ถอยไปใช้ค่าฝั่ง client
 */
const useServerPoints = computed(() => {
    const s = serverSummary.value
    return typeof s?.earned_points === 'number'
        && typeof s?.total_points === 'number'
        && s.total_points > 0
})

const displayEarnedPoints = computed(() =>
    useServerPoints.value ? Number(serverSummary.value.earned_points) : earnedPoints.value
)
const displayTotalPoints = computed(() =>
    useServerPoints.value ? Number(serverSummary.value.total_points) : totalPoints.value
)

const displayPercentage = computed(() => {
    const raw = displayTotalPoints.value > 0
        ? Math.round((displayEarnedPoints.value / displayTotalPoints.value) * 100)
        : 0
    const pct = Math.min(100, Math.max(0, raw))
    // ตอบถูกครบทุกข้อแล้วต้องไม่โชว์ 0% (เกิดได้ถ้าทุกข้อไม่ได้ตั้งคะแนน)
    if (allCorrect.value && pct <= 0) return 100
    return pct
})

// --- Celebration modal ---
const showCelebration = ref(false)

const closeCelebration = () => {
    showCelebration.value = false
}

const triggerCelebration = () => {
    if (props.isCreator || celebrationShown.value) return
    celebrationShown.value = true
    showCelebration.value = true
}

// --- Next-step actions ---
const handleMarkComplete = () => emit('mark-complete')
const handleGoAssignments = () => {
    closeCelebration()
    emit('go-assignments')
}
const handleGoNextLesson = () => {
    closeCelebration()
    emit('go-next-lesson')
}
const handleGoProgress = () => {
    closeCelebration()
    emit('go-progress')
}

const scrollToFirstIncorrect = async () => {
    const target = incorrectQuestions.value[0]
    if (!target) return
    await nextTick()
    const el = document.getElementById(`quiz-question-${target.id}`)
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

const selectOption = (questionId: number, optionId: number) => {
    // If already correct, forbid changing? Let's stick to "if correct, done".
    if (answerResults.value[questionId]?.is_correct) return 
    selectedAnswers.value[questionId] = optionId
}

// Utility to shuffle array
const shuffleArray = (array: any[]) => {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]];
    }
    return array;
}

const submitAnswer = async (question: any) => {
    const answerId = selectedAnswers.value[question.id]
    if (!answerId) return

    // นโยบาย: เปลี่ยนคำตอบที่ส่งไปแล้วต้องจ่ายแต้มตามที่ผู้สอนตั้ง (pp_fine) — ถามยืนยันก่อน
    const fee = questionFee(question)
    const previousAnswerId = question.user_answer?.answer_id ?? null
    const alreadyAnswered = isQuestionAnswered(question)
    const isChange = alreadyAnswered && Number(previousAnswerId) !== Number(answerId)
    if (fee > 0 && isChange) {
        const ok = await swal.confirm(
            `ระบบจะหัก <b>${fee}</b> แต้ม เพื่อแก้คำตอบข้อนี้<br><small>หักทุกครั้งที่แก้คำตอบ</small>`,
            'ยืนยันการแก้คำตอบ',
            { confirmText: `หัก ${fee} แต้ม`, icon: 'warning' }
        )
        if (!ok) return
    }

    submitting.value[question.id] = true
    // เก็บสถานะก่อนส่ง ไว้ใช้ fallback หา "เพิ่งทำครบ" ถ้า backend ไม่ส่ง quiz_summary
    const wasAllCorrect = allCorrect.value
    try {
        const response = await api.post(`/api/lessons/${props.lessonId}/questions/${question.id}/answer`, {
          answer_id: answerId
        })

        const data = response as any
        // จำคำตอบล่าสุดไว้ เพื่อให้รอบถัดไปรู้ว่าเป็นการ "แก้คำตอบ" (มีค่าแต้ม)
        question.user_answer = { ...(question.user_answer || {}), answer_id: answerId, is_correct: !!data.is_correct }
        if (Number(data.pp_deducted) > 0) {
            swal.toast(`ใช้ ${data.pp_deducted} แต้ม (คงเหลือ ${data.user_pp})`, 'info')
        }
        answerResults.value[question.id] = {
            is_correct: data.is_correct,
            points: data.points,
            message: data.message
        }

        const summary = data?.quiz_summary
        if (summary && typeof summary === 'object') {
            serverSummary.value = summary
        }

        if (data.is_correct) {
            swal.toast('ถูกต้อง! เก่งมาก', 'success')

            const justCompleted = summary && typeof summary.just_completed === 'boolean'
                ? summary.just_completed
                : (!wasAllCorrect && allCorrect.value)

            if (justCompleted) triggerCelebration()
        } else {
            swal.toast('ยังไม่ถูกต้อง ลองใหม่นะ', 'error')
            
            // Shuffle options for this question to provide variety on retry
            clearRetryTimer(question.id)
            const qIndex = localQuestions.value.findIndex(q => q.id === question.id)
            if (qIndex !== -1 && localQuestions.value[qIndex].options) {
                retryTimers[question.id] = setTimeout(() => {
                    delete retryTimers[question.id]
                    // ชุดคำถามอาจถูก re-sync ระหว่างรอ → หา index ใหม่ทุกครั้ง
                    const idx = localQuestions.value.findIndex(q => q.id === question.id)
                    if (idx === -1 || !localQuestions.value[idx].options) return
                    localQuestions.value[idx].options = shuffleArray([...localQuestions.value[idx].options])
                    selectedAnswers.value[question.id] = 0 // Clear selection
                }, 1000)
            }
        }

    } catch (error: any) {
        console.error('Submit answer failed', error)
        // useApi เก็บ body ของ error ไว้ที่ err.data
        swal.error(error?.data?.message || 'ส่งคำตอบไม่สำเร็จ')
    } finally {
        submitting.value[question.id] = false
    }
}
/** ดูเฉลย — ตอบครั้งแรกฟรี แต่ดูเฉลยต้องจ่ายแต้มเท่าค่าแก้คำตอบ (จ่ายครั้งเดียวต่อข้อ) */
const revealAnswer = async (question: any) => {
    const fee = questionFee(question)
    const ok = await swal.confirm(
        fee > 0
            ? `ระบบจะหัก <b>${fee}</b> แต้ม เพื่อเปิดอ่านเฉลยข้อนี้<br><small>หักทุกครั้งที่เปิดดู · คุณยังต้องเลือกคำตอบเอง</small>`
            : 'ดูเฉลยข้อนี้ได้ฟรี · คุณยังต้องเลือกคำตอบเอง',
        'ดูเฉลย',
        { confirmText: fee > 0 ? `หัก ${fee} แต้ม` : 'ดูเฉลย', icon: fee > 0 ? 'warning' : 'question' }
    )
    if (!ok) return

    revealing.value[question.id] = true
    try {
        const data: any = await api.post(`/api/lessons/${props.lessonId}/questions/${question.id}/reveal`, {})
        revealedKeys.value[question.id] = data.revealed
        if (Number(data.pp_deducted) > 0) {
            swal.toast(`ใช้ ${data.pp_deducted} แต้ม (คงเหลือ ${data.user_pp})`, 'info')
        }
    } catch (error: any) {
        // useApi เก็บ body ของ error ไว้ที่ err.data
        swal.error(error?.data?.message || 'เปิดเฉลยไม่สำเร็จ')
    } finally {
        revealing.value[question.id] = false
    }
}
// ---------------------

// --- Creator Logic ---
const deleteQuestion = async (question: any) => {
    const confirmed = await swal.confirm('ลบคำถาม', 'คุณแน่ใจหรือไม่ที่จะลบคำถามนี้?')
    if (!confirmed) return

    try {
        await api.delete(`/api/lessons/${props.lessonId}/questions/${question.id}`)
        swal.toast('ลบคำถามเรียบร้อย', 'success')
        const newQuestions = props.questions.filter(q => q.id !== question.id)
        emit('update:questions', newQuestions)
    } catch (error) {
        console.error('Failed to delete question:', error)
        swal.error('ไม่สามารถลบคำถามได้')
    }
}

// Lightbox Logic
const showLightbox = ref(false)
const lightboxImages = ref<any[]>([])
const lightboxIndex = ref(0)

const openLightbox = (images: any[], index: number) => {
    lightboxImages.value = images.map(img => ({
        ...img,
        url: img.full_url || img.image_url
    }))
    lightboxIndex.value = index
    showLightbox.value = true
}

const closeLightbox = () => {
    showLightbox.value = false
    lightboxImages.value = []
    lightboxIndex.value = 0
}

// --- Celebration modal a11y (escape / focus / body scroll lock) ---
const celebrationCloseBtn = ref<HTMLButtonElement | null>(null)
let previousBodyOverflow = ''

const onCelebrationKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        event.stopPropagation()
        closeCelebration()
    }
}

const unlockBodyScroll = () => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = previousBodyOverflow
}

watch(showCelebration, async (open) => {
    if (typeof document === 'undefined') return

    if (open) {
        previousBodyOverflow = document.body.style.overflow
        document.body.style.overflow = 'hidden'
        document.addEventListener('keydown', onCelebrationKeydown)
        await nextTick()
        celebrationCloseBtn.value?.focus()
    } else {
        document.removeEventListener('keydown', onCelebrationKeydown)
        unlockBodyScroll()
    }
})

onUnmounted(() => {
    clearAllRetryTimers()
    if (typeof document === 'undefined') return
    document.removeEventListener('keydown', onCelebrationKeydown)
    if (showCelebration.value) unlockBodyScroll()
})



</script>

<template>
  <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <h3 class="flex flex-wrap items-center gap-2 text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
            <Icon icon="fluent:quiz-new-24-filled" class="w-6 h-6 text-orange-600 flex-shrink-0" />
            แบบทดสอบ
            <span v-if="isCreator || canAnswer" class="px-2 py-0.5 text-sm font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-full whitespace-nowrap">
            {{ questions.length }} ข้อ
            </span>
        </h3>

        <!-- Admin Actions -->
        <div v-if="isCreator" class="flex flex-col sm:flex-row sm:items-center gap-2">
            <button
                @click="showImportModal = true"
                class="w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] px-3 sm:px-4 py-2 border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 dark:border-orange-800 dark:text-orange-400 dark:hover:bg-orange-900/30 transition-colors text-sm font-medium"
            >
                <Icon icon="fluent:arrow-upload-24-regular" class="w-4 h-4 flex-shrink-0" />
                <span class="whitespace-nowrap">อัปโหลดข้อสอบ</span>
            </button>
            <button
                @click="exportQuestions"
                :disabled="isExporting || questions.length === 0"
                class="w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] px-3 sm:px-4 py-2 border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 dark:border-orange-800 dark:text-orange-400 dark:hover:bg-orange-900/30 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium"
            >
                <Icon :icon="isExporting ? 'svg-spinners:180-ring-with-bg' : 'fluent:arrow-download-24-regular'" class="w-4 h-4 flex-shrink-0" />
                <span class="whitespace-nowrap">{{ isExporting ? 'กำลังดาวน์โหลด...' : 'ดาวน์โหลดข้อสอบ' }}</span>
            </button>
            <button
                @click="showBulkSettings = true"
                :disabled="questions.length === 0"
                class="w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] px-3 sm:px-4 py-2 border border-orange-300 text-orange-600 rounded-lg hover:bg-orange-50 dark:border-orange-800 dark:text-orange-400 dark:hover:bg-orange-900/30 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium"
            >
                <Icon icon="fluent:options-24-regular" class="w-4 h-4 flex-shrink-0" />
                <span class="whitespace-nowrap">ตั้งค่าทั้งชุด</span>
            </button>
            <button
                @click="emit('create')"
                class="w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] px-3 sm:px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm font-medium"
            >
                <Icon icon="fluent:add-24-filled" class="w-4 h-4 flex-shrink-0" />
                <span class="whitespace-nowrap">เพิ่มคำถาม</span>
            </button>
        </div>
      </div>
    </div>

    <!-- คนนอกรายวิชา: อ่านบทเรียนได้ แต่ทำแบบทดสอบไม่ได้ -->
    <div
        v-if="!isCreator && !canAnswer"
        class="flex flex-col items-center text-center gap-2 p-4 sm:p-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
    >
        <Icon icon="fluent:lock-closed-24-filled" class="w-10 h-10 text-gray-400" />
        <h4 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white break-words">แบบทดสอบสำหรับสมาชิกรายวิชา</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 break-words">คุณอ่านเนื้อหาบทเรียนได้ฟรี หากต้องการทำแบบทดสอบ กรุณาสมัครเข้าเป็นสมาชิกรายวิชานี้</p>
    </div>

    <!-- Sticky Progress Header (Student Only) -->
    <div
        v-if="!isCreator && hasQuestions"
        class="sticky top-36 z-20 mb-6 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700
               bg-white/90 dark:bg-gray-800/90 backdrop-blur shadow-sm"
      >
        <div class="flex items-center justify-between gap-3 mb-2">
          <p class="text-sm font-semibold text-gray-900 dark:text-white">
            ข้อ {{ answeredCount }}<span class="text-gray-400 dark:text-gray-500">/{{ totalQuestions }}</span>
          </p>
          <p class="text-xs sm:text-sm font-medium">
            <span v-if="remainingCount > 0" class="text-orange-600 dark:text-orange-400">
              เหลืออีก {{ remainingCount }} ข้อ
            </span>
            <span v-else-if="allCorrect" class="text-green-600 dark:text-green-400 flex items-center gap-1">
              <Icon icon="fluent:checkmark-circle-24-filled" class="w-4 h-4" />
              ทำครบแล้ว
            </span>
            <span v-else class="text-amber-600 dark:text-amber-400">
              ยังเหลือ {{ incorrectQuestions.length }} ข้อที่ยังไม่ถูก
            </span>
          </p>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
          <div
            class="h-2.5 rounded-full transition-all duration-500 ease-out"
            :class="allCorrect ? 'bg-green-500' : 'bg-orange-600'"
            :style="{ width: `${progressPercentage}%` }"
          ></div>
        </div>
    </div>

    <!-- Score Summary (Student Only, showing when there's progress) -->
    <div v-if="!isCreator && hasQuestions && answeredCount > 0 && !allCorrect"
         class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 
                rounded-xl p-4 border border-orange-200 dark:border-orange-800 mb-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Icon icon="fluent:trophy-24-filled" class="w-8 h-8 text-orange-500" />
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">คะแนนที่ได้</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
              {{ earnedPoints }} <span class="text-sm font-normal text-gray-500">/ {{ totalPoints }}</span>
            </p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-sm text-gray-600 dark:text-gray-400">ตอบแล้ว</p>
          <p class="text-lg font-bold text-gray-900 dark:text-white">
            {{ answeredCount }} <span class="text-sm font-normal text-gray-500">/ {{ questions.length }} ข้อ</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Student View: Active Quiz List (Using localQuestions) -->
    <div v-if="!isCreator && hasQuestions" class="space-y-8">
        <div 
          v-for="(question, index) in localQuestions"
          :key="question.id"
          :id="`quiz-question-${question.id}`"
          class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm transition-all hover:shadow-md scroll-mt-40"
        >
            <div class="flex items-start gap-5">
                <!-- Question Number -->
                <div class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg font-bold text-lg">
                    {{ index + 1 }}
                </div>
                
                <div class="flex-1 w-full min-w-0">
                    <!-- Question Text -->
                    <p class="font-medium text-lg text-gray-900 dark:text-white mb-4 leading-relaxed whitespace-pre-wrap">{{ question.text }}</p>
                    
                    <!-- Question Images -->
                    <div v-if="question.images?.length" class="flex gap-3 overflow-x-auto pb-4 scrollbar-hide">
                        <img 
                            v-for="(img, imgIndex) in question.images" 
                            :key="img.id" 
                            :src="img.full_url || img.image_url" 
                            class="h-40 w-auto rounded-xl object-cover border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-95 transition-opacity shadow-sm" 
                            @click="openLightbox(question.images, imgIndex)"
                        />
                    </div>

                    <!-- Options -->
                    <div class="space-y-3 mt-2">
                         <transition-group name="list" tag="div" class="space-y-3">
                            <button
                                v-for="option in question.options"
                                :key="option.id"
                                @click="selectOption(question.id, option.id)"
                                :disabled="submitting[question.id] || answerResults[question.id]?.is_correct"
                                class="w-full flex items-center gap-4 p-4 rounded-xl border-2 transition-all text-left group relative overflow-hidden"
                                :class="[
                                    selectedAnswers[question.id] === option.id
                                        ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/10'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-orange-200 dark:hover:border-orange-800 hover:bg-gray-50 dark:hover:bg-gray-800/50'
                                ]"
                            >
                                <!-- Selection Circle -->
                                <div 
                                    class="w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-colors"
                                    :class="[
                                        selectedAnswers[question.id] === option.id 
                                            ? 'border-orange-500 bg-orange-500 text-white' 
                                            : 'border-gray-300 dark:border-gray-600 group-hover:border-orange-400'
                                    ]"
                                >
                                    <Icon v-if="selectedAnswers[question.id] === option.id" icon="fluent:checkmark-16-filled" class="w-4 h-4" />
                                </div>
                                
                                <!-- Option Content -->
                                <div class="min-w-0 flex-1 break-words">
                                    <span class="text-gray-900 dark:text-gray-200 text-base">{{ option.text }}</span>
                                    <div v-if="option.images?.length" class="mt-3">
                                        <img 
                                            :src="option.images[0].full_url || option.images[0].image_url" 
                                            class="h-32 w-auto rounded-lg object-cover cursor-pointer hover:opacity-95 shadow-sm border border-gray-200 dark:border-gray-700"
                                            @click.stop="openLightbox(option.images, 0)"
                                        />
                                    </div>
                                </div>
                            </button>
                        </transition-group>
                    </div>

                    <!-- เฉลยสำหรับอ่าน (จ่ายแต้มแล้ว) — ไม่ไฮไลต์ตัวเลือก ผู้เรียนต้องเลือกตอบเอง -->
                    <div
                        v-if="revealedKeys[question.id]"
                        class="mt-4 p-3 sm:p-4 rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 text-sm text-sky-900 dark:text-sky-200"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="min-w-0 flex-1 font-semibold break-words">
                                <Icon icon="fluent:book-open-24-regular" class="w-4 h-4 inline -mt-0.5" />
                                เฉลย: {{ revealedKeys[question.id].correct_option_text || 'ดูจากรูปภาพในตัวเลือก' }}
                            </p>
                            <button
                                type="button"
                                class="flex-shrink-0 min-h-[44px] min-w-[44px] sm:min-h-0 sm:min-w-0 flex items-center justify-center text-sky-700 dark:text-sky-300 hover:text-sky-900"
                                aria-label="ปิดเฉลย"
                                @click="closeReveal(question.id)"
                            >
                                <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
                            </button>
                        </div>
                        <p v-if="revealedKeys[question.id].correct_option_explanation" class="mt-2 break-words">
                            {{ revealedKeys[question.id].correct_option_explanation }}
                        </p>
                        <p v-if="revealedKeys[question.id].explanation" class="mt-2 break-words">
                            <span class="font-semibold">คำอธิบาย:</span> {{ revealedKeys[question.id].explanation }}
                        </p>
                        <p class="mt-2 text-xs opacity-80 break-words">เลือกคำตอบด้วยตัวเองแล้วกด "ส่งคำตอบ" · ปิดแล้วเปิดดูใหม่ต้องใช้แต้มอีกครั้ง</p>
                    </div>

                    <!-- Actions & Feedback Area -->
                    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pt-4 border-t border-gray-100 dark:border-gray-700/50">
                        <div class="min-w-0 flex-1 min-h-[40px] flex items-center">
                             <transition
                                enter-active-class="transition ease-out duration-200"
                                enter-from-class="opacity-0 translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                             >
                                 <div v-if="answerResults[question.id]" class="flex items-center gap-3 font-medium px-4 py-2 rounded-lg" 
                                      :class="answerResults[question.id].is_correct ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'">
                                    <Icon :icon="answerResults[question.id].is_correct ? 'fluent:checkmark-circle-24-filled' : 'fluent:dismiss-circle-24-filled'" class="w-6 h-6" />
                                    <span>{{ answerResults[question.id].message }}</span>
                                    <span v-if="answerResults[question.id].is_correct" class="text-sm opacity-80 ml-1">
                                        (+{{ formatScore(answerResults[question.id].points) }} คะแนน)
                                    </span>
                                 </div>
                             </transition>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center flex-shrink-0">
                            <!-- ดูเฉลย: ใช้ได้หลังตอบครั้งแรก, ใช้แต้มเท่าค่าแก้คำตอบ -->
                            <button
                                v-if="isQuestionAnswered(question) && !answerResults[question.id]?.is_correct && !revealedKeys[question.id]"
                                type="button"
                                @click="revealAnswer(question)"
                                :disabled="revealing[question.id]"
                                class="min-h-[44px] sm:min-h-0 px-5 py-2.5 rounded-full border-2 border-green-500 text-green-700 dark:text-green-400 font-medium hover:bg-green-50 dark:hover:bg-green-900/20 disabled:opacity-50 transition-all flex items-center justify-center gap-2 whitespace-nowrap"
                            >
                                <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                                <span>{{ revealing[question.id] ? 'กำลังเปิด...' : 'ดูเฉลย' }}</span>
                                <span v-if="questionFee(question) > 0" class="text-xs opacity-80">({{ questionFee(question) }} แต้ม)</span>
                            </button>

                            <button
                                v-if="!answerResults[question.id]?.is_correct"
                                @click="submitAnswer(question)"
                                :disabled="!selectedAnswers[question.id] || submitting[question.id]"
                                class="min-h-[44px] sm:min-h-0 px-8 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-medium rounded-full shadow-lg shadow-orange-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 transform active:scale-95 whitespace-nowrap"
                            >
                                <span v-if="submitting[question.id]" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span>{{ submitting[question.id] ? 'กำลังส่ง...' : 'ส่งคำตอบ' }}</span>
                                <Icon v-if="!submitting[question.id]" icon="fluent:send-24-filled" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completion Card (ตอบถูกครบทุกข้อ) -->
        <LessonQuizCompletionCard
            v-if="allCorrect"
            :earned-points="displayEarnedPoints"
            :total-points="displayTotalPoints"
            :correct-count="displayCorrectCount"
            :total-questions="displayTotalQuestions"
            :percentage="displayPercentage"
            :lesson-completed="lessonCompleted"
            :has-assignments="hasAssignments"
            :assignment-count="assignmentCount"
            :next-lesson="nextLesson"
            :toggling-progress="togglingProgress"
            @mark-complete="handleMarkComplete"
            @go-assignments="handleGoAssignments"
            @go-next-lesson="handleGoNextLesson"
            @go-progress="handleGoProgress"
        />

        <!-- ตอบครบทุกข้อแล้ว แต่ยังมีข้อที่ผิดค้างอยู่ -->
        <div
            v-else-if="allAnswered && incorrectQuestions.length > 0"
            class="mt-8 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 sm:p-6"
        >
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 p-3 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <Icon icon="fluent:warning-24-filled" class="w-7 h-7" />
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-lg font-bold text-amber-800 dark:text-amber-300">
                        ยังเหลือ {{ incorrectQuestions.length }} ข้อที่ยังตอบไม่ถูก
                    </h4>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400/90">
                        คุณตอบครบทุกข้อแล้ว แต่ยังมีบางข้อที่ยังไม่ถูกต้อง ลองตอบใหม่อีกครั้งเพื่อทำแบบทดสอบให้ครบสมบูรณ์
                    </p>
                    <button
                        type="button"
                        class="min-h-[44px] sm:min-h-0 mt-4 w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-full
                               bg-amber-500 hover:bg-amber-600 text-white font-semibold shadow-md transition-all active:scale-95"
                        @click="scrollToFirstIncorrect"
                    >
                        <Icon icon="fluent:arrow-down-24-filled" class="w-5 h-5" />
                        ไปยังข้อที่ยังไม่ถูก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Question List (Legacy / Creator Mode) - Keeps using props.questions -->
    <div v-if="isCreator" class="space-y-4">
        <div v-if="!hasQuestions" class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-xl border-dashed border-2 border-gray-200 dark:border-gray-700">
             <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <Icon icon="fluent:question-circle-24-regular" class="w-8 h-8 text-orange-500" />
             </div>
             <p class="text-gray-500 dark:text-gray-400">ยังไม่มีคำถาม</p>
             <button @click="emit('create')" class="mt-4 text-orange-600 hover:text-orange-700 font-medium">
                เพิ่มคำถามแรก
             </button>
        </div>

        <div 
          v-for="(question, index) in questions" 
          :key="question.id"
          class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm relative group"
        >
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-lg font-bold">
                    {{ index + 1 }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 dark:text-white mb-2">{{ question.text }}</p>
                    
                    <!-- Images -->
                    <div v-if="question.images?.length" class="flex gap-2 overflow-x-auto pb-2">
                        <img 
                            v-for="(img, imgIndex) in question.images" 
                            :key="img.id" 
                            :src="img.full_url || img.image_url" 
                            class="h-16 w-auto rounded-lg object-cover border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-90 transition-opacity" 
                            @click="openLightbox(question.images, imgIndex)"
                        />
                    </div>

                    <!-- Options Preview -->
                    <div class="mt-3 space-y-1">
                        <div 
                           v-for="option in question.options" 
                           :key="option.id"
                           class="flex items-center gap-2 text-sm"
                           :class="option.is_correct ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-500 dark:text-gray-400'"
                        >
                            <Icon 
                                :icon="option.is_correct ? 'fluent:checkmark-circle-24-filled' : 'fluent:circle-24-regular'" 
                                class="w-4 h-4 flex-shrink-0"
                            />
                            <img 
                                v-if="option.images?.length" 
                                :src="option.images[0].full_url || option.images[0].image_url" 
                                class="h-10 w-auto rounded border border-gray-200 dark:border-gray-700 object-cover cursor-pointer hover:opacity-90 transition-opacity" 
                                @click.stop="openLightbox(option.images, 0)"
                            />
                            <span v-if="option.text">{{ option.text }}</span>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button 
                        @click="emit('edit', question)" 
                        class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg"
                        title="แก้ไข"
                    >
                        <Icon icon="fluent:edit-20-regular" class="w-5 h-5" />
                    </button>
                    <button 
                        @click="deleteQuestion(question)" 
                        class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                        title="ลบ"
                    >
                        <Icon icon="fluent:delete-20-regular" class="w-5 h-5" />
                    </button>
                </div>
            </div>
            
             <div class="absolute top-4 right-4 text-xs font-medium text-gray-400">
                {{ formatScore(question.points) }} คะแนน
            </div>
        </div>
    </div>
    
    <ImageLightbox
        :show="showLightbox"
        :images="lightboxImages"
        :initial-index="lightboxIndex"
        @close="closeLightbox"
    />

    <!-- Celebration Modal (เด้งครั้งแรกที่ทำครบเท่านั้น) -->
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
          v-if="showCelebration"
          role="dialog"
          aria-modal="true"
          aria-labelledby="quiz-celebration-title"
          class="fixed inset-0 z-[1000] flex items-end sm:items-center justify-center bg-black/60 backdrop-blur-sm p-0 sm:p-4"
          @click.self="closeCelebration"
        >
          <div class="w-full sm:max-w-lg max-h-[92vh] overflow-y-auto p-3 sm:p-0">
            <LessonQuizCompletionCard
              class="shadow-2xl"
              variant="modal"
              title-id="quiz-celebration-title"
              :earned-points="displayEarnedPoints"
              :total-points="displayTotalPoints"
              :correct-count="displayCorrectCount"
              :total-questions="displayTotalQuestions"
              :percentage="displayPercentage"
              :lesson-completed="lessonCompleted"
              :has-assignments="hasAssignments"
              :assignment-count="assignmentCount"
              :next-lesson="nextLesson"
              :toggling-progress="togglingProgress"
              @mark-complete="handleMarkComplete"
              @go-assignments="handleGoAssignments"
              @go-next-lesson="handleGoNextLesson"
              @go-progress="handleGoProgress"
            />
            <button
              ref="celebrationCloseBtn"
              type="button"
              class="mt-3 w-full py-3 rounded-xl font-medium text-white bg-white/15 hover:bg-white/25
                     backdrop-blur border border-white/20 transition-colors
                     focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
              @click="closeCelebration"
            >
              ปิดหน้าต่างนี้
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
    <QuestionImportModal v-if="isCreator" :show="showImportModal" :scope="importScope" @close="showImportModal = false" @imported="onQuestionsImported" />
    <QuestionBulkSettingsModal
        v-if="isCreator"
        :show="showBulkSettings"
        :endpoint="bulkSettingsEndpoint"
        :question-count="questions.length"
        @close="showBulkSettings = false"
        @saved="onBulkSettingsSaved"
    />
  </div>
</template>

<style scoped>
    .list-move, /* apply transition to moving elements */
    .list-enter-active,
    .list-leave-active {
        transition: all 0.5s ease;
    }

    .list-enter-from,
    .list-leave-to {
        opacity: 0;
        transform: translateX(30px);
    }

    /* ensure leaving items are taken out of layout flow so that moving
       items can be calculated correctly. */
    .list-leave-active {
        position: absolute;
    }
</style>
