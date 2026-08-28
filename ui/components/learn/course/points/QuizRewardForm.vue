<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

const props = defineProps<{ courseId: string | number; quizId: number; availableBalance: number }>()
const emit = defineEmits<{ updated: [] }>()
const points = useCoursePoints(computed(() => props.courseId))
const swal = useSweetAlert()
const existing = ref<any>(null)
const enabled = ref(false)
const pointsPerClaim = ref(100)
const maxClaims = ref<number | null>(null)
const hasMaxClaims = ref(false)
const startsAt = ref('')
const endsAt = ref('')
const saving = ref(false)
const cancelling = ref(false)
const reservePreview = computed(() => hasMaxClaims.value && maxClaims.value ? pointsPerClaim.value * maxClaims.value : null)
const insufficient = computed(() => {
  if (!reservePreview.value) return false
  const current = existing.value?.max_claims ? existing.value.max_claims * existing.value.points_per_claim : 0
  return reservePreview.value > props.availableBalance + current
})
onMounted(async () => {
  existing.value = await points.fetchQuizReward(props.quizId)
  if (existing.value) {
    enabled.value = ['active', 'paused'].includes(existing.value.status)
    pointsPerClaim.value = existing.value.points_per_claim
    hasMaxClaims.value = existing.value.max_claims !== null
    maxClaims.value = existing.value.max_claims
    startsAt.value = existing.value.starts_at || ''
    endsAt.value = existing.value.ends_at || ''
  }
})
const save = async () => {
  if (insufficient.value) return swal.error('แต้มสะสมรายวิชาไม่เพียงพอสำหรับการตั้งรางวัล')
  saving.value = true
  try {
    await points.saveQuizReward(props.quizId, { points_per_claim: pointsPerClaim.value, max_claims: hasMaxClaims.value ? maxClaims.value : null, starts_at: startsAt.value || null, ends_at: endsAt.value || null })
    swal.toast('บันทึกรางวัลสอบสำเร็จ', 'success'); emit('updated')
  } catch (e: any) { swal.error(e?.data?.message || 'ไม่สามารถบันทึกได้') } finally { saving.value = false }
}
const cancel = async () => {
  if (!await swal.confirm('ยืนยันการยกเลิกรางวัลสอบ?')) return
  cancelling.value = true
  try { await points.cancelQuizReward(props.quizId); existing.value = null; enabled.value = false; swal.toast('ยกเลิกรางวัลแล้ว', 'info'); emit('updated') } catch (e: any) { swal.error(e?.data?.message || 'ไม่สามารถยกเลิกได้') } finally { cancelling.value = false }
}
</script>
<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800 space-y-5">
    <div><h3 class="text-lg font-semibold text-slate-800 dark:text-white">รางวัลเมื่อสอบได้คะแนนเต็ม</h3><p class="text-sm text-slate-500">แจกแต้มเฉพาะเมื่อผู้เรียนทำคะแนนได้เต็ม 100%</p></div>
    <label class="flex items-center gap-2 text-sm"><input v-model="enabled" type="checkbox" class="rounded text-vikinger-purple" /> เปิดใช้งานรางวัล</label>
    <template v-if="enabled">
      <label class="block text-sm">แต้มต่อคน<input v-model.number="pointsPerClaim" type="number" min="1" class="mt-1 w-full rounded-lg border p-3 dark:bg-slate-900" /></label>
      <label class="flex items-center gap-2 text-sm"><input v-model="hasMaxClaims" type="checkbox" class="rounded" /> จำกัดจำนวนผู้รับ</label>
      <input v-if="hasMaxClaims" v-model.number="maxClaims" type="number" min="1" placeholder="จำนวนคน" class="w-full rounded-lg border p-3 dark:bg-slate-900" />
      <div v-if="reservePreview !== null" :class="['rounded-lg p-3 text-sm', insufficient ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700']">ต้องกันแต้ม {{ reservePreview.toLocaleString() }} จาก available {{ availableBalance.toLocaleString() }}<p v-if="insufficient">แต้มไม่เพียงพอ กรุณาลดจำนวนโควต้าหรือแต้มต่อคน</p></div>
      <div v-if="existing" class="grid grid-cols-3 gap-2 border-t pt-4 text-center text-sm"><div>รับแล้ว<strong class="block">{{ existing.total_claimed }}</strong></div><div>เหลือ<strong class="block">{{ existing.remaining ?? '∞' }}</strong></div><div>แต้มรวม<strong class="block">{{ (existing.total_claimed * existing.points_per_claim).toLocaleString() }}</strong></div></div>
      <div class="flex gap-2"><button :disabled="saving || insufficient" class="flex-1 rounded-lg bg-gradient-vikinger py-3 text-white disabled:opacity-50" @click="save">{{ saving ? 'กำลังบันทึก...' : 'บันทึกรางวัล' }}</button><button v-if="existing" :disabled="cancelling" class="rounded-lg bg-red-50 px-4 text-red-600" @click="cancel">ยกเลิก</button></div>
    </template>
  </div>
</template>
