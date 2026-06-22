<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

// State
const courses = ref([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const totalCourses = ref(0)

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
  if (append) {
    isLoadingMore.value = true
  } else {
    isLoading.value = true
  }
  
  try {
    const token = useCookie('token')
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: perPage.value.toString(),
      ...(searchQuery.value && { search: searchQuery.value }),
      ...(selectedStatus.value !== 'all' && { status: selectedStatus.value })
    })

    const response = await $fetch(`${apiBase}/api/admin/courses?${params}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      const newCourses = response.data.data || response.data
      
      if (append) {
        courses.value = [...courses.value, ...newCourses]
      } else {
        courses.value = newCourses
      }
      
      totalPages.value = response.data.last_page || 1
      totalCourses.value = response.data.total || courses.value.length
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
  const badges = {
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    draft: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    archived: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  return badges[status] || badges.draft
}

// Get status label
const getStatusLabel = (status: string) => {
  const labels = {
    published: 'เผยแพร่',
    draft: 'ฉบับร่าง',
    archived: 'เก็บถาวร'
  }
  return labels[status] || 'ไม่ทราบ'
}

onMounted(() => {
  fetchCourses()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">จัดการคอร์สเรียน</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">
          คอร์สทั้งหมด {{ totalCourses.toLocaleString() }} คอร์ส
        </p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/courses/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างคอร์สใหม่
      </NuxtLink>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาคอร์ส..."
            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 focus:border-transparent"
            @keyup.enter="handleSearch"
          />
        </div>

        <select
          v-model="selectedStatus"
          class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500"
          @change="handleSearch"
        >
          <option v-for="status in statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <button
          @click="handleSearch"
          class="px-4 py-2.5 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white transition-colors"
        >
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Courses Grid -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-hopeui-primary-600 animate-spin mx-auto" />
        <p class="text-slate-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <!-- Courses List -->
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-700">
        <div
          v-for="course in courses"
          :key="course.id"
          class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
        >
          <div class="flex flex-col sm:flex-row gap-4">
            <!-- Course Image -->
            <div class="w-full sm:w-40 h-24 flex-shrink-0 bg-gradient-to-br from-hopeui-primary-100 to-purple-100 dark:from-hopeui-primary-900/30 dark:to-purple-900/30 rounded-xl overflow-hidden">
              <img
                v-if="course.cover_url"
                :src="course.cover_url"
                :alt="course.title"
                class="w-full h-full object-cover"
                @error="(e) => e.target.style.display = 'none'"
              />
              <div v-else class="w-full h-full flex items-center justify-center">
                <Icon icon="fluent:hat-graduation-24-regular" class="w-10 h-10 text-hopeui-primary-400 dark:text-hopeui-primary-500" />
              </div>
            </div>

            <!-- Course Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="font-semibold text-slate-800 dark:text-white line-clamp-1">
                    {{ course.title }}
                  </h3>
                  <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 line-clamp-2">
                    {{ course.description }}
                  </p>
                </div>
                <span :class="[getStatusBadge(course.status), 'px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap']">
                  {{ getStatusLabel(course.status) }}
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-slate-500 dark:text-slate-400">
                <div class="flex items-center gap-1">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4" />
                  <span>{{ course.user?.name || 'ไม่ระบุ' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                  <span>{{ course.members_count || course.enrolled_students || 0 }} ผู้เรียน</span>
                </div>
                <div class="flex items-center gap-1">
                  <Icon icon="fluent:money-24-regular" class="w-4 h-4" />
                  <span>฿{{ (course.price || 0).toLocaleString() }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 sm:flex-col sm:justify-center">
              <NuxtLink
                :to="`/nuxnan-admin/courses/${course.id}`"
                class="p-2 text-slate-500 hover:text-hopeui-primary-600 hover:bg-hopeui-primary-100 dark:hover:bg-hopeui-primary-900/30 rounded-lg transition-colors"
              >
                <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
              </NuxtLink>
              <NuxtLink
                :to="`/nuxnan-admin/courses/${course.id}/edit`"
                class="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
              >
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
              </NuxtLink>
              <button class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="courses.length === 0 && !isLoading" class="p-8 text-center">
          <Icon icon="fluent:hat-graduation-24-regular" class="w-12 h-12 text-slate-300 mx-auto" />
          <p class="text-slate-500 mt-2">ไม่พบคอร์สเรียน</p>
        </div>
        
        <!-- Load More Button -->
        <div v-if="hasMorePages" class="p-4 flex justify-center border-t border-slate-100 dark:border-slate-700">
          <button
            @click="loadMore"
            :disabled="isLoadingMore"
            class="px-6 py-2.5 bg-hopeui-primary-100 hover:bg-hopeui-primary-100/80 dark:bg-hopeui-primary-900/30 dark:hover:bg-hopeui-primary-900/50 text-hopeui-primary-600 dark:text-hopeui-primary-400 rounded-xl font-medium transition-colors flex items-center gap-2 disabled:opacity-50"
          >
            <Icon v-if="isLoadingMore" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
            <span v-if="isLoadingMore">กำลังโหลด...</span>
            <span v-else>โหลดเพิ่มเติม ({{ courses.length }}/{{ totalCourses }})</span>
          </button>
        </div>
        
        <!-- All Loaded Indicator -->
        <div v-else-if="courses.length > 0 && !isLoading" class="p-4 text-center text-slate-400 text-sm border-t border-slate-100 dark:border-slate-700">
          <Icon icon="fluent:checkmark-circle-24-regular" class="w-5 h-5 inline-block mr-1" />
          แสดงทั้งหมด {{ courses.length }} คอร์ส
        </div>
      </div>
    </div>
  </div>
</template>
