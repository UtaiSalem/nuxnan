<script setup lang="ts">
import type { AcademyInfo, HomeVisitInfo, StudentProfile } from '~/composables/useStudentProfile'
import type { HomeVisitPayload, HomeVisitRecord, HomeVisitStatus } from '~/composables/useHomeVisit'

const props = defineProps<{
  homeVisit: HomeVisitInfo | null
  student: StudentProfile
  academy: AcademyInfo | null
  accessLevel: string
}>()

const emit = defineEmits<{
  saved: []
}>()

const { isLoading, isSubmitting, error, fetchVisits, createVisit, updateVisit, deleteVisit } = useHomeVisit(
  computed(() => props.academy?.name ?? ''),
  computed(() => props.student.id),
)

const visits = ref<HomeVisitRecord[]>([])
const currentPage = ref(1)
const lastPage = ref(1)
const total = ref(props.homeVisit?.total_visits ?? 0)
const showForm = ref(false)
const editingId = ref<number | null>(null)
const formError = ref<string | null>(null)

const localDateInputValue = () => {
  const now = new Date()
  const offset = now.getTimezoneOffset() * 60_000
  return new Date(now.getTime() - offset).toISOString().slice(0, 10)
}

const emptyForm = (): HomeVisitPayload => ({
  visit_date: localDateInputValue(),
  visit_time: null,
  visitor_name: '',
  visitor_position: '',
  visit_status: 'scheduled',
  zone_id: null,
  observations: null,
  recommendations: null,
  follow_up: null,
  next_visit: null,
})

const form = reactive<HomeVisitPayload>(emptyForm())

const canCreate = computed(() => ['admin', 'homeroom', 'teacher'].includes(props.accessLevel))
const canDelete = computed(() => ['admin', 'homeroom'].includes(props.accessLevel))
const canSeeObservations = computed(() => ['admin', 'homeroom', 'teacher'].includes(props.accessLevel))

const statusOptions: Array<{ value: HomeVisitStatus, label: string }> = [
  { value: 'scheduled', label: 'นัดหมาย' },
  { value: 'completed', label: 'เสร็จสิ้น' },
  { value: 'cancelled', label: 'ยกเลิก' },
]

const statusLabel = (status: HomeVisitStatus) =>
  statusOptions.find(option => option.value === status)?.label ?? status

const statusClass = (status: HomeVisitStatus) => ({
  scheduled: 'bg-blue-100 text-blue-700',
  completed: 'bg-green-100 text-green-700',
  cancelled: 'bg-gray-100 text-gray-600',
}[status])

const formatDate = (value: string | null) => value
  ? new Date(`${value}T00:00:00`).toLocaleDateString('th-TH', { dateStyle: 'medium' })
  : '—'

const loadVisits = async (page = 1) => {
  try {
    const result = await fetchVisits(page)
    visits.value = result.data
    currentPage.value = result.current_page
    lastPage.value = result.last_page
    total.value = result.total
  } catch {
    visits.value = []
  }
}

const resetForm = () => {
  Object.assign(form, emptyForm())
  editingId.value = null
  formError.value = null
}

const openCreate = () => {
  resetForm()
  showForm.value = true
}

const openEdit = (visit: HomeVisitRecord) => {
  editingId.value = visit.id
  Object.assign(form, {
    visit_date: visit.visit_date,
    visit_time: visit.visit_time,
    visitor_name: visit.visitor_name,
    visitor_position: visit.visitor_position ?? '',
    visit_status: visit.visit_status,
    zone_id: visit.zone_id,
    observations: visit.observations,
    recommendations: visit.recommendations,
    follow_up: visit.follow_up,
    next_visit: visit.next_visit,
  })
  formError.value = null
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  resetForm()
}

const saveVisit = async () => {
  formError.value = null
  try {
    if (editingId.value) await updateVisit(editingId.value, { ...form })
    else await createVisit({ ...form })
    closeForm()
    await loadVisits(currentPage.value)
    emit('saved')
  } catch (err: any) {
    const validation = err?.data?.errors
    formError.value = validation
      ? Object.values(validation).flat().join(' ')
      : err?.data?.message || err?.message || 'บันทึกข้อมูลไม่สำเร็จ'
  }
}

const removeVisit = async (visit: HomeVisitRecord) => {
  if (!window.confirm(`ต้องการลบรายการวันที่ ${formatDate(visit.visit_date)} หรือไม่?`)) return
  try {
    await deleteVisit(visit.id)
    await loadVisits(currentPage.value)
    if (visits.value.length === 0 && currentPage.value > 1) await loadVisits(currentPage.value - 1)
    emit('saved')
  } catch {
    // The composable exposes the API error in the page-level error block.
  }
}

watch(() => [props.academy?.name, props.student.id], () => loadVisits(), { immediate: true })
</script>

