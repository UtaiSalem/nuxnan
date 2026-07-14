<script setup lang="ts">
/**
 * Academy Admin - Home Visits Management
 * หน้าจัดการการเยี่ยมบ้านนักเรียนภายใต้การบริหารของโรงเรียน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const academy = ref<any>(null)
const visits = ref<any[]>([])
const zones = ref<any[]>([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedZone = ref('')
const selectedStatus = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const currentPage = ref(1)
const totalPages = ref(1)

// Academy Role
const academyId = ref<number | null>(null)
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Statistics
const stats = ref({
  totalVisits: 0,
  completedVisits: 0,
  pendingVisits: 0,
  totalStudents: 0,
  visitedStudents: 0,
  visitRate: 0
})

// Visit status options
const statusOptions = [
  { value: '', label: 'ทุกสถานะ' },
  { value: 'pending', label: 'รอดำเนินการ' },
  { value: 'scheduled', label: 'นัดหมายแล้ว' },
  { value: 'completed', label: 'เยี่ยมแล้ว' },
  { value: 'cancelled', label: 'ยกเลิก' }
]

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()
      
      if (!isAdmin.value && !can('home_visits.view')) {
        navigateTo(`/academies/${academyName.value}`)
        return
      }
      
      await Promise.all([
        fetchStatistics(),
        fetchZones(),
        fetchVisits()
      ])
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStatistics = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/home-visits/statistics`)
    if (response.success) {
      stats.value = response.statistics
    }
  } catch (err) {
    console.error('Failed to fetch statistics:', err)
  }
}

const fetchZones = async () => {
  if (!academyId.value) return
  
  try {
    const response: any = await api.get(`/api/academies/${academyId.value}/home-visits/zones`)
    if (response.success) {
      zones.value = response.zones || []
    }
  } catch (err) {
    console.error('Failed to fetch zones:', err)
  }
}

const fetchVisits = async () => {
  if (!academyId.value) return
  
  try {
    const params = new URLSearchParams()
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (selectedZone.value) params.append('zone_id', selectedZone.value)
    if (selectedStatus.value) params.append('status', selectedStatus.value)
    if (dateFrom.value) params.append('date_from', dateFrom.value)
    if (dateTo.value) params.append('date_to', dateTo.value)
    params.append('page', currentPage.value.toString())
    
    const response: any = await api.get(`/api/academies/${academyId.value}/home-visits/admin/visits?${params.toString()}`)
    if (response.success) {
      visits.value = response.visits?.data || response.visits || []
      totalPages.value = response.visits?.last_page || 1
    }
  } catch (err) {
    console.error('Failed to fetch visits:', err)
  }
}

const handleSearch = () => {
  currentPage.value = 1
  fetchVisits()
}

const handleFilter = () => {
  currentPage.value = 1
  fetchVisits()
}

const viewVisit = (visit: any) => {
  if (visit.student?.id) {
    navigateTo(`/academies/${academyName.value}/students/${visit.student.id}/profile?tab=homevisit`)
  } else {
    navigateTo(`/academies/${academyName.value}/admin/home-visits/${visit.id}`)
  }
}

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
    scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'รอดำเนินการ',
    scheduled: 'นัดหมายแล้ว',
    completed: 'เยี่ยมแล้ว',
    cancelled: 'ยกเลิก'
  }
  return labels[status] || status
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">การเยี่ยมบ้านนักเรียน</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">จัดการข้อมูลการเยี่ยมบ้านนักเรียน</p>
        </div>
        
        <div class="flex gap-2">
          <NuxtLink 
            :to="`/academies/${academyName}/admin/home-visits/zones`"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon name="fluent:map-24-regular" class="w-5 h-5" />
            <span>จัดการโซน</span>
          </NuxtLink>
          
          <NuxtLink 
            :to="`/academies/${academyName}/admin/home-visits/export`"
            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2"
          >
            <Icon name="fluent:arrow-download-24-regular" class="w-5 h-5" />
            <span>ส่งออกข้อมูล</span>
          </NuxtLink>
          
          <NuxtLink 
            :to="`/academies/${academyName}/admin/home-visits/create`"
            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center gap-2"
          >
            <Icon name="fluent:add-24-regular" class="w-5 h-5" />
            <span>เพิ่มการเยี่ยม</span>
          </NuxtLink>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
              <Icon name="fluent:home-24-filled" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalVisits }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">การเยี่ยมทั้งหมด</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
              <Icon name="fluent:checkmark-circle-24-filled" class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.completedVisits }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">เยี่ยมแล้ว</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-xl">
              <Icon name="fluent:clock-24-filled" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pendingVisits }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-xl">
              <Icon name="fluent:people-24-filled" class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.totalStudents }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียนทั้งหมด</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-cyan-100 dark:bg-cyan-900/50 rounded-xl">
              <Icon name="fluent:person-home-24-filled" class="w-6 h-6 text-cyan-600 dark:text-cyan-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.visitedStudents }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">นักเรียนที่เยี่ยมแล้ว</p>
            </div>
          </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl">
              <Icon name="fluent:chart-multiple-24-filled" class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.visitRate }}%</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">อัตราการเยี่ยม</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <Icon name="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="ค้นหาชื่อนักเรียน, ครู..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                @keyup.enter="handleSearch"
              />
            </div>
          </div>
          
          <div class="flex flex-wrap gap-2">
            <select
              v-model="selectedZone"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              @change="handleFilter"
            >
              <option value="">ทุกโซน</option>
              <option v-for="zone in zones" :key="zone.id" :value="zone.id">{{ zone.zone_name }}</option>
            </select>
            
            <select
              v-model="selectedStatus"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              @change="handleFilter"
            >
              <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
            
            <input
              v-model="dateFrom"
              type="date"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              @change="handleFilter"
            />
            
            <input
              v-model="dateTo"
              type="date"
              class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
              @change="handleFilter"
            />
            
            <button
              @click="handleSearch"
              class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
            >
              ค้นหา
            </button>
          </div>
        </div>
      </div>

      <!-- Visits Table -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">นักเรียน</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ระดับชั้น</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">ครูผู้เยี่ยม</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">วันที่เยี่ยม</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">โซน</th>
                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400">สถานะ</th>
                <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">จัดการ</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr 
                v-for="visit in visits" 
                :key="visit.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
              >
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img 
                      :src="visit.student?.profile_image || '/images/default-avatar.png'"
                      :alt="visit.student?.first_name_th"
                      class="w-10 h-10 rounded-full object-cover"
                    />
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ visit.student?.first_name_th }} {{ visit.student?.last_name_th }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ visit.student?.student_id }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  ม.{{ visit.student?.class_level }}/{{ visit.student?.class_section }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                  {{ visit.teacher?.name || '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {{ formatDate(visit.visit_date) }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                  {{ visit.zone?.name || '-' }}
                </td>
                <td class="px-4 py-3">
                  <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(visit.status)]">
                    {{ getStatusLabel(visit.status) }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex justify-center gap-2">
                    <button
                      @click="viewVisit(visit)"
                      class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/50 rounded-lg"
                      title="ดูรายละเอียด"
                    >
                      <Icon name="fluent:eye-24-regular" class="w-5 h-5" />
                    </button>
                  </div>
                </td>
              </tr>
              
              <tr v-if="visits.length === 0">
                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                  <Icon name="fluent:home-24-regular" class="w-12 h-12 mx-auto mb-4 opacity-50" />
                  <p>ไม่พบข้อมูลการเยี่ยมบ้าน</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            หน้า {{ currentPage }} จาก {{ totalPages }}
          </p>
          <div class="flex gap-2">
            <button
              @click="currentPage--; fetchVisits()"
              :disabled="currentPage <= 1"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50"
            >
              ก่อนหน้า
            </button>
            <button
              @click="currentPage++; fetchVisits()"
              :disabled="currentPage >= totalPages"
              class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50"
            >
              ถัดไป
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
