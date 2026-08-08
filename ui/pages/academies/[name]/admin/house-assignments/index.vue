<script setup lang="ts">
/**
 * Academy Admin — แบ่งนักเรียนเข้าคณะสี (S-S3b)
 *
 * โครงขั้นตอนมาจาก HopeUI form-wizard (step chips + การ์ด) แต่ใช้โทเคนของ nuxnan
 * และวางพี่น้องกับ admin/school-attendance ให้หน้าตาเข้าชุดกัน
 *
 * ทั้งโหมดสุ่มและโหมดนำเข้าจบที่ batch เดียวกัน → หน้านี้จึงมีจอ preview/commit/undo
 * ชุดเดียว ไม่ได้แยกตามโหมด
 */
import { Icon } from '@iconify/vue'
import type { HouseAssignmentBatch, HouseAssignmentRow, HouseRowStatus } from '~/composables/useHouseAssignments'

definePageMeta({ layout: 'main' })

const route = useRoute()
const api = useApi()
const houses = useHouseAssignments()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const { isAdmin, can, fetchMyRole } = useAcademyRole(academyId)

const isLoading = ref(true)
const isWorking = ref(false)
const errorMessage = ref('')

const selectedEditionId = ref<number | null>(null)
/** คณะสีของครั้งที่เลือก มาจากตัว edition โดยตรง ไม่ใช่จากคีย์ของยอดนับ — ยอดนับบังเอิญมีครบทุกสี
 * เพราะ API เติมศูนย์ให้ ถ้าวันหนึ่งมันเลิกเติม ตัวเลือกคณะสีจะว่างเงียบ ๆ โดยไม่มีอะไรฟ้อง */
const editionHouseIds = ref<number[]>([])
const houseGroups = ref<any[]>([])
const currentCounts = ref<Record<string, number>>({})
const batches = ref<HouseAssignmentBatch[]>([])

const mode = ref<'random' | 'import'>('random')
const batch = ref<HouseAssignmentBatch | null>(null)
const rows = ref<HouseAssignmentRow[]>([])
const rowFilter = ref<HouseRowStatus | ''>('')

const randomForm = ref({
  house_group_ids: [] as number[],
  strategy: 'stratified' as 'stratified' | 'pure_random',
  balance_gender: true,
  scope: 'unassigned_only' as 'unassigned_only' | 'all',
  seed: null as number | null,
})

const importForm = ref({
  file: null as File | null,
  student_identifier: 'เลขประจำตัว',
  house_name: 'คณะสี',
  first_name_th: '',
  last_name_th: '',
  on_conflict: 'skip' as 'skip' | 'overwrite',
})

/** ขั้นที่ผู้ใช้อยู่ตอนนี้ — ใช้ระบายสี step chips */
const step = computed(() => {
  if (!batch.value) return 1
  return batch.value.status === 'draft' ? 2 : 3
})

const steps = [
  { n: 1, label: 'เลือกวิธีแบ่ง', icon: 'fluent:options-24-filled' },
  { n: 2, label: 'ตรวจผลก่อนบันทึก', icon: 'fluent:clipboard-task-list-24-filled' },
  { n: 3, label: 'บันทึกแล้ว', icon: 'fluent:checkmark-circle-24-filled' },
]

const houseName = (id: number | string | null | undefined) => {
  if (id === null || id === undefined) return '—'
  return houseGroups.value.find((h) => Number(h.id) === Number(id))?.name ?? `#${id}`
}

const houseColor = (id: number | string) =>
  houseGroups.value.find((h) => Number(h.id) === Number(id))?.settings?.color || '#8b5cf6'

const totalAssignedNow = computed(() =>
  Object.values(currentCounts.value).reduce((sum, n) => sum + Number(n || 0), 0),
)

const canUndo = computed(() => houses.isUndoable(batch.value))

/** สถานะที่ต้องมีคนตัดสินก่อน commit — ไม่ใช่ทุกสถานะที่ไม่ใช่ ok */
const problemStatuses: HouseRowStatus[] = ['unmatched', 'ambiguous', 'unknown_house']

