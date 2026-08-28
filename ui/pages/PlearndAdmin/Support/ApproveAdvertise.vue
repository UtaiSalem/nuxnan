<script setup>
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'

definePageMeta({
  layout: 'main',
  middleware: ['plearnd-admin']
})

useHead({
  title: 'ระบบอนุมัติและจัดการแคมเปญ (Admin) | Nuxnan'
})

const authStore = useAuthStore()
const api = useApi()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const router = useRouter()

// Tabs
const activeTab = ref('pending') // 'pending' | 'refund' | 'audit'

// State
const isLoading = ref(true)
const campaigns = ref([])
const auditLogs = ref([])

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const logPage = ref(1)
const logLastPage = ref(1)

// Modal for slip review
const showSlipModal = ref(false)
const selectedSlipUrl = ref('')

async function loadCampaigns(page = 1) {
  try {
    isLoading.value = true
    // Fetch all campaigns for admin listing
    const res = await api.get(`/api/campaigns/admin?page=${page}`)
    if (res.success) {
      campaigns.value = res.campaigns?.data || res.campaigns || []
      const meta = res.campaigns?.meta || res.campaigns || {}
      currentPage.value = meta.current_page || page
      lastPage.value = meta.last_page || 1
    }
  } catch (error) {
    console.error('Failed to load campaigns', error)
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลแคมเปญได้', 'error')
  } finally {
    isLoading.value = false
  }
}

async function loadAuditLogs(page = 1) {
  try {
    isLoading.value = true
    const res = await api.get(`/api/campaigns/admin/audit-logs?page=${page}`)
    if (res.success) {
      auditLogs.value = res.logs?.data || res.logs || []
      const meta = res.logs?.meta || {}
      logPage.value = meta.current_page || page
      logLastPage.value = meta.last_page || 1
    }
  } catch (error) {
    console.error('Failed to load audit logs', error)
  } finally {
    isLoading.value = false
  }
}

// Compute items based on active tab
const pendingCampaigns = computed(() => {
  return campaigns.value.filter(c => c.review_status === 'pending')
})

const refundCampaigns = computed(() => {
  // List rejected campaigns that might need manual slip refund or display refunded state
  return campaigns.value.filter(c => c.review_status === 'rejected')
})

async function viewSlip(advertId) {
  const { blob } = await api.getBlob(`/api/advertises/${advertId}/slip`)
  selectedSlipUrl.value = URL.createObjectURL(blob)
  showSlipModal.value = true
}

