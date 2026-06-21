<script setup lang="ts">
import { ref, toRef, watch } from 'vue'
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
        class="relative ml-3 max-h-[calc(100vh-8rem)] space-y-4 overflow-y-auto border-l-2 border-zinc-200 dark:border-zinc-700"
      >
        <li v-for="row in rows" :key="row.id" class="relative ml-4">
          <span
            class="absolute -left-[1.4rem] top-1.5 h-3 w-3 rounded-full ring-2 ring-white dark:ring-zinc-900"
            :class="getEnrollmentStatusStyle(row.status).dotClass"
          />

          <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
              {{ row.classroom?.display_name ?? 'ห้องที่ลบไปแล้ว' }}
            </span>
            <StudentStatusBadge :status="row.status" :status-text="row.status_text" />
          </div>

          <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
            <span>{{ row.enrolled_at ?? '?' }}</span>
            <span class="mx-1">→</span>
            <span>{{ row.left_at ?? 'ปัจจุบัน' }}</span>
          </div>

          <div
            v-if="row.leave_reason"
            class="mt-1 text-xs italic text-zinc-600 dark:text-zinc-300"
          >
            {{ row.leave_reason }}
          </div>
        </li>
      </ol>
    </div>
  </SidebarDrawer>
</template>
