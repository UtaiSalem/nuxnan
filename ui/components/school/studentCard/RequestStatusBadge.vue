<script setup lang="ts">
import type { StudentCardRequestStatus } from '~/types/studentCardRequest'

const props = defineProps<{ status: StudentCardRequestStatus }>()
const labels: Record<StudentCardRequestStatus, string> = { pending: 'รออนุมัติ', approved: 'อนุมัติแล้ว', rejected: 'ปฏิเสธ', in_progress: 'กำลังจัดทำ', completed: 'เสร็จสิ้น', cancelled: 'ยกเลิก' }
const colors: Record<StudentCardRequestStatus, string> = { pending: 'bg-amber-100 text-amber-700', approved: 'bg-blue-100 text-blue-700', rejected: 'bg-red-100 text-red-700', in_progress: 'bg-violet-100 text-violet-700', completed: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-gray-100 text-gray-600' }
</script>

<template><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="colors[props.status]">{{ labels[props.status] }}</span></template>
