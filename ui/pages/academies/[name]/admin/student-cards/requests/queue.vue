<script setup lang="ts">
import type { StudentCardRequest, StudentCardRequestStatus } from '~/types/studentCardRequest'
definePageMeta({ layout: false })
const academyId = inject<Ref<number | null>>('academyId', ref(null))
const academyName = inject<ComputedRef<string>>('academyName', computed(() => String(useRoute().params.name)))
const api = useStudentCardRequests(academyId)
const rows = ref<StudentCardRequest[]>([])
const counts = ref<Record<string, number>>({})
const status = ref<StudentCardRequestStatus | ''>('pending')
const loading = ref(true)
const load = async () => { if (!academyId.value) return; loading.value = true; try { const [list, count] = await Promise.all([api.list(status.value ? `status=${status.value}` : ''), api.counts()]); rows.value = list.data || []; counts.value = count.data || {} } finally { loading.value = false } }
watch([academyId, status], load, { immediate: true })
</script>

<template><div class="space-y-6"><div><h1 class="text-2xl font-bold dark:text-white">คิวจัดทำบัตร</h1><p class="text-gray-500">อนุมัติและติดตามคำร้องทั้งหมด</p></div>
  <div class="grid grid-cols-2 gap-3 md:grid-cols-4"><button v-for="item in [{v:'pending',l:'รออนุมัติ'},{v:'approved',l:'อนุมัติแล้ว'},{v:'in_progress',l:'กำลังจัดทำ'},{v:'completed',l:'เสร็จสิ้น'}]" :key="item.v" class="rounded-xl border bg-white p-4 text-left dark:bg-gray-800" @click="status = item.v as StudentCardRequestStatus"><div class="text-2xl font-bold dark:text-white">{{ counts[item.v] || 0 }}</div><div class="text-sm text-gray-500">{{ item.l }}</div></button></div>
  <div class="overflow-hidden rounded-xl border bg-white dark:border-gray-700 dark:bg-gray-800"><div v-if="loading" class="p-12 text-center">กำลังโหลด...</div><table v-else class="w-full text-left"><thead class="bg-gray-50 dark:bg-gray-900"><tr><th class="p-4">นักเรียน</th><th class="p-4">ชั้น/ห้อง</th><th class="p-4">ประเภท</th><th class="p-4">สถานะ</th><th class="p-4"></th></tr></thead><tbody><tr v-for="row in rows" :key="row.id" class="border-t dark:border-gray-700"><td class="p-4"><div class="font-medium dark:text-white">{{ row.full_name }}</div><div class="text-xs text-gray-500">{{ row.student_number }}</div></td><td class="p-4">{{ row.grade_level }}/{{ row.section }}</td><td class="p-4">{{ row.request_type }}</td><td class="p-4"><SchoolStudentCardRequestStatusBadge :status="row.status" /></td><td class="p-4 text-right"><NuxtLink :to="`/academies/${academyName}/admin/student-cards/requests/${row.id}`" class="text-primary-600">ดูรายละเอียด</NuxtLink></td></tr><tr v-if="!rows.length"><td colspan="5" class="p-10 text-center text-gray-500">ไม่มีคำร้องในสถานะนี้</td></tr></tbody></table></div>
</div></template>
