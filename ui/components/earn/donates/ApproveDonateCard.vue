<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

const api = useApi()

const props = defineProps({
  donate: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['approved', 'rejected', 'edit', 'delete', 'view-slip'])

const showMenu = ref(false)

const slipUrl = ref<string | null>(null)
async function loadSlip() {
  slipUrl.value = null
  if (!props.donate.id) return
  try {
    const { blob } = await api.getBlob(`/api/plearnd-admin/supports/donates/${props.donate.id}/slip`)
    slipUrl.value = URL.createObjectURL(blob)
  } catch {
    slipUrl.value = null
  }
}
onMounted(loadSlip)
watch(() => props.donate.id, loadSlip)

// Status badge info
const statusInfo = computed(() => {
  switch (props.donate.status) {
    case 0:
      return { text: 'รออนุมัติ', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', icon: 'mdi:clock-outline' }
    case 1:
      return { text: 'อนุมัติแล้ว', class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', icon: 'mdi:check-circle' }
    case 2:
      return { text: 'ปฏิเสธ', class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', icon: 'mdi:close-circle' }
    default:
      return { text: 'ไม่ทราบ', class: 'bg-gray-100 text-gray-800', icon: 'mdi:help-circle' }
  }
})

const donorAvatar = computed(() => {
    return props.donate.donor?.avatar || `${config.public.apiBase}/storage/images/plearnd-logo.png`;
})

const handleApprove = () => {
  if (props.loading) return
  emit('approved', props.donate.id)
}

const handleReject = () => {
  if (props.loading) return
  emit('rejected', props.donate.id)
}
</script>

<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700 relative group">
    <!-- Header with Status -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Icon icon="mdi:hand-coin" class="w-5 h-5 text-purple-500" />
        <span class="font-semibold text-gray-900 dark:text-white text-sm">#{{ donate.id }}</span>
      </div>
      <div class="flex items-center gap-2">
        <span :class="[statusInfo.class, 'px-2 py-0.5 rounded-full text-[10px] font-bold flex items-center gap-1 uppercase tracking-wider']">
          <Icon :icon="statusInfo.icon" class="w-3 h-3" />
          {{ statusInfo.text }}
        </span>
        
        <!-- Action Menu Button -->
        <div class="relative">
          <button 
            @click="showMenu = !showMenu"
            class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-gray-500"
          >
            <Icon icon="fluent:more-horizontal-24-regular" class="w-5 h-5" />
          </button>
          
          <div 
            v-if="showMenu"
            v-click-outside="() => showMenu = false"
            class="absolute right-0 mt-1 w-40 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 z-10 overflow-hidden"
          >
            <button 
              @click="emit('edit', donate); showMenu = false"
              class="min-h-[44px] sm:min-h-0 w-full px-4 py-2 text-left text-sm flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
              แก้ไข
            </button>
            <button 
              @click="emit('delete', donate.id); showMenu = false"
              class="min-h-[44px] sm:min-h-0 w-full px-4 py-2 text-left text-sm flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-red-500 transition-colors"
            >
              <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
              ลบรายการ
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Body -->
    <div class="p-4 space-y-4">
      <!-- Donor Info -->
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-700 flex items-center justify-center border-2 border-purple-100 dark:border-purple-900/30">
          <img 
            :src="donorAvatar" 
            :alt="donate.donor_name"
            class="w-full h-full object-cover"
          />
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-bold text-gray-900 dark:text-white truncate text-sm">
            {{ donate.donor_name || 'ไม่ประสงค์ออกนาม' }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
            {{ donate.donor?.email || '-' }}
          </p>
        </div>
      </div>
      
      <!-- Amount & Points -->
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-blue-50/50 dark:bg-blue-900/20 rounded-xl p-2.5 border border-blue-100/50 dark:border-blue-900/30">
          <p class="text-[10px] uppercase font-bold text-blue-500 mb-0.5">จำนวนเงิน</p>
          <p class="text-base font-black text-blue-700 dark:text-blue-300">{{ donate.amounts }}</p>
        </div>
        <div class="bg-purple-50/50 dark:bg-purple-900/20 rounded-xl p-2.5 border border-purple-100/50 dark:border-purple-900/30">
          <p class="text-[10px] uppercase font-bold text-purple-500 mb-0.5">แต้มที่จะได้รับ</p>
          <p class="text-base font-black text-purple-700 dark:text-purple-300">{{ donate.total_points?.toLocaleString() || 0 }}</p>
        </div>
      </div>
      
      <!-- Transfer Info -->
      <div class="text-[11px] space-y-1.5 bg-gray-50/50 dark:bg-gray-900/20 p-3 rounded-xl border border-gray-100/50 dark:border-gray-700/50">
        <div class="flex justify-between items-center">
          <span class="text-gray-400 font-medium">วันที่โอน</span>
          <span class="text-gray-900 dark:text-white font-bold">{{ donate.transfer_date || '-' }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-400 font-medium">เวลา</span>
          <span class="text-gray-900 dark:text-white font-bold">{{ donate.transfer_time || '-' }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-400 font-medium">สร้างเมื่อ</span>
          <span class="text-gray-900 dark:text-white font-bold">{{ donate.diff_humans_created_at }}</span>
        </div>
      </div>
      
      <!-- Slip Image -->
      <div v-if="slipUrl" class="mt-3 relative group/slip">
        <div 
          @click="emit('view-slip', slipUrl)"
          class="cursor-pointer overflow-hidden rounded-xl aspect-[3/4] bg-gray-100 dark:bg-gray-900 border border-gray-100 dark:border-gray-700 transition-transform duration-300 group-hover/slip:scale-[1.02]"
        >
          <img 
            :src="slipUrl" 
            alt="สลิปโอนเงิน" 
            class="w-full h-full object-cover"
          />
          <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/slip:opacity-100 flex items-center justify-center transition-opacity rounded-xl">
             <Icon icon="fluent:zoom-in-24-regular" class="w-8 h-8 text-white" />
          </div>
        </div>
      </div>
      <div v-else class="mt-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl p-4 sm:p-8 border-2 border-dashed border-gray-100 dark:border-gray-700 text-center">
        <Icon icon="fluent:image-off-24-regular" class="w-10 h-10 text-gray-300 mx-auto mb-2" />
        <p class="text-xs text-gray-400 font-medium">ไม่มีสลิปโอนเงิน</p>
      </div>
      
      <!-- Notes -->
      <div v-if="donate.notes || donate.review_note" class="space-y-2">
          <div v-if="donate.notes" class="bg-gray-50/50 dark:bg-gray-700/30 rounded-lg p-2.5 border border-gray-100 dark:border-gray-700">
            <p class="text-[10px] font-bold text-gray-400 uppercase mb-0.5">หมายเหตุ:</p>
            <p class="text-xs text-gray-700 dark:text-gray-300 leading-relaxed">{{ donate.notes }}</p>
          </div>
          <div v-if="donate.review_note" class="bg-indigo-50/50 dark:bg-indigo-900/20 rounded-lg p-2.5 border border-indigo-100/50 dark:border-indigo-900/30">
            <p class="text-[10px] font-bold text-indigo-400 uppercase mb-0.5">หมายเหตุแอดมิน:</p>
            <p class="text-xs text-indigo-700 dark:text-indigo-300 leading-relaxed">{{ donate.review_note }}</p>
          </div>
      </div>
    </div>
    
    <!-- Actions -->
    <div v-if="donate.status === 0" class="p-4 pt-0 flex gap-2">
      <button
        @click="handleApprove"
        :disabled="loading"
        class="flex-1 h-10 flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 disabled:from-emerald-300 disabled:to-green-400 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/20"
      >
        <Icon v-if="loading" icon="fluent:spinner-24-regular" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:checkmark-circle-24-regular" class="w-5 h-5" />
        อนุมัติ
      </button>
      <button
        @click="handleReject"
        :disabled="loading"
        class="flex-1 h-10 flex items-center justify-center gap-2 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700 disabled:from-rose-300 disabled:to-red-400 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-rose-500/20"
      >
        <Icon v-if="loading" icon="fluent:spinner-24-regular" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:dismiss-circle-24-regular" class="w-5 h-5" />
        ปฏิเสธ
      </button>
    </div>
  </div>
</template>
