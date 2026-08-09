<script setup lang="ts">
import { Icon } from '@iconify/vue'
import type {
  AcademicYearDTO,
  ClassroomOptionDTO,
  RolloverBatchDTO,
  RolloverPlanResponse,
  RolloverPreviewDTO,
  RolloverPreviewEntryDTO,
  RolloverSummaryDTO,
} from '~/types/enrollment'

definePageMeta({
  layout: 'main',
})

interface YearForm {
  name: string
  start_date: string
  end_date: string
  is_current: boolean
  create_semesters: boolean
}

interface WizardState {
  step: number
  fromYearId: number | null
  toYearId: number | null
  toMode: 'existing' | 'create'
  newYearForm: YearForm
  preview: RolloverPreviewDTO | null
  mapping: RolloverPreviewEntryDTO[]
  planId: string | null
  planSummary: RolloverSummaryDTO | null
  planWarnings: string[]
  planEntriesCount: number
  planExpiresAt: string | null
  batch: RolloverBatchDTO | null
}

const route = useRoute()
const api = useApi()
const toast = useToast()
const swal = useSweetAlert()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isWorking = ref(false)
const isCreatingYear = ref(false)
const isCreatingClassroom = ref(false)
const isSubmittingCommit = ref(false)

const academicYears = ref<AcademicYearDTO[]>([])
const targetClassrooms = ref<ClassroomOptionDTO[]>([])

const defaultNewYearForm = (): YearForm => {
  const currentYear = new Date().getFullYear() + 543
  return {
    name: String(currentYear + 1),
    start_date: `${new Date().getFullYear() + 1}-05-16`,
    end_date: `${new Date().getFullYear() + 2}-03-31`,
    is_current: true,
    create_semesters: true,
  }
}

const state = reactive<WizardState>({
  step: 1,
  fromYearId: null,
  toYearId: null,
  toMode: 'existing',
  newYearForm: defaultNewYearForm(),
  preview: null,
  mapping: [],
  planId: null,
  planSummary: null,
  planWarnings: [],
  planEntriesCount: 0,
  planExpiresAt: null,
  batch: null,
})

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)
const {
  preview,
  plan,
  commit,
  undo,
  closeUndo,
  fetchBatch,
  fetchAcademicYears,
  createAcademicYear,
  fetchClassroomsByYear,
  createClassroom,
  getErrorMessage,
} = useRolloverActions(academyId)

const storageKey = computed(() => `rollover-wizard:${academyName.value}`)
const currentToYear = computed(() => academicYears.value.find((year) => year.id === state.toYearId) ?? null)
const incompleteCount = computed(() => {
  return state.mapping.filter((entry) => {
    if (entry.action === 'promote' || entry.action === 'repeat' || entry.action === 'new_intake') {
      return !entry.to_classroom_id
    }

    return false
  }).length
})

function persistState() {
  if (typeof sessionStorage === 'undefined') return
  sessionStorage.setItem(storageKey.value, JSON.stringify(state))
}

function restoreState() {
  if (typeof sessionStorage === 'undefined') return
  const raw = sessionStorage.getItem(storageKey.value)
  if (!raw) return

  try {
    const parsed = JSON.parse(raw)
    Object.assign(state, {
      ...state,
      ...parsed,
      newYearForm: {
        ...defaultNewYearForm(),
        ...(parsed.newYearForm ?? {}),
      },
    })
  } catch (error) {
    console.error('Failed to restore rollover wizard state:', error)
  }
}

function resetPlanState() {
  state.planId = null
  state.planSummary = null
  state.planWarnings = []
  state.planEntriesCount = 0
  state.planExpiresAt = null
}

function resetWizard() {
  state.step = 1
  state.fromYearId = null
  state.toYearId = null
  state.toMode = 'existing'
  state.newYearForm = defaultNewYearForm()
  state.preview = null
  state.mapping = []
  resetPlanState()
  state.batch = null
  targetClassrooms.value = []
  if (typeof sessionStorage !== 'undefined') {
    sessionStorage.removeItem(storageKey.value)
  }
}

function handleStepSelect(step: number) {
  if (step >= state.step) return
  state.step = step
}

