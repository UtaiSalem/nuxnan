<template>
  <div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          <Icon icon="mdi:history" class="w-7 h-7 mr-2 inline text-purple-600" />
          ประวัติกิจกรรมสมาชิก
        </h1>
        <p class="text-gray-500 mt-1">ติดตามการเปลี่ยนแปลงและกิจกรรมต่างๆ ของสมาชิก</p>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">กิจกรรมทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
            <Icon icon="mdi:history" class="w-6 h-6 text-purple-600" />
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">สมาชิกใหม่</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.by_action?.approve || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <Icon icon="mdi:account-plus" class="w-6 h-6 text-green-600" />
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">เปลี่ยนบทบาท</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.by_action?.role_change || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <Icon icon="mdi:badge-account" class="w-6 h-6 text-blue-600" />
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">ถูกระงับ</p>
            <p class="text-2xl font-bold text-orange-600">{{ stats.by_action?.suspend || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
            <Icon icon="mdi:account-lock" class="w-6 h-6 text-orange-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <select
          v-model="filterAction"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
          @change="loadLogs"
        >
          <option value="">ทุกกิจกรรม</option>
          <option v-for="action in availableActions" :key="action.value" :value="action.value">
            {{ action.label }}
          </option>
        </select>

        <input
          v-model="filterFromDate"
          type="date"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
          @change="loadLogs"
        />

        <input
          v-model="filterToDate"
          type="date"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
          @change="loadLogs"
        />

        <button
          v-if="filterAction || filterFromDate || filterToDate"
          @click="resetFilters"
          class="px-4 py-2 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <Icon icon="mdi:filter-remove" class="w-5 h-5 mr-1 inline" />
          ล้างตัวกรอง
        </button>
      </div>
    </div>

    <!-- Activity Timeline -->
    <div class="bg-white rounded-xl shadow-sm border">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-purple-600"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="logs.length === 0" class="text-center py-12">
        <Icon icon="mdi:timeline-clock-outline" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
        <p class="text-gray-500">ไม่พบประวัติกิจกรรม</p>
      </div>

      <!-- Timeline -->
      <div v-else class="divide-y divide-gray-100">
        <div
          v-for="log in logs"
          :key="log.id"
          class="p-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-start space-x-4">
            <!-- Icon -->
            <div
              class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center"
              :class="getIconBgClass(log.color)"
            >
              <Icon :icon="log.icon" class="w-5 h-5" :class="getIconColorClass(log.color)" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <!-- Performer -->
                <span v-if="log.user" class="font-medium text-gray-900">
                  {{ log.user.name }}
                </span>
                <span v-else class="text-gray-500">ระบบ</span>

                <!-- Action description -->
                <span class="text-gray-600">{{ log.description }}</span>

                <!-- Target user -->
                <span v-if="log.target_user && log.target_user.id !== log.user?.id" class="font-medium text-gray-900">
                  {{ log.target_user.name }}
                </span>
              </div>

              <!-- Role change details -->
              <div v-if="log.action === 'role_change' && log.old_values && log.new_values" class="mt-1 text-sm text-gray-500">
                <span class="bg-gray-100 px-2 py-0.5 rounded">{{ log.old_values.role || 'ไม่มี' }}</span>
                <Icon icon="mdi:arrow-right" class="w-4 h-4 mx-1 inline" />
                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">{{ log.new_values.role }}</span>
              </div>

              <!-- Bulk action details -->
              <div v-if="log.action === 'bulk_action' && log.new_values" class="mt-1 text-sm text-gray-500">
                ดำเนินการกับสมาชิก {{ log.new_values.count }} คน
              </div>

              <!-- Timestamp -->
              <div class="mt-1 text-xs text-gray-400">
                <Icon icon="mdi:clock-outline" class="w-3 h-3 inline mr-1" />
                {{ log.created_at_human }}
                <span class="mx-1">·</span>
                {{ formatDate(log.created_at) }}
              </div>
            </div>

            <!-- Avatars -->
            <div class="flex -space-x-2">
              <img
                v-if="log.user?.avatar"
                :src="log.user.avatar"
                :alt="log.user.name"
                class="w-8 h-8 rounded-full border-2 border-white object-cover"
              />
              <div v-else-if="log.user" class="w-8 h-8 rounded-full border-2 border-white bg-gray-200 flex items-center justify-center text-xs font-medium text-gray-600">
                {{ log.user.name?.charAt(0) }}
              </div>
              
              <img
                v-if="log.target_user?.avatar && log.target_user?.id !== log.user?.id"
                :src="log.target_user.avatar"
                :alt="log.target_user.name"
                class="w-8 h-8 rounded-full border-2 border-white object-cover"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
        <div class="text-sm text-gray-500">
          หน้า {{ pagination.current_page }} / {{ pagination.last_page }} ({{ pagination.total }} รายการ)
        </div>
        <div class="flex space-x-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 border rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ก่อนหน้า
          </button>
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 border rounded-lg text-sm hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ถัดไป
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, onMounted } from 'vue'

const route = useRoute()
const { $api } = useNuxtApp()

const academyName = route.params.name as string
const academyId = ref<number | null>(null)

// State
const logs = ref<any[]>([])
const stats = ref<any>({})
const availableActions = ref<any[]>([])
const loading = ref(false)

// Filters
const filterAction = ref('')
const filterFromDate = ref('')
const filterToDate = ref('')

// Pagination
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

// Load academy info
const loadAcademyInfo = async () => {
  try {
    const response = await $api.get(`/academies/${academyName}`)
    if (response.data) {
      academyId.value = response.data.id
    }
  } catch (error) {
    console.error('Error loading academy:', error)
  }
}

// Load logs
const loadLogs = async (page = 1) => {
  if (!academyId.value) return
  
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', page.toString())
    if (filterAction.value) params.append('action', filterAction.value)
    if (filterFromDate.value) params.append('from_date', filterFromDate.value)
    if (filterToDate.value) params.append('to_date', filterToDate.value)

    const response = await $api.get(`/academies/${academyId.value}/activity-log?${params.toString()}`)
    if (response.data.success) {
      logs.value = response.data.logs
      pagination.value = response.data.pagination
    }
  } catch (error) {
    console.error('Error loading logs:', error)
  } finally {
    loading.value = false
  }
}

