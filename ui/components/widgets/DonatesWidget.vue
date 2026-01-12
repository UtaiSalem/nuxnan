<script setup>
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'

const api = useApi()
const authStore = useAuthStore()

const donates = ref([])
const isLoading = ref(true)
const error = ref(null)
const processingId = ref(null)

const fetchDonates = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await api.get('/api/donates/widget')
    if (data?.donates) {
      donates.value = data.donates
    }
  } catch (err) {
    console.error('Error fetching donates:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลได้'
  } finally {
    isLoading.value = false
  }
}

const getDonate = async (donateId) => {
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถรับการสนับสนุนได้',
      confirmButtonText: 'เข้าสู่ระบบ',
      confirmButtonColor: '#3085d6',
      showCancelButton: true,
      cancelButtonText: 'ยกเลิก'
    }).then((result) => {
      if (result.isConfirmed) {
        navigateTo('/auth')
      }
    })
    return
  }

  processingId.value = donateId
  try {
    const data = await api.get(`/api/donates/${donateId}/get-donate`)
    if (data?.success) {
      // Update auth store points
      authStore.addPoints(240)
      
      // Refresh the list
      fetchDonates()
      
      Swal.fire({
        title: 'รับการสนับสนุนสำเร็จ!',
        text: 'คุณได้รับ 240 แต้ม',
        icon: 'success',
        showConfirmButton: false,
        timer: 1500
      })
    } else {
      // ตรวจสอบสถานะต่างๆ
      if (data?.pending_approval) {
        Swal.fire({
          icon: 'info',
          title: 'รอการอนุมัติ',
          text: data?.message || 'การสนับสนุนนี้ยังรอการตรวจสอบและอนุมัติจากแอดมิน',
          confirmButtonText: 'ตกลง',
          confirmButtonColor: '#3b82f6'
        })
      } else if (data?.daily_limit_reached) {
        Swal.fire({
          icon: 'warning',
          title: 'ถึงลิมิตประจำวัน',
          text: data?.message || 'คุณได้รับแต้มจากการสนับสนุนนี้ครบ 10 ครั้งแล้วในวันนี้',
          confirmButtonText: 'ตกลง',
          confirmButtonColor: '#f59e0b'
        })
      } else {
        Swal.fire({
          icon: 'error',
          title: 'ไม่สำเร็จ',
          text: data?.message || 'ไม่สามารถรับการสนับสนุนได้',
          confirmButtonText: 'ตกลง'
        })
      }
    }
  } catch (err) {
    console.error('Error getting donate:', err)
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถรับการสนับสนุนได้',
      confirmButtonText: 'ตกลง'
    })
  } finally {
    processingId.value = null
  }
}

onMounted(() => {
  fetchDonates()
})
</script>

<template>
  <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900 dark:text-white">สะสมแต้ม</h3>
      <NuxtLink 
        to="/earn/donates" 
        class="text-xs text-vikinger-purple hover:text-vikinger-purple/80 transition-colors"
      >
        ดูทั้งหมด
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="flex items-center gap-3 animate-pulse">
        <div class="w-10 h-10 bg-gray-200 dark:bg-vikinger-dark-100 rounded-lg"></div>
        <div class="flex-1 space-y-2">
          <div class="h-3 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-3/4"></div>
          <div class="h-2 bg-gray-200 dark:bg-vikinger-dark-100 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="fluent:error-circle-24-regular" class="w-8 h-8 mx-auto mb-2 text-red-400" />
      <p class="text-sm">{{ error }}</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="donates.length === 0" class="text-center py-4 text-gray-500 dark:text-gray-400">
      <Icon icon="mdi:hand-coin-outline" class="w-8 h-8 mx-auto mb-2 opacity-50" />
      <p class="text-sm">ไม่มีการสนับสนุนในขณะนี้</p>
    </div>

    <!-- Donates List -->
    <div v-else class="space-y-3">
      <div 
        v-for="donate in donates" 
        :key="donate.id" 
        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-vikinger-dark-100 transition-colors"
      >
        <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center relative">
          <Icon icon="mdi:hand-coin" class="w-5 h-5 text-white" />
          <!-- Pending Badge on Icon -->
          <div 
            v-if="donate.status === 0"
            class="absolute -top-1 -right-1 w-4 h-4 bg-amber-500 rounded-full flex items-center justify-center"
            title="รออนุมัติ"
          >
            <Icon icon="mdi:clock-outline" class="w-2.5 h-2.5 text-white" />
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-1.5">
            <p class="font-medium text-gray-900 dark:text-white text-sm truncate">
              {{ donate.donor_name || 'ผู้สนับสนุน' }}
            </p>
            <span 
              v-if="donate.status === 0"
              class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-medium rounded"
            >
              รออนุมัติ
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            เหลือ {{ typeof donate.remaining_points === 'string' ? donate.remaining_points : donate.remaining_points?.toLocaleString() || 0 }} แต้ม
          </p>
        </div>
        <button 
          @click="getDonate(donate.id)"
          :disabled="processingId === donate.id || donate.remaining_points < 270 || donate.status === 0"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-full transition-opacity flex items-center gap-1',
            donate.status === 0 
              ? 'bg-gray-400 dark:bg-gray-600 text-white cursor-not-allowed' 
              : 'text-white bg-gradient-to-r from-yellow-500 to-orange-500 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed'
          ]"
        >
          <Icon v-if="processingId === donate.id" icon="mdi:loading" class="w-3 h-3 animate-spin" />
          <span v-if="processingId === donate.id">กำลัง...</span>
          <span v-else-if="donate.status === 0">รอ</span>
          <span v-else>รับแต้ม</span>
        </button>
      </div>
    </div>
  </div>
</template>