function normalizePreviewEntries(entries: RolloverPreviewEntryDTO[]): RolloverPreviewEntryDTO[] {
  return entries.map((entry) => ({
    student_id: entry.student_id,
    student_name: entry.student_name,
    from_classroom_id: entry.from_classroom_id ?? null,
    from_classroom_name: entry.from_classroom_name ?? null,
    to_classroom_id: entry.to_classroom_id ?? null,
    to_classroom_name: entry.to_classroom_name ?? null,
    action: entry.action,
    reason: entry.reason ?? null,
  }))
}

function buildMappingPayload() {
  return state.mapping.map((entry) => ({
    student_id: entry.student_id,
    from_classroom_id: entry.from_classroom_id ?? null,
    to_classroom_id: entry.to_classroom_id ?? null,
    action: entry.action,
    reason: entry.reason ?? null,
  }))
}

async function loadAcademy() {
  const response: any = await api.get(`/api/academies/${academyName.value}`)
  if (!response.success) {
    throw new Error('Failed to load academy')
  }

  academy.value = response.academy
  academyId.value = response.academy.id
  await fetchMyRole()

  if (!isAdmin.value) {
    await navigateTo(`/academies/${academyName.value}`)
    return
  }
}

async function loadBaseData() {
  academicYears.value = await fetchAcademicYears()
  if (!state.fromYearId) {
    state.fromYearId = academicYears.value.find((year) => year.is_current)?.id ?? academicYears.value[0]?.id ?? null
  }
}

async function refreshPreviewAndTargets() {
  if (!state.fromYearId || !state.toYearId) return

  const [previewResponse, classrooms] = await Promise.all([
    preview(state.fromYearId, state.toYearId),
    fetchClassroomsByYear(state.toYearId),
  ])

  state.preview = previewResponse.preview
  targetClassrooms.value = classrooms
}

async function handleCreateYear() {
  if (!state.newYearForm.name || !state.newYearForm.start_date || !state.newYearForm.end_date) {
    toast.error('กรอกข้อมูลปีใหม่ให้ครบก่อน')
    return
  }

  isCreatingYear.value = true
  try {
    const createdYear = await createAcademicYear(state.newYearForm)
    academicYears.value = await fetchAcademicYears()
    state.toMode = 'existing'
    state.toYearId = createdYear.id
    toast.success(`สร้างปี ${createdYear.name} แล้ว`)
  } catch (error) {
    toast.error(getErrorMessage('ไม่สามารถสร้างปีการศึกษาใหม่ได้'))
  } finally {
    isCreatingYear.value = false
  }
}

async function handleStep1Next() {
  if (!state.fromYearId || !state.toYearId) {
    toast.error('เลือกปีต้นทางและปีปลายทางก่อน')
    return
  }

  isWorking.value = true
  try {
    await refreshPreviewAndTargets()
    state.step = 2
  } catch (error) {
    toast.error(getErrorMessage('ไม่สามารถเตรียมข้อมูล preview ได้'))
  } finally {
    isWorking.value = false
  }
}

async function handleCreateMissingGrade(gradeLevel: string) {
  if (!state.toYearId) return

  isCreatingClassroom.value = true
  try {
    await createClassroom({
      academic_year_id: state.toYearId,
      grade_level: gradeLevel,
      section: '1',
      name: `${gradeLevel}/1`,
      capacity: 50,
    })
    await refreshPreviewAndTargets()
    toast.success(`สร้างห้อง ${gradeLevel}/1 แล้ว`)
  } catch (error) {
    toast.error(getErrorMessage(`ไม่สามารถสร้างห้อง ${gradeLevel}/1 ได้`))
  } finally {
    isCreatingClassroom.value = false
  }
}

async function handleCreateAllMissingGrades() {
  const missingTargets = state.preview?.missing_targets ?? []
  if (!state.toYearId || missingTargets.length === 0) return

  isCreatingClassroom.value = true
  try {
    for (const gradeLevel of missingTargets) {
      await createClassroom({
        academic_year_id: state.toYearId,
        grade_level: gradeLevel,
        section: '1',
        name: `${gradeLevel}/1`,
        capacity: 50,
      })
    }

    await refreshPreviewAndTargets()
    toast.success('สร้างห้องที่ขาดทั้งหมดแล้ว')
  } catch (error) {
    toast.error(getErrorMessage('ไม่สามารถสร้างห้องครบทั้งหมดได้'))
  } finally {
    isCreatingClassroom.value = false
  }
}

