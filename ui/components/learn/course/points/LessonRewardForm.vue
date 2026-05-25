<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Props {
  courseId: string | number
  lessonId: number
  availableBalance: number   // แต้ม available จาก course account
}

const props = defineProps<Props>()
const emit  = defineEmits<{ updated: [] }>()

const coursePoints = useCoursePoints(computed(() => props.courseId))
const swal         = useSweetAlert()

// State
const existing    = ref<any>(null)    // existing campaign ถ้ามี
const isEnabled   = ref(false)
const pointsPerClaim = ref(100)
const maxClaims   = ref<number | null>(null)
const hasMaxClaims = ref(false)
const startsAt    = ref('')
const endsAt      = ref('')
const isSaving    = ref(false)
const isCancelling = ref(false)

// Preview: แต้มที่ต้อง reserve
const reservePreview = computed(() => {
  if (!hasMaxClaims.value || !maxClaims.value) return null
  return pointsPerClaim.value * maxClaims.value
})

const isBalanceInsufficient = computed(() => {
  if (!reservePreview.value) return false
  // ถ้ามี existing campaign อยู่ ให้หัก reserve เดิมออก
  const currentReserve = existing.value?.max_claims
    ? existing.value.max_claims * existing.value.points_per_claim
    : 0
  return reservePreview.value > (props.availableBalance + currentReserve)
})

// Load existing reward
onMounted(async () => {
  try {
    const data = await coursePoints.fetchLessonReward(props.lessonId)
    if (data) {
      existing.value = data
      isEnabled.value    = data.status === 'active' || data.status === 'paused'
      pointsPerClaim.value = data.points_per_claim
      hasMaxClaims.value = data.max_claims !== null
      maxClaims.value    = data.max_claims
    }
  } catch {}
})

// Save
const handleSave = async () => {
  if (isBalanceInsufficient.value) {
    swal.error('แต้มสะสมรายวิชาไม่เพียงพอสำหรับการตั้งรางวัล')
    return
  }

  isSaving.value = true
  try {
    await coursePoints.saveLessonReward(props.lessonId, {
      points_per_claim: pointsPerClaim.value,
      max_claims: hasMaxClaims.value ? maxClaims.value : null,
      starts_at: startsAt.value || null,
      ends_at: endsAt.value || null,
    })
    swal.toast('บันทึกรางวัลบทเรียนสำเร็จ', 'success')
    emit('updated')
  } catch (e: any) {
    swal.error(e?.data?.message || 'ไม่สามารถบันทึกได้')
  } finally {
    isSaving.value = false
  }
}

// Cancel reward
const handleCancel = async () => {
  const confirmed = await swal.confirm('ยืนยันการยกเลิกรางวัล? แต้มที่กันไว้จะถูกคืนสู่บัญชีรายวิชา', 'ยกเลิกรางวัล')
  if (!confirmed) return

  isCancelling.value = true
  try {
    await coursePoints.cancelLessonReward(props.lessonId)
    existing.value = null
    isEnabled.value = false
    swal.toast('ยกเลิกรางวัลแล้ว', 'info')
    emit('updated')
  } catch (e: any) {
    swal.error(e?.data?.message || 'ไม่สามารถยกเลิกได้')
  } finally {
    isCancelling.value = false
  }
}
</script>

