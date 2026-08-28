<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { ClassroomOptionDTO, RolloverAction, RolloverPreviewEntryDTO } from '~/types/enrollment'

const props = defineProps<{
  entries: RolloverPreviewEntryDTO[]
  targetClassrooms: ClassroomOptionDTO[]
}>()

const emit = defineEmits<{
  'update:entries': [entries: RolloverPreviewEntryDTO[]]
  back: []
  next: []
}>()

const page = ref(1)
const pageSize = 100
const search = ref('')
const filterAction = ref<'all' | RolloverAction>('all')
const filterFromClassroom = ref<'all' | string>('all')
const selectedIds = ref<number[]>([])
const bulkTargetByAction = ref<Record<string, number | null>>({
  promote: null,
  repeat: null,
  new_intake: null,
})

const actionOptions: Array<{ value: RolloverAction, label: string }> = [
  { value: 'promote', label: 'เลื่อนชั้น' },
  { value: 'graduate', label: 'จบการศึกษา' },
  { value: 'repeat', label: 'ซ้ำชั้น' },
  { value: 'drop', label: 'พ้นสภาพ' },
  { value: 'new_intake', label: 'รับเข้าใหม่' },
  { value: 'skip', label: 'ข้ามรายการ' },
]

const bucketLabels: Record<RolloverAction, string> = {
  promote: 'เลื่อนชั้น',
  graduate: 'จบการศึกษา',
  repeat: 'ซ้ำชั้น',
  drop: 'พ้นสภาพ',
  new_intake: 'รับเข้าใหม่',
  skip: 'ข้ามรายการ',
}

const fromClassroomOptions = computed(() => {
  return [...new Set(
    props.entries
      .map((entry) => entry.from_classroom_name)
      .filter((value): value is string => !!value),
  )].sort((a, b) => a.localeCompare(b, 'th'))
})

const filteredEntries = computed(() => {
  const query = search.value.trim().toLowerCase()

  return props.entries.filter((entry) => {
    if (filterAction.value !== 'all' && entry.action !== filterAction.value) return false
    if (filterFromClassroom.value !== 'all' && (entry.from_classroom_name ?? '') !== filterFromClassroom.value) return false
    if (!query) return true

    return [
      entry.student_name,
      entry.from_classroom_name ?? '',
      entry.to_classroom_name ?? '',
    ].some((value) => value.toLowerCase().includes(query))
  })
})

const pagedEntries = computed(() => {
  const start = (page.value - 1) * pageSize
  return filteredEntries.value.slice(start, start + pageSize)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredEntries.value.length / pageSize)))

const countsByAction = computed(() => {
  return props.entries.reduce<Record<string, number>>((acc, entry) => {
    acc[entry.action] = (acc[entry.action] ?? 0) + 1
    return acc
  }, {})
})

const incompleteCount = computed(() => {
  return props.entries.filter((entry) => !isComplete(entry)).length
})

watch([search, filterAction, filterFromClassroom], () => {
  page.value = 1
})

watch(totalPages, (value) => {
  if (page.value > value) {
    page.value = value
  }
})

function isSelected(studentId: number) {
  return selectedIds.value.includes(studentId)
}

function toggleSelected(studentId: number) {
  if (isSelected(studentId)) {
    selectedIds.value = selectedIds.value.filter((id) => id !== studentId)
    return
  }

  selectedIds.value = [...selectedIds.value, studentId]
}

function isTargetRequired(action: RolloverAction) {
  return action === 'promote' || action === 'repeat' || action === 'new_intake'
}

function isComplete(entry: RolloverPreviewEntryDTO) {
  return !isTargetRequired(entry.action) || !!entry.to_classroom_id
}

function updateEntry(studentId: number, patch: Partial<RolloverPreviewEntryDTO>) {
  const nextEntries = props.entries.map((entry) => {
    if (entry.student_id !== studentId) return entry

    const nextAction = patch.action ?? entry.action
    const nextToClassroomId = patch.to_classroom_id === undefined
      ? entry.to_classroom_id
      : patch.to_classroom_id

    const nextTarget = props.targetClassrooms.find((classroom) => classroom.id === nextToClassroomId) ?? null

    return {
      ...entry,
      ...patch,
      action: nextAction,
      to_classroom_id: isTargetRequired(nextAction) ? (nextToClassroomId ?? null) : null,
      to_classroom_name: isTargetRequired(nextAction) ? (nextTarget?.display_name ?? null) : null,
    }
  })

  emit('update:entries', nextEntries)
}