function handleStep2Next() {
  if ((state.preview?.missing_targets?.length ?? 0) > 0) {
    toast.error('สร้างห้องปลายทางให้ครบก่อน')
    return
  }

  state.mapping = normalizePreviewEntries(state.preview?.entries ?? [])
  resetPlanState()
  state.step = 3
}

async function handleStep3Next() {
  if (incompleteCount.value > 0) {
    toast.error(`ยังมี ${incompleteCount.value} รายการที่ต้องเลือกห้องปลายทาง`)
    return
  }

  if (!state.fromYearId || !state.toYearId) return

  isWorking.value = true
  try {
    const planResponse: RolloverPlanResponse = await plan(
      state.fromYearId,
      state.toYearId,
      buildMappingPayload(),
    )

    state.planId = planResponse.plan_id
    state.planSummary = planResponse.summary
    state.planWarnings = planResponse.warnings
    state.planEntriesCount = planResponse.entries_count
    state.planExpiresAt = new Date(Date.now() + (planResponse.expires_in_seconds * 1000)).toISOString()
    state.step = 4
  } catch (error) {
    toast.error(getErrorMessage('ไม่สามารถสร้างแผน rollover ได้'))
  } finally {
    isWorking.value = false
  }
}

function handlePlanExpired() {
  resetPlanState()
  state.step = 3
  toast.warning('plan หมดอายุแล้ว กรุณาสร้างแผนใหม่อีกครั้ง')
}

function backToStep3() {
  resetPlanState()
  state.step = 3
}

async function handleCommit(confirmText: string) {
  if (!state.planId) return

  const confirmed = await swal.confirm(
    `ระบบจะ commit แผนไปยังปี ${currentToYear.value?.name ?? ''} และเริ่มหน้าต่าง undo 24 ชั่วโมง`,
    'ยืนยัน commit rollover',
    {
      confirmText: 'Commit',
      cancelText: 'ยกเลิก',
      icon: 'warning',
      isDanger: true,
    },
  )

  if (!confirmed) return

  isSubmittingCommit.value = true
  try {
    const response = await commit(state.planId, confirmText.trim())
    state.batch = response.batch
    resetPlanState()
    state.step = 6
    toast.success('commit rollover สำเร็จ')
  } catch (error: any) {
    const apiError = error?.data?.error
    if (apiError === 'plan_expired') {
      handlePlanExpired()
      return
    }

    toast.error(getErrorMessage('ไม่สามารถ commit rollover ได้'))
  } finally {
    isSubmittingCommit.value = false
  }
}

async function handleUndo() {
  if (!state.batch) return

  try {
    await undo(state.batch.id)
    resetWizard()
    toast.success('undo rollover สำเร็จแล้ว ระบบพากลับไป Step 1')
  } catch (error) {
    toast.error(getErrorMessage('ไม่สามารถ undo rollover ได้'))
  }
}

async function handleCloseUndo() {
  if (!state.batch) return

  try {
    const response = await closeUndo(state.batch.id)
    state.batch = response.batch
    toast.success('ปิดหน้าต่าง undo แล้ว')
  } catch (error) {
    toast.error(getErrorMessage('ไม่สามารถปิดหน้าต่าง undo ได้'))
  }
}

let batchRefreshTimer: ReturnType<typeof setInterval> | null = null

function startBatchRefresh() {
  stopBatchRefresh()
  if (!state.batch) return

  batchRefreshTimer = setInterval(async () => {
    if (!state.batch) return

    try {
      const response = await fetchBatch(state.batch.id)
      state.batch = response.batch
    } catch (error) {
      console.error('Failed to refresh rollover batch:', error)
    }
  }, 60000)
}

function stopBatchRefresh() {
  if (batchRefreshTimer) {
    clearInterval(batchRefreshTimer)
    batchRefreshTimer = null
  }
}

watch(state, () => {
  persistState()
}, { deep: true })

watch(() => state.step, (step) => {
  if (step === 6 && state.batch) {
    startBatchRefresh()
    return
  }

  stopBatchRefresh()
})

