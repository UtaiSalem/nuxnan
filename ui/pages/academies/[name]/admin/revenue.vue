<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  ssr: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

const academyId = ref<number | null>(null)
const { can, isOwner, isAdmin, fetchMyRole } = useAcademyRole(academyId)
const { canViewRevenue, canManageRevenue, fetchRevenueDashboard, revenueDashboard } = useAcademyRevenue(academyId)

const activeTab = ref('overview')
const isLoading = ref(true)

const tabs = computed(() => [
  { id: 'overview', label: 'ภาพรวมรายได้', icon: 'fluent:wallet-24-regular', permission: 'finance.view' },
  { id: 'donations', label: 'รายการสนับสนุน', icon: 'fluent:heart-24-regular', permission: 'finance.view' },
  { id: 'campaigns', label: 'แคมเปญโฆษณา', icon: 'fluent:megaphone-24-regular', permission: 'finance.view' },
  { id: 'activity', label: 'ประวัติการจัดการ', icon: 'fluent:history-24-regular', permission: 'finance.view' },
])

const visibleTabs = computed(() => tabs.value.filter(tab => can(tab.permission) || isOwner.value || isAdmin.value))

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!can('finance.view') && !isOwner.value && !isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      
      await fetchRevenueDashboard()
    }
  } catch (err) {
    console.error('Failed to load revenue page:', err)
    navigateTo(`/academies/${academyName.value}/admin`)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div v-if="isLoading" class="flex items-center justify-center py-20">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mx-auto mb-4"></div>
      <p class="text-gray-500 dark:text-gray-400">กำลังโหลด...</p>
    </div>
  </div>

  <div v-else class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการรายได้</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">จัดการการสนับสนุน แต้ม และแคมเปญโฆษณา</p>
      </div>
      <div v-if="revenueDashboard" class="flex items-center gap-2">
        <span v-if="revenueDashboard.donations.pending_count > 0" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
          <Icon icon="fluent:person-clock-24-regular" class="w-4 h-4" />
          รอตรวจสอบ {{ revenueDashboard.donations.pending_count }} รายการ
        </span>
      </div>
    </div>

    <div v-if="visibleTabs.length > 1" class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex gap-6 overflow-x-auto">
        <button
          v-for="tab in visibleTabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center gap-2 whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors',
            activeTab === tab.id
              ? 'border-primary-500 text-primary-600 dark:text-primary-400'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          <Icon :icon="tab.icon" class="w-5 h-5" />
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <div v-if="activeTab === 'overview'">
      <AcademyAdminRevenueWidget v-if="academyId" :academy-id="academyId" />
    </div>

    <div v-else-if="activeTab === 'donations'">
      <AcademySupportReviewTable v-if="academyId" :academy-id="academyId" />
    </div>

    <div v-else-if="activeTab === 'campaigns'">
      <AcademyCampaignManagementTable v-if="academyId" :academy-id="academyId" />
    </div>

    <div v-else-if="activeTab === 'activity'">
      <AcademyRevenueActivity v-if="academyId" :academy-id="academyId" />
    </div>
  </div>
</template>
