<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onMounted, ref } from 'vue'
import RolloverBatchHistoryCard from '~/components/academy/rollover/RolloverBatchHistoryCard.vue'
import type { RolloverBatchDTO } from '~/types/enrollment'

definePageMeta({
  layout: 'main',
})

const route = useRoute()
const api = useApi()
const toast = useToast()
const swal = useSweetAlert()
const academyName = computed(() => route.params.name as string)

const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const isLoading = ref(true)
const isWorking = ref(false)

const batches = ref<RolloverBatchDTO[]>([])
const page = ref(1)
const meta = ref<Record<string, any>>({})
const filter = ref<'all' | 'committed' | 'undone'>('all')

const selectedBatch = ref<RolloverBatchDTO | null>(null)
const selectedAudit = ref<any[]>([])
const detailOpen = ref(false)
const detailLoading = ref(false)

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)
const { listBatches, fetchBatch, undo, closeUndo } = useRolloverActions(academyId)

const filteredBatches = computed(() => {
  if (filter.value === 'all') return batches.value
  return batches.value.filter((b) => b.status === filter.value)
})

const counts = computed(() => ({
  all: batches.value.length,
  committed: batches.value.filter((b) => b.status === 'committed').length,
  undone: batches.value.filter((b) => b.status === 'undone').length,
}))

onMounted(async () => {
  try {
    const res: any = await api.get(`/api/academies/${academyName.value}`)
    if (!res.success) throw new Error('Failed to load academy')
    academy.value = res.academy
    academyId.value = res.academy.id

    await fetchMyRole()
    if (!isAdmin.value) {
      navigateTo(`/academies/${academyName.value}`)
      return
    }

    await loadBatches()
  } catch (err: any) {
    toast.error(err?.message ?? 'โหลดข้อมูลไม่สำเร็จ')
  } finally {
    isLoading.value = false
  }
})

async function loadBatches() {
  try {
    const res = await listBatches(page.value, 20)
    batches.value = res.data ?? []
    meta.value = res.meta ?? {}
  } catch (err: any) {
    toast.error(err?.data?.message ?? 'โหลดประวัติไม่สำเร็จ')
    batches.value = []
  }
}

async function changePage(next: number) {
  if (next < 1) return
  page.value = next
  await loadBatches()
}

async function openDetail(batch: RolloverBatchDTO) {
  selectedBatch.value = batch
  selectedAudit.value = []
  detailOpen.value = true
  detailLoading.value = true
  try {
    const res: any = await fetchBatch(batch.id)
    selectedBatch.value = res.batch
    selectedAudit.value = res.audit_logs ?? []
  } catch (err: any) {
    toast.error(err?.data?.message ?? 'โหลดรายละเอียดไม่สำเร็จ')
  } finally {
    detailLoading.value = false
  }
}

async function onUndo(batch: RolloverBatchDTO) {
  const ok = await swal.confirm({
    title: 'ยกเลิก rollover นี้?',
    text: `ระบบจะคืนสถานะนักเรียนทั้งหมดที่เกิดจาก batch นี้กลับเป็นปีเดิม`,
    icon: 'warning',
    confirmButtonText: 'ยกเลิก rollover',
    cancelButtonText: 'ไม่ ขอบคุณ',
  })
  if (!ok) return

  isWorking.value = true
  try {
    await undo(batch.id)
    toast.success('ยกเลิก rollover เรียบร้อย')
    await loadBatches()
    if (selectedBatch.value?.id === batch.id) await openDetail(batch)
  } catch (err: any) {
    toast.error(err?.data?.message ?? 'ยกเลิกไม่สำเร็จ')
  } finally {
    isWorking.value = false
  }
}

async function onCloseUndo(batch: RolloverBatchDTO) {
  const ok = await swal.confirm({
    title: 'ปิด undo ทันที?',
    text: 'หลังจากนี้จะไม่สามารถยกเลิก rollover นี้ได้อีก',
    icon: 'warning',
    confirmButtonText: 'ปิด undo',
    cancelButtonText: 'ไม่ ขอบคุณ',
  })
  if (!ok) return

  isWorking.value = true
  try {
    await closeUndo(batch.id)
    toast.success('ปิด undo เรียบร้อย')
    await loadBatches()
  } catch (err: any) {
    toast.error(err?.data?.message ?? 'ปิด undo ไม่สำเร็จ')
  } finally {
    isWorking.value = false
  }
}

function formatAuditAction(action: string): string {
  return {
    created: 'สร้าง',
    updated: 'แก้ไข',
    deleted: 'ลบ',
  }[action] ?? action
}

