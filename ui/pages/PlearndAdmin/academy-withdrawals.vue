<script setup lang="ts">
import { useAcademyPointWithdrawals, type AcademyPointWithdrawal } from '~/composables/useAcademyPointWithdrawals'
import type { PointFundSource } from '~/composables/useCoursePointWithdrawals'
definePageMeta({ layout: 'main', middleware: ['auth', 'plearnd-admin'] })

const { adminList, adminShow, adminReview, adminApprove, adminReject, adminMarkPaid, adminSourceOfFunds } = useAcademyPointWithdrawals()
const rows = ref<AcademyPointWithdrawal[]>([])
const selected = ref<AcademyPointWithdrawal | null>(null)
const status = ref('')
const search = ref('')
const loading = ref(false)
const note = ref('')
const reason = ref('')
const reference = ref('')
const message = ref('')

const fundSource = ref<PointFundSource | null>(null)
const fundSourceLoading = ref(false)
const showFundDetails = ref(false)

const load = async () => {
  loading.value = true
  try {
    const r = await adminList({ status: status.value || undefined, search: search.value || undefined, per_page: 20 })
    rows.value = r.data
  } finally {
    loading.value = false
  }
}
onMounted(load)

const loadFundSource = async (id: number) => {
  fundSourceLoading.value = true
  fundSource.value = null
  showFundDetails.value = false
  try {
    const r = await adminSourceOfFunds(id)
    if (r.success) fundSource.value = r.data
  } catch {
    fundSource.value = null
  } finally {
    fundSourceLoading.value = false
  }
}

const open = async (r: AcademyPointWithdrawal) => {
  const x = await adminShow(r.id)
  selected.value = x.data
  loadFundSource(r.id)
}
const refresh = async () => {
  await load()
  if (selected.value) await open(selected.value)
}
const act = async (fn: () => Promise<any>) => {
  try {
    await fn()
    message.value = 'ดำเนินการสำเร็จ'
    await refresh()
  } catch (e: any) {
    message.value = e.data?.message || 'ดำเนินการไม่สำเร็จ'
  }
}
const paid = () => {
  if (!selected.value) return
  return act(() => adminMarkPaid(selected.value!.id, reference.value || undefined))
}

