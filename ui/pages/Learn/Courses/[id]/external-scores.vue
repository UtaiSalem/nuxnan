<script setup lang="ts">
import { Icon } from '@iconify/vue'
import ExternalScoreTopicsPanel from '~/components/learn/course/scores/ExternalScoreTopicsPanel.vue'
import ExternalScoreImportModal from '~/components/learn/course/scores/ExternalScoreImportModal.vue'
import HorizontalScrollBar from '~/components/Common/HorizontalScrollBar.vue'
import { useExternalScoreImportService } from '~/services/externalScoreImportService'

interface ScoreColumn {
  id: number
  title: string
  description: string | null
  category: string
  max_score: number
  scored_at: string | null
  entries_count: number
}

interface MemberScoreData {
  score: number | null
  note: string | null
  entry_id: number | null
}

interface MemberRow {
  id: number
  user_id: number
  name: string
  avatar: string
  order_number: number | null
  member_code: number | null
  group_id: number | null
  group: { id: number; name: string } | null
  scores: Record<number, MemberScoreData>
}

interface Group {
  id: number
  name: string
  course_members_count?: number
  members_count?: number
}

const course = inject<Ref<any>>('course')
const isCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')

const api = useApi()
const swal = useSweetAlert()

// State
const columns = ref<ScoreColumn[]>([])
const members = ref<MemberRow[]>([])
const groups = ref<Group[]>([])
const selectedGroupId = ref<number | null>(null)
const loading = ref(true)
const saving = ref(false)

// Track which cells have been modified and which are saving
const modifiedCells = ref<Set<string>>(new Set())
const savingCells = ref<Set<string>>(new Set())
const savedCells = ref<Set<string>>(new Set())

// Create form
const showCreateForm = ref(false)
const createForm = ref({
  title: '',
  description: '',
  category: 'other' as string,
  max_score: 100,
  scored_at: new Date().toISOString().split('T')[0],
})
const creatingScore = ref(false)

// Edit form
const showEditForm = ref(false)
const editForm = ref({
  id: 0,
  title: '',
  description: '',
  category: 'other' as string,
  max_score: 100,
  scored_at: '',
})

// Active column (for header click / column detail)
const activeColumnId = ref<number | null>(null)
const showColumnMenu = ref(false)

const categories = [
  { value: 'exam', label: 'สอบ', icon: 'mdi:file-document-edit-outline' },
  { value: 'activity', label: 'กิจกรรม', icon: 'mdi:run-fast' },
  { value: 'project', label: 'โปรเจกต์', icon: 'mdi:folder-star-outline' },
  { value: 'other', label: 'อื่นๆ', icon: 'mdi:dots-horizontal-circle-outline' },
]

const getCategoryLabel = (cat: string) => categories.find(c => c.value === cat)?.label ?? cat
const getCategoryIcon = (cat: string) => categories.find(c => c.value === cat)?.icon ?? 'mdi:dots-horizontal-circle-outline'

// Scroll container
const tableContainer = ref<HTMLElement | null>(null)

const scrollToEnd = async () => {
  await nextTick()
  if (tableContainer.value) {
    tableContainer.value.scrollLeft = tableContainer.value.scrollWidth
    updateTableFades()
  }
}

const showTableLeftFade = ref(false)
const showTableRightFade = ref(false)

const updateTableFades = () => {
  if (!tableContainer.value) return
  const el = tableContainer.value
  showTableLeftFade.value = el.scrollLeft > 8
  showTableRightFade.value = el.scrollLeft < (el.scrollWidth - el.clientWidth - 8)
}

// Fetch table data
const fetchTableData = async (groupId?: number | null) => {
  if (!course?.value?.id) return
  loading.value = true
  try {
    const url = groupId
      ? `/api/courses/${course.value.id}/external-scores/table-view/${groupId}`
      : `/api/courses/${course.value.id}/external-scores/table-view`

    const res: any = await api.get(url)
    if (res.success) {
      columns.value = res.columns || []
      members.value = (res.members || []).map((m: MemberRow) => {
        // Ensure every column has a scores entry
        const cols = res.columns || []
        for (const col of cols) {
          if (!m.scores[col.id]) {
            m.scores[col.id] = { score: null, note: null, entry_id: null }
          }
        }
        return m
      })
      groups.value = res.groups || []
      modifiedCells.value.clear()
      savingCells.value.clear()
      savedCells.value.clear()
      await scrollToEnd()
    }
  } catch (err) {
    console.error('Error fetching table data:', err)
  } finally {
    loading.value = false
  }
}