async function approveCampaign(campaignId) {
  const result = await Swal.fire({
    title: 'อนุมัติแคมเปญ?',
    text: 'คุณแน่ใจว่าได้ตรวจสอบข้อมูลและสลิป (ถ้ามี) เรียบร้อยแล้ว?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'อนุมัติ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#10B981'
  })

  if (result.isConfirmed) {
    try {
      isLoading.value = true
      const res = await api.patch(`/api/campaigns/${campaignId}/review`, {
        action: 'approve'
      })
      if (res.success) {
        Swal.fire('สำเร็จ', 'อนุมัติแคมเปญเรียบร้อยแล้ว', 'success')
        loadCampaigns(currentPage.value)
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถทำรายการได้', 'error')
    } finally {
      isLoading.value = false
    }
  }
}

async function rejectCampaign(campaignId) {
  const { value: reason, isConfirmed } = await Swal.fire({
    title: 'ปฏิเสธแคมเปญ',
    input: 'textarea',
    inputLabel: 'เหตุผลในการปฏิเสธคำขอ',
    inputPlaceholder: 'ระบุเหตุผลเพื่อแจ้งเตือนให้ผู้สร้างทราบ...',
    inputAttributes: {
      'aria-label': 'ระบุเหตุผลเพื่อแจ้งเตือนให้ผู้สร้างทราบ'
    },
    showCancelButton: true,
    confirmButtonText: 'ยืนยันปฏิเสธ',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: '#EF4444',
    inputValidator: (value) => {
      if (!value) {
        return 'กรุณาระบุเหตุผลในการปฏิเสธ!'
      }
    }
  })

  if (isConfirmed && reason) {
    try {
      isLoading.value = true
      const res = await api.patch(`/api/campaigns/${campaignId}/review`, {
        action: 'reject',
        rejection_reason: reason
      })
      if (res.success) {
        Swal.fire('ปฏิเสธเรียบร้อย', 'ยกเลิกคำขอแคมเปญและบันทึกข้อมูลแล้ว', 'success')
        loadCampaigns(currentPage.value)
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถทำรายการได้', 'error')
    } finally {
      isLoading.value = false
    }
  }
}

async function markAsRefunded(campaignId) {
  const result = await Swal.fire({
    title: 'ยืนยันการคืนเงินสลิป?',
    text: 'คุณได้ดำเนินการโอนเงินคืนผู้สร้างบัญชีเรียบร้อยตามรายละเอียดสลิปแล้ว?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ใช่, คืนเงินแล้ว',
    cancelButtonText: 'ยังไม่ได้คืน',
    confirmButtonColor: '#3B82F6'
  })

  if (result.isConfirmed) {
    try {
      isLoading.value = true
      const res = await api.patch(`/api/campaigns/${campaignId}/payment`, {
        payment_status: 'refunded'
      })
      if (res.success) {
        Swal.fire('สำเร็จ', 'บันทึกการคืนเงินเรียบร้อยแล้ว', 'success')
        loadCampaigns(currentPage.value)
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', error.message || 'ไม่สามารถทำรายการได้', 'error')
    } finally {
      isLoading.value = false
    }
  }
}

function handleTabChange(tab) {
  activeTab.value = tab
  if (tab === 'audit') {
    loadAuditLogs()
  } else {
    loadCampaigns()
  }
}

function getLogActionLabel(type) {
  switch (type) {
    case 'create_advertise': return 'สร้างแคมเปญ'
    case 'view_advertise': return 'รับชมโฆษณา'
    case 'approve_advertise': return 'อนุมัติแคมเปญ'
    case 'reject_advertise': return 'ปฏิเสธแคมเปญ'
    default: return type
  }
}

function getLogActionClass(type) {
  switch (type) {
    case 'create_advertise': return 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400'
    case 'view_advertise': return 'bg-teal-100 text-teal-800 dark:bg-teal-950/40 dark:text-teal-400'
    case 'approve_advertise': return 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400'
    case 'reject_advertise': return 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400'
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400'
  }
}

function formatScope(scope) {
  switch (scope) {
    case 'academy': return 'สถาบัน/โรงเรียน'
    case 'course': return 'ในรายวิชา'
    default: return 'สาธารณะ'
  }
}

onMounted(() => {
  loadCampaigns()
})
</script>

<template>
  <div class="min-h-screen py-8 px-0 sm:px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <div class="max-w-7xl mx-auto">
      
      <!-- Top banner -->
      <div class="relative rounded-3xl overflow-hidden shadow-xl mb-8 bg-gradient-to-r from-slate-800 to-slate-900 h-44 flex items-center px-8">
        <div class="absolute inset-0 opacity-15 bg-[url('/storage/images/banner/banner-bg.png')] bg-cover bg-center"></div>
        <div class="relative z-10 flex items-center gap-6">
          <div class="p-4 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20">
            <Icon icon="solar:shield-keyhole-bold-duotone" class="w-12 h-12 text-blue-400" />
          </div>
          <div>
            <h1 class="text-3xl font-bold text-white mb-1">จัดการระบบแคมเปญโฆษณา (Admin)</h1>
            <p class="text-slate-300">พิจารณาอนุมัติ จ่ายเงินคืน และตรวจสอบประวัติการใช้งานแคมเปญทั้งหมด</p>
          </div>
        </div>
      </div>

      <!-- Tab Buttons -->
      <div class="flex gap-2 p-1.5 bg-gray-200/60 dark:bg-gray-800 rounded-2xl mb-8 max-w-md">
        <button 
          @click="handleTabChange('pending')"
          :class="activeTab === 'pending' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
          class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="solar:bell-bing-bold" class="w-4 h-4" />
          รอตรวจสอบ
          <span v-if="pendingCampaigns.length > 0" class="ml-1 px-2 py-0.5 text-xs bg-amber-500 text-white rounded-full font-extrabold">
            {{ pendingCampaigns.length }}
          </span>
        </button>
        <button 
          @click="handleTabChange('refund')"
          :class="activeTab === 'refund' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
          class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="solar:card-recive-bold" class="w-4 h-4" />
          ประวัติการคืนเงิน
        </button>
        <button 
          @click="handleTabChange('audit')"
          :class="activeTab === 'audit' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-white'"
          class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2"
        >
          <Icon icon="solar:history-bold" class="w-4 h-4" />
          Audit Log
        </button>
      </div>

      <!-- Main Content Area -->
      <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        
        <div v-if="isLoading" class="flex flex-col items-center justify-center py-24 text-gray-500">
          <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-600 mb-3" />
          <p>กำลังดึงข้อมูล...</p>
        </div>

        <!-- 1. PENDING REVIEW TAB -->
        <div v-else-if="activeTab === 'pending'">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white text-lg">รายการคำขอแคมเปญใหม่รอการตรวจสอบ</h3>
          </div>

          <div v-if="pendingCampaigns.length === 0" class="text-center py-20 text-gray-400">
            <Icon icon="solar:smile-circle-bold-duotone" class="w-16 h-16 mx-auto mb-4 text-emerald-500" />
            <p class="text-lg font-semibold">ไม่มีแคมเปญรอตรวจสอบในขณะนี้</p>
          </div>

          <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="c in pendingCampaigns" :key="c.id" class="p-6 hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-all flex flex-col lg:flex-row gap-6 justify-between items-start">
              
              <!-- Info details -->
              <div class="space-y-3 flex-1">
                <div class="flex items-center gap-3">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" :class="c.campaign_type === 'advertisement' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400' : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400'">
                    <Icon :icon="c.campaign_type === 'advertisement' ? 'mdi:bullhorn' : 'solar:heart-bold'" />
                    {{ c.campaign_type === 'advertisement' ? 'โฆษณา' : 'สนับสนุน' }}
                  </span>
                  <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2.5 py-1 rounded-full">
                    พื้นที่: {{ formatScope(c.scope_type) }}
                  </span>
                  <span v-if="c.payment_status === 'pending_slip'" class="text-xs bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400 px-2.5 py-1 rounded-full font-bold">
                    สลิปธนาคาร (รอตรวจสอบ)
                  </span>
                  <span v-else class="text-xs bg-teal-100 text-teal-800 dark:bg-teal-950/40 dark:text-teal-400 px-2.5 py-1 rounded-full font-bold">
                    Wallet (หักเรียบร้อย)
                  </span>
                </div>

                <div>
                  <h4 class="text-lg font-bold text-gray-900 dark:text-white">{{ c.title }}</h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ c.description || 'ไม่มีคำบรรยาย' }}</p>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-500">
                  <p>ผู้เสนอ: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ c.advertiser?.name }}</span></p>
                  <p v-if="c.campaign_type === 'advertisement'">ยอดการแสดงผล: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ c.total_views }} วิว ({{ c.duration }}วิ/ครั้ง)</span></p>
                  <p v-else>ได้รับแต้มสะสม: <span class="font-bold text-teal-600">{{ (c.amounts * 1080).toLocaleString() }} PP</span></p>
                  <p>เสนอเมื่อ: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ new Date(c.created_at).toLocaleDateString('th-TH') }}</span></p>
                </div>
              </div>

              <!-- Budget & Actions -->
              <div class="flex flex-col sm:flex-row lg:flex-col items-start sm:items-center lg:items-end gap-4 min-w-[200px]">
                <div class="text-left lg:text-right">
                  <span class="block text-[10px] text-gray-400 font-bold uppercase">งบสุทธิคำขอ</span>
                  <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400">{{ parseFloat(c.budget_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }} ฿</span>
                </div>

                <div class="flex gap-2">
                  <button v-if="c.slip" @click="viewSlip(c.id)" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                    <Icon icon="solar:document-bold" />
                    ดูสลิป
                  </button>
                  <button @click="rejectCampaign(c.id)" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-red-100 hover:bg-red-200 text-red-600 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                    <Icon icon="solar:close-circle-bold" />
                    ปฏิเสธ
                  </button>
                  <button @click="approveCampaign(c.id)" class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded-xl transition-all flex items-center gap-1.5">
                    <Icon icon="solar:check-circle-bold" />
                    อนุมัติ
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- 2. REFUND TAB -->
        <div v-else-if="activeTab === 'refund'">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white text-lg">รายการคืนเงินสำหรับแคมเปญที่ถูกปฏิเสธ</h3>
          </div>

          <div v-if="refundCampaigns.length === 0" class="text-center py-20 text-gray-400">
            <Icon icon="solar:card-recive-linear" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p class="text-lg font-semibold">ไม่มีรายการคืนเงินในประวัติ</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-400 uppercase tracking-wider">
                  <th class="p-4 pl-6">ผู้เสนอ / แคมเปญ</th>
                  <th class="p-4">ประเภท/ชำระผ่าน</th>
                  <th class="p-4 text-right">งบประมาณ</th>
                  <th class="p-4">ข้อมูลโอนเงินคืน</th>
                  <th class="p-4 text-center">สถานะการคืนเงิน</th>
                  <th class="p-4 text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                <tr v-for="c in refundCampaigns" :key="c.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                  <td class="p-4 pl-6">
                    <div class="font-bold text-gray-900 dark:text-white">{{ c.advertiser?.name }}</div>
                    <div class="text-xs text-gray-400">แคมเปญ: {{ c.title }}</div>
                  </td>
                  <td class="p-4">
                    <div>{{ c.campaign_type === 'advertisement' ? 'โฆษณา' : 'สนับสนุน' }}</div>
                    <div class="text-xs text-gray-500 font-medium">{{ c.slip ? 'สลิปธนาคาร' : 'Wallet' }}</div>
                  </td>
                  <td class="p-4 text-right font-bold text-gray-900 dark:text-white">
                    {{ parseFloat(c.budget_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }} ฿
                  </td>
                  <td class="p-4 text-xs space-y-0.5">
                    <div v-if="c.slip" class="text-gray-500 dark:text-gray-400">
                      <p>วันที่โอนในสลิป: {{ c.transfer_date }} ({{ c.transfer_time }})</p>
                      <button @click="viewSlip(c.id)" class="text-blue-500 hover:underline flex items-center gap-1 mt-1 font-bold">
                        <Icon icon="solar:document-bold" class="w-3.5 h-3.5" />
                        ดูสลิปที่โอนเข้ามา
                      </button>
                    </div>
                    <div v-else class="text-teal-600 font-semibold">
                      ระบบโอนคืนเข้ากระเป๋า Wallet อัตโนมัติแล้ว
                    </div>
                  </td>
                  <td class="p-4 text-center">
                    <span 
                      :class="c.payment_status === 'refunded' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'"
                      class="text-xs font-bold px-2.5 py-1 rounded-full"
                    >
                      {{ c.payment_status === 'refunded' ? 'คืนเงินเรียบร้อย' : 'ต้องทำจ่ายคืน (Manual)' }}
                    </span>
                  </td>
                  <td class="p-4 text-center">
                    <button 
                      v-if="c.slip && c.payment_status !== 'refunded'" 
                      @click="markAsRefunded(c.id)"
                      class="min-h-[44px] sm:min-h-0 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition-all"
                    >
                      ยืนยันโอนคืนแล้ว
                    </button>
                    <span v-else class="text-xs text-gray-400">-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 3. AUDIT LOG TAB -->
        <div v-else-if="activeTab === 'audit'">
          <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-bold text-gray-900 dark:text-white text-lg">ประวัติกิจกรรมแคมเปญ (Audit Log)</h3>
          </div>

          <div v-if="auditLogs.length === 0" class="text-center py-20 text-gray-400">
            <Icon icon="solar:history-linear" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
            <p class="text-lg font-semibold">ไม่พบประวัติการทำกิจกรรมแคมเปญ</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-400 uppercase tracking-wider">
                  <th class="p-4 pl-6">เวลา</th>
                  <th class="p-4">ผู้ทำรายการ</th>
                  <th class="p-4">กิจกรรม</th>
                  <th class="p-4">แคมเปญที่เกี่ยวข้อง</th>
                  <th class="p-4 text-right">งบประมาณ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
                <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                  <td class="p-4 pl-6 text-xs text-gray-400">
                    {{ new Date(log.created_at).toLocaleString('th-TH') }}
                  </td>
                  <td class="p-4 font-semibold text-gray-900 dark:text-white">
                    {{ log.user?.name || 'ระบบ' }}
                  </td>
                  <td class="p-4">
                    <span :class="getLogActionClass(log.activity_type)" class="text-xs font-bold px-2.5 py-1 rounded-full">
                      {{ getLogActionLabel(log.activity_type) }}
                    </span>
                  </td>
                  <td class="p-4">
                    <span v-if="log.campaign" class="font-medium text-gray-800 dark:text-white">
                      {{ log.campaign.title }}
                    </span>
                    <span v-else class="text-gray-400 text-xs">ไม่ได้อ้างอิงหรือถูกลบ</span>
                  </td>
                  <td class="p-4 text-right font-bold text-gray-900 dark:text-white">
                    <span v-if="log.campaign">
                      {{ parseFloat(log.campaign.budget_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }} ฿
                    </span>
                    <span v-else>-</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Audit pagination -->
          <div v-if="logLastPage > 1" class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-center gap-2">
            <button 
              @click="loadAuditLogs(logPage - 1)" 
              :disabled="logPage <= 1"
              class="min-h-[44px] sm:min-h-0 px-3 py-1.5 bg-white dark:bg-gray-800 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 text-xs font-bold"
            >
              ย้อนกลับ
            </button>
            <span class="text-xs text-gray-500 self-center">หน้า {{ logPage }} จากทั้งหมด {{ logLastPage }}</span>
            <button 
              @click="loadAuditLogs(logPage + 1)" 
              :disabled="logPage >= logLastPage"
              class="min-h-[44px] sm:min-h-0 px-3 py-1.5 bg-white dark:bg-gray-800 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 text-xs font-bold"
            >
              หน้าถัดไป
            </button>
          </div>
        </div>

        <!-- Main pagination for Campaigns list -->
        <div v-if="activeTab !== 'audit' && lastPage > 1" class="p-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex justify-center gap-2">
          <button 
            @click="loadCampaigns(currentPage - 1)" 
            :disabled="currentPage <= 1"
            class="min-h-[44px] sm:min-h-0 px-3 py-1.5 bg-white dark:bg-gray-800 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 text-xs font-bold"
          >
            ย้อนกลับ
          </button>
          <span class="text-xs text-gray-500 self-center">หน้า {{ currentPage }} จากทั้งหมด {{ lastPage }}</span>
          <button 
            @click="loadCampaigns(currentPage + 1)" 
            :disabled="currentPage >= lastPage"
            class="min-h-[44px] sm:min-h-0 px-3 py-1.5 bg-white dark:bg-gray-800 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 text-xs font-bold"
          >
            หน้าถัดไป
          </button>
        </div>

      </div>

    </div>

    <!-- Slip Modal -->
    <div v-if="showSlipModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showSlipModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full overflow-hidden shadow-2xl relative">
        <button @click="showSlipModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
          <Icon icon="solar:close-circle-bold" class="w-6 h-6" />
        </button>
        <div class="p-6">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">สลิปหลักฐานการโอนเงินที่แจ้งส่งมา</h3>
          <div class="aspect-[3/4] rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <img :src="selectedSlipUrl" class="w-full h-full object-contain" />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