<template>
  <div class="bg-white dark:bg-slate-800 rounded-vikinger border
              border-slate-200 dark:border-slate-700 p-4 space-y-4">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Icon icon="mdi:star" class="w-5 h-5 text-amber-400" />
        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
          รางวัลเมื่ออ่านจบ
        </span>
        <!-- Status badge ถ้ามี existing -->
        <span
          v-if="existing"
          :class="[
            'px-2 py-0.5 text-xs font-bold rounded',
            existing.status === 'active'
              ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
              : existing.status === 'depleted'
              ? 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'
              : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
          ]"
        >
          {{ existing.status === 'active' ? 'กำลังใช้งาน' :
             existing.status === 'depleted' ? 'โควต้าหมด' : existing.status }}
        </span>
      </div>

      <!-- Toggle เปิด/ปิด -->
      <button
        type="button"
        @click="isEnabled = !isEnabled"
        :class="[
          'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
          isEnabled ? 'bg-gradient-vikinger' : 'bg-slate-200 dark:bg-slate-600',
        ]"
        :aria-label="isEnabled ? 'ปิดรางวัล' : 'เปิดรางวัล'"
      >
        <span
          :class="[
            'inline-block h-4 w-4 rounded-full bg-white shadow transition-transform',
            isEnabled ? 'translate-x-6' : 'translate-x-1',
          ]"
        />
      </button>
    </div>

    <!-- Form (แสดงเมื่อ enabled) -->
    <template v-if="isEnabled">
      <!-- แต้มต่อคน -->
      <div class="space-y-1">
        <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">
          แต้มที่จะให้ต่อ 1 คน
        </label>
        <div class="flex items-center gap-2">
          <input
            v-model.number="pointsPerClaim"
            type="number" min="1"
            class="w-32 px-3 py-2 rounded-lg text-sm border
                   border-slate-300 dark:border-slate-600
                   bg-white dark:bg-slate-800
                   text-slate-900 dark:text-slate-100
                   focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
          />
          <span class="text-xs text-slate-500 dark:text-slate-400">แต้ม</span>
        </div>
      </div>

      <!-- จำนวนโควต้า -->
      <div class="space-y-2">
        <label class="flex items-center gap-2 cursor-pointer">
          <input type="checkbox" v-model="hasMaxClaims"
                 class="rounded border-slate-300 text-vikinger-purple
                        focus:ring-vikinger-purple" />
          <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
            จำกัดจำนวนผู้รับ
          </span>
        </label>
        <input
          v-if="hasMaxClaims"
          v-model.number="maxClaims"
          type="number" min="1"
          placeholder="จำนวนคน"
          class="w-32 px-3 py-2 rounded-lg text-sm border
                 border-slate-300 dark:border-slate-600
                 bg-white dark:bg-slate-800
                 text-slate-900 dark:text-slate-100
                 focus:ring-2 focus:ring-vikinger-purple focus:border-transparent"
        />
      </div>

      <!-- Preview งบ + แจ้งเตือน -->
      <div
        v-if="reservePreview !== null"
        :class="[
          'rounded-lg p-3 text-xs font-medium',
          isBalanceInsufficient
            ? 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300'
            : 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300',
        ]"
      >
        <div class="flex items-center gap-1.5">
          <Icon
            :icon="isBalanceInsufficient ? 'mdi:alert-circle' : 'mdi:information'"
            class="w-4 h-4 shrink-0"
          />
          <span>
            ต้องกันแต้ม <strong>{{ reservePreview.toLocaleString() }} แต้ม</strong>
            จาก available <strong>{{ availableBalance.toLocaleString() }} แต้ม</strong>
          </span>
        </div>
        <p v-if="isBalanceInsufficient" class="mt-1 ml-5">
          แต้มสะสมรายวิชาไม่เพียงพอ กรุณาลดจำนวนโควต้าหรือแต้มต่อคน
        </p>
      </div>

      <!-- Stats ของ existing campaign -->
      <div
        v-if="existing"
        class="grid grid-cols-3 gap-2 text-center pt-2 border-t
               border-slate-100 dark:border-slate-700"
      >
        <div>
          <p class="text-xs text-slate-400 dark:text-slate-500">รับแล้ว</p>
          <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
            {{ existing.claimed_count }}
          </p>
        </div>
        <div>
          <p class="text-xs text-slate-400 dark:text-slate-500">เหลือ</p>
          <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
            {{ existing.remaining_claims ?? '∞' }}
          </p>
        </div>
        <div>
          <p class="text-xs text-slate-400 dark:text-slate-500">แต้มรวม</p>
          <p class="text-sm font-bold text-amber-600 dark:text-amber-400">
            {{ (existing.claimed_count * existing.points_per_claim).toLocaleString() }}
          </p>
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex gap-2 pt-1">
        <button
          type="button"
          @click="handleSave"
          :disabled="isSaving || isBalanceInsufficient"
          class="flex-1 bg-gradient-vikinger text-white text-sm font-semibold
                 py-2 rounded-lg shadow-vikinger hover:shadow-vikinger-lg
                 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="isSaving">กำลังบันทึก...</span>
          <span v-else>{{ existing ? 'อัพเดทรางวัล' : 'บันทึกรางวัล' }}</span>
        </button>

        <button
          v-if="existing"
          type="button"
          @click="handleCancel"
          :disabled="isCancelling"
          class="px-4 py-2 rounded-lg text-sm font-semibold
                 bg-red-50 text-red-600 hover:bg-red-100
                 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40
                 transition-colors disabled:opacity-50"
        >
          {{ isCancelling ? '...' : 'ยกเลิก' }}
        </button>
      </div>
    </template>

    <!-- ยังไม่ได้เปิด + ไม่มี existing -->
    <p v-else-if="!existing" class="text-xs text-slate-400 dark:text-slate-500">
      เปิดใช้งานเพื่อแจกแต้มให้นักเรียนที่อ่านจบบทเรียนนี้
    </p>
  </div>
</template>
