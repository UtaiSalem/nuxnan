<template>
  <div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            <Icon icon="mdi:account-child" class="w-7 h-7 mr-2 inline text-blue-600" />
            จัดการผู้ปกครอง
          </h1>
          <p class="text-gray-500 mt-1">รายการผู้ปกครองทั้งหมดของนักเรียนในโรงเรียน</p>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">ผู้ปกครองทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
            <Icon icon="mdi:account-group" class="w-6 h-6 text-blue-600" />
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">บิดา</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.by_type?.father || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-xl">
            👨
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">มารดา</p>
            <p class="text-2xl font-bold text-pink-600">{{ stats.by_type?.mother || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center text-xl">
            👩
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-xl shadow-sm border p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">มีข้อมูลติดต่อ</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.with_contact || 0 }}</p>
          </div>
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
            <Icon icon="mdi:phone-check" class="w-6 h-6 text-green-600" />
          </div>
        </div>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-sm border p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon icon="mdi:magnify" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาชื่อผู้ปกครองหรือเบอร์โทร..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @input="debouncedSearch"
          />
        </div>
        
        <select
          v-model="filterType"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          @change="loadGuardians"
        >
          <option value="">ทุกประเภท</option>
          <option value="father">บิดา</option>
          <option value="mother">มารดา</option>
          <option value="grandfather">ปู่/ตา</option>
          <option value="grandmother">ย่า/ยาย</option>
          <option value="other">อื่นๆ</option>
        </select>
      </div>
    </div>

    <!-- Guardian List -->
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
      <!-- Loading -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600"></div>
      </div>

      <!-- Empty -->
      <div v-else-if="guardians.length === 0" class="text-center py-12">
        <Icon icon="mdi:account-search-outline" class="w-16 h-16 mx-auto text-gray-300 mb-4" />
        <p class="text-gray-500">ไม่พบข้อมูลผู้ปกครอง</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ปกครอง</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">ประเภท</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">นักเรียน</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">เบอร์โทร</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="guardian in guardians"
              :key="guardian.id"
              class="hover:bg-gray-50"
            >
              <td class="px-4 py-4">
                <div class="flex items-center">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3" :class="getTypeColor(guardian.guardian_type)">
                    {{ getTypeIcon(guardian.guardian_type) }}
                  </div>
                  <div>
                    <div class="font-medium text-gray-900">{{ guardian.full_name }}</div>
                    <div v-if="guardian.is_primary_contact" class="text-xs text-green-600">
                      <Icon icon="mdi:check-circle" class="w-3 h-3 inline" />
                      ผู้ติดต่อหลัก
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-4 hidden sm:table-cell">
                <span class="px-2 py-1 text-xs rounded-full" :class="getTypeBadgeClass(guardian.guardian_type)">
                  {{ getTypeLabel(guardian.guardian_type) }}
                </span>
              </td>
              <td class="px-4 py-4 hidden md:table-cell">
                <div v-if="guardian.student" class="text-sm">
                  <div class="font-medium text-gray-900">{{ guardian.student.name }}</div>
                  <div class="text-gray-500">{{ guardian.student.student_id }}</div>
                </div>
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-4 py-4 hidden lg:table-cell text-sm text-gray-600">
                {{ guardian.primary_phone || '-' }}
              </td>
              <td class="px-4 py-4 text-center">
                <div class="flex items-center justify-center space-x-1">
                  <NuxtLink
                    v-if="guardian.student"
                    :to="`/academies/${academyName}/admin/students/${guardian.student.id}`"
                    class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                    title="ดูข้อมูลนักเรียน"
                  >
                    <Icon icon="mdi:account-search" class="w-5 h-5" />
                  </NuxtLink>
                  <button
                    @click="callGuardian(guardian)"
                    v-if="guardian.primary_phone"
                    class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                    title="โทร"
                  >
                    <Icon icon="mdi:phone" class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
        <div class="text-sm text-gray-500">
          แสดง {{ (pagination.current_page - 1) * pagination.per_page + 1 }} - {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} จาก {{ pagination.total }} รายการ
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

const loading = ref(false)
const searchQuery = ref('')
const filterType = ref('')
const guardians = ref<any[]>([])
const stats = ref<any>({})
const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 20,
  total: 0,
})

let searchTimeout: any = null

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

// Load guardians
const loadGuardians = async (page = 1) => {
  if (!academyId.value) return
  
  loading.value = true
  try {
    const params = new URLSearchParams()
    params.append('page', page.toString())
    params.append('per_page', '20')
    if (searchQuery.value) params.append('search', searchQuery.value)
    if (filterType.value) params.append('type', filterType.value)

    const response = await $api.get(`/academies/${academyId.value}/guardians?${params.toString()}`)
    if (response.data.success) {
      guardians.value = response.data.guardians
      pagination.value = response.data.pagination
    }
  } catch (error) {
    console.error('Error loading guardians:', error)
  } finally {
    loading.value = false
  }
}

// Load statistics
const loadStats = async () => {
  if (!academyId.value) return
  
  try {
    const response = await $api.get(`/academies/${academyId.value}/guardians/statistics`)
    if (response.data.success) {
      stats.value = response.data.statistics
    }
  } catch (error) {
    console.error('Error loading stats:', error)
  }
}

// Debounced search
const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadGuardians()
  }, 300)
}

// Change page
const changePage = (page: number) => {
  loadGuardians(page)
}

// Call guardian
const callGuardian = (guardian: any) => {
  if (guardian.primary_phone) {
    window.open(`tel:${guardian.primary_phone}`, '_self')
  }
}

// Helper functions
const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    father: 'บิดา',
    mother: 'มารดา',
    grandfather: 'ปู่/ตา',
    grandmother: 'ย่า/ยาย',
    uncle: 'ลุง/อา',
    aunt: 'ป้า/น้า',
    sibling: 'พี่/น้อง',
    other: 'อื่นๆ',
  }
  return labels[type] || type
}

const getTypeIcon = (type: string) => {
  const icons: Record<string, string> = {
    father: '👨',
    mother: '👩',
    grandfather: '👴',
    grandmother: '👵',
    uncle: '👨',
    aunt: '👩',
    sibling: '🧑',
    other: '👤',
  }
  return icons[type] || '👤'
}

const getTypeColor = (type: string) => {
  const colors: Record<string, string> = {
    father: 'bg-blue-500',
    mother: 'bg-pink-500',
    grandfather: 'bg-gray-500',
    grandmother: 'bg-purple-500',
    uncle: 'bg-indigo-500',
    aunt: 'bg-rose-500',
    sibling: 'bg-teal-500',
    other: 'bg-gray-400',
  }
  return colors[type] || 'bg-gray-400'
}

const getTypeBadgeClass = (type: string) => {
  const classes: Record<string, string> = {
    father: 'bg-blue-100 text-blue-700',
    mother: 'bg-pink-100 text-pink-700',
    grandfather: 'bg-gray-100 text-gray-700',
    grandmother: 'bg-purple-100 text-purple-700',
    other: 'bg-gray-100 text-gray-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

onMounted(async () => {
  await loadAcademyInfo()
  loadGuardians()
  loadStats()
})
</script>