// Load statistics
const loadStats = async () => {
  if (!academyId.value) return
  
  try {
    const response = await $api.get(`/academies/${academyId.value}/activity-log/statistics`)
    if (response.data.success) {
      stats.value = response.data.statistics
    }
  } catch (error) {
    console.error('Error loading stats:', error)
  }
}

// Load available actions
const loadAvailableActions = async () => {
  try {
    const response = await $api.get('/academies/activity-log/actions')
    if (response.data.success) {
      availableActions.value = response.data.actions
    }
  } catch (error) {
    console.error('Error loading actions:', error)
  }
}

// Reset filters
const resetFilters = () => {
  filterAction.value = ''
  filterFromDate.value = ''
  filterToDate.value = ''
  loadLogs()
}

// Change page
const changePage = (page: number) => {
  loadLogs(page)
}

// Helper functions
const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getIconBgClass = (color: string) => {
  const classes: Record<string, string> = {
    blue: 'bg-blue-100',
    green: 'bg-green-100',
    red: 'bg-red-100',
    orange: 'bg-orange-100',
    purple: 'bg-purple-100',
    indigo: 'bg-indigo-100',
    teal: 'bg-teal-100',
    gray: 'bg-gray-100',
  }
  return classes[color] || 'bg-gray-100'
}

const getIconColorClass = (color: string) => {
  const classes: Record<string, string> = {
    blue: 'text-blue-600',
    green: 'text-green-600',
    red: 'text-red-600',
    orange: 'text-orange-600',
    purple: 'text-purple-600',
    indigo: 'text-indigo-600',
    teal: 'text-teal-600',
    gray: 'text-gray-600',
  }
  return classes[color] || 'text-gray-600'
}

onMounted(async () => {
  await loadAcademyInfo()
  loadAvailableActions()
  loadLogs()
  loadStats()
})
</script>
