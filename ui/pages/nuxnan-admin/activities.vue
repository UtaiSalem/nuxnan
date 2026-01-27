<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// State
const activities = ref([])
const isLoading = ref(true)
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(20)
const totalActivities = ref(0)
const selectedType = ref('all')

// Activity types
const activityTypes = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'login', label: 'เข้าสู่ระบบ' },
  { value: 'logout', label: 'ออกจากระบบ' },
  { value: 'register', label: 'ลงทะเบียน' },
  { value: 'purchase', label: 'ซื้อคอร์ส' },
  { value: 'complete', label: 'เรียนจบ' },
  { value: 'review', label: 'รีวิว' },
  { value: 'profile_update', label: 'แก้ไขโปรไฟล์' }
]

// Fetch activities
const fetchActivities = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(selectedType.value !== 'all' && { type: selectedType.value })
    })

    const response = await $fetch(`${apiBase}/api/admin/activities?${params}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      activities.value = response.data.data || response.data
      totalPages.value = response.data.last_page || 1
      totalActivities.value = response.data.total || activities.value.length
    }
  } catch (error) {
    console.error('Failed to fetch activities:', error)
    // Mock data for demo
    activities.value = [
      { id: 1, user: { name: 'John Doe', avatar: null }, type: 'login', description: 'เข้าสู่ระบบ', ip_address: '192.168.1.1', created_at: '2026-01-21T10:30:00' },
      { id: 2, user: { name: 'Jane Smith', avatar: null }, type: 'purchase', description: 'ซื้อคอร์ส Python เบื้องต้น', ip_address: '192.168.1.2', created_at: '2026-01-21T10:25:00' },
      { id: 3, user: { name: 'Bob Wilson', avatar: null }, type: 'register', description: 'ลงทะเบียนผู้ใช้ใหม่', ip_address: '192.168.1.3', created_at: '2026-01-21T10:20:00' },
    ]
    totalActivities.value = 3
  } finally {
    isLoading.value = false
  }
}

// Handle filter
const handleFilter = () => {
  currentPage.value = 1
  fetchActivities()
}

// Handle pagination
const goToPage = (page: number) => {
  currentPage.value = page
  fetchActivities()
}

// Get activity icon
const getActivityIcon = (type: string) => {
  const icons: Record<string, string> = {
    login: 'fluent:sign-out-24-regular',
    logout: 'fluent:door-arrow-right-24-regular',
    register: 'fluent:person-add-24-regular',
    purchase: 'fluent:cart-24-regular',
    complete: 'fluent:checkmark-circle-24-regular',
    review: 'fluent:star-24-regular',
    profile_update: 'fluent:person-edit-24-regular'
  }
  return icons[type] || 'fluent:activity-24-regular'
}

// Get activity color
const getActivityColor = (type: string) => {
  const colors: Record<string, string> = {
    login: 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    logout: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    register: 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    purchase: 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    complete: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    review: 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
    profile_update: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
  }
  return colors[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
}

// Format date
const formatDate = (dateStr: string) => {
  const date = new Date(dateStr)
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchActivities()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">กิจกรรมล่าสุด</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          กิจกรรมทั้งหมด {{ totalActivities.toLocaleString() }} รายการ
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Type Filter -->
        <select
          v-model="selectedType"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="handleFilter"
        >
          <option v-for="type in activityTypes" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>

        <button
          @click="fetchActivities"
          class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 transition-colors inline-flex items-center gap-2"
        >
          <Icon icon="fluent:arrow-sync-24-regular" class="w-5 h-5" />
          รีเฟรช
        </button>
      </div>
    </div>

    <!-- Activities List -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="activities.length === 0" class="p-8 text-center">
        <Icon icon="fluent:activity-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
        <p class="text-gray-500 mt-2">ไม่พบกิจกรรม</p>
      </div>

      <!-- Activities -->
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
        >
          <div class="flex items-start gap-4">
            <!-- Activity Icon -->
            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="getActivityColor(activity.type)">
              <Icon :icon="getActivityIcon(activity.type)" class="w-5 h-5" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span class="font-medium text-gray-800 dark:text-white">
                  {{ activity.user?.name || 'Unknown User' }}
                </span>
                <span class="text-gray-500 dark:text-gray-400">-</span>
                <span class="text-gray-600 dark:text-gray-300">{{ activity.description }}</span>
              </div>
              <div class="flex items-center gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                  <Icon icon="fluent:clock-24-regular" class="w-4 h-4" />
                  {{ formatDate(activity.created_at) }}
                </span>
                <span v-if="activity.ip_address" class="flex items-center gap-1">
                  <Icon icon="fluent:globe-24-regular" class="w-4 h-4" />
                  {{ activity.ip_address }}
                </span>
              </div>
            </div>

            <!-- Type Badge -->
            <div class="flex-shrink-0">
              <span class="px-2 py-1 text-xs rounded-lg" :class="getActivityColor(activity.type)">
                {{ activityTypes.find(t => t.value === activity.type)?.label || activity.type }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="p-4 border-t border-gray-100 dark:border-gray-700">
        <div class="flex justify-center gap-1">
          <button
            v-for="page in totalPages"
            :key="page"
            @click="goToPage(page)"
            class="w-10 h-10 rounded-lg text-sm font-medium transition-colors"
            :class="currentPage === page 
              ? 'bg-indigo-600 text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