const statusMeta: Record<HouseRowStatus, { label: string; tone: string; hint: string }> = {
  ok: { label: 'พร้อมบันทึก', tone: 'text-emerald-700 bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300', hint: 'จับคู่นักเรียนและคณะสีได้ครบ' },
  unmatched: { label: 'ไม่พบนักเรียน', tone: 'text-rose-700 bg-rose-100 dark:bg-rose-900/30 dark:text-rose-300', hint: 'ไม่มีนักเรียนที่ตรงกับข้อมูลในแถวนี้' },
  ambiguous: { label: 'ระบุตัวไม่ได้', tone: 'text-amber-700 bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300', hint: 'ตรงกับนักเรียนมากกว่า 1 คน หรือมีชื่อซ้ำในไฟล์ — ระบบไม่เดาให้' },
  unknown_house: { label: 'ไม่รู้จักคณะสี', tone: 'text-orange-700 bg-orange-100 dark:bg-orange-900/30 dark:text-orange-300', hint: 'ชื่อคณะสีในไฟล์ไม่ตรงกับที่มีอยู่ — ต้องสร้างคณะสีก่อน ระบบไม่สร้างให้อัตโนมัติ' },
  already_assigned: { label: 'มีสีอยู่แล้ว', tone: 'text-slate-700 bg-slate-100 dark:bg-slate-700 dark:text-slate-300', hint: 'ข้ามไว้ตามค่าตั้งต้น เลือก "ทับของเดิม" ถ้าต้องการย้าย' },
  skipped: { label: 'แถวว่าง', tone: 'text-slate-500 bg-slate-100 dark:bg-slate-700 dark:text-slate-400', hint: '' },
}

const byStatus = computed(() => batch.value?.summary?.by_status ?? null)

const problemCount = computed(() => {
  if (!byStatus.value) return 0
  return problemStatuses.reduce((sum, s) => sum + Number(byStatus.value?.[s] ?? 0), 0)
})

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (!res?.success) return
    academy.value = res.academy
    academyId.value = res.academy.id
    await fetchMyRole()
    if (!isAdmin.value && !can('sports.manage')) {
      navigateTo(`/academies/${academyName.value}`)
      return
    }
    await Promise.all([loadHouses(), refreshEditionState()])
  } finally {
    isLoading.value = false
  }
})

const loadHouses = async () => {
  const res: any = await houses.listHouses(academyId.value!)
  houseGroups.value = res?.groups || []
}

const refreshEditionState = async () => {
  if (!academyId.value || !selectedEditionId.value) {
    currentCounts.value = {}
    batches.value = []
    return
  }
  const [counts, list]: any = await Promise.all([
    houses.getCurrentCounts(academyId.value, selectedEditionId.value),
    houses.listBatches(academyId.value, { edition_id: selectedEditionId.value, per_page: 10 }),
  ])
  currentCounts.value = counts?.counts || {}
  batches.value = (list?.data ?? []) as HouseAssignmentBatch[]
}

watch(selectedEditionId, () => {
  batch.value = null
  rows.value = []
  refreshEditionState()
})

watch(editionHouseIds, (ids) => {
  randomForm.value.house_group_ids = [...ids]
})

