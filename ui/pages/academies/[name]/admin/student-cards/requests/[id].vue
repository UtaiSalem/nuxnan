<script setup lang="ts">
import type { StudentCardRequest } from '~/types/studentCardRequest'
definePageMeta({ layout: 'main' })
const route = useRoute()
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => String(route.params.name)))
const api = useStudentCardRequests(academyId)
const item = ref<StudentCardRequest | null>(null)
const loading = ref(true)
const rejectionReason = ref('')
const adminNotes = ref('')
const error = ref('')
const hasClassroomChanged = computed(() => Boolean(item.value?.classroom && (
  item.value.classroom.grade_level !== item.value.grade_level
  || item.value.classroom.section !== item.value.section
)))
const load = async () => { if (!academyId.value) return; loading.value = true; try { item.value = (await api.show(Number(route.params.id))).data } catch (e: any) { error.value = e?.data?.message || 'โหลดคำร้องไม่สำเร็จ' } finally { loading.value = false } }
const act = async (action: 'approve' | 'reject' | 'start' | 'complete') => { if (!item.value) return; try { await api.transition(item.value.id, action, { rejection_reason: rejectionReason.value, admin_notes: adminNotes.value }); await load() } catch (e: any) { error.value = e?.data?.message || 'ดำเนินการไม่สำเร็จ' } }
watch(academyId, load, { immediate: true })
</script>

<template><div class="mx-auto max-w-4xl space-y-6"><NuxtLink :to="`/academies/${academyName}/admin/student-cards/requests`" class="text-sm text-primary-600">← กลับรายการคำร้อง</NuxtLink><div v-if="error" class="rounded-lg bg-red-50 p-3 text-red-700">{{ error }}</div><div v-if="loading" class="py-16 text-center">กำลังโหลด...</div><template v-else-if="item"><div class="flex flex-wrap items-start justify-between gap-3"><div><h1 class="text-2xl font-bold dark:text-white">{{ item.full_name }}</h1><p class="text-gray-500">{{ item.student_number }} · {{ item.grade_level }}/{{ item.section }}</p></div><SchoolStudentCardRequestStatusBadge :status="item.status" /></div>
  <SchoolStudentCardClassroomHeader :classroom="item.classroom || null" />
  <div v-if="hasClassroomChanged" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">ห้องมีการเปลี่ยนแปลงหลังส่งคำร้อง — ตอนส่ง: {{ item.grade_level }}/{{ item.section }}</div>
  <div class="grid gap-5 md:grid-cols-2"><section class="rounded-xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800"><h2 class="font-semibold dark:text-white">รายละเอียดคำร้อง</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-gray-500">ประเภท</dt><dd class="dark:text-white">{{ item.request_type }}</dd></div><div><dt class="text-gray-500">ความเร่งด่วน</dt><dd class="dark:text-white">{{ item.priority }}</dd></div><div><dt class="text-gray-500">เหตุผล</dt><dd class="dark:text-white">{{ item.reason_label || item.reason || '-' }}<span v-if="item.reason_label && item.reason" class="block text-gray-500">{{ item.reason }}</span></dd></div><div><dt class="text-gray-500">ผู้แจ้งเรื่อง</dt><dd class="dark:text-white">{{ item.requester_name || '-' }}<span v-if="item.requester_phone" class="text-gray-500"> · {{ item.requester_phone }}</span></dd></div></dl></section>
  <section class="rounded-xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-800"><h2 class="font-semibold dark:text-white">ดำเนินการ</h2><textarea v-model="adminNotes" rows="3" placeholder="บันทึกภายใน" class="mt-4 w-full rounded-lg border p-2.5 dark:bg-gray-900"/><textarea v-if="item.status === 'pending'" v-model="rejectionReason" rows="2" placeholder="เหตุผลเมื่อปฏิเสธ" class="mt-3 w-full rounded-lg border p-2.5 dark:bg-gray-900"/><div class="mt-4 flex flex-wrap gap-2"><button v-if="item.status === 'pending'" class="min-h-[44px] sm:min-h-0 rounded-lg bg-blue-600 px-4 py-2 text-white" @click="act('approve')">อนุมัติ</button><button v-if="item.status === 'pending'" class="min-h-[44px] sm:min-h-0 rounded-lg bg-red-600 px-4 py-2 text-white" :disabled="!rejectionReason" @click="act('reject')">ปฏิเสธ</button><button v-if="item.status === 'approved'" class="min-h-[44px] sm:min-h-0 rounded-lg bg-violet-600 px-4 py-2 text-white" @click="act('start')">เริ่มจัดทำ</button><button v-if="item.status === 'in_progress'" class="min-h-[44px] sm:min-h-0 rounded-lg bg-emerald-600 px-4 py-2 text-white" @click="act('complete')">จัดทำเสร็จ</button><NuxtLink v-if="item.result_card_id" :to="`/academies/${academyName}/admin/student-cards/${item.result_card_id}`" class="rounded-lg border px-4 py-2 dark:text-white">เปิดบัตรที่สร้าง</NuxtLink></div></section></div>
</template></div></template>