// Group selection (for filtering display only)
const selectGroup = (groupId: number) => {
  if (selectedGroupId.value === groupId) {
    // Toggle off = show all
    selectedGroupId.value = null
    fetchTableData()
  } else {
    selectedGroupId.value = groupId
  }
}

watch(selectedGroupId, (newId) => {
  fetchTableData(newId)
})

// Mark cell as modified
const onCellChange = (memberId: number, columnId: number) => {
  const key = `${memberId}-${columnId}`
  modifiedCells.value.add(key)
  savedCells.value.delete(key)
}

const hasModifiedCells = computed(() => modifiedCells.value.size > 0)

// Save individual cell score
const handleSaveCell = async (memberId: number, columnId: number) => {
  if (!course?.value?.id) return
  const key = `${memberId}-${columnId}`
  const member = members.value.find(m => m.id === memberId)
  if (!member) return

  savingCells.value.add(key)
  try {
    const res: any = await api.post(
      `/api/courses/${course.value.id}/external-scores/${columnId}/entries`,
      {
        entries: [{
          course_member_id: memberId,
          score: member.scores[columnId]?.score,
          note: member.scores[columnId]?.note,
        }]
      }
    )
    if (res.success) {
      modifiedCells.value.delete(key)
      savedCells.value.add(key)
      // Auto-clear saved indicator after 2s
      setTimeout(() => savedCells.value.delete(key), 2000)
    }
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ไม่สามารถบันทึกคะแนนได้')
  } finally {
    savingCells.value.delete(key)
  }
}

// Save all modified entries (grouped by column)
const handleSaveAll = async () => {
  if (!course?.value?.id || !hasModifiedCells.value) return
  saving.value = true

  try {
    // Group modified cells by column
    const columnIds = new Set<number>()
    modifiedCells.value.forEach(key => {
      const [, colId] = key.split('-')
      columnIds.add(Number(colId))
    })

    // Save each column's entries
    for (const colId of columnIds) {
      const entries = members.value.map(m => ({
        course_member_id: m.id,
        score: m.scores[colId]?.score,
        note: m.scores[colId]?.note,
      }))

      await api.post(
        `/api/courses/${course.value.id}/external-scores/${colId}/entries`,
        { entries }
      )
    }

    swal.success('สำเร็จ', 'บันทึกคะแนนเรียบร้อยแล้ว')
    modifiedCells.value.clear()
    // Refresh data
    await fetchTableData(selectedGroupId.value)
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ไม่สามารถบันทึกคะแนนได้')
  } finally {
    saving.value = false
  }
}

// Save single column
const handleSaveColumn = async (columnId: number) => {
  if (!course?.value?.id) return
  saving.value = true
  try {
    const entries = members.value.map(m => ({
      course_member_id: m.id,
      score: m.scores[columnId]?.score,
      note: m.scores[columnId]?.note,
    }))

    const res: any = await api.post(
      `/api/courses/${course.value.id}/external-scores/${columnId}/entries`,
      { entries }
    )
    if (res.success) {
      swal.success('สำเร็จ', 'บันทึกคะแนนเรียบร้อยแล้ว')
      // Clear modified for this column
      members.value.forEach(m => {
        modifiedCells.value.delete(`${m.id}-${columnId}`)
      })
    }
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ไม่สามารถบันทึกคะแนนได้')
  } finally {
    saving.value = false
  }
}

// Create new external score
const handleCreate = async () => {
  if (!createForm.value.title.trim()) return
  creatingScore.value = true
  try {
    const res: any = await api.post(`/api/courses/${course!.value.id}/external-scores`, createForm.value)
    if (res.success) {
      swal.success('สำเร็จ', 'สร้างหัวข้อคะแนนเรียบร้อยแล้ว')
      showCreateForm.value = false
      resetCreateForm()
      await fetchTableData(selectedGroupId.value)
    }
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ไม่สามารถสร้างหัวข้อคะแนนได้')
  } finally {
    creatingScore.value = false
  }
}

