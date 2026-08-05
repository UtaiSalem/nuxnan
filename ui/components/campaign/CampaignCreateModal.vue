<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const props = withDefaults(defineProps<{
  visible: boolean
  scopeType: 'academy' | 'course'
  targetId: number | string
  targetName?: string
  // For a course that belongs to an academy, pass it so the campaign is linked.
  academyId?: number | string | null
  // Which tab the modal opens on, so a "สนับสนุน" CTA does not land on the ad form.
  defaultType?: 'advertisement' | 'support'
}>(), { defaultType: 'advertisement' })

const emit = defineEmits<{
  'update:visible': [boolean]
  created: [unknown]
}>()

const authStore = useAuthStore()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase

// Pricing must mirror the server (config campaign.ad_price_per_view_second, default 0.10)
// and the central create page, otherwise the backend rejects a mismatched budget.
const AD_PRICE_PER_VIEW_SECOND = 0.10

const campaignType = ref<'advertisement' | 'support'>(props.defaultType)
const title = ref('')
const description = ref('')
const mediaLink = ref('')
const mediaImage = ref<{ file: File; url: string } | null>(null)
const inputMediaImage = ref<HTMLInputElement | null>(null)
const duration = ref(5)
const totalViews = ref(1000)
const customBudget = ref(100)

const isLoading = ref(false)
const error = ref('')
const fieldErrors = ref<Record<string, string[]>>({})

const durationOptions = [5, 10, 15, 30, 60]
const viewsOptions = [100, 500, 1000, 2000, 5000, 10000]

const budgetAmount = computed(() =>
  campaignType.value === 'advertisement'
    ? totalViews.value * duration.value * AD_PRICE_PER_VIEW_SECOND
    : (parseFloat(String(customBudget.value)) || 0)
)
const walletBalance = computed(() => parseFloat(authStore.user?.wallet) || 0)

function reset() {
  campaignType.value = props.defaultType
  title.value = ''
  description.value = ''
  mediaLink.value = ''
  clearMedia()
  duration.value = 5
  totalViews.value = 1000
  customBudget.value = 100
  isLoading.value = false
  error.value = ''
  fieldErrors.value = {}
}

watch(() => props.visible, value => {
  if (value) reset()
})

function clearMedia() {
  if (mediaImage.value) URL.revokeObjectURL(mediaImage.value.url)
  mediaImage.value = null
}

function onMediaChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  if (file.size > 20 * 1024 * 1024) {
    error.value = 'ไฟล์สื่อต้องมีขนาดไม่เกิน 20MB'
    return
  }
  clearMedia()
  mediaImage.value = { file, url: URL.createObjectURL(file) }
  error.value = ''
}

onUnmounted(() => {
  if (mediaImage.value) URL.revokeObjectURL(mediaImage.value.url)
})

function validate(): boolean {
  error.value = ''
  if (campaignType.value === 'advertisement') {
    if (!title.value.trim()) {
      error.value = 'กรุณาระบุชื่อแคมเปญ'
      return false
    }
    if (!mediaImage.value) {
      error.value = 'กรุณาอัปโหลดสื่อโฆษณา (รูปภาพหรือวิดีโอ)'
      return false
    }
    if (totalViews.value < 100) {
      error.value = 'จำนวนแสดงผลขั้นต่ำ 100 วิว'
      return false
    }
  } else if (budgetAmount.value < 1) {
    error.value = 'งบสนับสนุนขั้นต่ำ 1 บาท'
    return false
  }
  if (walletBalance.value < budgetAmount.value) {
    error.value = 'ยอดเงินใน Wallet ไม่เพียงพอ'
    return false
  }
  return true
}