function applyBulkAction(action: RolloverAction) {
  if (selectedIds.value.length === 0) return

  const targetId = bulkTargetByAction.value[action] ?? null
  const target = props.targetClassrooms.find((classroom) => classroom.id === targetId) ?? null

  const nextEntries = props.entries.map((entry) => {
    if (!selectedIds.value.includes(entry.student_id)) return entry

    return {
      ...entry,
      action,
      to_classroom_id: isTargetRequired(action) ? targetId : null,
      to_classroom_name: isTargetRequired(action) ? (target?.display_name ?? null) : null,
    }
  })

  emit('update:entries', nextEntries)
  selectedIds.value = []
}
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">จัดกลุ่มนักเรียน</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
          ใช้ multiselect + button สำหรับจัดนักเรียนจำนวนมาก และแก้รายคนได้ในตารางเดียวกัน
        </p>
      </div>
      <div class="text-sm text-gray-600 dark:text-gray-400">
        จัดครบแล้ว {{ entries.length - incompleteCount }} / {{ entries.length }} รายการ
      </div>
    </div>

    <div class="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">ค้นหา</div>
        <input
          v-model="search"
          type="text"
          placeholder="ชื่อนักเรียน / ห้องเดิม / ห้องปลายทาง"
          class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
        />
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">กรอง action</div>
        <select
          v-model="filterAction"
          class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
        >
          <option value="all">ทั้งหมด</option>
          <option v-for="action in actionOptions" :key="action.value" :value="action.value">
            {{ action.label }}
          </option>
        </select>
      </div>
      <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <div class="text-xs text-gray-500 dark:text-gray-400">กรองห้องต้นทาง</div>
        <select
          v-model="filterFromClassroom"
          class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
        >
          <option value="all">ทุกห้อง</option>
          <option v-for="room in fromClassroomOptions" :key="room" :value="room">{{ room }}</option>
        </select>
      </div>
    </div>

    <div class="mb-5 grid gap-3 lg:grid-cols-3">
      <div
        v-for="action in actionOptions"
        :key="action.value"
        class="rounded-2xl border border-gray-200 p-4 dark:border-gray-700"
      >
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ action.label }}</div>
          <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
            {{ countsByAction[action.value] ?? 0 }}
          </div>
        </div>
        <div v-if="isTargetRequired(action.value)" class="mt-3 space-y-2">
          <select
            v-model="bulkTargetByAction[action.value]"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
          >
            <option :value="null">เลือกห้องปลายทาง</option>
            <option v-for="classroom in targetClassrooms" :key="classroom.id" :value="classroom.id">
              {{ classroom.display_name }}
            </option>
          </select>
          <button
            type="button"
            :disabled="selectedIds.length === 0 || !bulkTargetByAction[action.value]"
            class="min-h-[44px] sm:min-h-0 w-full rounded-xl bg-primary-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
            @click="applyBulkAction(action.value)"
          >
            ย้าย {{ selectedIds.length }} รายการไป {{ action.label }}
          </button>
        </div>
        <button
          v-else
          type="button"
          :disabled="selectedIds.length === 0"
          class="min-h-[44px] sm:min-h-0 mt-3 w-full rounded-xl bg-primary-500 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
          @click="applyBulkAction(action.value)"
        >
          ย้าย {{ selectedIds.length }} รายการไป {{ action.label }}
        </button>
      </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-gray-50 dark:bg-gray-900/40">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"></th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">นักเรียน</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ห้องเดิม</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Action</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">ห้องปลายทาง</th>
              <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">สถานะ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
            <tr v-for="entry in pagedEntries" :key="entry.student_id">
              <td class="px-4 py-3">
                <input
                  :checked="isSelected(entry.student_id)"
                  type="checkbox"
                  class="h-4 w-4 rounded text-primary-500"
                  @change="toggleSelected(entry.student_id)"
                />
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-white">{{ entry.student_name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">ID {{ entry.student_id }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                {{ entry.from_classroom_name ?? 'รับเข้าใหม่' }}
              </td>
              <td class="px-4 py-3">
                <select
                  :value="entry.action"
                  class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                  @change="updateEntry(entry.student_id, { action: ($event.target as HTMLSelectElement).value as RolloverAction, to_classroom_id: null, to_classroom_name: null })"
                >
                  <option v-for="action in actionOptions" :key="action.value" :value="action.value">
                    {{ action.label }}
                  </option>
                </select>
              </td>
              <td class="px-4 py-3">
                <select
                  :value="entry.to_classroom_id ?? ''"
                  :disabled="!isTargetRequired(entry.action)"
                  class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:disabled:bg-gray-900/40"
                  @change="updateEntry(entry.student_id, { to_classroom_id: Number(($event.target as HTMLSelectElement).value) || null })"
                >
                  <option value="">{{ isTargetRequired(entry.action) ? 'เลือกห้องปลายทาง' : 'ไม่ต้องเลือก' }}</option>
                  <option v-for="classroom in targetClassrooms" :key="classroom.id" :value="classroom.id">
                    {{ classroom.display_name }}
                  </option>
                </select>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'inline-flex rounded-full px-3 py-1 text-xs font-medium',
                    isComplete(entry)
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                      : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                  ]"
                >
                  {{ isComplete(entry) ? 'พร้อม' : 'รอเลือกห้อง' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="text-sm text-gray-600 dark:text-gray-400">
        แสดง {{ pagedEntries.length }} / {{ filteredEntries.length }} รายการ
        <span class="ml-2">ค้างจัดการ {{ incompleteCount }} รายการ</span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          :disabled="page <= 1"
          class="min-h-[44px] sm:min-h-0 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
          @click="page -= 1"
        >
          ก่อนหน้า
        </button>
        <span class="text-sm text-gray-600 dark:text-gray-400">หน้า {{ page }} / {{ totalPages }}</span>
        <button
          type="button"
          :disabled="page >= totalPages"
          class="min-h-[44px] sm:min-h-0 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
          @click="page += 1"
        >
          ถัดไป
        </button>
      </div>
    </div>

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-between">
      <button
        type="button"
        class="rounded-xl border border-gray-300 px-5 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="emit('back')"
      >
        ย้อนกลับ
      </button>
      <button
        type="button"
        :disabled="incompleteCount > 0"
        class="rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600 disabled:cursor-not-allowed disabled:bg-gray-400"
        @click="emit('next')"
      >
        ถัดไป: ตรวจแผน
      </button>
    </div>
  </div>
</template>
