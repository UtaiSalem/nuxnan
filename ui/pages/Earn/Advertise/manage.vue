<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'

const authStore = useAuthStore()
const api = useApi()
const config = useRuntimeConfig()
const apiBase = config.public.apiBase
const router = useRouter()

definePageMeta({
  layout: 'main',
  middleware: 'auth'
})

useHead({
  title: 'จัดการแคมเปญของคุณ | Nuxnan'
})

const isLoading = ref(true)
const campaigns = ref<any[]>([])
const currentPage = ref(1)
const lastPage = ref(1)
const totalCampaigns = ref(0)

// Filters
const filterType = ref('all') // 'all' | 'advertisement' | 'support'
const filterStatus = ref('all') // 'all' | 'pending' | 'approved' | 'rejected'

// Selected slip modal
const showSlipModal = ref(false)
const selectedSlipUrl = ref('')

async function fetchCampaigns(page = 1) {
  try {
    isLoading.value = true
    const response = await api.get(`/api/campaigns/manage?page=${page}`)
    if (response.success) {
      campaigns.value = response.campaigns?.data || response.campaigns || []
      const meta = response.campaigns?.meta || response.campaigns || {}
      currentPage.value = meta.current_page || page
      lastPage.value = meta.last_page || 1
      totalCampaigns.value = meta.total || campaigns.value.length
    }
  } catch (error) {
    console.error('Failed to fetch user campaigns', error)
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดรายการแคมเปญได้', 'error')
  } finally {
    isLoading.value = false
  }
}

// Stats computations based on fetched campaigns
const stats = computed(() => {
  let totalSpent = 0
  let totalViewsDelivered = 0
  let pendingCount = 0
  let activeCount = 0

  campaigns.value.forEach(c => {
    totalSpent += parseFloat(c.budget_amount) || 0
    if (c.campaign_type === 'advertisement') {
      totalViewsDelivered += ((c.total_views || 0) - (c.remaining_views || 0))
    }
    if (c.review_status === 'pending') {
      pendingCount++
    }
    if (c.review_status === 'approved' && c.remaining_views > 0) {
      activeCount++
    }
  })

  return {
    totalSpent,
    totalViewsDelivered,
    pendingCount,
    activeCount
  }
})

// Filtered campaigns list
const filteredCampaigns = computed(() => {
  return campaigns.value.filter(c => {
    const typeMatch = filterType.value === 'all' || c.campaign_type === filterType.value
    const statusMatch = filterStatus.value === 'all' || c.review_status === filterStatus.value
    return typeMatch && statusMatch
  })
})

async function viewSlip(advertId: number) {
  const { blob } = await api.getBlob(`/api/advertises/${advertId}/slip`)
  selectedSlipUrl.value = URL.createObjectURL(blob)
  showSlipModal.value = true
}