function formatDateTime(value: string | null | undefined): string {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('th-TH', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(date)
}
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <NuxtLink
          :to="`/academies/${academyName}/admin/gradebook/rollover`"
          class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200"
        >
          <Icon icon="fluent:arrow-left-24-filled" class="h-5 w-5" />
        </NuxtLink>
        <div>
          <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
            ประวัติการเลื่อนชั้น
          </h1>
          <p class="text-sm text-zinc-500 dark:text-zinc-400">
            รายการ rollover ทั้งหมดของโรงเรียน {{ academy?.name ?? '' }}
          </p>
        </div>
      </div>

      <NuxtLink
        :to="`/academies/${academyName}/admin/gradebook/rollover`"
        class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
      >
        <Icon icon="mdi:plus" class="h-4 w-4" />
        เลื่อนชั้นใหม่
      </NuxtLink>
    </header>

    <div v-if="isLoading" class="flex justify-center py-20">
      <div class="h-10 w-10 animate-spin rounded-full border-4 border-indigo-500 border-t-transparent" />
    </div>

    <template v-else>
      <nav class="-mb-px flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
        <button
          v-for="opt in [
            { key: 'all',       label: `ทั้งหมด (${counts.all})` },
            { key: 'committed', label: `บันทึกแล้ว (${counts.committed})` },
            { key: 'undone',    label: `ถูกยกเลิก (${counts.undone})` },
          ] as const"
          :key="opt.key"
          type="button"
          :class="[
            'px-4 py-2 text-sm font-medium transition border-b-2',
            filter === opt.key
              ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
              : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200',
          ]"
          @click="filter = opt.key"
        >
          {{ opt.label }}
        </button>
      </nav>

      <div v-if="filteredBatches.length === 0" class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 py-16 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
        <Icon icon="mdi:history" class="mx-auto mb-3 h-12 w-12 text-zinc-300 dark:text-zinc-600" />
        <p>ยังไม่มีประวัติการเลื่อนชั้น</p>
      </div>

      <div v-else class="space-y-4">
        <RolloverBatchHistoryCard
          v-for="batch in filteredBatches"
          :key="batch.id"
          :batch="batch"
          :busy="isWorking"
          @view="openDetail"
          @undo="onUndo"
          @close-undo="onCloseUndo"
        />
      </div>

      <div v-if="meta.last_page && meta.last_page > 1" class="flex items-center justify-center gap-2 pt-4">
        <button
          :disabled="page <= 1"
          class="rounded-md border border-zinc-300 px-3 py-1 text-sm disabled:opacity-50 dark:border-zinc-700"
          @click="changePage(page - 1)"
        >
          ← ก่อนหน้า
        </button>
        <span class="text-sm text-zinc-600 dark:text-zinc-400">
          หน้า {{ page }} / {{ meta.last_page }}
        </span>
        <button
          :disabled="page >= meta.last_page"
          class="rounded-md border border-zinc-300 px-3 py-1 text-sm disabled:opacity-50 dark:border-zinc-700"
          @click="changePage(page + 1)"
        >
          ถัดไป →
        </button>
      </div>
    </template>

    <ClientOnly>
      <Teleport to="body">
        <div v-if="detailOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div class="absolute inset-0 bg-black/50" @click="detailOpen = false" />
          <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-3">
              <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                รายละเอียด rollover
              </h2>
              <button
                class="rounded p-1 text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800"
                @click="detailOpen = false"
              >
                <Icon icon="mdi:close" class="h-5 w-5" />
              </button>
            </div>

            <div v-if="detailLoading" class="py-10 text-center text-sm text-zinc-500">
              กำลังโหลด...
            </div>

            <div v-else-if="selectedBatch" class="mt-4 space-y-4">
              <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">batch_id</div>
                <code class="text-xs">{{ selectedBatch.id }}</code>
                <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                  <div>
                    <div class="text-xs text-zinc-500">ปีต้นทาง → ปลายทาง</div>
                    <div class="font-medium">{{ selectedBatch.from_year?.name }} → {{ selectedBatch.to_year?.name }}</div>
                  </div>
                  <div>
                    <div class="text-xs text-zinc-500">บันทึกเมื่อ</div>
                    <div class="font-medium">{{ formatDateTime(selectedBatch.committed_at) }}</div>
                  </div>
                  <div v-if="selectedBatch.committed_by?.name">
                    <div class="text-xs text-zinc-500">โดย</div>
                    <div class="font-medium">{{ selectedBatch.committed_by.name }}</div>
                  </div>
                  <div v-if="selectedBatch.undone_at">
                    <div class="text-xs text-zinc-500">ยกเลิกเมื่อ</div>
                    <div class="font-medium">{{ formatDateTime(selectedBatch.undone_at) }}</div>
                  </div>
                </div>
                <p v-if="selectedBatch.notes" class="mt-2 text-sm italic text-zinc-600 dark:text-zinc-300">
                  {{ selectedBatch.notes }}
                </p>
              </div>

              <div v-if="selectedAudit.length" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                  ประวัติการแก้ไข ({{ selectedAudit.length }})
                </h3>
                <ol class="space-y-2 text-xs text-zinc-600 dark:text-zinc-300">
                  <li v-for="(log, idx) in selectedAudit" :key="idx" class="border-l-2 border-zinc-200 pl-3 dark:border-zinc-700">
                    <div class="font-medium">
                      {{ formatAuditAction(log.action) }}
                      <span v-if="log.user_name" class="text-zinc-500">โดย {{ log.user_name }}</span>
                    </div>
                    <div class="text-[11px] text-zinc-400">{{ formatDateTime(log.created_at) }}</div>
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </ClientOnly>
  </div>
</template>
