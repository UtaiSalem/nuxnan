<script setup lang="ts">
/**
 * Academy Admin - Courses Management
 * หน้าจัดการรายวิชาของสถาบัน
 */
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false,
  middleware: 'auth',
  ssr: false
})

const route = useRoute()
const api = useApi()
const academyName = computed(() => route.params.name as string)

// State
const courses = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const totalCourses = ref(0)

// Academy data
const academyId = ref<number | null>(null)

// Computed - Can load more?
const hasMorePages = computed(() => currentPage.value < totalPages.value)

// Status options
const statuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'published', label: 'เผยแพร่แล้ว' },
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'archived', label: 'เก็บถาวร' }
]

// Fetch courses
const fetchCourses = async (append = false) => {
  if (!academyId.value) return
  
  if (append) {
    isLoadingMore.value = true
  } else {
    isLoading.value = true
  }
  
  try {
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedStatus.value !== 'all' && { status: selectedStatus.value })
    })

    const response: any = await api.get(`/api/academies/${academyId.value}/courses?${params}`)

    if (response.success) {
      const newCourses = response.courses?.data || response.courses || []
      
      if (append) {
        courses.value = [...courses.value, ...newCourses]
      } else {
        courses.value = newCourses
      }
      
      totalPages.value = response.courses?.last_page || 1
      totalCourses.value = response.courses?.total || courses.value.length
    }
  } catch (error) {
    console.error('Failed to fetch courses:', error)
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Load more courses
const loadMore = () => {
  if (hasMorePages.value && !isLoadingMore.value) {
    currentPage.value++
    fetchCourses(true)
  }
}

// Handle search
const handleSearch = () => {
  currentPage.value = 1
  courses.value = []
  fetchCourses()
}

// Get status badge class
const getStatusBadge = (status: string) => {
  const badges: Record<string, string> = {
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    draft: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    archived: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
  return badges[status] || badges.draft
}

// Get status label
const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    published: 'เผยแพร่',
    draft: 'ฉบับร่าง',
    archived: 'เก็บถาวร'
  }
  return labels[status] || 'ไม่ทราบ'
}

onMounted(async () => {
  try {
    // Get academy ID first
    const response: any = await api.get(`/api/academies/${encodeURIComponent(academyName.value)}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchCourses()
    }
  } catch (err: any) {
    console.error('Failed to load academy:', err)
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการรายวิชา</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          รายวิชาทั้งหมด {{ totalCourses.toLocaleString() }} รายวิชา
        </p>
      </div>
      <NuxtLink
        :to="`/academies/${academyName}/admin/courses/create`"
        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างรายวิชาใหม่
      </NuxtLink>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหารายวิชา..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @keyup.enter="handleSearch"
          />
        </div>

        <select
          v-model="selectedStatus"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
          @change="handleSearch"
        >
          <option v-for="status in statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <button
          @click="handleSearch"
          class="px-4 py-2.5 bg-primary-600 hover:bg-primary-700 rounded-xl text-white transition-colors"
        >
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Courses Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-primary-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Courses List -->
      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div
          v-for="course in courses"
          :key="course.id"
          class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
        >
          <div class="flex flex-col sm:flex-row gap-4">
            <!-- Course Image -->
            <div class="w-full sm:w-40 h-24 flex-shrink-0 bg-gradient-to-br from-primary-100 to-purple-100 dark:from-primary-900/30 dark:to-purple-900/30 rounded-xl overflow-hidden">
              <img
                v-if="course.cover_url || course.cover_image"
                :src="course.cover_url || course.cover_image"
                :alt="course.title"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <Icon icon="fluent:hat-graduation-24-regular" class="w-10 h-10 text-primary-400 dark:text-primary-500" />
              </div>
            </div>

            <!-- Course Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="font-semibold text-gray-800 dark:text-white line-clamp-1">
                    {{ course.title || course.name }}
                  </h3>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                    {{ course.description }}
                  </p>
                </div>
                <span :class="[getStatusBadge(course.status), 'px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap']">
                  {{ getStatusLabel(course.status) }}
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                  <span>{{ course.user?.name || course.instructor?.name || 'ไม่ระบุ' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                  <span>{{ course.members_count || course.enrolled_students || 0 }} ผู้เรียน</span>
                </div>
                <div v-if="course.price > 0" class="flex items-center gap-1">
                  <Icon icon="fluent:money-24-regular" class="w-4 h-4" />
                  <span>฿{{ (course.price || 0).toLocaleString() }}</span>
                </div>
                <div v-else class="flex items-center gap-1 text-green-600">
                  <Icon icon="fluent:gift-24-regular" class="w-4 h-4" />
                  <span>ฟรี</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 sm:flex-col sm:justify-center">
              <NuxtLink
                :to="`/courses/${course.name || course.slug}`"
                class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-lg transition-colors"
                title="ดูรายวิชา"
              >
                <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
              </NuxtLink>
              <NuxtLink
                :to="`/academies/${academyName}/admin/courses/${course.id}/edit`"
                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                title="แก้ไข"
              >
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
              </NuxtLink>
              <button 
                class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                title="ลบ"
              >
                <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="courses.length === 0 && !isLoading" class="p-8 text-center">
          <Icon icon="fluent:hat-graduation-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
          <p class="text-gray-500 mt-2">ไม่พบรายวิชา</p>
          <NuxtLink
            :to="`/academies/${academyName}/admin/courses/create`"
            class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-xl text-white transition-colors"
          >
            <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
            สร้างรายวิชาใหม่
          </NuxtLink>
        </div>
        
        <!-- Load More Button -->
        <div v-if="hasMorePages" class="p-4 flex justify-center border-t border-gray-100 dark:border-gray-700">
          <button
            @click="loadMore"
            :disabled="isLoadingMore"
            class="px-6 py-2.5 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/30 dark:hover:bg-primary-900/50 text-primary-600 dark:text-primary-400 rounded-xl font-medium transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            <Icon v-if="isLoadingMore" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
            <span v-if="isLoadingMore">กำลังโหลด...</span>
            <span v-else>โหลดเพิ่มเติม ({{ courses.length }}/{{ totalCourses }})</span>
          </button>
        </div>
        
        <!-- All Loaded Indicator -->
        <div v-else-if="courses.length > 0 && !isLoading" class="p-4 text-center text-gray-400 text-sm border-t border-gray-100 dark:border-gray-700">
          <Icon icon="fluent:checkmark-circle-24-regular" class="w-5 h-5 inline-block mr-1" />
          แสดงทั้งหมด {{ courses.length }} รายวิชา
        </div>
      </div>
    </div>
  </div>
</template>