const label = (s: string) => ({ pending: 'รอดำเนินการ', reviewing: 'กำลังตรวจสอบ', approved: 'อนุมัติแล้ว', paid: 'โอนแล้ว', rejected: 'ปฏิเสธ', cancelled: 'ยกเลิก' }[s] || s)
const fmtPoints = (n: number) => (n || 0).toLocaleString() + ' แต้ม'
const RISK_META: Record<string, { cls: string; text: string }> = {
  high: { cls: 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200', text: 'ความเสี่ยงสูง — ควรตรวจสอบก่อนอนุมัติ' },
  medium: { cls: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200', text: 'ควรตรวจสอบ' },
  low: { cls: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-300', text: 'ไม่พบสัญญาณผิดปกติ' },
}
const riskMeta = computed(() => RISK_META[fundSource.value?.risk?.level || 'low'] || RISK_META.low)
</script>

<template>
  <div class="mx-auto max-w-7xl space-y-6 px-0 py-6 sm:px-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">ถอนแต้มสถาบัน (Academy)</h1>
      <NuxtLink to="/academies" class="text-primary-600">หน้าหลักสถาบัน</NuxtLink>
    </div>
    <div v-if="message" class="rounded-xl bg-slate-100 p-4 dark:bg-slate-800">{{ message }}</div>
    <div class="flex flex-col gap-3 sm:flex-row">
      <select v-model="status" class="min-h-[44px] w-full min-w-0 rounded-lg border p-3 sm:flex-1" @change="load">
        <option value="">ทุกสถานะ</option>
        <option v-for="s in ['pending','reviewing','approved','paid','rejected','cancelled']" :key="s" :value="s">{{ label(s) }}</option>
      </select>
      <input v-model="search" class="min-h-[44px] w-full min-w-0 rounded-lg border p-3 sm:flex-1" placeholder="ค้นหาวัตถุประสงค์" @keyup.enter="load" />
      <button class="min-h-[44px] w-full shrink-0 rounded-lg bg-primary-600 px-4 text-white sm:w-auto" @click="load">ค้นหา</button>
    </div>
    <div class="overflow-hidden rounded-2xl bg-white shadow dark:bg-slate-800">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 dark:bg-slate-700">
            <tr><th class="p-4">ID</th><th class="p-4">สถาบัน</th><th class="p-4">ผู้ขอ</th><th class="p-4">จำนวน</th><th class="p-4">สถานะ</th><th class="p-4">วันที่ขอ</th></tr>
          </thead>
          <tbody>
            <tr v-for="r in rows" :key="r.id" class="cursor-pointer border-t hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40" @click="open(r)">
              <td class="p-4">#{{ r.id }}</td>
              <td class="p-4">{{ r.academy?.name || r.academy_id }}</td>
              <td class="p-4">{{ r.requester?.name }}</td>
              <td class="p-4">{{ r.amount.toLocaleString() }}</td>
              <td class="p-4">{{ label(r.status) }}</td>
              <td class="p-4">{{ new Date(r.requested_at).toLocaleString() }}</td>
            </tr>
            <tr v-if="!rows.length"><td colspan="6" class="p-8 text-center">ไม่พบรายการ</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <aside v-if="selected" class="fixed inset-y-0 right-0 z-20 w-full max-w-md overflow-y-auto bg-white p-4 shadow-2xl dark:bg-slate-800 sm:p-6">
      <button class="float-right text-2xl" @click="selected = null">×</button>
      <h2 class="mb-5 text-xl font-bold">ถอนแต้ม #{{ selected.id }}</h2>
      <dl class="space-y-2 text-sm">
        <div><dt class="text-slate-500">สถาบัน / ผู้ขอ</dt><dd>{{ selected.academy?.name || selected.academy_id }} / {{ selected.requester.name }}</dd></div>
        <div><dt class="text-slate-500">จำนวน / สถานะ</dt><dd>{{ selected.amount.toLocaleString() }} / {{ label(selected.status) }}</dd></div>
        <div><dt class="text-slate-500">วัตถุประสงค์</dt><dd>{{ selected.purpose || '—' }}</dd></div>
        <div v-for="person in [{label:'ผู้ตรวจสอบ',value:selected.reviewer?.name,time:selected.reviewed_at},{label:'ผู้อนุมัติ',value:selected.approver?.name,time:selected.approved_at},{label:'ผู้โอน',value:selected.payer?.name,time:selected.paid_at}]" :key="person.label">
          <dt class="text-slate-500">{{ person.label }}</dt>
          <dd>{{ person.value || '—' }} <span v-if="person.time">({{ new Date(person.time).toLocaleString() }})</span></dd>
        </div>
      </dl>

      <section class="mt-6">
        <h3 class="mb-2 text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">แหล่งที่มาของแต้ม</h3>
        <div v-if="fundSourceLoading" class="flex items-center gap-2 rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900/20">กำลังตรวจสอบแหล่งที่มาของแต้ม…</div>
        <div v-else-if="fundSource" class="space-y-3 rounded-2xl border-2 p-3 text-sm" :class="riskMeta.cls">
          <div><p class="font-bold break-words">{{ riskMeta.text }}</p><p class="mt-0.5 text-xs opacity-80">ตรวจย้อนหลัง {{ fundSource.window_days }} วันก่อนการขอถอน</p></div>
          <ul v-if="fundSource.risk.reasons.length" class="space-y-1 text-xs"><li v-for="(r, i) in fundSource.risk.reasons" :key="i" class="break-words">• {{ r }}</li></ul>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-xl bg-white/70 p-2 dark:bg-slate-900/40"><p class="opacity-70">รับแต้มจากผู้ใช้</p><p class="mt-0.5 font-bold break-words">{{ fmtPoints(fundSource.summary.user_funded_points) }}</p><p class="opacity-70">{{ fundSource.summary.user_funded_count }} รายการ</p></div>
            <div class="rounded-xl bg-white/70 p-2 dark:bg-slate-900/40"><p class="opacity-70">ผู้เติมรายใหญ่สุด / ยอดถอน</p><p class="mt-0.5 font-bold">{{ fundSource.summary.top_funder_coverage_percent }}%</p></div>
          </div>
          <div v-if="fundSource.summary.funders.length" class="space-y-1.5">
            <p class="text-[11px] font-semibold uppercase tracking-wider opacity-80">ผู้เติมแต้มเข้าบัญชี</p>
            <div v-for="f in fundSource.summary.funders" :key="f.user.id" class="flex items-center gap-2 rounded-xl bg-white/70 px-2 py-1.5 dark:bg-slate-900/40">
              <img v-if="f.user.avatar" :src="f.user.avatar" class="h-6 w-6 flex-shrink-0 rounded-full object-cover" alt="" />
              <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-semibold">{{ f.user.name || ('ผู้ใช้ #' + f.user.id) }}<span v-if="f.is_requester" class="ml-1 rounded bg-red-200 px-1 text-[10px] text-red-800 dark:bg-red-900 dark:text-red-200">ผู้ขอถอน</span></p>
                <p v-if="f.user.username" class="truncate text-[11px] opacity-70">@{{ f.user.username }}</p>
              </div>
              <div class="flex-shrink-0 whitespace-nowrap text-right"><p class="text-xs font-bold">{{ fmtPoints(f.points) }}</p><p class="text-[11px] opacity-70">{{ f.count }} ครั้ง</p></div>
            </div>
          </div>
          <button v-if="fundSource.inflows.length" class="min-h-[44px] w-full rounded-xl bg-white/70 px-3 text-xs font-medium dark:bg-slate-900/40 sm:min-h-0 sm:py-1.5" @click="showFundDetails = !showFundDetails">{{ showFundDetails ? 'ซ่อนรายการแต้มเข้า' : `ดูรายการแต้มเข้าทั้งหมด (${fundSource.inflows.length})` }}</button>
          <div v-if="showFundDetails" class="space-y-1.5">
            <div v-for="(flow, i) in fundSource.inflows" :key="i" class="flex items-center gap-2 rounded-xl bg-white/70 px-2 py-1.5 dark:bg-slate-900/40">
              <span class="flex-shrink-0 whitespace-nowrap rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">{{ flow.type_label }}</span>
              <div class="min-w-0 flex-1"><p class="truncate text-xs"><span v-if="flow.counterparty">จาก {{ flow.counterparty.name }}</span><span v-else class="opacity-70">{{ flow.description || 'ระบบ' }}</span></p><p class="text-[11px] opacity-70">{{ flow.created_at ? new Date(flow.created_at).toLocaleString() : '—' }}</p></div>
              <p class="flex-shrink-0 whitespace-nowrap text-xs font-bold">{{ fmtPoints(flow.amount) }}</p>
            </div>
          </div>
        </div>
      </section>

      <div class="mt-6 space-y-3">
        <button v-if="selected.status === 'pending'" class="w-full rounded-lg bg-primary-600 p-3 text-white" @click="act(() => adminReview(selected!.id))">รับเรื่อง</button>
        <template v-if="selected.status === 'reviewing'">
          <textarea v-model="note" class="w-full rounded-lg border p-3" placeholder="หมายเหตุอนุมัติ" />
          <button class="w-full rounded-lg bg-emerald-600 p-3 text-white" @click="act(() => adminApprove(selected!.id, note))">อนุมัติ</button>
          <textarea v-model="reason" class="w-full rounded-lg border p-3" placeholder="เหตุผลปฏิเสธ (จำเป็น)" />
          <button :disabled="!reason.trim()" class="w-full rounded-lg bg-red-600 p-3 text-white disabled:opacity-50" @click="act(() => adminReject(selected!.id, reason))">ปฏิเสธ</button>
        </template>
        <template v-if="selected.status === 'approved'">
          <input v-model="reference" class="w-full rounded-lg border p-3" placeholder="เลขอ้างอิงการโอน (ถ้ามี)" />
          <button class="w-full rounded-lg bg-emerald-600 p-3 text-white" @click="paid">บันทึกโอนแล้ว</button>
        </template>
      </div>
    </aside>
  </div>
</template>
