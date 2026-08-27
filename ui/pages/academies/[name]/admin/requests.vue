<script setup lang="ts">
/**
 * Academy Admin - Member Requests Page
 * หน้าจัดการคำขอเข้าร่วมโรงเรียน
 */
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academyId = ref<number | null>(null)
const pendingRequests = ref<any[]>([])
const isLoading = ref(true)
const processingIds = ref<Set<number>>(new Set())

// Academy Role
const { can, fetchMyRole } = useAcademyRole(academyId)

onMounted(async () => {
  try {
    // Get academy ID first
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchMyRole()

      if (!can('members.manage')) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }

      await fetchPendingRequests()
    }
  } catch (err: any) {
    console.error('Failed to load academy:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchPendingRequests = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/pending-requests`)
    if (response.success) {
      pendingRequests.value = response.pendingRequests || []
    }
  } catch (err) {
    console.error('Failed to fetch pending requests:', err)
  }
}

const acceptRequest = async (request: any) => {
  if (processingIds.value.has(request.id)) return
  
  processingIds.value.add(request.id)
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/members/${request.id}/accept`, {})
    if (response.success) {
      pendingRequests.value = pendingRequests.value.filter(r => r.id !== request.id)
      
      Swal.fire({
        icon: 'success',
        title: 'อนุมัติสำเร็จ',
        text: `${request.user?.name} เป็นสมาชิกแล้ว`,
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถอนุมัติได้',
    })
  } finally {
    processingIds.value.delete(request.id)
  }
}

const rejectRequest = async (request: any) => {
  if (processingIds.value.has(request.id)) return
  
  const result = await Swal.fire({
    title: 'ยืนยันการปฏิเสธ?',
    text: `ต้องการปฏิเสธคำขอจาก ${request.user?.name} หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'ปฏิเสธ',
    cancelButtonText: 'ยกเลิก'
  })
  
  if (!result.isConfirmed) return
  
  processingIds.value.add(request.id)
  try {
    const response: any = await api.post(`/api/academies/${academyId.value}/members/${request.id}/reject`, {})
    if (response.success) {
      pendingRequests.value = pendingRequests.value.filter(r => r.id !== request.id)
      
      Swal.fire({
        icon: 'success',
        title: 'ปฏิเสธเรียบร้อย',
        timer: 2000,
        showConfirmButton: false
      })
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถปฏิเสธได้',
    })
  } finally {
    processingIds.value.delete(request.id)
  }
}

const acceptAll = async () => {
  if (pendingRequests.value.length === 0) return

  const result = await Swal.fire({
    title: 'อนุมัติทั้งหมด?',
    text: `ต้องการอนุมัติคำขอทั้ง ${pendingRequests.value.length} รายการหรือไม่?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#6b7280',
    confirmButtonText: 'อนุมัติทั้งหมด',
    cancelButtonText: 'ยกเลิก'
  })

  if (!result.isConfirmed) return

  isLoading.value = true
  try {
    const memberIds = pendingRequests.value.map(r => r.id)
    const response: any = await api.post(`/api/academies/${academyId.value}/members/bulk-action`, {
      member_ids: memberIds,
      action: 'approve',
    })

    const successCount = response?.success_count ?? 0
    await fetchPendingRequests()

    Swal.fire({
      icon: 'success',
      title: 'อนุมัติสำเร็จ',
      text: `อนุมัติแล้ว ${successCount} รายการ`,
      timer: 2000,
      showConfirmButton: false
    })
  } catch (err) {
    console.error('Failed to bulk accept:', err)
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถอนุมัติทั้งหมดได้',
    })
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('th-TH', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คำขอเข้าร่วม</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการคำขอเข้าร่วมโรงเรียนจากผู้ใช้</p>
        </div>
        
        <button
          v-if="pendingRequests.length > 0"
          @click="acceptAll"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-green-500 text-white rounded-lg font-medium text-sm hover:bg-green-600 transition-colors flex items-center gap-2"
        >
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
          อนุมัติทั้งหมด ({{ pendingRequests.length }})
        </button>
      </div>

      <!-- Stats Card -->
      <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-xl p-6 text-white">
        <div class="flex items-center gap-4">
          <div class="p-3 bg-white/20 rounded-xl">
            <Icon icon="fluent:person-clock-24-filled" class="w-8 h-8" />
          </div>
          <div>
            <p class="text-3xl font-bold">{{ pendingRequests.length }}</p>
            <p class="text-amber-100">คำขอรอดำเนินการ</p>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="pendingRequests.length === 0" class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center">
        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-10 h-10 text-green-500" />
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่มีคำขอรอดำเนินการ</h3>
        <p class="text-gray-500 dark:text-gray-400">คำขอเข้าร่วมทั้งหมดได้รับการดำเนินการแล้ว</p>
        
        <NuxtLink 
          :to="`/academies/${academyName}/admin/members`"
          class="inline-flex items-center gap-2 mt-6 px-4 py-2 text-primary-600 dark:text-primary-400 hover:underline"
        >
          <Icon icon="fluent:people-24-regular" class="w-5 h-5" />
          ดูรายชื่อสมาชิกทั้งหมด
        </NuxtLink>
      </div>

      <!-- Requests List -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
          <div 
            v-for="request in pendingRequests" 
            :key="request.id"
            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
          >
            <div class="flex items-center justify-between gap-4">
              <!-- User Info -->
              <div class="flex items-center gap-4 flex-1 min-w-0">
                <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden shrink-0">
                  <img 
                    v-if="request.user?.profile_photo_url" 
                    :src="request.user.profile_photo_url"
                    :alt="request.user?.name"
                    class="w-full h-full object-cover"
                  />
                  <div v-else class="w-full h-full flex items-center justify-center">
                    <Icon icon="fluent:person-24-regular" class="w-6 h-6 text-gray-400" />
                  </div>
                </div>
                
                <div class="flex-1 min-w-0">
                  <h4 class="font-medium text-gray-900 dark:text-white truncate">
                    {{ request.user?.name }}
                  </h4>
                  <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span v-if="request.user?.reference_code">@{{ request.user.reference_code }}</span>
                    <span v-if="request.user?.email" class="truncate">{{ request.user.email }}</span>
                  </div>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    ส่งคำขอเมื่อ {{ formatDate(request.created_at) }}
                  </p>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex items-center gap-2 shrink-0">
                <button
                  @click="acceptRequest(request)"
                  :disabled="processingIds.has(request.id)"
                  class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-green-500 text-white rounded-lg text-sm font-medium hover:bg-green-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                >
                  <Icon 
                    :icon="processingIds.has(request.id) ? 'svg-spinners:ring-resize' : 'fluent:checkmark-24-filled'" 
                    class="w-4 h-4" 
                  />
                  อนุมัติ
                </button>
                <button
                  @click="rejectRequest(request)"
                  :disabled="processingIds.has(request.id)"
                  class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-500 text-white rounded-lg text-sm font-medium hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
                >
                  <Icon icon="fluent:dismiss-24-filled" class="w-4 h-4" />
                  ปฏิเสธ
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