function getStatusBadge(status: string) {
  switch (status) {
    case 'approved':
      return { text: 'อนุมัติแล้ว', class: 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-400', icon: 'solar:check-circle-bold-duotone' }
    case 'rejected':
      return { text: 'ปฏิเสธ', class: 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-400', icon: 'solar:close-circle-bold-duotone' }
    default:
      return { text: 'รอตรวจสอบ', class: 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400', icon: 'solar:clock-circle-bold-duotone' }
  }
}

function getPaymentBadge(status: string) {
  switch (status) {
    case 'paid':
      return { text: 'ชำระแล้ว', class: 'bg-teal-100 text-teal-700 dark:bg-teal-950/40 dark:text-teal-400' }
    case 'refunded':
      return { text: 'คืนเงินแล้ว', class: 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400' }
    default:
      return { text: 'รอตรวจสอบสลิป', class: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400' }
  }
}

function formatScope(scope: string) {
  switch (scope) {
    case 'academy': return 'สถาบัน/โรงเรียน'
    case 'course': return 'ในรายวิชา'
    default: return 'สาธารณะ'
  }
}

onMounted(() => {
  fetchCampaigns()
})
</script>

<template>
  <div class="min-h-screen py-8 px-0 sm:px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
    <div class="max-w-7xl mx-auto">

      <!-- Header & Navigation -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
            <Icon icon="solar:chart-square-bold-duotone" class="text-blue-600 w-9 h-9" />
            แดชบอร์ดแคมเปญ
          </h1>
          <p class="text-sm text-gray-500 dark:text-gray-400">ดูสถิติการรับชม งบประมาณ และการอนุมัติแคมเปญของคุณ</p>
        </div>

        <div class="flex gap-3">
          <NuxtLink to="/earn/advertise" class="flex items-center gap-2 px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-2xl border hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-semibold text-sm">
            <Icon icon="solar:arrow-left-bold" />
            ดูโฆษณาทั้งหมด
          </NuxtLink>
          <NuxtLink to="/earn/advertise/create" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-2xl shadow-lg hover:shadow-blue-500/20 hover:from-blue-700 hover:to-indigo-700 transition-all font-bold text-sm">
            <Icon icon="solar:add-circle-bold" class="w-5 h-5" />
            สร้างแคมเปญใหม่
          </NuxtLink>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        
        <!-- Total spent -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
          <div class="p-3 bg-blue-50 dark:bg-blue-950/20 text-blue-600 dark:text-blue-400 rounded-2xl">
            <Icon icon="solar:card-2-bold-duotone" class="w-7 h-7" />
          </div>
          <div>
            <span class="block text-xs font-bold text-gray-400 uppercase">งบประมาณสะสม</span>
            <span class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.totalSpent.toLocaleString(undefined, {minimumFractionDigits: 2}) }} ฿</span>
          </div>
        </div>

        <!-- Total views delivered -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
          <div class="p-3 bg-teal-50 dark:bg-teal-950/20 text-teal-600 dark:text-teal-400 rounded-2xl">
            <Icon icon="solar:eye-bold-duotone" class="w-7 h-7" />
          </div>
          <div>
            <span class="block text-xs font-bold text-gray-400 uppercase">ยอดรับชมโฆษณา</span>
            <span class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.totalViewsDelivered.toLocaleString() }} ครั้ง</span>
          </div>
        </div>

        <!-- Active campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
          <div class="p-3 bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 rounded-2xl">
            <Icon icon="solar:play-bold-duotone" class="w-7 h-7" />
          </div>
          <div>
            <span class="block text-xs font-bold text-gray-400 uppercase">แคมเปญที่รันอยู่</span>
            <span class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.activeCount }} แคมเปญ</span>
          </div>
        </div>

        <!-- Pending review campaigns -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 md:p-6 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
          <div class="p-3 bg-amber-50 dark:bg-amber-950/20 text-amber-600 dark:text-amber-400 rounded-2xl">
            <Icon icon="solar:bell-bing-bold-duotone" class="w-7 h-7" />
          </div>
          <div>
            <span class="block text-xs font-bold text-gray-400 uppercase">รอตรวจสอบ</span>
            <span class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.pendingCount }} รายการ</span>
          </div>
        </div>

      </div>

      <!-- Filters & Content -->
      <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        
        <!-- Filter Bar -->
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <h3 class="font-bold text-gray-900 dark:text-white text-lg">รายการแคมเปญของคุณ</h3>
          
          <div class="flex flex-wrap gap-3">
            <!-- Filter Type -->
            <select v-model="filterType" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm h-10 px-3">
              <option value="all">ทุกประเภท</option>
              <option value="advertisement">แคมเปญโฆษณา</option>
              <option value="support">แคมเปญสนับสนุน</option>
            </select>

            <!-- Filter Status -->
            <select v-model="filterStatus" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 shadow-sm text-sm h-10 px-3">
              <option value="all">ทุกสถานะรีวิว</option>
              <option value="pending">รออนุมัติ</option>
              <option value="approved">อนุมัติแล้ว</option>
              <option value="rejected">ปฏิเสธ</option>
            </select>
          </div>
        </div>

        <!-- Table list -->
        <div v-if="isLoading" class="flex flex-col items-center justify-center py-20 text-gray-500">
          <Icon icon="svg-spinners:ring-resize" class="w-10 h-10 text-blue-600 mb-3" />
          <p>กำลังดึงข้อมูลแคมเปญ...</p>
        </div>

        <div v-else-if="filteredCampaigns.length === 0" class="text-center py-20 text-gray-400">
          <Icon icon="solar:inbox-line-bold-duotone" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
          <p class="text-lg font-medium">ไม่พบรายการแคมเปญที่ค้นหา</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700 text-xs font-bold text-gray-400 uppercase tracking-wider">
                <th class="p-4 pl-6">แคมเปญ / รายละเอียด</th>
                <th class="p-4">ประเภท</th>
                <th class="p-4">พื้นที่</th>
                <th class="p-4 text-right">งบประมาณ</th>
                <th class="p-4 text-center">สถิติ (วิวเห็น / ทั้งหมด)</th>
                <th class="p-4 text-center">ชำระเงิน</th>
                <th class="p-4 text-center">สถานะ Review</th>
                <th class="p-4 text-center">หลักฐาน</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm text-gray-700 dark:text-gray-300">
              <tr v-for="c in filteredCampaigns" :key="c.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                <!-- Info -->
                <td class="p-4 pl-6 max-w-xs">
                  <div class="font-bold text-gray-900 dark:text-white truncate" :title="c.title">{{ c.title }}</div>
                  <div class="text-xs text-gray-400 dark:text-gray-500 line-clamp-1" :title="c.description">{{ c.description || 'ไม่มีรายละเอียด' }}</div>
                </td>
                <!-- Type -->
                <td class="p-4">
                  <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold" :class="c.campaign_type === 'advertisement' ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/20 dark:text-blue-400' : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-400'">
                    <Icon :icon="c.campaign_type === 'advertisement' ? 'mdi:bullhorn' : 'solar:heart-bold'" />
                    {{ c.campaign_type === 'advertisement' ? 'โฆษณา' : 'สนับสนุน' }}
                  </span>
                </td>
                <!-- Scope -->
                <td class="p-4 font-medium">
                  <div>{{ formatScope(c.scope_type) }}</div>
                  <div v-if="c.scope_type === 'academy'" class="text-xs text-gray-400">{{ c.academy?.name }}</div>
                  <div v-if="c.scope_type === 'course'" class="text-xs text-gray-400">{{ c.course?.title }}</div>
                </td>
                <!-- Budget -->
                <td class="p-4 text-right font-extrabold text-gray-900 dark:text-white">
                  {{ parseFloat(c.budget_amount).toLocaleString(undefined, {minimumFractionDigits: 2}) }} ฿
                </td>
                <!-- Views / Imp -->
                <td class="p-4 text-center">
                  <div v-if="c.campaign_type === 'advertisement'">
                    <div class="font-bold">{{ (c.total_views - c.remaining_views) }} / {{ c.total_views }}</div>
                    <div class="text-[10px] text-gray-400">เห็น: {{ c.impressions_count || 0 }} ครั้ง</div>
                  </div>
                  <div v-else class="text-teal-600 font-semibold text-xs">
                    {{ (c.amounts * 1080).toLocaleString() }} PP
                  </div>
                </td>
                <!-- Payment status -->
                <td class="p-4 text-center">
                  <span :class="getPaymentBadge(c.payment_status).class" class="text-xs font-semibold px-2 py-0.5 rounded-full">
                    {{ getPaymentBadge(c.payment_status).text }}
                  </span>
                </td>
                <!-- Review Status -->
                <td class="p-4 text-center">
                  <span :class="getStatusBadge(c.review_status).class" class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full">
                    <Icon :icon="getStatusBadge(c.review_status).icon" />
                    {{ getStatusBadge(c.review_status).text }}
                  </span>
                </td>
                <!-- Action / Slip image link -->
                <td class="p-4 text-center">
                  <button v-if="c.slip" @click="viewSlip(c.id)" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-full transition-colors" title="ดูสลิปโอนเงิน">
                    <Icon icon="solar:document-bold" class="w-5 h-5" />
                  </button>
                  <span v-else class="text-xs text-gray-400">Wallet</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

    </div>

    <!-- Slip Image Modal -->
    <div v-if="showSlipModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" @click.self="showSlipModal = false">
      <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full overflow-hidden shadow-2xl relative">
        <button @click="showSlipModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
          <Icon icon="solar:close-circle-bold" class="w-6 h-6" />
        </button>
        <div class="p-6">
          <h3 class="font-bold text-gray-900 dark:text-white mb-4">สลิปหลักฐานการโอนเงิน</h3>
          <div class="aspect-[3/4] rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <img :src="selectedSlipUrl" class="w-full h-full object-contain" />
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
