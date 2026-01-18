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
const academies = ref([])
const isLoading = ref(true)
const searchQuery = ref('')

// Fetch academies
const fetchAcademies = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/academies`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      academies.value = response.data.data || response.data
    }
  } catch (error) {
    console.error('Failed to fetch academies:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchAcademies()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการอะคาเดมี</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการอะคาเดมีทั้งหมดในระบบ</p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/academies/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างอะคาเดมีใหม่
      </NuxtLink>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="flex gap-4">
        <div class="flex-1 relative">
          <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="ค้นหาอะคาเดมี..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
      </div>
    </div>

    <!-- Academies Grid -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
      <div v-if="isLoading" class="p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
        <div
          v-for="academy in academies"
          :key="academy.id"
          class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start gap-4">
            <img
              :src="academy.logo || '/storage/images/default-academy.jpg'"
              :alt="academy.name"
              class="w-16 h-16 rounded-xl object-cover"
            />
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-gray-800 dark:text-white line-clamp-1">{{ academy.name }}</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1">{{ academy.description }}</p>
              <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                <span class="flex items-center gap-1">
                  <Icon icon="fluent:book-24-regular" class="w-4 h-4" />
                  {{ academy.courses_count || 0 }} คอร์ส
                </span>
                <span class="flex items-center gap-1">
                  <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
                  {{ academy.members_count || 0 }} สมาชิก
                </span>
              </div>
            </div>
          </div>
          <div class="flex justify-end gap-2 mt-4">
            <NuxtLink
              :to="`/nuxnan-admin/academies/${academy.id}`"
              class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
            >
              <Icon icon="fluent:eye-24-regular" class="w-5 h-5" />
            </NuxtLink>
            <NuxtLink
              :to="`/nuxnan-admin/academies/${academy.id}/edit`"
              class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
            >
              <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
            </NuxtLink>
          </div>
        </div>

        <div v-if="academies.length === 0" class="col-span-full p-8 text-center">
          <Icon icon="fluent:building-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
          <p class="text-gray-500 mt-2">ไม่พบอะคาเดมี</p>
        </div>
      </div>
    </div>
  </div>
</template>
