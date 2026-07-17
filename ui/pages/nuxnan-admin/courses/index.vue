<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string

const courses = ref<any[]>([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const searchQuery = ref('')
const selectedStatus = ref('all')
const currentPage = ref(1)
const totalPages = ref(1)
const perPage = ref(12)
const totalCourses = ref(0)

// View mode persists so admin's preference survives navigation.
const viewMode = ref<'grid' | 'table'>('table')
if (import.meta.client) {
  const saved = localStorage.getItem('admin.courses.viewMode')
  if (saved === 'grid' || saved === 'table') viewMode.value = saved
}
const setViewMode = (mode: 'grid' | 'table') => {
  viewMode.value = mode
  if (import.meta.client) localStorage.setItem('admin.courses.viewMode', mode)
}

const hasMorePages = computed(() => currentPage.value < totalPages.value)

const statuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'published', label: 'เผยแพร่แล้ว' },
  { value: 'draft', label: 'ฉบับร่าง' },
  { value: 'archived', label: 'เก็บถาวร' }
]

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

    const response = await $fetch<any>(`${apiBase}/api/admin/courses?${params}`, {
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

const loadMore = () => {
  if (hasMorePages.value && !isLoadingMore.value) {
    currentPage.value++
    fetchCourses(true)
  }
}

const handleSearch = () => {
  currentPage.value = 1
  courses.value = []
  fetchCourses()
}

const getStatusBadge = (status: string) => {
  const badges: Record<string, string> = {
    published: 'bg-green-500/90 text-white',
    draft: 'bg-yellow-500/90 text-white',
    archived: 'bg-slate-500/90 text-white'
  }
  return badges[status] || 'bg-slate-400/90 text-white'
}

const getStatusChip = (status: string) => {
  const chips: Record<string, string> = {
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    draft: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    archived: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
  }
  return chips[status] || 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    published: 'เผยแพร่',
    draft: 'ฉบับร่าง',
    archived: 'เก็บถาวร'
  }
  return labels[status] || 'ไม่ระบุสถานะ'
}

// Strip HTML tags + decode entities + collapse whitespace so course descriptions
// authored via the rich-text editor render cleanly in the card.
const stripHtml = (html: string) => {
  if (!html) return ''
  const text = html
    .replace(/<style[\s\S]*?<\/style>/gi, '')
    .replace(/<script[\s\S]*?<\/script>/gi, '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&amp;/gi, '&')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/\s+/g, ' ')
    .trim()
  return text
}

const formatPrice = (price: any) => {
  const num = Number(price) || 0
  return num === 0 ? 'ฟรี' : `฿${num.toLocaleString()}`
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
        <p class="text-slate-500 dark:text-slate-400 mt-1 text-sm">
          คอร์สทั้งหมด <span class="font-semibold text-slate-700 dark:text-slate-200">{{ totalCourses.toLocaleString() }}</span> คอร์ส
        </p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/courses/create"
        class="inline-flex items-center gap-2 px-5 py-2.5 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white font-medium transition-colors shadow-sm"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างคอร์สใหม่
      </NuxtLink>
    </div>

    <!-- Filters + View Toggle -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 shadow-hopeui border border-slate-100 dark:border-slate-700">
      <div class="flex flex-col sm:flex-row gap-3">
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
          class="px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-hopeui-primary-500 sm:w-44"
          @change="handleSearch"
        >
          <option v-for="status in statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>

        <button
          @click="handleSearch"
          class="px-6 py-2.5 bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-xl text-white font-medium transition-colors"
        >
          ค้นหา
        </button>

        <!-- View toggle -->
        <div class="inline-flex rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700 p-1">
          <button
            @click="setViewMode('table')"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-medium inline-flex items-center gap-1.5 transition-colors',
              viewMode === 'table'
                ? 'bg-white dark:bg-slate-800 text-hopeui-primary-600 dark:text-hopeui-primary-400 shadow-sm'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
            ]"
            title="มุมมองตาราง"
          >
            <Icon icon="fluent:table-24-regular" class="w-4 h-4" />
            <span class="hidden sm:inline">ตาราง</span>
          </button>
          <button
            @click="setViewMode('grid')"
            :class="[
              'px-3 py-1.5 rounded-lg text-sm font-medium inline-flex items-center gap-1.5 transition-colors',
              viewMode === 'grid'
                ? 'bg-white dark:bg-slate-800 text-hopeui-primary-600 dark:text-hopeui-primary-400 shadow-sm'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'
            ]"
            title="มุมมองการ์ด"
          >
            <Icon icon="fluent:grid-24-regular" class="w-4 h-4" />
            <span class="hidden sm:inline">การ์ด</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Loading State (initial) -->
    <div v-if="isLoading" class="bg-white dark:bg-slate-800 rounded-2xl p-16 text-center shadow-hopeui border border-slate-100 dark:border-slate-700">
      <Icon icon="fluent:spinner-ios-20-regular" class="w-10 h-10 text-hopeui-primary-500 animate-spin mx-auto" />
      <p class="text-slate-500 mt-3">กำลังโหลดข้อมูล...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="courses.length === 0" class="bg-white dark:bg-slate-800 rounded-2xl p-16 text-center shadow-hopeui border border-slate-100 dark:border-slate-700">
      <Icon icon="fluent:hat-graduation-24-regular" class="w-14 h-14 text-slate-300 dark:text-slate-600 mx-auto" />
      <p class="text-slate-500 mt-3">ไม่พบคอร์สเรียนที่ตรงกับเงื่อนไข</p>
    </div>

    <!-- Courses Table -->
    <div v-else-if="viewMode === 'table'" class="bg-white dark:bg-slate-800 rounded-2xl shadow-hopeui border border-slate-100 dark:border-slate-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm table-fixed">
          <colgroup>
            <col class="w-[40%]" />
            <col class="w-[14%]" />
            <col class="w-[14%]" />
            <col class="w-[8%]" />
            <col class="w-[8%]" />
            <col class="w-[8%]" />
            <col class="w-[8%]" />
          </colgroup>
          <thead class="bg-slate-50 dark:bg-slate-900/30 text-xs uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <tr>
              <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">คอร์ส</th>
              <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">โรงเรียน</th>
              <th class="text-left px-4 py-3 font-semibold whitespace-nowrap">ผู้สอน</th>
              <th class="text-right px-4 py-3 font-semibold whitespace-nowrap">ผู้เรียน</th>
              <th class="text-right px-4 py-3 font-semibold whitespace-nowrap">ราคา</th>
              <th class="text-center px-4 py-3 font-semibold whitespace-nowrap">สถานะ</th>
              <th class="text-right px-4 py-3 font-semibold whitespace-nowrap">การจัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <tr
              v-for="course in courses"
              :key="course.id"
              class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-3 min-w-0">
                  <NuxtLink :to="`/nuxnan-admin/courses/${course.id}`" class="flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden bg-gradient-to-br from-hopeui-primary-100 to-purple-100 dark:from-hopeui-primary-900/40 dark:to-purple-900/30">
                    <img
                      v-if="course.cover_url"
                      :src="course.cover_url"
                      :alt="course.title"
                      class="w-full h-full object-cover"
                      @error="($event.target as HTMLImageElement).style.display = 'none'"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                      <Icon icon="fluent:hat-graduation-24-regular" class="w-6 h-6 text-hopeui-primary-400" />
                    </div>
                  </NuxtLink>
                  <div class="min-w-0">
                    <NuxtLink
                      :to="`/nuxnan-admin/courses/${course.id}`"
                      class="font-medium text-slate-800 dark:text-white line-clamp-1 hover:text-hopeui-primary-600 dark:hover:text-hopeui-primary-400 transition-colors"
                    >
                      {{ course.title }}
                    </NuxtLink>
                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1 mt-0.5">
                      {{ stripHtml(course.description) || 'ไม่มีคำอธิบาย' }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <Icon icon="fluent:building-24-regular" class="w-4 h-4 text-slate-400" />
                  <span>{{ course.academy?.name || '—' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-slate-400" />
                  <span>{{ course.user?.name || 'ไม่ระบุ' }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap font-medium">
                {{ (course.members_count || course.enrolled_students || 0).toLocaleString() }}
              </td>
              <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-300 whitespace-nowrap font-medium">
                {{ formatPrice(course.price) }}
              </td>
              <td class="px-4 py-3 text-center whitespace-nowrap">
                <span :class="[getStatusChip(course.status), 'inline-flex px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getStatusLabel(course.status) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <NuxtLink
                    :to="`/nuxnan-admin/courses/${course.id}`"
                    class="p-2 text-slate-500 hover:text-hopeui-primary-600 hover:bg-hopeui-primary-50 dark:hover:bg-hopeui-primary-900/30 rounded-lg transition-colors"
                    title="ดูรายละเอียด"
                  >
                    <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
                  </NuxtLink>
                  <NuxtLink
                    :to="`/nuxnan-admin/courses/${course.id}/edit`"
                    class="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
                    title="แก้ไข"
                  >
                    <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
                  </NuxtLink>
                  <button
                    class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                    title="ลบ"
                  >
                    <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Courses Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <div
        v-for="course in courses"
        :key="course.id"
        class="group bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-hopeui border border-slate-100 dark:border-slate-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 flex flex-col"
      >
        <!-- Cover -->
        <NuxtLink :to="`/nuxnan-admin/courses/${course.id}`" class="relative block aspect-[16/10] bg-gradient-to-br from-hopeui-primary-100 to-purple-100 dark:from-hopeui-primary-900/40 dark:to-purple-900/30 overflow-hidden">
          <img
            v-if="course.cover_url"
            :src="course.cover_url"
            :alt="course.title"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
            @error="($event.target as HTMLImageElement).style.display = 'none'"
          />
          <div v-else class="w-full h-full flex items-center justify-center">
            <Icon icon="fluent:hat-graduation-24-regular" class="w-14 h-14 text-hopeui-primary-400 dark:text-hopeui-primary-500" />
          </div>

          <!-- Status badge overlay -->
          <span
            :class="[getStatusBadge(course.status), 'absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-medium backdrop-blur-sm shadow-sm']"
          >
            {{ getStatusLabel(course.status) }}
          </span>

          <!-- Price badge overlay -->
          <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-xs font-semibold bg-white/90 dark:bg-slate-900/80 text-slate-800 dark:text-white backdrop-blur-sm shadow-sm">
            {{ formatPrice(course.price) }}
          </span>
        </NuxtLink>

        <!-- Body -->
        <div class="p-4 flex flex-col flex-1">
          <NuxtLink
            :to="`/nuxnan-admin/courses/${course.id}`"
            class="font-semibold text-slate-800 dark:text-white line-clamp-2 min-h-[3rem] leading-6 hover:text-hopeui-primary-600 dark:hover:text-hopeui-primary-400 transition-colors"
          >
            {{ course.title }}
          </NuxtLink>

          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1.5 line-clamp-2 min-h-[2.5rem]">
            {{ stripHtml(course.description) || 'ไม่มีคำอธิบาย' }}
          </p>

          <!-- Meta -->
          <div class="flex items-center gap-3 mt-3 pt-3 border-t border-slate-100 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-1 min-w-0 flex-1">
              <Icon icon="fluent:person-24-regular" class="w-3.5 h-3.5 flex-shrink-0" />
              <span class="truncate">{{ course.user?.name || 'ไม่ระบุ' }}</span>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0">
              <Icon icon="fluent:people-24-regular" class="w-3.5 h-3.5" />
              <span>{{ (course.members_count || course.enrolled_students || 0).toLocaleString() }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 mt-3">
            <NuxtLink
              :to="`/nuxnan-admin/courses/${course.id}`"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-200 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg transition-colors"
            >
              <Icon icon="fluent:eye-24-regular" class="w-4 h-4" />
              <span>ดู</span>
            </NuxtLink>
            <NuxtLink
              :to="`/nuxnan-admin/courses/${course.id}/edit`"
              class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-medium text-white bg-hopeui-primary-500 hover:bg-hopeui-primary-600 rounded-lg transition-colors"
            >
              <Icon icon="fluent:edit-24-regular" class="w-4 h-4" />
              <span>แก้ไข</span>
            </NuxtLink>
            <button
              class="inline-flex items-center justify-center p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
              title="ลบคอร์ส"
            >
              <Icon icon="fluent:delete-24-regular" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination footer -->
    <div v-if="!isLoading && courses.length > 0" class="flex justify-center">
      <button
        v-if="hasMorePages"
        @click="loadMore"
        :disabled="isLoadingMore"
        class="px-6 py-2.5 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 rounded-xl font-medium transition-colors flex items-center gap-2 disabled:opacity-50 shadow-sm"
      >
        <Icon v-if="isLoadingMore" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
        <Icon v-else icon="fluent:arrow-download-24-regular" class="w-5 h-5" />
        <span v-if="isLoadingMore">กำลังโหลด...</span>
        <span v-else>โหลดเพิ่มเติม ({{ courses.length }}/{{ totalCourses }})</span>
      </button>
      <div v-else class="text-slate-400 text-sm flex items-center gap-1.5">
        <Icon icon="fluent:checkmark-circle-24-regular" class="w-5 h-5" />
        แสดงทั้งหมด {{ courses.length }} คอร์ส
      </div>
    </div>
  </div>
</template>