const resetCreateForm = () => {
  createForm.value = {
    title: '',
    description: '',
    category: 'other',
    max_score: 100,
    scored_at: new Date().toISOString().split('T')[0],
  }
}

// Edit external score
const openEdit = (col: ScoreColumn) => {
  editForm.value = {
    id: col.id,
    title: col.title,
    description: col.description || '',
    category: col.category,
    max_score: col.max_score,
    scored_at: col.scored_at || '',
  }
  showEditForm.value = true
  showColumnMenu.value = false
}

const handleEdit = async () => {
  creatingScore.value = true
  try {
    const res: any = await api.patch(
      `/api/courses/${course!.value.id}/external-scores/${editForm.value.id}`,
      editForm.value
    )
    if (res.success) {
      swal.success('สำเร็จ', 'อัปเดตหัวข้อคะแนนเรียบร้อยแล้ว')
      showEditForm.value = false
      await fetchTableData(selectedGroupId.value)
    }
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ไม่สามารถอัปเดตได้')
  } finally {
    creatingScore.value = false
  }
}

// Delete external score
const handleDelete = async (col: ScoreColumn) => {
  showColumnMenu.value = false
  const result = await swal.confirm(
    'ยืนยันการลบ?',
    `ลบหัวข้อ "${col.title}" พร้อมคะแนนทั้งหมด?`,
    'warning'
  )
  if (!result) return

  try {
    const res: any = await api.delete(`/api/courses/${course!.value.id}/external-scores/${col.id}`)
    if (res.success) {
      swal.success('สำเร็จ', 'ลบหัวข้อคะแนนเรียบร้อยแล้ว')
      await fetchTableData(selectedGroupId.value)
    }
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ไม่สามารถลบได้')
  }
}

// Quick actions for a column
const setFullScoreColumn = (columnId: number, maxScore: number) => {
  members.value.forEach(m => {
    if (!m.scores[columnId]) {
      m.scores[columnId] = { score: null, note: null, entry_id: null }
    }
    m.scores[columnId].score = maxScore
    onCellChange(m.id, columnId)
  })
  showColumnMenu.value = false
}

const clearColumnScores = (columnId: number) => {
  members.value.forEach(m => {
    if (m.scores[columnId]) {
      m.scores[columnId].score = null
      onCellChange(m.id, columnId)
    }
  })
  showColumnMenu.value = false
}

// Column header click
const openColumnMenu = (col: ScoreColumn, event: MouseEvent) => {
  activeColumnId.value = col.id
  showColumnMenu.value = true
}

const closeColumnMenu = () => {
  showColumnMenu.value = false
  activeColumnId.value = null
}

const activeColumn = computed(() => {
  return columns.value.find(c => c.id === activeColumnId.value) || null
})

const importService = useExternalScoreImportService()

const showImportModal = ref(false)
const importTopicId = ref<number | null>(null)
const downloadingColumnId = ref<number | null>(null)

const selectedGroup = computed(() => groups.value.find(g => g.id === selectedGroupId.value) ?? null)
const groupLabel = computed(() => selectedGroup.value?.name ?? 'ทุกกลุ่มเรียน')

const importTopics = computed(() => columns.value.map(c => ({
  id: c.id, title: c.title, max_score: c.max_score,
})))

const handleDownloadTemplate = async (col: ScoreColumn) => {
  if (!course?.value?.id) return
  downloadingColumnId.value = col.id
  try {
    await importService.downloadTemplate(course.value.id, col.id, selectedGroupId.value)
  } catch (err: any) {
    swal.error('ผิดพลาด', err?.message || 'ดาวน์โหลดแบบฟอร์มไม่สำเร็จ')
  } finally {
    downloadingColumnId.value = null
  }
}

const openImportModal = (col: ScoreColumn | null) => {
  importTopicId.value = col?.id ?? null
  showImportModal.value = true
  showColumnMenu.value = false
}

const onImported = async () => {
  showImportModal.value = false
  await fetchTableData(selectedGroupId.value)
}

// Column stats
const getColumnStats = (columnId: number) => {
  const scored = members.value.filter(m => m.scores[columnId]?.score !== null && m.scores[columnId]?.score !== undefined)
  const total = members.value.length
  const avg = scored.length > 0
    ? scored.reduce((sum, m) => sum + (m.scores[columnId]?.score || 0), 0) / scored.length
    : 0
  return { scored: scored.length, total, avg: avg.toFixed(1) }
}