const runRandom = async () => {
  if (!academyId.value || !selectedEditionId.value || isWorking.value) return
  errorMessage.value = ''
  isWorking.value = true
  try {
    const res: any = await houses.previewRandom(academyId.value, {
      edition_id: selectedEditionId.value,
      ...randomForm.value,
      seed: randomForm.value.seed || undefined,
    })
    batch.value = res?.batch || null
    await loadRows()
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'สุ่มไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const runImport = async () => {
  if (!academyId.value || !selectedEditionId.value || !importForm.value.file || isWorking.value) return
  errorMessage.value = ''
  isWorking.value = true
  try {
    const mapping: Record<string, string> = {
      student_identifier: importForm.value.student_identifier,
      house_name: importForm.value.house_name,
    }
    if (importForm.value.first_name_th) mapping.first_name_th = importForm.value.first_name_th
    if (importForm.value.last_name_th) mapping.last_name_th = importForm.value.last_name_th

    const res: any = await houses.previewImport(
      academyId.value,
      selectedEditionId.value,
      importForm.value.file,
      mapping,
      importForm.value.on_conflict,
    )
    batch.value = res?.batch || null
    await loadRows()
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'นำเข้าไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const loadRows = async () => {
  if (!academyId.value || !batch.value) return
  const res: any = await houses.getRows(academyId.value, batch.value.id, {
    status: rowFilter.value || undefined,
    per_page: 100,
  })
  rows.value = (res?.data || []) as HouseAssignmentRow[]
}

watch(rowFilter, loadRows)

const onFilePicked = (event: Event) => {
  const input = event.target as HTMLInputElement
  importForm.value.file = input.files?.[0] || null
}

const commit = async () => {
  if (!academyId.value || !batch.value || isWorking.value) return
  isWorking.value = true
  errorMessage.value = ''
  try {
    const res: any = await houses.commitBatch(academyId.value, batch.value.id)
    batch.value = res?.batch || batch.value
    await refreshEditionState()
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'บันทึกไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const undo = async () => {
  if (!academyId.value || !batch.value || isWorking.value) return
  isWorking.value = true
  errorMessage.value = ''
  try {
    const res: any = await houses.undoBatch(academyId.value, batch.value.id)
    batch.value = res?.batch || batch.value
    await refreshEditionState()
  } catch (e: any) {
    errorMessage.value = e?.data?.message || 'ย้อนกลับไม่สำเร็จ'
  } finally {
    isWorking.value = false
  }
}

const discard = async () => {
  if (!academyId.value || !batch.value || isWorking.value) return
  isWorking.value = true
  try {
    await houses.discardBatch(academyId.value, batch.value.id)
    batch.value = null
    rows.value = []
    await refreshEditionState()
  } finally {
    isWorking.value = false
  }
}

const downloadTemplate = async () => {
  if (!academyId.value) return
  try {
    await houses.downloadTemplate(academyId.value)
  } catch {
    errorMessage.value = 'ดาวน์โหลดไฟล์ตัวอย่างไม่สำเร็จ'
  }
}

const openBatch = async (item: HouseAssignmentBatch) => {
  batch.value = item
  rowFilter.value = ''
  await loadRows()
}

const startOver = () => {
  batch.value = null
  rows.value = []
  rowFilter.value = ''
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div v-if="isLoading" class="space-y-6 p-6">
      <div class="h-40 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      <div class="grid grid-cols-3 gap-4">
        <div v-for="i in 3" :key="i" class="h-24 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
      </div>
      <div class="h-64 rounded-vikinger bg-slate-200 dark:bg-slate-700 animate-pulse" />
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 px-6 py-8">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-vikinger bg-white/20 flex items-center justify-center shadow-vikinger">
              <Icon icon="fluent:flag-24-filled" class="w-8 h-8 text-white" />
            </div>
            <div>
              <h1 class="font-heading text-2xl font-bold text-white">แบ่งนักเรียนเข้าคณะสี</h1>
              <p class="text-purple-200 text-sm mt-0.5">สุ่มหรือนำเข้าจากไฟล์ — {{ academy?.name }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-6xl mx-auto px-6 space-y-6 pb-10">
        <SportsEditionPanel
          v-model="selectedEditionId"
          v-model:house-ids="editionHouseIds"
          :academy-id="academyId!"
          :academy-name="academyName"
          :house-groups="houseGroups"
          @changed="refreshEditionState"
        />

        <div
          v-if="!selectedEditionId"
          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-vikinger p-5 flex items-start gap-4"
        >
          <Icon icon="fluent:warning-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" />
          <div class="text-sm">
            <p class="font-semibold text-amber-900 dark:text-amber-200">ยังไม่ได้สร้างงานกีฬาสี — สร้าง 'ครั้งที่จัด' ก่อนจึงจะแบ่งนักเรียนได้</p>
          </div>
        </div>

        <div
          v-else-if="editionHouseIds.length < 2"
          class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-vikinger p-5 flex items-start gap-4"
        >
          <Icon icon="fluent:warning-24-filled" class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" />
          <div class="text-sm">
            <p class="font-semibold text-amber-900 dark:text-amber-200">ครั้งนี้ยังเลือกคณะสีไม่ครบ ต้องมีอย่างน้อย 2 คณะ</p>
            <p v-if="houseGroups.length < 2" class="text-amber-800 dark:text-amber-300 mt-1">
              สร้างที่หน้าโรงเรียน แท็บ "ส่วนงาน" → ปุ่ม "สร้างกลุ่มใหม่" แล้วเลือกประเภท "คณะสี"
            </p>
            <NuxtLink
              v-if="houseGroups.length < 2"
              :to="`/academies/${academyName}`"
              class="inline-flex items-center gap-1.5 mt-2 text-amber-900 dark:text-amber-200 font-semibold hover:underline"
            >
              ไปหน้าโรงเรียน
              <Icon icon="fluent:arrow-right-24-regular" class="w-4 h-4" />
            </NuxtLink>
          </div>
        </div>

        <template v-else>
          <!-- สภาพปัจจุบันของครั้งที่เลือก -->
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5"
          >
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-heading font-bold text-slate-900 dark:text-white">สังกัดคณะสีของครั้งนี้</h2>
              <span class="text-sm text-slate-500 dark:text-slate-400">
                แบ่งแล้ว {{ totalAssignedNow.toLocaleString() }} คน
              </span>
            </div>
            <div v-if="totalAssignedNow === 0" class="text-sm text-slate-500 dark:text-slate-400 py-4 text-center">
              ยังไม่มีการแบ่งคณะสีในครั้งนี้
            </div>
          <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="(count, id) in currentCounts"
              :key="id"
              class="rounded-vikinger border border-slate-200 dark:border-slate-700 p-4"
            >
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: houseColor(id) }" />
                <span class="font-semibold text-slate-900 dark:text-white">{{ houseName(id) }}</span>
              </div>
              <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white mt-2">
                {{ Number(count).toLocaleString() }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400">คน</p>
            </div>
          </div>
        </div>

        <!-- Step chips (โครงจาก HopeUI form-wizard) -->
        <ul class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <li v-for="s in steps" :key="s.n">
            <div
              class="flex items-center gap-3 p-4 rounded-vikinger border transition-all"
              :class="
                step === s.n
                  ? 'bg-gradient-vikinger text-white border-transparent shadow-vikinger'
                  : step > s.n
                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800'
                    : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700'
              "
            >
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                :class="step === s.n ? 'bg-white/20' : step > s.n ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-slate-100 dark:bg-slate-700'"
              >
                <Icon
                  :icon="s.icon"
                  class="w-5 h-5"
                  :class="step === s.n ? 'text-white' : step > s.n ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400'"
                />
              </div>
              <div>
                <p class="text-xs" :class="step === s.n ? 'text-purple-100' : 'text-slate-400 dark:text-slate-500'">
                  ขั้นที่ {{ s.n }}
                </p>
                <p
                  class="font-semibold"
                  :class="step === s.n ? 'text-white' : 'text-slate-900 dark:text-white'"
                >
                  {{ s.label }}
                </p>
              </div>
            </div>
          </li>
        </ul>

        <div
          v-if="errorMessage"
          class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-vikinger p-4 flex items-center gap-3"
        >
          <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
          <p class="text-sm text-rose-800 dark:text-rose-300">{{ errorMessage }}</p>
        </div>

        <!-- ขั้นที่ 1: เลือกวิธี -->
        <div
          v-if="!batch"
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700"
        >
          <div class="flex border-b border-slate-200 dark:border-slate-700">
            <button
              v-for="m in (['random', 'import'] as const)"
              :key="m"
              class="flex-1 px-5 py-4 font-semibold transition-all flex items-center justify-center gap-2"
              :class="
                mode === m
                  ? 'text-purple-700 dark:text-purple-300 border-b-2 border-purple-600'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
              "
              @click="mode = m"
            >
              <Icon :icon="m === 'random' ? 'fluent:dice-24-filled' : 'fluent:document-table-24-filled'" class="w-5 h-5" />
              {{ m === 'random' ? 'สุ่มแบ่ง' : 'นำเข้าจากไฟล์' }}
            </button>
          </div>

          <!-- โหมดสุ่ม -->
          <div v-if="mode === 'random'" class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คณะสีที่จะใช้แบ่ง</label>
              <div class="flex flex-wrap gap-2">
                <label
                  v-for="h in houseGroups.filter(hg => editionHouseIds.includes(Number(hg.id)))"
                  :key="h.id"
                  class="flex items-center gap-2 px-4 py-2 rounded-vikinger border cursor-pointer transition-all"
                  :class="
                    randomForm.house_group_ids.includes(Number(h.id))
                      ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                      : 'border-slate-200 dark:border-slate-700'
                  "
                >
                  <input v-model="randomForm.house_group_ids" type="checkbox" :value="Number(h.id)" class="accent-purple-600" />
                  <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: h.settings?.color || '#8b5cf6' }" />
                  <span class="text-sm font-medium text-slate-900 dark:text-white">{{ h.name }}</span>
                </label>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">วิธีกระจาย</label>
                <select
                  v-model="randomForm.strategy"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                >
                  <option value="stratified">คละทุกห้องเท่า ๆ กัน</option>
                  <option value="pure_random">สุ่มล้วนทั้งโรงเรียน</option>
                </select>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                  แบบคละจะกระจายนักเรียนของแต่ละห้องไปทุกคณะสี ทำให้ทุกสีมีเด็กจากทุกระดับชั้น
                </p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">ขอบเขต</label>
                <select
                  v-model="randomForm.scope"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                >
                  <option value="unassigned_only">เฉพาะคนที่ยังไม่มีสี</option>
                  <option value="all">แบ่งใหม่ทั้งหมด (ทับของเดิม)</option>
                </select>
                <p v-if="randomForm.scope === 'all'" class="text-xs text-amber-600 dark:text-amber-400 mt-1.5">
                  คนที่มีสีอยู่แล้วจะถูกย้าย — ดูจำนวนที่ถูกย้ายได้ในหน้าตรวจผลก่อนกดบันทึก
                </p>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
              <label class="flex items-center gap-3 px-4 py-3 rounded-vikinger border border-slate-200 dark:border-slate-700 cursor-pointer">
                <input v-model="randomForm.balance_gender" type="checkbox" class="accent-purple-600 w-4 h-4" />
                <div>
                  <p class="text-sm font-medium text-slate-900 dark:text-white">สมดุลชาย/หญิงด้วย</p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    นักเรียนที่ไม่มีข้อมูลเพศจะถูกกระจายเป็นกลุ่มของตัวเอง ไม่ถูกเหมารวม
                  </p>
                </div>
              </label>

              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">
                  เลขสุ่ม (seed) — เว้นว่างให้ระบบสุ่มให้
                </label>
                <input
                  v-model.number="randomForm.seed"
                  type="number"
                  placeholder="เช่น 2569"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                  เลขเดิมให้ผลการแบ่งเดิมเสมอ ใช้ยืนยันกับนักเรียนได้ว่าเป็นการจับสลาก ไม่ใช่การเลือกที่รัก
                </p>
              </div>
            </div>

            <button
              :disabled="isWorking || randomForm.house_group_ids.length < 2"
              class="w-full sm:w-auto px-6 py-3 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              @click="runRandom"
            >
              <Icon :icon="isWorking ? 'fluent:spinner-ios-20-filled' : 'fluent:dice-24-filled'" :class="['w-5 h-5', isWorking && 'animate-spin']" />
              {{ isWorking ? 'กำลังสุ่ม…' : 'สุ่มแบ่ง แล้วดูผลก่อนบันทึก' }}
            </button>
          </div>

          <!-- โหมดนำเข้า -->
          <div v-else class="p-6 space-y-5">
            <div class="flex items-center justify-between flex-wrap gap-3">
              <p class="text-sm text-slate-600 dark:text-slate-300">
                ไฟล์ .csv หรือ .xlsx ที่มีคอลัมน์เลขประจำตัวนักเรียนและชื่อคณะสี
              </p>
              <button
                class="text-sm font-semibold text-purple-700 dark:text-purple-300 hover:underline flex items-center gap-1.5"
                @click="downloadTemplate"
              >
                <Icon icon="fluent:arrow-download-24-regular" class="w-4 h-4" />
                ดาวน์โหลดไฟล์ตัวอย่าง
              </button>
            </div>

            <label
              class="block border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-vikinger p-8 text-center cursor-pointer hover:border-purple-400 transition-all"
            >
              <input type="file" accept=".csv,.xlsx" class="hidden" @change="onFilePicked" />
              <Icon icon="fluent:document-arrow-up-24-regular" class="w-10 h-10 mx-auto text-slate-400" />
              <p class="mt-2 font-medium text-slate-900 dark:text-white">
                {{ importForm.file?.name || 'เลือกไฟล์รายชื่อ' }}
              </p>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">คลิกเพื่อเลือกไฟล์</p>
            </label>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คอลัมน์เลขประจำตัว *</label>
                <input
                  v-model="importForm.student_identifier"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คอลัมน์ชื่อคณะสี *</label>
                <input
                  v-model="importForm.house_name"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คอลัมน์ชื่อ (ถ้ามี)</label>
                <input
                  v-model="importForm.first_name_th"
                  placeholder="ใช้เมื่อเลขประจำตัวไม่ตรง"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">คอลัมน์นามสกุล (ถ้ามี)</label>
                <input
                  v-model="importForm.last_name_th"
                  class="w-full px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-semibold text-slate-700 dark:text-slate-200 mb-2">ถ้านักเรียนมีสีอยู่แล้ว</label>
              <select
                v-model="importForm.on_conflict"
                class="w-full md:w-72 px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:outline-none"
              >
                <option value="skip">ข้ามไว้ ไม่แตะของเดิม</option>
                <option value="overwrite">ทับของเดิม (ย้อนกลับได้)</option>
              </select>
            </div>

            <button
              :disabled="isWorking || !importForm.file"
              class="w-full sm:w-auto px-6 py-3 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              @click="runImport"
            >
              <Icon :icon="isWorking ? 'fluent:spinner-ios-20-filled' : 'fluent:document-table-24-filled'" :class="['w-5 h-5', isWorking && 'animate-spin']" />
              {{ isWorking ? 'กำลังอ่านไฟล์…' : 'อ่านไฟล์ แล้วดูผลก่อนบันทึก' }}
            </button>
          </div>
        </div>

        <!-- ขั้นที่ 2/3: ผลการแบ่ง -->
        <div v-else class="space-y-6">
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-6"
          >
            <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
              <div>
                <div class="flex items-center gap-2">
                  <h2 class="font-heading font-bold text-lg text-slate-900 dark:text-white">
                    {{ batch.mode === 'random' ? 'ผลการสุ่ม' : 'ผลการอ่านไฟล์' }}
                  </h2>
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                    :class="
                      batch.status === 'committed'
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                        : batch.status === 'undone'
                          ? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                          : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                    "
                  >
                    {{ batch.status === 'committed' ? 'บันทึกแล้ว' : batch.status === 'undone' ? 'ย้อนกลับแล้ว' : 'ยังไม่บันทึก' }}
                  </span>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                  <template v-if="batch.mode === 'random'">
                    seed {{ batch.options?.seed }} · {{ batch.options?.strategy === 'stratified' ? 'คละทุกห้อง' : 'สุ่มล้วน' }}
                  </template>
                  <template v-else>{{ batch.source_filename }}</template>
                </p>
              </div>

              <div class="flex items-center gap-2">
                <button
                  v-if="batch.status === 'draft'"
                  class="px-4 py-2.5 rounded-vikinger border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                  @click="discard"
                >
                  ทิ้งผลนี้
                </button>
                <button
                  v-if="batch.status === 'draft'"
                  :disabled="isWorking || !(batch.summary?.by_status?.ok ?? batch.summary?.total)"
                  class="px-6 py-2.5 bg-gradient-vikinger text-white font-semibold rounded-vikinger shadow-vikinger hover:shadow-vikinger-lg transition-all disabled:opacity-50 flex items-center gap-2"
                  @click="commit"
                >
                  <Icon icon="fluent:save-24-filled" class="w-5 h-5" />
                  บันทึกการแบ่ง
                </button>
                <button
                  v-if="canUndo"
                  :disabled="isWorking"
                  class="px-4 py-2.5 rounded-vikinger border border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-300 font-medium hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all flex items-center gap-2"
                  @click="undo"
                >
                  <Icon icon="fluent:arrow-undo-24-filled" class="w-5 h-5" />
                  ย้อนกลับ
                </button>
                <button
                  v-if="batch.status !== 'draft'"
                  class="px-4 py-2.5 rounded-vikinger border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                  @click="startOver"
                >
                  แบ่งชุดใหม่
                </button>
              </div>
            </div>

            <p v-if="canUndo" class="text-xs text-amber-600 dark:text-amber-400 mb-4">
              ย้อนกลับได้ภายใน 24 ชั่วโมงหลังบันทึก — คนที่ถูกย้ายจะกลับไปคณะสีเดิม
            </p>

            <!-- ยอดต่อคณะสี -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div
                v-for="(count, id) in batch.summary?.per_house || {}"
                :key="id"
                class="rounded-vikinger border border-slate-200 dark:border-slate-700 p-4"
              >
                <div class="flex items-center gap-2">
                  <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: houseColor(id) }" />
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ houseName(id) }}</span>
                </div>
                <p class="text-2xl font-heading font-bold text-slate-900 dark:text-white mt-2">{{ count }}</p>
                <div v-if="batch.summary?.per_house_by_grade?.[id]" class="mt-2 space-y-0.5">
                  <p
                    v-for="(n, grade) in batch.summary.per_house_by_grade[id]"
                    :key="grade"
                    class="text-xs text-slate-500 dark:text-slate-400 flex justify-between"
                  >
                    <span>{{ grade }}</span><span>{{ n }}</span>
                  </p>
                </div>
              </div>
            </div>

            <!-- สิ่งที่ต้องรู้ก่อนกดบันทึก -->
            <div class="flex flex-wrap gap-3 mt-5 text-sm">
              <span class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                รวม {{ batch.summary?.total ?? 0 }} แถว
              </span>
              <span
                v-if="batch.summary?.skipped_count"
                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300"
              >
                ข้าม {{ batch.summary.skipped_count }} คน (มีสีอยู่แล้ว)
              </span>
              <span
                v-if="batch.summary?.moved_count"
                class="px-3 py-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 font-medium"
              >
                ย้ายสี {{ batch.summary.moved_count }} คน
              </span>
              <span
                v-if="problemCount"
                class="px-3 py-1.5 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-800 dark:text-rose-300 font-medium"
              >
                ต้องแก้ {{ problemCount }} แถว — แถวเหล่านี้จะไม่ถูกบันทึก
              </span>
            </div>
          </div>

          <!-- รายแถว -->
          <div
            class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700"
          >
            <div class="p-5 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-center gap-2">
              <button
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                :class="rowFilter === '' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="rowFilter = ''"
              >
                ทั้งหมด
              </button>
              <button
                v-for="(meta, status) in statusMeta"
                :key="status"
                v-show="byStatus?.[status as HouseRowStatus]"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                :class="rowFilter === status ? meta.tone : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700'"
                @click="rowFilter = status as HouseRowStatus"
              >
                {{ meta.label }} ({{ byStatus?.[status as HouseRowStatus] }})
              </button>
            </div>

            <div v-if="!rows.length" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
              ไม่มีแถวในหมวดนี้
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400">
                  <tr>
                    <th class="text-left font-medium px-5 py-3">#</th>
                    <th class="text-left font-medium px-5 py-3">นักเรียน</th>
                    <th class="text-left font-medium px-5 py-3">คณะสี</th>
                    <th class="text-left font-medium px-5 py-3">สถานะ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                  <tr v-for="row in rows" :key="row.id">
                    <td class="px-5 py-3 text-slate-400">{{ row.row_number }}</td>
                    <td class="px-5 py-3">
                      <template v-if="row.student">
                        <p class="font-medium text-slate-900 dark:text-white">
                          {{ row.student.first_name_th }} {{ row.student.last_name_th }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ row.student.student_id }}</p>
                      </template>
                      <!-- แถวที่จับคู่ไม่ได้ต้องเห็นข้อมูลดิบ ไม่งั้นคนแก้ไฟล์ไม่รู้ว่าแถวไหน -->
                      <p v-else class="text-slate-500 dark:text-slate-400 font-mono text-xs">
                        {{ row.raw ? Object.values(row.raw).filter(Boolean).join(' · ') : '—' }}
                      </p>
                    </td>
                    <td class="px-5 py-3">
                      <div v-if="row.house_group_id" class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: houseColor(row.house_group_id) }" />
                        <span class="text-slate-900 dark:text-white">{{ houseName(row.house_group_id) }}</span>
                        <span
                          v-if="row.previous_house_group_id && row.previous_house_group_id !== row.house_group_id"
                          class="text-xs text-amber-600 dark:text-amber-400"
                        >
                          (ย้ายจาก {{ houseName(row.previous_house_group_id) }})
                        </span>
                      </div>
                      <span v-else class="text-slate-400">—</span>
                    </td>
                    <td class="px-5 py-3">
                      <span class="px-2.5 py-1 rounded-full text-xs font-medium" :class="statusMeta[row.status].tone">
                        {{ statusMeta[row.status].label }}
                      </span>
                      <p v-if="row.message" class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ row.message }}</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ประวัติการแบ่ง -->
        <div
          v-if="batches.length"
          class="bg-white dark:bg-slate-800 rounded-vikinger shadow-card dark:shadow-card-dark border border-slate-200 dark:border-slate-700 p-5"
        >
          <h2 class="font-heading font-bold text-slate-900 dark:text-white mb-4">ประวัติการแบ่ง</h2>
          <div class="space-y-2">
            <button
              v-for="item in batches"
              :key="item.id"
              class="w-full flex items-center justify-between gap-3 p-3 rounded-vikinger border border-slate-200 dark:border-slate-700 hover:border-purple-300 dark:hover:border-purple-700 transition-all text-left"
              @click="openBatch(item)"
            >
              <div class="flex items-center gap-3">
                <Icon
                  :icon="item.mode === 'random' ? 'fluent:dice-24-regular' : 'fluent:document-table-24-regular'"
                  class="w-5 h-5 text-slate-400"
                />
                <div>
                  <p class="text-sm font-medium text-slate-900 dark:text-white">
                    {{ item.mode === 'random' ? 'สุ่มแบ่ง' : 'นำเข้าจากไฟล์' }}
                    <span class="text-slate-400 font-normal">· {{ item.summary?.total ?? 0 }} แถว</span>
                  </p>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ new Date(item.created_at).toLocaleString('th-TH') }}
                  </p>
                </div>
              </div>
              <span
                class="px-2.5 py-0.5 rounded-full text-xs font-semibold flex-shrink-0"
                :class="
                  item.status === 'committed'
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                    : item.status === 'undone'
                      ? 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                      : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                "
              >
                {{ item.status === 'committed' ? 'บันทึกแล้ว' : item.status === 'undone' ? 'ย้อนกลับแล้ว' : 'ยังไม่บันทึก' }}
              </span>
            </button>
          </div>
        </div>
        </template>
      </div>
    </div>
  </div>
</template>