async function submit() {
  if (isLoading.value || !validate()) return
  isLoading.value = true
  error.value = ''
  fieldErrors.value = {}

  const formData = new FormData()
  formData.append('campaign_type', campaignType.value)
  formData.append('scope_type', props.scopeType)
  formData.append('budget_amount', budgetAmount.value.toFixed(2))
  formData.append('payment_method', 'wallet')
  if (description.value) formData.append('description', description.value)
  if (mediaLink.value) formData.append('media_link', mediaLink.value)

  if (props.scopeType === 'academy') {
    formData.append('academy_id', String(props.targetId))
  } else {
    formData.append('course_id', String(props.targetId))
    if (props.academyId) formData.append('academy_id', String(props.academyId))
  }

  if (campaignType.value === 'advertisement') {
    formData.append('title', title.value)
    formData.append('duration', String(duration.value))
    formData.append('total_views', String(totalViews.value))
    if (mediaImage.value) formData.append('media_image', mediaImage.value.file)
  }

  try {
    const res: any = await $fetch(`${apiBase}/api/campaigns`, {
      method: 'POST',
      body: formData,
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    if (res?.success === false) throw new Error(res.message || 'เกิดข้อผิดพลาดจากระบบ')

    Swal.fire({ title: 'สำเร็จ!', text: 'สร้างแคมเปญเรียบร้อยแล้ว', icon: 'success', timer: 2000, showConfirmButton: false })
    emit('created', res?.data ?? res)
    emit('update:visible', false)
  } catch (e: any) {
    if (e?.status === 422 || e?.response?.status === 422) {
      fieldErrors.value = e?.data?.errors || e?.response?._data?.errors || {}
      error.value = 'กรุณาตรวจสอบข้อมูลอีกครั้ง'
    } else {
      error.value = e?.data?.message || e?.message || 'ไม่สามารถสร้างแคมเปญได้'
    }
  } finally {
    isLoading.value = false
  }
}

const scopeLabel = computed(() => (props.scopeType === 'academy' ? 'โรงเรียน' : 'รายวิชา'))
</script>

<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-[2000] flex items-end justify-center bg-slate-900/70 p-0 backdrop-blur-sm sm:items-center sm:p-4"
      @click.self="emit('update:visible', false)"
    >
      <div class="flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl dark:bg-gray-900 sm:rounded-3xl">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
          <div class="flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
              <Icon icon="solar:megaphone-bold-duotone" class="h-5 w-5" />
            </span>
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-500">สร้างแคมเปญใน{{ scopeLabel }}</p>
              <h2 class="line-clamp-1 max-w-[220px] text-sm font-bold text-gray-900 dark:text-white">{{ targetName || '' }}</h2>
            </div>
          </div>
          <button type="button" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800" @click="emit('update:visible', false)">
            <Icon icon="heroicons:x-mark" class="h-5 w-5" />
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
          <!-- Campaign type -->
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              class="rounded-xl border p-3 text-left transition"
              :class="campaignType === 'advertisement' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40' : 'border-gray-200 dark:border-gray-700'"
              @click="campaignType = 'advertisement'"
            >
              <Icon icon="solar:videocamera-record-bold-duotone" class="h-5 w-5 text-indigo-500" />
              <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">โฆษณา</p>
              <p class="text-[11px] text-gray-500">แสดงสื่อให้ผู้เรียนเห็น</p>
            </button>
            <button
              type="button"
              class="rounded-xl border p-3 text-left transition"
              :class="campaignType === 'support' ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40' : 'border-gray-200 dark:border-gray-700'"
              @click="campaignType = 'support'"
            >
              <Icon icon="solar:hand-heart-bold-duotone" class="h-5 w-5 text-emerald-500" />
              <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">สนับสนุน</p>
              <p class="text-[11px] text-gray-500">สนับสนุนการเรียนรู้</p>
            </button>
          </div>

          <!-- Advertisement fields -->
          <template v-if="campaignType === 'advertisement'">
            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">ชื่อแคมเปญ <span class="text-rose-500">*</span></label>
              <input v-model="title" type="text" maxlength="255" placeholder="เช่น โปรโมชันคอร์สใหม่" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">สื่อโฆษณา (รูปหรือวิดีโอ) <span class="text-rose-500">*</span></label>
              <div v-if="mediaImage" class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                <img :src="mediaImage.url" alt="preview" class="max-h-40 w-full object-cover" />
                <button type="button" class="absolute right-2 top-2 rounded-lg bg-black/60 p-1 text-white" @click="clearMedia">
                  <Icon icon="heroicons:x-mark" class="h-4 w-4" />
                </button>
              </div>
              <button v-else type="button" class="flex w-full flex-col items-center gap-1 rounded-xl border-2 border-dashed border-gray-300 py-6 text-gray-500 hover:border-indigo-400 dark:border-gray-600" @click="inputMediaImage?.click()">
                <Icon icon="heroicons:cloud-arrow-up" class="h-6 w-6" />
                <span class="text-xs font-semibold">คลิกเพื่อเลือกไฟล์ (ไม่เกิน 20MB)</span>
              </button>
              <input ref="inputMediaImage" type="file" accept="image/*,video/mp4,video/webm,video/ogg" class="hidden" @change="onMediaChange" />
            </div>

            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">ลิงก์ปลายทาง (ถ้ามี)</label>
              <input v-model="mediaLink" type="url" placeholder="https://..." class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">ระยะเวลา/วิว (วินาที)</label>
                <select v-model.number="duration" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                  <option v-for="d in durationOptions" :key="d" :value="d">{{ d }} วินาที</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">จำนวนแสดงผล (วิว)</label>
                <select v-model.number="totalViews" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                  <option v-for="v in viewsOptions" :key="v" :value="v">{{ v.toLocaleString() }} วิว</option>
                </select>
              </div>
            </div>
          </template>

          <!-- Support fields -->
          <template v-else>
            <div>
              <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">จำนวนเงินสนับสนุน (บาท)</label>
              <input v-model.number="customBudget" type="number" min="1" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
          </template>

          <!-- Description -->
          <div>
            <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">คำอธิบาย</label>
            <textarea v-model="description" rows="2" maxlength="5000" placeholder="รายละเอียดแคมเปญ..." class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
          </div>

          <!-- Budget summary -->
          <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
            <div>
              <p class="text-xs text-gray-500">งบประมาณ (ชำระผ่าน Wallet)</p>
              <p class="text-lg font-black text-indigo-600 dark:text-indigo-400">฿{{ budgetAmount.toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</p>
            </div>
            <div class="text-right text-[11px] text-gray-500">
              คงเหลือใน Wallet<br />
              <strong :class="walletBalance < budgetAmount ? 'text-rose-500' : 'text-emerald-600'">฿{{ walletBalance.toLocaleString() }}</strong>
            </div>
          </div>

          <!-- Errors -->
          <div v-if="error || Object.keys(fieldErrors).length" class="space-y-1 rounded-xl bg-rose-50 p-3 dark:bg-rose-950/30">
            <p v-if="error" class="text-xs font-semibold text-rose-600">⚠️ {{ error }}</p>
            <p v-for="(msgs, field) in fieldErrors" :key="field" class="text-xs text-rose-500">{{ field }}: {{ msgs.join(', ') }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center gap-2 border-t border-gray-100 px-5 py-4 dark:border-gray-800">
          <NuxtLink
            :to="{ path: '/earn/advertise/create', query: { scope: scopeType, [`${scopeType}_id`]: targetId } }"
            class="text-xs font-semibold text-gray-500 hover:text-indigo-600"
          >
            ตัวเลือกเพิ่มเติม / ชำระด้วยสลิป
          </NuxtLink>
          <div class="flex-1" />
          <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 dark:border-gray-700 dark:text-gray-300" @click="emit('update:visible', false)">
            ยกเลิก
          </button>
          <button
            type="button"
            :disabled="isLoading"
            class="flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2 text-sm font-bold text-white hover:bg-indigo-700 disabled:opacity-50"
            @click="submit"
          >
            <Icon v-if="isLoading" icon="svg-spinners:ring-resize" class="h-4 w-4" />
            <span>{{ isLoading ? 'กำลังสร้าง...' : 'สร้างแคมเปญ' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
