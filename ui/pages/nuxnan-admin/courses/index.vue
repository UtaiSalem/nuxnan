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
const searchQuery = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(10)
const totalCourses = ref(0)

// Status options
const statuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'published', label: 'เผยแพร่แล้ว' },
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'archived', label: 'เก็บถาวร' }
]

// Fetch courses
const fetchCourses = async () => {
  isLoading.value = true
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
      courses.value = response.data.data || response.data
      totalPages.value = response.data.last_page || 1
      totalCourses.value = response.data.total || courses.value.length
    }
  } catch (error) {
    console.error('Failed to fetch courses:', error)
  } finally {
    isLoading.value = false
  }
}

// Handle search
const handleSearch = () => {
  currentPage.value = 1
  fetchCourses()
}

// Get status badge class
const getStatusBadge = (status: string) => {
  const badges = {
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    draft: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    archived: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
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
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการคอร์สเรียน</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">
          คอร์สทั้งหมด {{ totalCourses.toLocaleString() }} คอร์ส
        </p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/courses/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างคอร์สใหม่
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
            placeholder="ค้นหาคอร์ส..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            @keyup.enter="handleSearch"
          />
        </div>

        <select
          v-model="selectedStatus"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @change="handleSearch"
        >
          <option v-for="status in statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <button
          @click="handleSearch"
          class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
        >
          ค้นหา
        </button>
      </div>
    </div>

    <!-- Courses Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
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
            <div class="w-full sm:w-40 h-24 flex-shrink-0">
              <img
                :src="course.thumbnail || '/storage/images/default-course.jpg'"
                :alt="course.title"
                class="w-full h-full object-cover rounded-xl"
              />
            </div>

            <!-- Course Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="font-semibold text-gray-800 dark:text-white line-clamp-1">
                    {{ course.title }}
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
                  <span>{{ course.instructor?.name || 'ไม่ระบุ' }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                  <span>{{ course.enrollments_count || 0 }} ผู้เรียน</span>
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
                class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
              >
                <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
              </NuxtLink>
              <NuxtLink
                :to="`/nuxnan-admin/courses/${course.id}/edit`"
                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
              >
                <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
              </NuxtLink>
              <button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="courses.length === 0" class="p-8 text-center">
          <Icon icon="fluent:hat-graduation-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
          <p class="text-gray-500 mt-2">ไม่พบคอร์สเรียน</p>
        </div>
      </div>
    </div>
  </div>
</template>
