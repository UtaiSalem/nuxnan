<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

// State
const contents = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedType = ref('all')

// Content types
const types = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'post', label: 'โพสต์' },
  { value: 'article', label: 'บทความ' },
  { value: 'video', label: 'วิดีโอ' },
  { value: 'announcement', label: 'ประกาศ' }
]

// Fetch contents
const fetchContents = async () => {
  isLoading.value = true
  try {
    // Mock data
    contents.value = [
      { id: 1, title: 'ประกาศ: อัพเดทระบบใหม่', type: 'announcement', author: 'Admin', views: 1250, status: 'published', date: '2026-01-18' },
      { id: 2, title: 'เทคนิคการเรียน Python อย่างมีประสิทธิภาพ', type: 'article', author: 'สมชาย ใจดี', views: 890, status: 'published', date: '2026-01-17' },
      { id: 3, title: 'แนะนำคอร์สใหม่ประจำเดือน', type: 'post', author: 'Admin', views: 456, status: 'draft', date: '2026-01-16' },
      { id: 4, title: 'สอน JavaScript พื้นฐาน EP.1', type: 'video', author: 'วิชัย มั่นคง', views: 2340, status: 'published', date: '2026-01-15' },
      { id: 5, title: 'Tips: การใช้งาน Git สำหรับมือใหม่', type: 'article', author: 'พิมพ์ใจ รักเรียน', views: 678, status: 'published', date: '2026-01-14' }
    ]
  } finally {
    isLoading.value = false
  }
}

// Get type badge
const getTypeBadge = (type: string) => {
  const badges = {
    post: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    article: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    video: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    announcement: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
  }
  return badges[type] || 'bg-gray-100 text-gray-700'
}

const getTypeLabel = (type: string) => {
  const labels = { post: 'โพสต์', article: 'บทความ', video: 'วิดีโอ', announcement: 'ประกาศ' }
  return labels[type] || type
}

const getStatusBadge = (status: string) => {
  const badges = {
    published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    draft: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
  }
  return badges[status] || badges.draft
}

onMounted(() => {
  fetchContents()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการเนื้อหา</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการโพสต์ บทความ และเนื้อหาต่างๆ</p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/content/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างเนื้อหาใหม่
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
            placeholder="ค้นหาเนื้อหา..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <select
          v-model="selectedType"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option v-for="type in types" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Contents Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">หัวข้อ</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
              <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase">ผู้เขียน</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">ยอดดู</th>
              <th class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">วันที่</th>
              <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 uppercase">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="content in contents" :key="content.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="px-6 py-4">
                <p class="font-medium text-gray-800 dark:text-white line-clamp-1">{{ content.title }}</p>
              </td>
              <td class="px-6 py-4">
                <span :class="[getTypeBadge(content.type), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ getTypeLabel(content.type) }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-300">{{ content.author }}</td>
              <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-300">{{ content.views.toLocaleString() }}</td>
              <td class="px-6 py-4 text-center">
                <span :class="[getStatusBadge(content.status), 'px-2.5 py-1 rounded-full text-xs font-medium']">
                  {{ content.status === 'published' ? 'เผยแพร่' : 'ฉบับร่าง' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right text-gray-500">{{ content.date }}</td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors">
                    <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
                  </button>
                  <button class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors">
                    <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
                  </button>
                  <button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                    <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
