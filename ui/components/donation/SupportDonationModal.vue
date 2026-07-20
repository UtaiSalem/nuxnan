<script setup lang="ts">
import Dialog from 'primevue/dialog'
import { useAcademyDonations } from '~/composables/useAcademyDonations'
import { useCourseDonations } from '~/composables/useCourseDonations'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{
  visible: boolean
  scope: 'academy' | 'course'
  targetId: number
  targetName: string
  balance?: number
}>()

const emit = defineEmits<{
  'update:visible': [boolean]
  donated: [unknown]
}>()

const academyApi = useAcademyDonations()
const courseApi = useCourseDonations()
const auth = useAuthStore()

const step = ref(1)
const type = ref<'point' | 'cash'>('point')
const points = ref(100)
const cash = ref<number | null>(null)
const purpose = ref('')
const anonymous = ref(false)
const displayName = ref('')
const paymentMethod = ref('bank_transfer')
const reference = ref('')
const slip = ref<File | null>(null)
const slipPreviewUrl = ref<string | null>(null)
const isDragging = ref(false)

const loading = ref(false)
const error = ref('')
const result = ref<any>(null)
const fieldErrors = ref<Record<string, string[]>>({})

const userBalance = computed(() => props.balance ?? auth.user?.pp ?? 0)

const pointPresets = [50, 100, 300, 500, 1000, 2500]
const cashPresets = [50, 100, 300, 500, 1000, 2000]
const messagePresets = [
  'ขอเป็นกำลังใจให้โรงเรียนและคณะครูครับ 💖',
  'สนับสนุนการเรียนรู้และพัฒนาหลักสูตรใหม่ 🚀',
  'ร่วมสร้างโอกาสทางการศึกษาให้กับผู้เรียนทุกคน 🌟',
]

const stepsList = [
  { id: 1, title: 'เลือกวิธี', icon: '⚡' },
  { id: 2, title: 'ระบุจำนวน', icon: '💰' },
  { id: 3, title: 'ข้อความ', icon: '✍️' },
  { id: 4, title: 'ยืนยัน', icon: '🛡️' },
]

function reset() {
  step.value = 1
  type.value = 'point'
  points.value = 100
  cash.value = null
  purpose.value = ''
  anonymous.value = false
  displayName.value = auth.user?.name || ''
  paymentMethod.value = 'bank_transfer'
  reference.value = ''
  slip.value = null
  if (slipPreviewUrl.value) {
    URL.revokeObjectURL(slipPreviewUrl.value)
    slipPreviewUrl.value = null
  }
  loading.value = false
  error.value = ''
  result.value = null
  fieldErrors.value = {}
}

watch(() => props.visible, value => {
  if (value) reset()
})

onUnmounted(() => {
  if (slipPreviewUrl.value) {
    URL.revokeObjectURL(slipPreviewUrl.value)
  }
})

function setSlipFile(file: File) {
  if (file.size > 5 * 1024 * 1024) {
    error.value = 'ไฟล์สลิปต้องมีขนาดไม่เกิน 5MB'
    return
  }
  slip.value = file
  error.value = ''
  if (file.type.startsWith('image/')) {
    if (slipPreviewUrl.value) URL.revokeObjectURL(slipPreviewUrl.value)
    slipPreviewUrl.value = URL.createObjectURL(file)
  } else {
    slipPreviewUrl.value = null
  }
}

function chooseFile(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (file) setSlipFile(file)
}

function handleDrop(event: DragEvent) {
  isDragging.value = false
  const file = event.dataTransfer?.files?.[0]
  if (file) setSlipFile(file)
}

function removeSlip() {
  slip.value = null
  if (slipPreviewUrl.value) {
    URL.revokeObjectURL(slipPreviewUrl.value)
    slipPreviewUrl.value = null
  }
}

function validateStep2(): boolean {
  error.value = ''
  if (type.value === 'point') {
    if (!points.value || points.value < 1) {
      error.value = 'กรุณาระบุจำนวนแต้มที่ถูกต้อง'
      return false
    }
    if (points.value > userBalance.value) {
      error.value = `แต้มสะสมของคุณไม่พอ (คงเหลือ ${userBalance.value.toLocaleString()} แต้ม)`
      return false
    }
  } else {
    if (!cash.value || cash.value < 1) {
      error.value = 'กรุณาระบุจำนวนเงินอย่างน้อย 1 บาท'
      return false
    }
    if (!slip.value) {
      error.value = 'กรุณาแนบไฟล์สลิปการโอนเงินเพื่อดำเนินการต่อ'
      return false
    }
  }
  return true
}