// Image error handler
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  const name = img.alt || 'User'
  img.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&color=7F9CF5&background=EBF4FF`
}

// Close column menu when clicking outside
const onClickOutside = (event: MouseEvent) => {
  if (showColumnMenu.value) {
    const target = event.target as HTMLElement
    if (!target.closest('.column-menu-popup')) {
      closeColumnMenu()
    }
  }
}

onMounted(() => {
  fetchTableData()
  document.addEventListener('click', onClickOutside)
  nextTick(() => updateTableFades())
  tableContainer.value?.addEventListener('scroll', updateTableFades, { passive: true })
})

onUnmounted(() => {
  document.removeEventListener('click', onClickOutside)
})
</script>

<template>
  <div class="space-y-4 sm:space-y-6 -mx-4 px-2 sm:mx-0 sm:px-4 lg:px-0 pb-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
      <div>
        <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <Icon icon="mdi:clipboard-text-outline" class="w-6 h-6 text-indigo-500" />
          บันทึกคะแนนจากภายนอก
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          บันทึกคะแนนจากการสอบหรือกิจกรรมนอกระบบ
        </p>
      </div>
      <!-- Save button (when modified) -->
      <div class="flex items-center gap-2">
        <button
          v-if="hasModifiedCells"
          @click="handleSaveAll"
          :disabled="saving"
          class="flex items-center gap-2 px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors text-sm font-medium shadow-sm animate-pulse"
        >
          <Icon v-if="saving" icon="mdi:loading" class="w-5 h-5 animate-spin" />
          <Icon v-else icon="mdi:content-save-outline" class="w-5 h-5" />
          <span>บันทึกคะแนนทั้งหมด</span>
        </button>
      </div>
    </div>

    <ExternalScoreTopicsPanel
      :columns="columns"
      :is-course-admin="!!isCourseAdmin"
      :member-total="members.length"
      :group-label="groupLabel"
      @create="showCreateForm = true"
      @edit="openEdit"
      @download="handleDownloadTemplate"
      @upload="openImportModal"
    />

    <!-- Group Filter (optional - for display filtering only) -->
    <div v-if="isCourseAdmin && groups.length > 1" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        <Icon icon="fluent:filter-24-regular" class="w-5 h-5 inline-block mr-2" />
        กรองตามกลุ่มเรียน
        <span class="text-xs text-gray-400 ml-1">(กดอีกครั้งเพื่อแสดงทั้งหมด)</span>
      </label>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <button
          v-for="group in groups"
          :key="group.id"
          @click="selectGroup(group.id)"
          :class="[
            'relative p-3 rounded-xl border-2 transition-all duration-300 text-left',
            selectedGroupId === group.id
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md'
              : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-sm'
          ]"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="flex-1 min-w-0">
              <h4 class="font-semibold text-gray-900 dark:text-white truncate text-sm">
                {{ group.name || `กลุ่ม ${group.id}` }}
              </h4>
              <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                <Icon icon="fluent:people-20-regular" class="w-3.5 h-3.5" />
                <span>{{ group.members_count || group.course_members_count || 0 }} คน</span>
              </p>
            </div>
            <Icon
              v-if="selectedGroupId === group.id"
              icon="fluent:checkmark-circle-24-filled"
              class="w-5 h-5 text-blue-500 flex-shrink-0"
            />
          </div>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
      <div class="animate-pulse space-y-4">
        <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded w-1/4"></div>
        <div class="space-y-3">
          <div v-for="i in 5" :key="i" class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="flex-1 h-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="w-20 h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="w-20 h-8 bg-gray-200 dark:bg-gray-700 rounded"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty: no members at all -->
    <div v-else-if="members.length === 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8 sm:p-12 text-center">
      <Icon icon="fluent:people-team-24-regular" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มีสมาชิกในรายวิชา</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">เพิ่มสมาชิกในรายวิชาเพื่อเริ่มบันทึกคะแนน</p>
    </div>

    <!-- Matrix Table -->
    <div v-else-if="members.length > 0" class="bg-white dark:bg-gray-800 rounded-xl sm:rounded-2xl shadow-lg sm:shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div v-if="isCourseAdmin" class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="min-w-0 flex-1 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
          <Icon icon="fluent:people-team-20-regular" class="w-5 h-5 flex-shrink-0 text-blue-500" />
          <span class="min-w-0 break-words">
            กำลังบันทึกคะแนนของ <span class="font-semibold">{{ groupLabel }}</span> ({{ members.length }} คน)
          </span>
        </div>
        <button
          @click="openImportModal(null)"
          :disabled="columns.length === 0"
          class="w-full sm:w-auto flex-shrink-0 min-h-[44px] inline-flex items-center justify-center gap-2 px-4 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 text-sm font-medium whitespace-nowrap"
        >
          <Icon icon="mdi:file-upload-outline" class="w-5 h-5" />
          อัปโหลดคะแนนจากไฟล์
        </button>
      </div>
      <HorizontalScrollBar
        :target="tableContainer"
        hint="ลากแถบนี้ หรือปัดตารางซ้าย–ขวา เพื่อดูคอลัมน์คะแนน"
      />
      <div class="relative">
  <div v-show="showTableLeftFade" class="pointer-events-none absolute left-0 top-0 bottom-0 w-6 z-30 bg-gradient-to-r from-white dark:from-gray-800 to-transparent"></div>
  <div v-show="showTableRightFade" class="pointer-events-none absolute right-0 top-0 bottom-0 w-6 z-30 bg-gradient-to-l from-white dark:from-gray-800 to-transparent"></div>
  <div ref="tableContainer" 
       class="relative overflow-x-auto scroll-smooth always-scrollbar-x"
       style="-webkit-overflow-scrolling: touch;"
       @scroll.passive="updateTableFades">
        <table class="w-full">
          <!-- Table Header -->
          <thead class="bg-gradient-to-r from-gray-50 via-gray-100 to-slate-100 dark:from-gray-700 dark:via-gray-800 dark:to-gray-700 border-b-2 border-gray-200 dark:border-gray-600">
            <tr class="text-center">
              <!-- Member column - STICKY LEFT -->
              <th scope="col" class="px-6 py-4 border border-slate-300 dark:border-gray-600 font-black text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 min-w-[280px] sm:sticky sm:left-0 sm:z-20">
                <div class="flex items-center justify-center gap-3">
                  <div class="w-10 h-10 bg-gradient-to-br from-violet-100 to-purple-100 dark:from-violet-900 dark:to-purple-900 rounded-xl flex items-center justify-center shadow-sm">
                    <Icon icon="fluent:people-24-filled" class="w-5 h-5 text-violet-600 dark:text-violet-400" />
                  </div>
                  <span class="uppercase tracking-wide text-sm">สมาชิก</span>
                </div>
              </th>

              <!-- Score columns -->
              <th
                v-for="(col, index) in columns"
                :key="col.id"
                scope="col"
                class="px-3 py-4 border border-slate-300 dark:border-gray-600 min-w-[120px] max-w-[160px]"
              >
                <button
                  @click.stop="openColumnMenu(col, $event)"
                  class="flex flex-col justify-center items-center mx-auto text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl px-3 py-2 transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md w-full"
                >
                  <span class="text-xs text-gray-500 dark:text-gray-400">#{{ index + 1 }}</span>
                  <span class="mt-1 truncate max-w-full text-xs">{{ col.title }}</span>
                  <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">เต็ม {{ col.max_score }}</span>
                  <Icon icon="mdi:chevron-down" class="w-4 h-4 mt-0.5 text-gray-400" />
                </button>
              </th>

            </tr>
          </thead>

          <!-- Table Body -->
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr
              v-for="member in members"
              :key="member.id"
              class="odd:bg-white even:bg-slate-100 dark:odd:bg-gray-800 dark:even:bg-gray-900/60 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors duration-200"
            >
              <!-- Member info - STICKY LEFT -->
              <td class="p-2 border border-slate-300 dark:border-gray-600 whitespace-nowrap sm:sticky sm:left-0 sm:z-10 bg-inherit">
                <div class="flex items-center min-w-fit group">
                  <div class="relative">
                    <img
                      class="w-12 h-12 rounded-full border-2 border-white dark:border-gray-700 shadow-md transition-all duration-300 group-hover:scale-105 group-hover:shadow-lg object-cover"
                      :src="member.avatar"
                      :alt="member.name"
                      @error="handleImageError"
                    >
                  </div>
                  <div class="ml-3 flex-1">
                    <p class="text-gray-900 dark:text-gray-100 font-bold text-sm tracking-wide group-hover:text-blue-700 dark:group-hover:text-blue-400 transition-colors duration-200">
                      {{ member.name }}
                    </p>
                    <div class="flex items-center mt-1 space-x-2 text-xs">
                      <span v-if="member.order_number" class="inline-flex items-center gap-1 px-2 py-0.5 bg-gradient-to-r from-violet-100 to-purple-100 dark:from-violet-900/50 dark:to-purple-900/50 text-violet-700 dark:text-violet-300 rounded-full font-medium border border-violet-200 dark:border-violet-700">
                        <Icon icon="fluent:number-symbol-square-20-regular" width="12" height="12" />
                        <span>{{ member.order_number }}</span>
                      </span>
                      <span v-if="member.group" class="inline-flex items-center gap-1 px-2 py-0.5 bg-gradient-to-r from-blue-100 to-indigo-100 dark:from-blue-900/50 dark:to-indigo-900/50 text-blue-700 dark:text-blue-300 rounded-full font-medium border border-blue-200 dark:border-blue-700">
                        <Icon icon="fluent:people-team-20-regular" width="12" height="12" />
                        <span>{{ member.group.name }}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Score cells -->
              <td
                v-for="col in columns"
                :key="col.id"
                class="p-1.5 whitespace-nowrap border border-slate-300 dark:border-gray-600 text-center"
              >
                <div class="flex items-center justify-center gap-1">
                  <input
                    v-model.number="member.scores[col.id].score"
                    type="number"
                    :min="0"
                    :max="col.max_score"
                    step="0.5"
                    :placeholder="`-`"
                    :disabled="savingCells.has(`${member.id}-${col.id}`)"
                    @input="onCellChange(member.id, col.id)"
                    class="w-[70px] px-2 py-1.5 border rounded-lg text-sm text-center text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all disabled:opacity-50"
                    :class="{
                      'ring-2 ring-amber-400 border-amber-400 bg-amber-50 dark:bg-amber-900/20': modifiedCells.has(`${member.id}-${col.id}`),
                      'ring-2 ring-green-400 border-green-400 bg-green-50 dark:bg-green-900/20': savedCells.has(`${member.id}-${col.id}`),
                      'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700': !modifiedCells.has(`${member.id}-${col.id}`) && !savedCells.has(`${member.id}-${col.id}`)
                    }"
                  />
                  <!-- Save button per cell -->
                  <button
                    v-if="modifiedCells.has(`${member.id}-${col.id}`)"
                    @click="handleSaveCell(member.id, col.id)"
                    :disabled="savingCells.has(`${member.id}-${col.id}`)"
                    class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 text-white disabled:opacity-70 transition-all shadow-sm"
                    title="บันทึกคะแนน"
                  >
                    <Icon v-if="savingCells.has(`${member.id}-${col.id}`)" icon="mdi:loading" class="w-4 h-4 animate-spin" />
                    <Icon v-else icon="mdi:check" class="w-4 h-4" />
                  </button>
                  <!-- Saved indicator -->
                  <span
                    v-else-if="savedCells.has(`${member.id}-${col.id}`)"
                    class="flex-shrink-0 w-7 h-7 flex items-center justify-center text-green-500"
                  >
                    <Icon icon="mdi:check-circle" class="w-5 h-5" />
                  </span>
                </div>
              </td>

            </tr>

            <!-- Empty members -->
            <tr v-if="members.length === 0">
              <td :colspan="columns.length + 1" class="p-12 text-center">
                <div class="flex flex-col items-center justify-center">
                  <Icon icon="fluent:people-24-regular" class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" />
                  <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่มีสมาชิกในกลุ่มนี้</p>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Table Footer: Stats -->
          <tfoot v-if="columns.length > 0 && members.length > 0" class="bg-gray-50 dark:bg-gray-700/50 border-t-2 border-gray-200 dark:border-gray-600">
            <tr>
              <td class="px-4 py-3 border border-slate-300 dark:border-gray-600 sm:sticky sm:left-0 sm:z-10 bg-gray-50 dark:bg-gray-700/50">
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">สถิติ</span>
              </td>
              <td
                v-for="col in columns"
                :key="`stat-${col.id}`"
                class="px-2 py-3 border border-slate-300 dark:border-gray-600 text-center"
              >
                <div class="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
                  <div>{{ getColumnStats(col.id).scored }}/{{ getColumnStats(col.id).total }}</div>
                  <div class="font-semibold text-gray-700 dark:text-gray-200">x̄ {{ getColumnStats(col.id).avg }}</div>
                </div>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
      </div>

      <!-- Bottom save bar -->
      <div v-if="hasModifiedCells" class="px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border-t border-amber-200 dark:border-amber-700 flex items-center justify-between">
        <span class="text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2">
          <Icon icon="mdi:alert-circle-outline" class="w-5 h-5" />
          มีคะแนนที่ยังไม่ได้บันทึก {{ modifiedCells.size }} รายการ
        </span>
        <button
          @click="handleSaveAll"
          :disabled="saving"
          class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors text-sm font-medium"
        >
          <Icon v-if="saving" icon="mdi:loading" class="w-4 h-4 animate-spin" />
          <Icon v-else icon="mdi:content-save-outline" class="w-4 h-4" />
          บันทึกทั้งหมด
        </button>
      </div>
    </div>

    <!-- ============ COLUMN MENU POPUP ============ -->
    <Teleport to="body">
      <div
        v-if="showColumnMenu && activeColumn"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="closeColumnMenu"
      >
        <div class="column-menu-popup bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm overflow-hidden" @click.stop>
          <!-- Header -->
          <div class="p-4 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/30 dark:to-purple-900/30 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Icon :icon="getCategoryIcon(activeColumn.category)" class="w-5 h-5 text-indigo-500" />
                <h3 class="font-bold text-gray-900 dark:text-white">{{ activeColumn.title }}</h3>
              </div>
              <button @click="closeColumnMenu" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <Icon icon="mdi:close" class="w-5 h-5" />
              </button>
            </div>
            <div class="flex flex-wrap gap-2 mt-2 text-xs text-gray-500 dark:text-gray-400">
              <span class="flex items-center gap-1">
                <Icon icon="mdi:star-outline" class="w-3.5 h-3.5" />
                เต็ม: {{ activeColumn.max_score }}
              </span>
              <span class="flex items-center gap-1">
                <Icon icon="mdi:account-group-outline" class="w-3.5 h-3.5" />
                {{ getColumnStats(activeColumn.id).scored }}/{{ getColumnStats(activeColumn.id).total }} คน
              </span>
              <span class="flex items-center gap-1">
                <Icon icon="mdi:chart-line" class="w-3.5 h-3.5" />
                เฉลี่ย: {{ getColumnStats(activeColumn.id).avg }}
              </span>
            </div>
            <p v-if="activeColumn.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ activeColumn.description }}</p>
          </div>

          <!-- Actions -->
          <div class="p-2 space-y-1">
            <button @click="setFullScoreColumn(activeColumn.id, activeColumn.max_score)"
              class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors">
              <Icon icon="mdi:check-all" class="w-5 h-5 text-green-500" />
              ให้คะแนนเต็มทั้งหมด
            </button>
            <button @click="clearColumnScores(activeColumn.id)"
              class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
              <Icon icon="mdi:eraser" class="w-5 h-5 text-gray-400" />
              ล้างคะแนนทั้งหมด
            </button>
            <button @click="handleSaveColumn(activeColumn.id)"
              class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
              <Icon icon="mdi:content-save-outline" class="w-5 h-5 text-blue-500" />
              บันทึกคะแนนหัวข้อนี้
            </button>
            <button @click="handleDownloadTemplate(activeColumn); closeColumnMenu()"
              class="w-full flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors">
              <Icon icon="mdi:file-download-outline" class="w-5 h-5 flex-shrink-0 text-amber-500" />
              <span class="min-w-0 flex-1 text-left break-words">ดาวน์โหลดรายชื่อ ({{ groupLabel }})</span>
            </button>
            <button @click="openImportModal(activeColumn)"
              class="w-full flex items-center gap-3 px-3 py-2.5 min-h-[44px] text-sm text-gray-700 dark:text-gray-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors">
              <Icon icon="mdi:file-upload-outline" class="w-5 h-5 flex-shrink-0 text-emerald-500" />
              <span class="min-w-0 flex-1 text-left break-words">อัปโหลดคะแนนจากไฟล์</span>
            </button>
            <hr class="my-1 border-gray-200 dark:border-gray-700" />
            <button @click="openEdit(activeColumn)"
              class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
              <Icon icon="mdi:pencil-outline" class="w-5 h-5 text-indigo-500" />
              แก้ไขหัวข้อ
            </button>
            <button @click="handleDelete(activeColumn)"
              class="w-full flex items-center gap-3 px-3 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
              <Icon icon="mdi:trash-can-outline" class="w-5 h-5" />
              ลบหัวข้อ
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ============ CREATE MODAL ============ -->
    <Teleport to="body">
      <div v-if="showCreateForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showCreateForm = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">สร้างหัวข้อคะแนนใหม่</h3>
              <button @click="showCreateForm = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <Icon icon="mdi:close" class="w-5 h-5" />
              </button>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหัวข้อ <span class="text-red-500">*</span></label>
                <input v-model="createForm.title" type="text" placeholder="เช่น สอบกลางภาค, กิจกรรมทัศนศึกษา"
                  class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                <textarea v-model="createForm.description" rows="2" placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)"
                  class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                  <select v-model="createForm.category"
                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนนเต็ม <span class="text-red-500">*</span></label>
                  <input v-model.number="createForm.max_score" type="number" min="0.01" step="0.01"
                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่</label>
                  <input v-model="createForm.scored_at" type="date"
                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                </div>
              </div>
            </div>

            <div class="flex items-center gap-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm text-blue-700 dark:text-blue-300">
              <Icon icon="mdi:information-outline" class="w-5 h-5 flex-shrink-0" />
              <span>คะแนนจะถูกบันทึกให้นักเรียนทุกคนในรายวิชา</span>
            </div>

            <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
              <Icon icon="mdi:alert-circle-outline" class="w-5 h-5 flex-shrink-0 text-amber-500 mt-0.5" />
              <p class="min-w-0 flex-1 text-xs sm:text-sm text-amber-700 dark:text-amber-300 break-words">
                เมื่อสร้างหัวข้อนี้ คะแนนเต็มของรายวิชาจะเพิ่มขึ้น {{ createForm.max_score || 0 }} คะแนนทันที
                และเปอร์เซ็นต์/เกรดของนักเรียนทุกคนจะถูกคำนวณใหม่ จนกว่าจะบันทึกคะแนนครบ
              </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="showCreateForm = false" class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium">
                ยกเลิก
              </button>
              <button @click="handleCreate" :disabled="creatingScore || !createForm.title.trim()"
                class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium flex items-center gap-2">
                <Icon v-if="creatingScore" icon="mdi:loading" class="w-4 h-4 animate-spin" />
                สร้างหัวข้อ
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ============ EDIT MODAL ============ -->
    <Teleport to="body">
      <div v-if="showEditForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showEditForm = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-900 dark:text-white">แก้ไขหัวข้อคะแนน</h3>
              <button @click="showEditForm = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <Icon icon="mdi:close" class="w-5 h-5" />
              </button>
            </div>

            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อหัวข้อ <span class="text-red-500">*</span></label>
                <input v-model="editForm.title" type="text"
                  class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                <textarea v-model="editForm.description" rows="2"
                  class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภท</label>
                  <select v-model="editForm.category"
                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คะแนนเต็ม <span class="text-red-500">*</span></label>
                  <input v-model.number="editForm.max_score" type="number" min="0.01" step="0.01"
                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่</label>
                  <input v-model="editForm.scored_at" type="date"
                    class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="showEditForm = false" class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm font-medium">
                ยกเลิก
              </button>
              <button @click="handleEdit" :disabled="creatingScore || !editForm.title.trim()"
                class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-sm font-medium flex items-center gap-2">
                <Icon v-if="creatingScore" icon="mdi:loading" class="w-4 h-4 animate-spin" />
                บันทึก
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <ExternalScoreImportModal
      v-if="isCourseAdmin"
      :show="showImportModal"
      :course-id="course?.id"
      :topics="importTopics"
      :initial-topic-id="importTopicId"
      :group-id="selectedGroupId"
      :group-name="selectedGroup?.name ?? null"
      @close="showImportModal = false"
      @imported="onImported"
    />
  </div>
</template>
