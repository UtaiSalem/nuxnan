<script setup lang="ts">
import Dialog from 'primevue/dialog'
import { useAcademyDonations } from '~/composables/useAcademyDonations'
import { useCourseDonations } from '~/composables/useCourseDonations'
import { useAuthStore } from '~/stores/auth'

const props = defineProps<{ visible: boolean; scope: 'academy' | 'course'; targetId: number; targetName: string; balance?: number }>()
const emit = defineEmits<{ 'update:visible': [boolean]; donated: [unknown] }>()
const academyApi = useAcademyDonations(); const courseApi = useCourseDonations(); const auth = useAuthStore()
const step = ref(1); const type = ref<'point' | 'cash'>('point'); const points = ref(100); const cash = ref<number | null>(null)
const purpose = ref(''); const anonymous = ref(false); const displayName = ref(''); const paymentMethod = ref('bank_transfer'); const reference = ref(''); const slip = ref<File | null>(null)
const loading = ref(false); const error = ref(''); const result = ref<any>(null); const fieldErrors = ref<Record<string, string[]>>({})
const reset = () => { step.value=1; type.value='point'; points.value=100; cash.value=null; purpose.value=''; anonymous.value=false; displayName.value=''; paymentMethod.value='bank_transfer'; reference.value=''; slip.value=null; loading.value=false; error.value=''; result.value=null; fieldErrors.value={} }
watch(() => props.visible, value => { if (value) reset() })
function chooseFile(event: Event) { const file=(event.target as HTMLInputElement).files?.[0]; if (!file) return; if (file.size > 5*1024*1024) { error.value='ไฟล์สลิปต้องมีขนาดไม่เกิน 5MB'; return }; slip.value=file; error.value='' }
async function submit() { loading.value=true; error.value=''; fieldErrors.value={}; try { let response:any; if (type.value==='point') { const payload={points_amount:points.value,purpose:purpose.value||undefined,anonymous:anonymous.value,donor_display_name:displayName.value||undefined}; response=props.scope==='academy'?await academyApi.sendPointDonation(props.targetId,payload):await courseApi.sendPointDonation(props.targetId,payload) } else { if (!cash.value||cash.value<1) throw new Error('กรุณาระบุจำนวนเงิน'); if (!slip.value) throw new Error('กรุณาแนบสลิปการโอนเงิน'); const form=new FormData(); form.append('cash_amount',String(cash.value)); form.append('slip',slip.value); form.append('payment_method',paymentMethod.value); if(reference.value)form.append('payment_reference',reference.value); if(purpose.value)form.append('purpose',purpose.value); form.append('anonymous',String(anonymous.value)); if(displayName.value)form.append('donor_display_name',displayName.value); response=props.scope==='academy'?await academyApi.sendCashDonation(props.targetId,form):await courseApi.sendCashDonation(props.targetId,form) } result.value=response?.data||response; step.value=5; emit('donated',result.value) } catch(e:any) { if(e?.status===422)fieldErrors.value=e.data?.errors||{}; else error.value=e?.data?.message||e?.message||'ไม่สามารถส่งคำขอสนับสนุนได้' } finally { loading.value=false } }
</script>
<template>
  <Dialog :visible="visible" modal appendTo="body" maskClass="support-donation-dialog-mask" :autoZIndex="true" :baseZIndex="2000" :draggable="false" :style="{ width: 'min(92vw, 32rem)' }" :header="`สนับสนุน${scope === 'academy' ? 'โรงเรียน' : 'รายวิชา'}: ${targetName}`" class="support-donation-dialog" @update:visible="emit('update:visible',$event)">
    <div class="space-y-6"><div class="flex gap-2"><span v-for="n in 5" :key="n" class="h-2 flex-1 rounded-full" :class="n<=step?'bg-indigo-600':'bg-slate-200 dark:bg-slate-700'"/></div>
      <div v-if="step===1" class="space-y-4"><h3 class="text-xl font-bold">เลือกวิธีสนับสนุน</h3><p class="text-sm text-slate-500">เลือกแต้มสะสมหรือการโอนเงิน</p><button v-for="item in [{v:'point',t:'สนับสนุนด้วยแต้ม',d:'ใช้แต้มสะสมของคุณ'},{v:'cash',t:'สนับสนุนด้วยเงิน',d:'แนบสลิปเพื่อรอตรวจสอบ'}]" :key="item.v" type="button" class="w-full rounded-2xl border p-4 text-left" :class="type===item.v?'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30':'border-slate-200 dark:border-slate-700'" @click="type=item.v as 'point'|'cash'"><b>{{item.t}}</b><span class="block text-sm text-slate-500">{{item.d}}</span></button><button class="w-full rounded-xl bg-indigo-600 p-3 font-semibold text-white" @click="step=2">ดำเนินการต่อ</button></div>
      <div v-else-if="step===2" class="space-y-4"><h3 class="text-xl font-bold">ระบุจำนวน</h3><label v-if="type==='point'" class="block">จำนวนแต้ม<input v-model.number="points" type="number" min="1" class="mt-2 w-full rounded-xl border p-3"/><small>ยอดคงเหลือ: {{balance??auth.user?.pp??0}} แต้ม</small></label><template v-else><label class="block">จำนวนเงิน<input v-model.number="cash" type="number" min="1" step=".01" class="mt-2 w-full rounded-xl border p-3"/></label><label class="block">สลิปการโอนเงิน<input type="file" accept="image/*,.pdf,application/pdf" class="mt-2 block w-full" @change="chooseFile"/></label><select v-model="paymentMethod" class="w-full rounded-xl border p-3"><option value="bank_transfer">โอนเงินผ่านธนาคาร</option><option value="promptpay">พร้อมเพย์</option></select><input v-model="reference" placeholder="หมายเลขอ้างอิง (ถ้ามี)" class="w-full rounded-xl border p-3"/></template><p v-if="error" class="text-sm text-red-600">{{error}}</p><button class="w-full rounded-xl bg-indigo-600 p-3 text-white" @click="step=3">ดำเนินการต่อ</button></div>
      <div v-else-if="step===3" class="space-y-4"><h3 class="text-xl font-bold">รายละเอียดการสนับสนุน</h3><textarea v-model="purpose" maxlength="500" placeholder="ข้อความหรือวัตถุประสงค์ (ถ้ามี)" class="min-h-24 w-full rounded-xl border p-3"/><label class="flex gap-2"><input v-model="anonymous" type="checkbox"/> ไม่เปิดเผยชื่อ</label><input v-if="!anonymous" v-model="displayName" placeholder="ชื่อที่ต้องการให้แสดง" class="w-full rounded-xl border p-3"/><button class="w-full rounded-xl bg-indigo-600 p-3 text-white" @click="step=4">ตรวจสอบรายการ</button></div>
      <div v-else-if="step===4" class="space-y-4"><h3 class="text-xl font-bold">ตรวจสอบก่อนยืนยัน</h3><div class="rounded-2xl bg-slate-50 p-4"><b>{{targetName}}</b><p class="mt-2">{{type==='point'?`${points.toLocaleString()} แต้ม`:`${Number(cash||0).toLocaleString()} บาท`}}</p><p class="text-sm text-slate-500">{{purpose||'ไม่ได้ระบุวัตถุประสงค์'}}</p></div><p v-for="(messages,field) in fieldErrors" :key="field" class="text-sm text-red-600">{{field}}: {{messages.join(', ')}}</p><p v-if="error" class="text-sm text-red-600">{{error}}</p><button :disabled="loading" class="w-full rounded-xl bg-indigo-600 p-3 text-white disabled:opacity-50" @click="submit">{{loading?'กำลังส่งข้อมูล...':'ยืนยันการสนับสนุน'}}</button></div>
      <div v-else class="space-y-4 text-center"><div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">✓</div><h3 class="text-xl font-bold text-emerald-600">ส่งคำขอสำเร็จ</h3><p class="text-sm text-slate-500">หมายเลขรายการ #{{result?.id||'-'}}</p><button class="rounded-xl bg-indigo-600 px-5 py-3 text-white" @click="emit('update:visible',false)">ปิดหน้าต่าง</button></div>
    </div>
  </Dialog>
</template>

<style>
.support-donation-dialog .p-dialog-content {
  max-height: min(72vh, 38rem);
  overflow-y: auto;
  background: #ffffff !important;
  color: #0f172a;
  opacity: 1 !important;
}

.support-donation-dialog .p-dialog-header,
.support-donation-dialog .p-dialog-footer {
  background: #ffffff !important;
  opacity: 1 !important;
}

.support-donation-dialog {
  background: #ffffff !important;
  opacity: 1 !important;
}

.p-dialog-mask.support-donation-dialog-mask {
  background: rgba(15, 23, 42, 0.58) !important;
  backdrop-filter: blur(10px) saturate(115%);
  -webkit-backdrop-filter: blur(10px) saturate(115%);
}

@media (max-width: 640px) {
  .support-donation-dialog .p-dialog-header {
    padding: 1rem 1rem 0.75rem;
  }

  .support-donation-dialog .p-dialog-content {
    padding: 0.75rem 1rem 1rem;
  }
}

.dark .support-donation-dialog,
.dark .support-donation-dialog .p-dialog-header,
.dark .support-donation-dialog .p-dialog-content,
.dark .support-donation-dialog .p-dialog-footer {
  background: #1e293b !important;
  color: #f8fafc;
}
</style>