function goNextStep() {
  if (step.value === 2 && !validateStep2()) return
  if (step.value < 4) {
    step.value++
  }
}

function goPrevStep() {
  if (step.value > 1 && step.value < 5) {
    error.value = ''
    step.value--
  }
}

async function submit() {
  loading.value = true
  error.value = ''
  fieldErrors.value = {}
  try {
    let response: any
    if (type.value === 'point') {
      const payload = {
        points_amount: points.value,
        purpose: purpose.value || undefined,
        anonymous: anonymous.value,
        donor_display_name: displayName.value || undefined,
      }
      response = props.scope === 'academy'
        ? await academyApi.sendPointDonation(props.targetId, payload)
        : await courseApi.sendPointDonation(props.targetId, payload)
    } else {
      if (!cash.value || cash.value < 1) throw new Error('กรุณาระบุจำนวนเงิน')
      if (!slip.value) throw new Error('กรุณาแนบสลิปการโอนเงิน')
      const form = new FormData()
      form.append('cash_amount', String(cash.value))
      form.append('slip', slip.value)
      form.append('payment_method', paymentMethod.value)
      if (reference.value) form.append('payment_reference', reference.value)
      if (purpose.value) form.append('purpose', purpose.value)
      form.append('anonymous', String(anonymous.value))
      if (displayName.value) form.append('donor_display_name', displayName.value)
      response = props.scope === 'academy'
        ? await academyApi.sendCashDonation(props.targetId, form)
        : await courseApi.sendCashDonation(props.targetId, form)
    }
    result.value = response?.data || response
    step.value = 5
    emit('donated', result.value)
  } catch (e: any) {
    if (e?.status === 422) fieldErrors.value = e.data?.errors || {}
    else error.value = e?.data?.message || e?.message || 'ไม่สามารถส่งคำขอสนับสนุนได้'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    appendTo="body"
    maskClass="support-donation-dialog-mask"
    :autoZIndex="true"
    :baseZIndex="2000"
    :draggable="false"
    :dismissableMask="true"
    :showHeader="false"
    :style="{ width: 'min(94vw, 34rem)' }"
    class="support-donation-dialog border-0 shadow-2xl overflow-hidden rounded-3xl"
    @update:visible="emit('update:visible', $event)"
  >
    <div class="relative overflow-hidden bg-slate-900 text-slate-100 rounded-3xl selection:bg-indigo-500 selection:text-white">
      <!-- Decorative background glow -->
      <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-gradient-to-br from-indigo-500/20 via-purple-500/20 to-pink-500/0 blur-3xl" />
      <div class="pointer-events-none absolute -bottom-16 -left-16 h-56 w-56 rounded-full bg-gradient-to-tr from-amber-500/15 via-emerald-500/15 to-transparent blur-3xl" />

      <!-- Top Header Bar -->
      <div class="relative border-b border-slate-800/80 px-6 py-4 bg-slate-900/90 backdrop-blur-md">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button
              v-if="step > 1 && step < 5"
              type="button"
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800/80 text-slate-300 hover:bg-slate-700 hover:text-white transition"
              title="ย้อนกลับ"
              @click="goPrevStep"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <div class="flex items-center gap-2">
              <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 to-indigo-600 shadow-md shadow-indigo-500/20 text-lg">
                {{ scope === 'academy' ? '🏫' : '📚' }}
              </span>
              <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-400">
                  สนับสนุน{{ scope === 'academy' ? 'โรงเรียน' : 'คอร์สเรียน' }}
                </span>
                <h2 class="text-base font-bold text-white line-clamp-1 max-w-[200px] sm:max-w-[260px]">
                  {{ targetName }}
                </h2>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800/60 text-slate-400 hover:bg-slate-700 hover:text-white transition"
            @click="emit('update:visible', false)"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Step Indicator Bar -->
        <div v-if="step < 5" class="mt-4 flex items-center justify-between gap-1 sm:gap-2 px-1">
          <template v-for="(s, idx) in stepsList" :key="s.id">
            <div
              class="flex flex-1 items-center gap-1.5 rounded-xl px-2 py-1.5 transition-all duration-300"
              :class="[
                step === s.id
                  ? 'bg-gradient-to-r from-indigo-600/30 to-purple-600/30 border border-indigo-500/50 text-white'
                  : step > s.id
                    ? 'text-emerald-400 font-medium'
                    : 'text-slate-500'
              ]"
            >
              <div
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold transition"
                :class="[
                  step === s.id
                    ? 'bg-indigo-500 text-white shadow-sm shadow-indigo-500/50'
                    : step > s.id
                      ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40'
                      : 'bg-slate-800 text-slate-400'
                ]"
              >
                <span v-if="step > s.id">✓</span>
                <span v-else>{{ s.id }}</span>
              </div>
              <span class="text-xs font-semibold hidden sm:inline truncate">{{ s.title }}</span>
            </div>
            <div v-if="idx < stepsList.length - 1" class="h-0.5 w-2 bg-slate-800 shrink-0" />
          </template>
        </div>
      </div>

      <!-- Main Step Contents -->
      <div class="p-6 space-y-6">
        <!-- STEP 1: Choose Type -->
        <div v-if="step === 1" class="space-y-5 animate-fadeIn">
          <div class="text-center sm:text-left space-y-1">
            <h3 class="text-xl font-black text-white tracking-tight">เลือกรูปแบบการสนับสนุน</h3>
            <p class="text-sm text-slate-400">เลือกวิธีส่งมอบกำลังใจและสนับสนุนการเรียนรู้ที่คุณสะดวก</p>
          </div>

          <div class="grid gap-3 sm:grid-cols-2">
            <!-- Points option -->
            <button
              type="button"
              class="group relative flex flex-col justify-between rounded-2xl border p-4 text-left transition-all duration-200"
              :class="[
                type === 'point'
                  ? 'border-amber-500/80 bg-gradient-to-br from-amber-500/15 via-indigo-900/30 to-slate-900 shadow-lg shadow-amber-500/10 ring-1 ring-amber-500/50'
                  : 'border-slate-800 bg-slate-800/40 hover:border-slate-700 hover:bg-slate-800/80'
              ]"
              @click="type = 'point'"
            >
              <div class="flex items-start justify-between w-full">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-amber-500 to-yellow-400 text-2xl shadow-md shadow-amber-500/20 group-hover:scale-105 transition-transform">
                  💎
                </div>
                <span
                  class="h-5 w-5 rounded-full border flex items-center justify-center transition"
                  :class="type === 'point' ? 'border-amber-400 bg-amber-400 text-slate-950 font-bold text-xs' : 'border-slate-600'"
                >
                  <span v-if="type === 'point'">✓</span>
                </span>
              </div>
              <div class="mt-4 space-y-1">
                <div class="flex items-center gap-1.5">
                  <h4 class="font-bold text-white text-base">สนับสนุนด้วยแต้ม</h4>
                  <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-bold text-amber-300 border border-amber-500/30">ยอดนิยม</span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">ใช้แต้มสะสมในระบบ ส่งกำลังใจได้ทันทีโดยไม่ต้องโอนเงิน</p>
                <div class="mt-3 inline-flex items-center gap-1 text-[11px] font-semibold text-amber-400 bg-amber-500/10 rounded-lg px-2 py-1 border border-amber-500/20">
                  <span>คงเหลือ:</span>
                  <span class="font-bold text-white">{{ userBalance.toLocaleString() }}</span>
                  <span>แต้ม</span>
                </div>
              </div>
            </button>

            <!-- Cash option -->
            <button
              type="button"
              class="group relative flex flex-col justify-between rounded-2xl border p-4 text-left transition-all duration-200"
              :class="[
                type === 'cash'
                  ? 'border-indigo-500/80 bg-gradient-to-br from-indigo-500/15 via-purple-900/30 to-slate-900 shadow-lg shadow-indigo-500/10 ring-1 ring-indigo-500/50'
                  : 'border-slate-800 bg-slate-800/40 hover:border-slate-700 hover:bg-slate-800/80'
              ]"
              @click="type = 'cash'"
            >
              <div class="flex items-start justify-between w-full">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-2xl shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                  💵
                </div>
                <span
                  class="h-5 w-5 rounded-full border flex items-center justify-center transition"
                  :class="type === 'cash' ? 'border-indigo-400 bg-indigo-400 text-slate-950 font-bold text-xs' : 'border-slate-600'"
                >
                  <span v-if="type === 'cash'">✓</span>
                </span>
              </div>
              <div class="mt-4 space-y-1">
                <h4 class="font-bold text-white text-base">สนับสนุนด้วยเงินโอน</h4>
                <p class="text-xs text-slate-400 leading-relaxed">โอนผ่านธนาคารหรือพร้อมเพย์ พร้อมแนบสลิปเพื่อยืนยันรายการ</p>
                <div class="mt-3 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400 bg-emerald-500/10 rounded-lg px-2 py-1 border border-emerald-500/20">
                  <span>พร้อมเพย์ / สแกน QR</span>
                </div>
              </div>
            </button>
          </div>

          <button
            type="button"
            class="mt-2 w-full rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 p-3.5 font-bold text-white shadow-lg shadow-indigo-600/30 hover:brightness-110 active:scale-[0.99] transition"
            @click="step = 2"
          >
            ถัดไป: ระบุจำนวน
          </button>
        </div>

        <!-- STEP 2: Amount & Details -->
        <div v-else-if="step === 2" class="space-y-5 animate-fadeIn">
          <!-- Point Amount Section -->
          <div v-if="type === 'point'" class="space-y-4">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-bold text-white">ระบุจำนวนแต้มที่ต้องการมอบให้</label>
              <span class="text-xs text-slate-400">คงเหลือ: <strong class="text-amber-400 font-bold">{{ userBalance.toLocaleString() }}</strong> แต้ม</span>
            </div>

            <!-- Main Input Field -->
            <div class="relative">
              <input
                v-model.number="points"
                type="number"
                min="1"
                placeholder="ระบุจำนวนแต้ม"
                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3.5 pl-12 text-2xl font-black text-amber-400 placeholder-slate-600 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
              />
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl">💎</span>
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase tracking-wider">แต้ม</span>
            </div>

            <!-- Quick Presets -->
            <div class="space-y-1.5">
              <span class="text-[11px] font-semibold text-slate-400">กดเลือกจำนวนด่วน:</span>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="p in pointPresets"
                  :key="p"
                  type="button"
                  class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                  :class="[
                    points === p
                      ? 'border-amber-400 bg-amber-500/20 text-amber-300'
                      : 'border-slate-800 bg-slate-800/60 text-slate-300 hover:border-slate-700 hover:bg-slate-800'
                  ]"
                  @click="points = p"
                >
                  +{{ p.toLocaleString() }}
                </button>
                <button
                  type="button"
                  class="rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-400 hover:bg-amber-500/20 transition"
                  @click="points = userBalance"
                >
                  แต้มทั้งหมด
                </button>
              </div>
            </div>
          </div>

          <!-- Cash Amount & Slip Section -->
          <div v-else class="space-y-4">
            <div>
              <label class="block text-sm font-bold text-white mb-2">ระบุจำนวนเงิน (บาท)</label>
              <div class="relative">
                <input
                  v-model.number="cash"
                  type="number"
                  min="1"
                  step="any"
                  placeholder="0.00"
                  class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3.5 pl-12 text-2xl font-black text-emerald-400 placeholder-slate-600 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                />
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xl font-bold text-emerald-400">฿</span>
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 uppercase tracking-wider">บาท</span>
              </div>
            </div>

            <!-- Quick Presets for Cash -->
            <div class="flex flex-wrap gap-2">
              <button
                v-for="c in cashPresets"
                :key="c"
                type="button"
                class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                :class="[
                  cash === c
                    ? 'border-emerald-400 bg-emerald-500/20 text-emerald-300'
                    : 'border-slate-800 bg-slate-800/60 text-slate-300 hover:border-slate-700 hover:bg-slate-800'
                ]"
                @click="cash = c"
              >
                ฿{{ c.toLocaleString() }}
              </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">ช่องทางชำระเงิน</label>
                <select
                  v-model="paymentMethod"
                  class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-xs text-white focus:border-indigo-500 focus:outline-none"
                >
                  <option value="bank_transfer">โอนเงินผ่านธนาคาร</option>
                  <option value="promptpay">พร้อมเพย์ (PromptPay)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">หมายเลขอ้างอิง (ถ้ามี)</label>
                <input
                  v-model="reference"
                  placeholder="เช่น เลขอ้างอิงธนาคาร"
                  class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-xs text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
                />
              </div>
            </div>

            <!-- Slip Drag & Drop Area -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">
                แนบสลิปการโอนเงิน <span class="text-rose-400">*</span>
              </label>

              <!-- Uploaded Preview -->
              <div v-if="slip" class="relative flex items-center justify-between rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-3">
                <div class="flex items-center gap-3">
                  <img
                    v-if="slipPreviewUrl"
                    :src="slipPreviewUrl"
                    alt="Slip Preview"
                    class="h-12 w-12 rounded-xl object-cover border border-emerald-500/30"
                  />
                  <div v-else class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 font-bold text-xs">
                    📄 PDF
                  </div>
                  <div class="space-y-0.5">
                    <p class="text-xs font-bold text-white line-clamp-1 max-w-[180px] sm:max-w-[220px]">
                      {{ slip.name }}
                    </p>
                    <p class="text-[11px] text-emerald-300">
                      {{ (slip.size / (1024 * 1024)).toFixed(2) }} MB • พร้อมส่ง
                    </p>
                  </div>
                </div>
                <button
                  type="button"
                  class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-900/80 text-slate-400 hover:bg-rose-500/20 hover:text-rose-400 transition"
                  title="ยกเลิกไฟล์"
                  @click="removeSlip"
                >
                  ✕
                </button>
              </div>

              <!-- Dropzone -->
              <div
                v-else
                class="group relative flex flex-col items-center justify-center rounded-2xl border-2 border-dashed p-5 text-center cursor-pointer transition"
                :class="[
                  isDragging
                    ? 'border-indigo-500 bg-indigo-500/10'
                    : 'border-slate-700 bg-slate-950/40 hover:border-slate-600 hover:bg-slate-950/80'
                ]"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
              >
                <input
                  type="file"
                  accept="image/*,.pdf,application/pdf"
                  class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                  @change="chooseFile"
                />
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-800 text-slate-300 group-hover:scale-110 transition">
                  📥
                </div>
                <p class="mt-2 text-xs font-bold text-slate-200">ลากไฟล์สลิปมาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</p>
                <p class="mt-0.5 text-[11px] text-slate-400">รองรับภาพ PNG, JPG, WEBP หรือ PDF (ไม่เกิน 5MB)</p>
              </div>
            </div>
          </div>

          <p v-if="error" class="text-xs font-semibold text-rose-400 bg-rose-500/10 border border-rose-500/20 p-3 rounded-xl">
            ⚠️ {{ error }}
          </p>

          <div class="flex gap-3">
            <button
              type="button"
              class="w-1/3 rounded-2xl border border-slate-700 bg-slate-800 py-3 font-semibold text-slate-300 hover:bg-slate-700 transition"
              @click="step = 1"
            >
              ย้อนกลับ
            </button>
            <button
              type="button"
              class="w-2/3 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 py-3 font-bold text-white shadow-lg shadow-indigo-600/30 hover:brightness-110 active:scale-[0.99] transition"
              @click="goNextStep"
            >
              ถัดไป: ข้อความให้กำลังใจ
            </button>
          </div>
        </div>

        <!-- STEP 3: Message & Donor Profile -->
        <div v-else-if="step === 3" class="space-y-5 animate-fadeIn">
          <div class="space-y-1">
            <h3 class="text-lg font-bold text-white">ข้อความส่งต่อความรู้สึก</h3>
            <p class="text-xs text-slate-400">เขียนข้อความให้กำลังใจ หรือเลือกข้อความสำเร็จรูป</p>
          </div>

          <!-- Presets -->
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="msg in messagePresets"
              :key="msg"
              type="button"
              class="rounded-xl border border-slate-800 bg-slate-800/60 px-3 py-1.5 text-xs font-medium text-slate-300 hover:border-indigo-500/50 hover:bg-slate-800 hover:text-white transition text-left"
              @click="purpose = msg"
            >
              {{ msg }}
            </button>
          </div>

          <textarea
            v-model="purpose"
            maxlength="500"
            rows="3"
            placeholder="เขียนข้อความหรือระบุวัตถุประสงค์การสนับสนุน..."
            class="w-full rounded-2xl border border-slate-700 bg-slate-950 px-4 py-3 text-xs text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
          />

          <!-- Identity Settings -->
          <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4 space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-xs font-bold text-white">การแสดงตนของผู้สนับสนุน</h4>
                <p class="text-[11px] text-slate-400">ซ่อนชื่อของคุณบนบอร์ดผู้สนับสนุนสาธารณะ</p>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input v-model="anonymous" type="checkbox" class="sr-only peer" />
                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600" />
              </label>
            </div>

            <div v-if="!anonymous" class="space-y-1 pt-1">
              <label class="block text-xs font-semibold text-slate-300">ชื่อที่จะแสดงในบัตรผู้สนับสนุน</label>
              <input
                v-model="displayName"
                placeholder="เช่น ครูวิชัย หรือ พี่เอก"
                class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white placeholder-slate-600 focus:border-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <!-- Live Card Preview -->
          <div class="rounded-2xl border border-indigo-500/30 bg-gradient-to-r from-indigo-950/40 via-purple-950/20 to-slate-950 p-3.5 space-y-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">ตัวอย่างบัตรผู้สนับสนุนของคุณ:</span>
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-tr from-amber-500 to-indigo-600 font-bold text-white text-sm shadow">
                {{ anonymous ? '👤' : (displayName ? displayName.charAt(0).toUpperCase() : '👤') }}
              </div>
              <div class="space-y-0.5 min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                  <p class="text-xs font-bold text-white truncate">
                    {{ anonymous ? 'ผู้สนับสนุนไม่ประสงค์ออกนาม' : (displayName || auth.user?.name || 'ผู้สนับสนุน') }}
                  </p>
                  <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold text-white" :class="type === 'point' ? 'bg-amber-500' : 'bg-emerald-500'">
                    {{ type === 'point' ? `+${points.toLocaleString()} แต้ม` : `+฿${Number(cash||0).toLocaleString()}` }}
                  </span>
                </div>
                <p class="text-[11px] text-slate-400 line-clamp-1 italic">
                  "{{ purpose || 'ร่วมสนับสนุนการเรียนรู้' }}"
                </p>
              </div>
            </div>
          </div>

          <div class="flex gap-3">
            <button
              type="button"
              class="w-1/3 rounded-2xl border border-slate-700 bg-slate-800 py-3 font-semibold text-slate-300 hover:bg-slate-700 transition"
              @click="step = 2"
            >
              ย้อนกลับ
            </button>
            <button
              type="button"
              class="w-2/3 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 py-3 font-bold text-white shadow-lg shadow-indigo-600/30 hover:brightness-110 active:scale-[0.99] transition"
              @click="step = 4"
            >
              ถัดไป: ตรวจสอบรายการ
            </button>
          </div>
        </div>

        <!-- STEP 4: Summary & Submit -->
        <div v-else-if="step === 4" class="space-y-5 animate-fadeIn">
          <div class="space-y-1">
            <h3 class="text-lg font-bold text-white">ตรวจสอบความถูกต้อง</h3>
            <p class="text-xs text-slate-400">โปรดตรวจสอบรายละเอียดการสนับสนุนก่อนยืนยันส่งข้อมูล</p>
          </div>

          <!-- Receipt Card -->
          <div class="relative rounded-3xl border border-slate-800 bg-slate-950 p-5 space-y-4 overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-amber-500 via-indigo-500 to-emerald-500" />

            <div class="flex justify-between items-start border-b border-slate-800/80 pb-3">
              <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ผู้รับการสนับสนุน</span>
                <p class="text-sm font-bold text-white">{{ targetName }}</p>
              </div>
              <div class="text-right">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">ประเภทยอด</span>
                <p class="text-xs font-bold" :class="type === 'point' ? 'text-amber-400' : 'text-emerald-400'">
                  {{ type === 'point' ? 'แต้มสะสม' : 'เงินโอน (บาท)' }}
                </p>
              </div>
            </div>

            <div class="space-y-2 text-xs">
              <div class="flex justify-between text-slate-300">
                <span>จำนวนที่สนับสนุน:</span>
                <strong class="text-sm font-black text-white">
                  {{ type === 'point' ? `${points.toLocaleString()} แต้ม` : `฿${Number(cash||0).toLocaleString()}` }}
                </strong>
              </div>

              <div class="flex justify-between text-slate-300">
                <span>การ แสดงตัวตน:</span>
                <span class="font-medium text-slate-200">
                  {{ anonymous ? 'ไม่เปิดเผยชื่อ' : (displayName || auth.user?.name || 'ผู้สนับสนุน') }}
                </span>
              </div>

              <div v-if="type === 'cash'" class="flex justify-between text-slate-300">
                <span>หลักฐานการชำระ:</span>
                <span class="font-medium text-emerald-400 flex items-center gap-1">
                  ✓ แนบสลิปแล้ว ({{ slip?.name }})
                </span>
              </div>

              <div class="pt-2 border-t border-slate-900">
                <span class="text-slate-400 block text-[11px] mb-1">ข้อความเพิ่มเติม:</span>
                <div class="rounded-xl bg-slate-900 p-2.5 text-slate-200 italic text-[11px]">
                  "{{ purpose || 'ไม่ได้ระบุวัตถุประสงค์' }}"
                </div>
              </div>
            </div>
          </div>

          <!-- Error Messages -->
          <div v-if="Object.keys(fieldErrors).length > 0 || error" class="space-y-1 bg-rose-500/10 border border-rose-500/20 p-3 rounded-2xl">
            <p v-for="(messages, field) in fieldErrors" :key="field" class="text-xs font-semibold text-rose-400">
              ⚠️ {{ field }}: {{ messages.join(', ') }}
            </p>
            <p v-if="error" class="text-xs font-semibold text-rose-400">
              ⚠️ {{ error }}
            </p>
          </div>

          <div class="flex gap-3">
            <button
              type="button"
              :disabled="loading"
              class="w-1/3 rounded-2xl border border-slate-700 bg-slate-800 py-3.5 font-semibold text-slate-300 hover:bg-slate-700 disabled:opacity-50 transition"
              @click="step = 3"
            >
              แก้ไข
            </button>
            <button
              type="button"
              :disabled="loading"
              class="w-2/3 flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-600 to-indigo-600 py-3.5 font-bold text-white shadow-lg shadow-emerald-500/25 hover:brightness-110 active:scale-[0.99] disabled:opacity-50 transition"
              @click="submit"
            >
              <svg v-if="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
              <span>{{ loading ? 'กำลังส่งคำขอ...' : '✨ ยืนยันการสนับสนุน' }}</span>
            </button>
          </div>
        </div>

        <!-- STEP 5: Success State -->
        <div v-else-if="step === 5" class="py-4 text-center space-y-5 animate-scaleUp">
          <div class="relative mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 shadow-xl shadow-emerald-500/30">
            <span class="text-4xl text-white">✓</span>
            <div class="absolute -inset-2 rounded-full border-2 border-emerald-500/30 animate-ping opacity-75" />
          </div>

          <div class="space-y-1">
            <h3 class="text-2xl font-black text-white">ขอบคุณสำหรับการสนับสนุน! 🎉</h3>
            <p class="text-sm text-slate-300">
              คำขอสนับสนุนให้ <strong class="text-white">{{ targetName }}</strong> ถูกส่งเรียบร้อยแล้ว
            </p>
          </div>

          <div class="mx-auto max-w-xs rounded-2xl bg-slate-950 border border-slate-800 p-4 text-xs space-y-2 text-slate-400">
            <div class="flex justify-between">
              <span>หมายเลขรายการ:</span>
              <strong class="text-white">#{{ result?.id || result?.donation?.id || 'SUCCESS' }}</strong>
            </div>
            <div class="flex justify-between">
              <span>สถานะ:</span>
              <span class="font-bold text-emerald-400">
                {{ type === 'point' ? 'อนุมัติสำเร็จทันที' : 'รอเจ้าหน้าที่ตรวจสอบสลิป' }}
              </span>
            </div>
          </div>

          <button
            type="button"
            class="w-full rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 py-3.5 font-bold text-white shadow-lg shadow-indigo-600/30 hover:brightness-110 transition"
            @click="emit('update:visible', false)"
          >
            เสร็จสิ้น
          </button>
        </div>
      </div>
    </div>
  </Dialog>
</template>

<style>
.support-donation-dialog-mask {
  background: rgba(15, 23, 42, 0.75) !important;
  backdrop-filter: blur(12px) saturate(130%);
  -webkit-backdrop-filter: blur(12px) saturate(130%);
}

.support-donation-dialog .p-dialog-content {
  padding: 0 !important;
  background: transparent !important;
  border-radius: 1.5rem !important;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scaleUp {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.25s ease-out forwards;
}

.animate-scaleUp {
  animation: scaleUp 0.3s ease-out forwards;
}
</style>

