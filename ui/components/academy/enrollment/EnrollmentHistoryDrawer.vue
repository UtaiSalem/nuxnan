<script setup lang="ts">
import { computed, ref, toRef, watch } from 'vue'
import { Icon } from '@iconify/vue'
import SidebarDrawer from '~/components/Common/SidebarDrawer.vue'
import StudentStatusBadge from '~/components/academy/enrollment/StudentStatusBadge.vue'
import { useStudentEnrollmentActions } from '~/composables/useStudentEnrollmentActions'
import type { ClassroomStudentDTO, MaybeEnrollmentAcademyId, StudentSummaryDTO } from '~/types/enrollment'
import { getEnrollmentStatusStyle } from '~/types/enrollment'

interface Props {
  open: boolean
  academyId: MaybeEnrollmentAcademyId
  student: StudentSummaryDTO | null
}

const props = defineProps<Props>()
const emit = defineEmits<{ 'update:open': [v: boolean] }>()

const { fetchHistory, isLoading } = useStudentEnrollmentActions(toRef(props, 'academyId'))
const rows = ref<ClassroomStudentDTO[]>([])
const error = ref<string | null>(null)

const historySummary = computed(() => {
  const current = rows.value.find((row) => row.status === 'active')

  return {
    total: rows.value.length,
    currentClassroom: current?.classroom?.display_name ?? null,
    currentYear: current?.academic_year?.name ?? null,
  }
})

async function load() {
  if (!props.student) return
  error.value = null

  try {
    rows.value = await fetchHistory(props.student.id)
  } catch (err: any) {
    error.value = err?.data?.message ?? 'โหลดประวัติไม่สำเร็จ'
    rows.value = []
  }
}

const formatDate = (value: string | null | undefined) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(date)
}

const getTimelineTitle = (row: ClassroomStudentDTO) => {
  const classroomName = row.classroom?.display_name ?? 'ห้องที่ลบไปแล้ว'

  switch (row.status) {
    case 'active':
      return `เข้าลงห้อง ${classroomName}`
    case 'transferred':
      return `ย้ายออกจาก ${classroomName}`
    case 'promoted':
      return `เลื่อนชั้นจาก ${classroomName}`
    case 'graduated':
      return `จบการศึกษาจาก ${classroomName}`
    case 'dropped':
      return `ออกจาก ${classroomName}`
    case 'repeating':
      return `ซ้ำชั้นจาก ${classroomName}`
    case 'superseded':
      return `ปิดรายการเดิมของ ${classroomName}`
    default:
      return classroomName
  }
}

const getTimelineSubtitle = (row: ClassroomStudentDTO) => {
  return [
    row.academic_year?.name ? `ปีการศึกษา ${row.academic_year.name}` : null,
    row.student_number ? `เลขที่ ${row.student_number}` : null,
    row.created_by?.name ? `ดำเนินการโดย ${row.created_by.name}` : 'ดำเนินการโดยระบบ',
  ]
    .filter(Boolean)
    .join(' • ')
}

watch(
  () => [props.open, props.student?.id] as const,
  ([open]) => {
    if (open) load()
  },
)
</script>

<template>
  <SidebarDrawer
    :open="open"
    side="right"
    :title="`ประวัติการลงห้อง — ${student?.first_name_th ?? ''} ${student?.last_name_th ?? ''}`"
    @update:open="emit('update:open', $event)"
  >
    <div class="flex h-full flex-col overflow-y-auto p-4">
      <div
        v-if="student"
        class="mb-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-900/60"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
              {{ student.first_name_th }} {{ student.last_name_th }}
            </div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
              รหัส {{ student.student_id }}
            </div>
          </div>
          <div class="rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-600 shadow-sm dark:bg-zinc-800 dark:text-zinc-300">
            {{ historySummary.total }} รายการ
          </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 text-xs text-zinc-600 dark:text-zinc-300 sm:grid-cols-2">
          <div class="rounded-xl bg-white px-3 py-2 dark:bg-zinc-800/80">
            <div class="text-[11px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
              ห้องปัจจุบัน
            </div>
            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
              {{ historySummary.currentClassroom ?? 'ไม่มีรายการกำลังศึกษา' }}
            </div>
          </div>
          <div class="rounded-xl bg-white px-3 py-2 dark:bg-zinc-800/80">
            <div class="text-[11px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
              ปีการศึกษาปัจจุบัน
            </div>
            <div class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
              {{ historySummary.currentYear ?? '-' }}
            </div>
          </div>
        </div>
      </div>

      <div v-if="isLoading" class="text-sm text-zinc-500 dark:text-zinc-400">
        กำลังโหลด...
      </div>

      <div
        v-else-if="error"
        class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300"
      >
        {{ error }}
      </div>

      <div
        v-else-if="!rows.length"
        class="rounded-md bg-zinc-50 px-3 py-4 text-center text-sm text-zinc-500 dark:bg-zinc-800/60 dark:text-zinc-400"
      >
        ยังไม่มีประวัติการลงห้อง
      </div>

      <ol
        v-else
        class="relative ml-3 max-h-[calc(100vh-12rem)] space-y-4 overflow-y-auto border-l-2 border-zinc-200 pr-1 dark:border-zinc-700"
      >
        <li v-for="row in rows" :key="row.id" class="relative ml-4">
          <span
            class="absolute -left-[1.4rem] top-5 h-3 w-3 rounded-full ring-2 ring-white dark:ring-zinc-900"
            :class="getEnrollmentStatusStyle(row.status).dotClass"
          />

          <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/70">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ getTimelineTitle(row) }}
                  </span>
                  <StudentStatusBadge :status="row.status" :status-text="row.status_text" />
                </div>
                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                  {{ getTimelineSubtitle(row) }}
                </div>
              </div>

              <div class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                <Icon icon="mdi:calendar-range" class="h-3.5 w-3.5" />
                {{ row.enrolled_at ? formatDate(row.enrolled_at) : 'ไม่ระบุวันเริ่ม' }}
              </div>
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 text-xs text-zinc-600 dark:text-zinc-300 sm:grid-cols-2">
              <div class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800/80">
                <div class="text-[11px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500">ช่วงเวลา</div>
                <div class="mt-1 font-medium text-zinc-800 dark:text-zinc-100">
                  {{ formatDate(row.enrolled_at) }}
                  <span class="mx-1 text-zinc-400">→</span>
                  {{ row.left_at ? formatDate(row.left_at) : 'ปัจจุบัน' }}
                </div>
              </div>

              <div class="rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800/80">
                <div class="text-[11px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500">ห้อง / ปีการศึกษา</div>
                <div class="mt-1 font-medium text-zinc-800 dark:text-zinc-100">
                  {{ row.classroom?.display_name ?? 'ห้องที่ลบไปแล้ว' }}
                </div>
                <div class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                  {{ row.academic_year?.name ? `ปีการศึกษา ${row.academic_year.name}` : 'ไม่ระบุปีการศึกษา' }}
                </div>
              </div>
            </div>

            <div
              v-if="row.leave_reason"
              class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/30 dark:text-amber-200"
            >
              <span class="font-medium">เหตุผล:</span> {{ row.leave_reason }}
            </div>
          </div>
        </li>
      </ol>
    </div>
  </SidebarDrawer>
</template>