onMounted(async () => {
  try {
    await loadAcademy()
    restoreState()
    await loadBaseData()

    if (state.toYearId) {
      targetClassrooms.value = await fetchClassroomsByYear(state.toYearId)
    }

    if (state.fromYearId && state.toYearId && state.step >= 2 && !state.preview) {
      await refreshPreviewAndTargets()
    }

    if (state.step >= 4 && state.planExpiresAt && new Date(state.planExpiresAt).getTime() <= Date.now()) {
      handlePlanExpired()
    }

    if (state.step === 6 && state.batch) {
      const response = await fetchBatch(state.batch.id)
      state.batch = response.batch
      startBatchRefresh()
    }
  } catch (error) {
    console.error('Failed to initialize rollover wizard:', error)
  } finally {
    isLoading.value = false
  }
})

onBeforeUnmount(() => {
  stopBatchRefresh()
})
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="h-10 w-10 animate-spin rounded-full border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="mx-auto max-w-7xl space-y-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <div class="mb-2 flex items-center gap-3">
            <NuxtLink :to="`/academies/${academyName}/admin/gradebook`" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
              <Icon icon="fluent:arrow-left-24-filled" class="h-5 w-5" />
            </NuxtLink>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Year Rollover Wizard</h1>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            เลื่อนชั้นรายปีแบบมี preview, plan cache 15 นาที, commit gate และ undo 24 ชั่วโมง
          </p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
          <div>Academy: <span class="font-semibold text-gray-900 dark:text-white">{{ academy?.name }}</span></div>
          <div v-if="currentToYear">Target year: <span class="font-semibold text-gray-900 dark:text-white">{{ currentToYear.name }}</span></div>
        </div>
      </div>

      <RolloverStepIndicator
        :current="state.step"
        :unlocked-through="state.step"
        @select="handleStepSelect"
      />

      <div v-if="isWorking" class="rounded-2xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-700 dark:border-primary-800 dark:bg-primary-900/20 dark:text-primary-300">
        กำลังประมวลผลข้อมูล rollover...
      </div>

      <RolloverYearPicker
        v-if="state.step === 1"
        :academic-years="academicYears"
        :from-year-id="state.fromYearId"
        :to-year-id="state.toYearId"
        :to-mode="state.toMode"
        :new-year-form="state.newYearForm"
        :is-creating-year="isCreatingYear"
        @update:from-year-id="state.fromYearId = $event"
        @update:to-year-id="state.toYearId = $event"
        @update:to-mode="state.toMode = $event"
        @update:new-year-form="state.newYearForm = $event"
        @create-year="handleCreateYear"
        @next="handleStep1Next"
      />

      <RolloverClassroomChecklist
        v-else-if="state.step === 2"
        :preview="state.preview"
        :target-classrooms="targetClassrooms"
        :target-year-name="currentToYear?.name ?? '-'"
        :is-creating="isCreatingClassroom"
        @create-grade="handleCreateMissingGrade"
        @create-all="handleCreateAllMissingGrades"
        @back="state.step = 1"
        @next="handleStep2Next"
      />

      <RolloverStudentBucket
        v-else-if="state.step === 3"
        :entries="state.mapping"
        :target-classrooms="targetClassrooms"
        @update:entries="state.mapping = $event"
        @back="state.step = 2"
        @next="handleStep3Next"
      />

      <RolloverPreviewSummary
        v-else-if="state.step === 4"
        :summary="state.planSummary"
        :warnings="state.planWarnings"
        :entries-count="state.planEntriesCount"
        :plan-id="state.planId"
        :expires-at="state.planExpiresAt"
        :entries="state.mapping"
        @back="backToStep3"
        @next="state.step = 5"
        @expired="handlePlanExpired"
      />

      <RolloverCommitPanel
        v-else-if="state.step === 5"
        :target-year-name="currentToYear?.name ?? ''"
        :plan-id="state.planId"
        :summary="state.planSummary"
        :warnings="state.planWarnings"
        :expires-at="state.planExpiresAt"
        :is-submitting="isSubmittingCommit"
        @back="state.step = 4"
        @commit="handleCommit"
        @expired="handlePlanExpired"
      />

      <RolloverUndoBanner
        v-else-if="state.step === 6 && state.batch"
        :batch="state.batch"
        @undo="handleUndo"
        @close-undo="handleCloseUndo"
        @reset="resetWizard"
      />
    </div>
  </div>
</template>