<template>
  <div class="space-y-4">
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="text-sm font-semibold text-gray-900">การเยี่ยมบ้าน</h3>
          <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{{ total }} ครั้ง</span>
        </div>
        <button
          v-if="canCreate"
          type="button"
          class="min-h-[44px] sm:min-h-0 rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-blue-700"
          @click="openCreate"
        >
          + บันทึกใหม่
        </button>
      </div>

      <div v-if="homeVisit?.next_scheduled" class="mt-4 rounded-xl border border-amber-100 bg-amber-50 p-3">
        <p class="text-xs font-medium text-amber-700">นัดเยี่ยมครั้งต่อไป</p>
        <p class="mt-0.5 text-sm text-amber-900">{{ formatDate(homeVisit.next_scheduled.scheduled_at) }}</p>
      </div>

      <form v-show="showForm" class="mt-4 space-y-4 rounded-xl border border-blue-100 bg-blue-50/40 p-4" @submit.prevent="saveVisit">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-semibold text-gray-800">{{ editingId ? 'แก้ไขการเยี่ยมบ้าน' : 'บันทึกการเยี่ยมบ้าน' }}</h4>
          <button type="button" class="text-xs text-gray-500 hover:text-gray-800" @click="closeForm">ปิด</button>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label class="text-xs font-medium text-gray-700">วันที่เยี่ยม *
            <input v-model="form.visit_date" required type="date" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
          </label>
          <label class="text-xs font-medium text-gray-700">เวลา
            <input v-model="form.visit_time" type="time" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
          </label>
          <label class="text-xs font-medium text-gray-700">ผู้เยี่ยม *
            <input v-model="form.visitor_name" required maxlength="255" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
          </label>
          <label class="text-xs font-medium text-gray-700">ตำแหน่ง *
            <input v-model="form.visitor_position" required maxlength="255" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
          </label>
          <label class="text-xs font-medium text-gray-700">สถานะ *
            <select v-model="form.visit_status" required class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm">
              <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
          </label>
          <label class="text-xs font-medium text-gray-700">นัดครั้งถัดไป
            <input v-model="form.next_visit" type="date" :min="form.visit_date" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
          </label>
        </div>

        <label v-if="canSeeObservations" class="block text-xs font-medium text-gray-700">ข้อสังเกต
          <textarea v-model="form.observations" rows="3" maxlength="5000" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
        </label>
        <label class="block text-xs font-medium text-gray-700">ข้อเสนอแนะ
          <textarea v-model="form.recommendations" rows="2" maxlength="2000" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm" />
        </label>

        <p v-if="formError" class="text-xs text-red-600">{{ formError }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" class="min-h-[44px] sm:min-h-0 rounded-lg bg-gray-100 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200" @click="closeForm">ยกเลิก</button>
          <button type="submit" :disabled="isSubmitting" class="min-h-[44px] sm:min-h-0 rounded-lg bg-blue-600 px-4 py-2 text-xs font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
            {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึก' }}
          </button>
        </div>
      </form>
    </div>

    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
      <h4 class="mb-4 text-sm font-semibold text-gray-900">รายการเยี่ยมบ้านทั้งหมด</h4>

      <div v-if="isLoading" class="py-10 text-center text-sm text-gray-400">กำลังโหลดข้อมูล...</div>
      <div v-else-if="error" class="rounded-xl bg-red-50 p-4 text-center text-sm text-red-600">
        <p>{{ error }}</p>
        <button type="button" class="mt-2 font-medium underline" @click="loadVisits(currentPage)">ลองอีกครั้ง</button>
      </div>
      <div v-else-if="visits.length === 0" class="py-10 text-center text-sm text-gray-400">ยังไม่มีประวัติการเยี่ยมบ้าน</div>

      <ul v-else class="space-y-3">
        <li v-for="visit in visits" :key="visit.id" class="rounded-xl border border-gray-100 bg-gray-50 p-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-gray-800">{{ formatDate(visit.visit_date) }}</p>
                <span :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusClass(visit.visit_status)]">{{ statusLabel(visit.visit_status) }}</span>
              </div>
              <p class="mt-1 text-xs text-gray-500">{{ visit.visitor_name }} · {{ visit.visitor_position || 'ไม่ระบุตำแหน่ง' }}</p>
              <p v-if="canSeeObservations && visit.observations" class="mt-2 whitespace-pre-line text-xs text-gray-700">{{ visit.observations }}</p>
              <p v-if="visit.recommendations" class="mt-2 text-xs text-gray-600"><span class="font-medium">ข้อเสนอแนะ:</span> {{ visit.recommendations }}</p>
            </div>
            <div v-if="canCreate" class="flex gap-2">
              <button type="button" class="text-xs font-medium text-blue-600 hover:text-blue-800" @click="openEdit(visit)">แก้ไข</button>
              <button v-if="canDelete" type="button" class="text-xs font-medium text-red-600 hover:text-red-800" @click="removeVisit(visit)">ลบ</button>
            </div>
          </div>
        </li>
      </ul>

      <div v-if="lastPage > 1" class="mt-4 flex items-center justify-between border-t border-gray-100 pt-4">
        <button type="button" :disabled="currentPage <= 1" class="min-h-[44px] sm:min-h-0 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40" @click="loadVisits(currentPage - 1)">ก่อนหน้า</button>
        <span class="text-xs text-gray-500">หน้า {{ currentPage }} / {{ lastPage }}</span>
        <button type="button" :disabled="currentPage >= lastPage" class="min-h-[44px] sm:min-h-0 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40" @click="loadVisits(currentPage + 1)">ถัดไป</button>
      </div>
    </div>
  </div>
</template>
