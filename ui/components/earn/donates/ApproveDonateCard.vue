<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()
const config = useRuntimeConfig()

const props = defineProps({
  donate: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['approved', 'rejected'])

const isLoading = ref(false)
const showSlipModal = ref(false)

// Get full slip URL with API base
const slipUrl = computed(() => {
  if (!props.donate.slip) return null
  // If already full URL, return as is
  if (props.donate.slip.startsWith('http')) return props.donate.slip
  // Prepend API base
  return `${config.public.apiBase}${props.donate.slip}`
})

// Status badge info
const statusInfo = computed(() => {
  switch (props.donate.status) {
    case 0:
      return { text: 'รออนุมัติ', class: 'bg-yellow-100 text-yellow-800', icon: 'mdi:clock-outline' }
    case 1:
      return { text: 'อนุมัติแล้ว', class: 'bg-green-100 text-green-800', icon: 'mdi:check-circle' }
    case 2:
      return { text: 'ปฏิเสธ', class: 'bg-red-100 text-red-800', icon: 'mdi:close-circle' }
    default:
      return { text: 'ไม่ทราบ', class: 'bg-gray-100 text-gray-800', icon: 'mdi:help-circle' }
  }
})

const handleApprove = async () => {
  if (isLoading.value) return
  
  try {
    isLoading.value = true
    const response = await api.patch(`/api/plearnd-admin/supports/donates/${props.donate.id}/recieve`, {})
    
    if (response.success) {
      emit('approved', props.donate.id)
    }
  } catch (error) {
    console.error('Failed to approve donation:', error)
    alert('ไม่สามารถอนุมัติการสนับสนุนได้')
  } finally {
    isLoading.value = false
  }
}

const handleReject = async () => {
  if (isLoading.value) return
  
  const confirmed = confirm('คุณแน่ใจหรือไม่ที่จะปฏิเสธการสนับสนุนนี้?')
  if (!confirmed) return
  
  try {
    isLoading.value = true
    const response = await api.patch(`/api/plearnd-admin/supports/donates/${props.donate.id}/reject`, {})
    
    if (response.success) {
      emit('rejected', props.donate.id)
    }
  } catch (error) {
    console.error('Failed to reject donation:', error)
    alert('ไม่สามารถปฏิเสธการสนับสนุนได้')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
    <!-- Header with Status -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Icon icon="mdi:hand-coin" class="w-5 h-5 text-purple-500" />
        <span class="font-semibold text-gray-900 dark:text-white">การสนับสนุน #{{ donate.id }}</span>
      </div>
      <span :class="[statusInfo.class, 'px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1']">
        <Icon :icon="statusInfo.icon" class="w-4 h-4" />
        {{ statusInfo.text }}
      </span>
    </div>
    
    <!-- Body -->
    <div class="p-4 space-y-4">
      <!-- Donor Info -->
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
          <img 
            v-if="donate.donor?.avatar" 
            :src="donate.donor.avatar" 
            :alt="donate.donor_name"
            class="w-full h-full object-cover"
          />
          <Icon v-else icon="mdi:account" class="w-6 h-6 text-gray-400" />
        </div>
        <div>
          <p class="font-medium text-gray-900 dark:text-white">
            {{ donate.donor_name || 'ไม่ประสงค์ออกนาม' }}
          </p>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ donate.donor?.email || donate.donor_email || '-' }}
          </p>
        </div>
      </div>
      
      <!-- Amount & Points -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-3 text-center">
          <p class="text-xs text-blue-600 dark:text-blue-400 mb-1">จำนวนเงิน</p>
          <p class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ donate.amounts }}</p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-3 text-center">
          <p class="text-xs text-purple-600 dark:text-purple-400 mb-1">แต้มทั้งหมด</p>
          <p class="text-lg font-bold text-purple-700 dark:text-purple-300">{{ donate.total_points?.toLocaleString() || 0 }}</p>
        </div>
      </div>
      
      <!-- Transfer Info -->
      <div class="text-sm space-y-2">
        <div class="flex justify-between">
          <span class="text-gray-500 dark:text-gray-400">วันที่โอน:</span>
          <span class="text-gray-900 dark:text-white">{{ donate.transfer_date || '-' }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500 dark:text-gray-400">เวลา:</span>
          <span class="text-gray-900 dark:text-white">{{ donate.transfer_time || '-' }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ:</span>
          <span class="text-gray-900 dark:text-white">{{ donate.diff_humans_created_at }}</span>
        </div>
      </div>
      
      <!-- Slip Image -->
      <div v-if="slipUrl" class="mt-3">
        <button 
          @click="showSlipModal = true"
          class="w-full border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-2 hover:border-purple-500 transition-colors"
        >
          <img 
            :src="slipUrl" 
            alt="สลิปโอนเงิน" 
            class="w-full h-40 object-contain rounded-lg"
          />
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">คลิกเพื่อดูขนาดใหญ่</p>
        </button>
      </div>
      <div v-else class="mt-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
        <Icon icon="mdi:image-off" class="w-8 h-8 text-gray-400 mx-auto" />
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">ไม่มีสลิปโอนเงิน</p>
      </div>
      
      <!-- Notes -->
      <div v-if="donate.notes" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">หมายเหตุ:</p>
        <p class="text-sm text-gray-900 dark:text-white">{{ donate.notes }}</p>
      </div>
    </div>
    
    <!-- Actions -->
    <div v-if="donate.status === 0" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex gap-2">
      <button
        @click="handleApprove"
        :disabled="isLoading"
        class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white font-medium rounded-lg transition-colors"
      >
        <Icon v-if="isLoading" icon="mdi:loading" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="mdi:check" class="w-5 h-5" />
        อนุมัติ
      </button>
      <button
        @click="handleReject"
        :disabled="isLoading"
        class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white font-medium rounded-lg transition-colors"
      >
        <Icon v-if="isLoading" icon="mdi:loading" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="mdi:close" class="w-5 h-5" />
        ปฏิเสธ
      </button>
    </div>
    
    <!-- Slip Modal -->
    <Teleport to="body">
      <div 
        v-if="showSlipModal" 
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        @click="showSlipModal = false"
      >
        <div class="relative max-w-4xl max-h-[90vh]">
          <button 
            @click="showSlipModal = false"
            class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors"
          >
            <Icon icon="mdi:close" class="w-8 h-8" />
          </button>
          <img 
            :src="slipUrl" 
            alt="สลิปโอนเงิน" 
            class="max-w-full max-h-[85vh] object-contain rounded-lg"
            @click.stop
          />
        </div>
      </div>
    </Teleport>
  </div>
</template>
