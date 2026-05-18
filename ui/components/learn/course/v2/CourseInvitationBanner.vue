<script setup lang="ts">
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const props = defineProps({
  courseId: { type: [String, Number], required: true },
  courseMemberOfAuth: { type: Object, required: true },
})

const emit = defineEmits(['refresh'])
const api = useApi()

const acceptInvite = async () => {
    try {
        const res: any = await api.post(`/api/courses/${props.courseId}/admins/invitations/${props.courseMemberOfAuth.id}/accept`, {})
        if (res.success) {
            Swal.fire({title: 'สำเร็จ', text: 'คุณได้เข้าร่วมรายวิชาแล้ว', icon: 'success'})
            emit('refresh')
        }
    } catch (e) {
        Swal.fire({title: 'ผิดพลาด', text: 'ไม่สามารถตอบรับได้', icon: 'error'})
    }
}

const declineInvite = async () => {
    const result = await Swal.fire({
        title: 'ยืนยันการปฏิเสธ?',
        text: 'คุณต้องการปฏิเสธคำเชิญนี้ใช่หรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ใช่, ปฏิเสธ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444'
    })

    if (result.isConfirmed) {
        try {
            const res: any = await api.post(`/api/courses/${props.courseId}/admins/invitations/${props.courseMemberOfAuth.id}/decline`, {})
            if (res.success) {
                Swal.fire({title: 'ปฏิเสธแล้ว', text: 'คุณได้ปฏิเสธคำเชิญเรียบร้อยแล้ว', icon: 'success'})
                navigateTo('/dashboard')
            }
        } catch (e) {
            Swal.fire({title: 'ผิดพลาด', text: 'ไม่สามารถปฏิเสธได้', icon: 'error'})
        }
    }
}
</script>

<template>
  <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-2xl p-4 sm:p-6 flex flex-col md:flex-row items-center justify-between shadow-xl gap-4">
    <div class="flex items-center gap-4 w-full">
      <div class="p-3 bg-yellow-100 dark:bg-yellow-800/40 rounded-2xl flex-shrink-0 animate-bounce">
        <Icon icon="mdi:email-alert" class="w-8 h-8 text-yellow-600 dark:text-yellow-500" />
      </div>
      <div class="min-w-0">
        <h3 class="font-black text-yellow-900 dark:text-yellow-100 text-lg sm:text-xl truncate">คุณได้รับเชิญเข้าร่วมรายวิชานี้</h3>
        <p class="text-sm text-yellow-800/70 dark:text-yellow-300/70">
          ในฐานะ <span class="font-bold underline decoration-yellow-400">{{ courseMemberOfAuth.role === 4 ? 'ผู้ดูแลระบบ (Admin)' : 'ผู้ช่วยสอน (TA)' }}</span>
        </p>
      </div>
    </div>
    <div class="flex gap-3 w-full md:w-auto">
      <button 
        @click="acceptInvite" 
        class="flex-1 md:flex-none px-8 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 font-black shadow-lg shadow-green-600/20 transition-all active:scale-95"
      >
        ตอบรับ
      </button>
      <button 
        @click="declineInvite" 
        class="flex-1 md:flex-none px-8 py-3 bg-white dark:bg-gray-800 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900/50 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 font-black shadow-sm transition-all active:scale-95"
      >
        ปฏิเสธ
      </button>
    </div>
  </div>
</template>
