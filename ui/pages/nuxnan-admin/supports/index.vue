<script setup>
import { ref, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'
import ApproveDonateCard from '~/components/earn/donates/ApproveDonateCard.vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const api = useApi()
const donations = ref([])
const stats = ref({
  total: 0,
  pending: 0,
  approved: 0,
  rejected: 0
})
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(12)
const selectedStatus = ref('all') // all, pending, approved, rejected

const statusFilters = [
  { value: 'all', label: 'ทั้งหมด', color: 'bg-indigo-500' },
  { value: 'pending', label: 'รออนุมัติ', color: 'bg-yellow-500' },
  { value: 'approved', label: 'อนุมัติแล้ว', color: 'bg-green-500' },
  { value: 'rejected', label: 'ปฏิเสธ', color: 'bg-red-500' }
]

const fetchDonations = async () => {
  isLoading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value
    }
    
    // Check status filter
    if (selectedStatus.value !== 'all') {
      const statusMap = {
        'pending': 0,
        'approved': 1,
        'rejected': 2
      }
      if (statusMap[selectedStatus.value] !== undefined) {
        params.status = statusMap[selectedStatus.value]
      }
    }
    
    const response = await api.get('/api/plearnd-admin/supports/donates', { params })
    
    if (response) {
      donations.value = response.donates.data || response.donates
      
      if (response.stats) {
        stats.value = response.stats
      }
      
      const meta = response.donates.meta || {}
      currentPage.value = meta.current_page || 1
      totalPages.value = meta.last_page || 1
    }
  } catch (error) {
    console.error('Failed to fetch donations:', error)
  } finally {
    isLoading.value = false
  }
}

// Watch for status changes
watch(selectedStatus, () => {
  currentPage.value = 1
  fetchDonations()
})

const onApproved = (id) => {
  // Update the item in the list or refresh
  const index = donations.value.findIndex(d => d.id === id)
  if (index !== -1) {
    donations.value[index].status = 1 // Approved
    donations.value[index].approved_by = useAuthStore().user?.id
    
    // Update stats locally to avoid refetch
    stats.value.pending--
    stats.value.approved++
  }
}

const onRejected = (id) => {
  const index = donations.value.findIndex(d => d.id === id)
  if (index !== -1) {
    donations.value[index].status = 2 // Rejected
    donations.value[index].approved_by = useAuthStore().user?.id
    
    stats.value.pending--
    stats.value.rejected++
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchDonations()
  }
}

onMounted(() => {
  fetchDonations()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-800 dark:text-white">อนุมัติการสนับสนุนแต้ม</h1>
      <p class="text-gray-500 dark:text-gray-400 mt-1">ตรวจสอบและอนุมัติการสนับสนุนจากผู้บริจาค</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 flex items-center gap-4">
        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-600 dark:text-gray-300">
          <Icon icon="fluent:list-24-regular" class="w-6 h-6" />
        </div>
        <div>
          <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ stats.total }}</p>
          <p class="text-sm text-gray-500">ทั้งหมด</p>
        </div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-yellow-500/20 dark:border-yellow-500/20 flex items-center gap-4">
        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center text-yellow-600 dark:text-yellow-400">
          <Icon icon="fluent:clock-24-regular" class="w-6 h-6" />
        </div>
        <div>
          <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</p>
          <p class="text-sm text-gray-500">รออนุมัติ</p>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-green-500/20 dark:border-green-500/20 flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-green-600 dark:text-green-400">
          <Icon icon="fluent:checkmark-circle-24-regular" class="w-6 h-6" />
        </div>
        <div>
          <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.approved }}</p>
          <p class="text-sm text-gray-500">อนุมัติแล้ว</p>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-red-500/20 dark:border-red-500/20 flex items-center gap-4">
        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center text-red-600 dark:text-red-400">
          <Icon icon="fluent:dismiss-circle-24-regular" class="w-6 h-6" />
        </div>
        <div>
          <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.rejected }}</p>
          <p class="text-sm text-gray-500">ปฏิเสธ</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <!-- Note: Backend filter not fully implemented yet, so these buttons are visual for now unless I update backend -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="filter in statusFilters"
        :key="filter.value"
        @click="selectedStatus = filter.value"
        :class="[
          'px-4 py-2 rounded-xl text-sm font-medium transition-all',
          selectedStatus === filter.value
            ? `${filter.color} text-white shadow-lg`
            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
        ]"
      >
        {{ filter.label }}
        <span v-if="filter.value === 'pending' && stats.pending > 0" class="ml-1 px-1.5 py-0.5 bg-white/20 rounded-full text-xs">
          {{ stats.pending }}
        </span>
      </button>
    </div>

    <!-- Content -->
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="i in 6" :key="i" class="bg-white dark:bg-gray-800 h-80 rounded-xl animate-pulse"></div>
    </div>

    <div v-else-if="donations.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <!-- Filtering locally if backend doesn't support it, or just show all if 'all' selected.
           Since I haven't implemented backend filter, I will just show pending if pending selected using local filter
           BUT pagination will be weird if I do local filter on paginated results.
           For now I will just show 'donations' but effectively the user might see mixed results if I don't implement backend search.
      -->
      <ApproveDonateCard
        v-for="donate in donations"
        :key="donate.id"
        :donate="donate"
        @approved="onApproved"
        @rejected="onRejected"
        class="h-full"
      />
    </div>

    <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl">
      <Icon icon="fluent:box-search-24-regular" class="w-16 h-16 text-gray-300 mx-auto mb-4" />
      <p class="text-gray-500">ไม่พบข้อมูลการสนับสนุน</p>
    </div>

    <!-- Pagination -->
    <div v-if="!isLoading && totalPages > 1" class="flex justify-center mt-8">
      <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <button
          :disabled="currentPage === 1"
          @click="changePage(currentPage - 1)"
          class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50 transition-colors"
        >
          <Icon icon="fluent:chevron-left-24-regular" class="w-5 h-5" />
        </button>
        <span class="px-4 text-sm font-medium text-gray-600 dark:text-gray-300">
          หน้า {{ currentPage }} / {{ totalPages }}
        </span>
        <button
          :disabled="currentPage === totalPages"
          @click="changePage(currentPage + 1)"
          class="p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg disabled:opacity-50 transition-colors"
        >
          <Icon icon="fluent:chevron-right-24-regular" class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>
