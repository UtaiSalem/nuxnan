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
const coupons = ref([])
const isLoading = ref(true)
const searchQuery = ref('')
const selectedStatus = ref('all')

// Status options
const statuses = [
  { value: 'all', label: 'ทั้งหมด' },
  { value: 'active', label: 'ใช้งานได้' },
  { value: 'expired', label: 'หมดอายุ' },
  { value: 'disabled', label: 'ปิดใช้งาน' }
]

// Fetch coupons
const fetchCoupons = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/coupons`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })

    if (response.success) {
      coupons.value = response.data.data || response.data
    }
  } catch (error) {
    console.error('Failed to fetch coupons:', error)
    // Mock data
    coupons.value = [
      { id: 1, code: 'WELCOME50', discount: 50, type: 'percentage', usageLimit: 100, usedCount: 45, status: 'active', expiresAt: '2026-02-28' },
      { id: 2, code: 'SAVE100', discount: 100, type: 'fixed', usageLimit: 50, usedCount: 50, status: 'expired', expiresAt: '2026-01-15' },
      { id: 3, code: 'NEWYEAR25', discount: 25, type: 'percentage', usageLimit: 200, usedCount: 89, status: 'active', expiresAt: '2026-03-31' },
      { id: 4, code: 'FLASH20', discount: 20, type: 'percentage', usageLimit: null, usedCount: 234, status: 'active', expiresAt: '2026-01-31' },
      { id: 5, code: 'VIP500', discount: 500, type: 'fixed', usageLimit: 10, usedCount: 8, status: 'active', expiresAt: '2026-06-30' }
    ]
  } finally {
    isLoading.value = false
  }
}

// Get status badge
const getStatusBadge = (status: string) => {
  const badges = {
    active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    expired: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    disabled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
  return badges[status] || badges.disabled
}

const getStatusLabel = (status: string) => {
  const labels = { active: 'ใช้งานได้', expired: 'หมดอายุ', disabled: 'ปิดใช้งาน' }
  return labels[status] || 'ไม่ทราบ'
}

onMounted(() => {
  fetchCoupons()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">จัดการคูปอง</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">สร้างและจัดการโค้ดส่วนลด</p>
      </div>
      <NuxtLink
        to="/nuxnan-admin/coupons/create"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
        สร้างคูปองใหม่
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
            placeholder="ค้นหาโค้ดคูปอง..."
            class="w-full pl-10 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <select
          v-model="selectedStatus"
          class="px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option v-for="status in statuses" :key="status.value" :value="status.value">
            {{ status.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- Coupons Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-if="isLoading" class="col-span-full p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>

      <template v-else>
        <div
          v-for="coupon in coupons"
          :key="coupon.id"
          class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-4">
            <div>
              <div class="flex items-center gap-2">
                <span class="font-mono font-bold text-lg text-indigo-600 dark:text-indigo-400">
                  {{ coupon.code }}
                </span>
                <span :class="[getStatusBadge(coupon.status), 'px-2 py-0.5 rounded-full text-xs font-medium']">
                  {{ getStatusLabel(coupon.status) }}
                </span>
              </div>
              <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                <template v-if="coupon.type === 'percentage'">{{ coupon.discount }}%</template>
                <template v-else>฿{{ coupon.discount }}</template>
                <span class="text-sm font-normal text-gray-500 ml-1">ส่วนลด</span>
              </p>
            </div>
            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl">
              <Icon icon="fluent:ticket-diagonal-24-regular" class="w-6 h-6 text-indigo-600" />
            </div>
          </div>

          <div class="space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-gray-500">การใช้งาน</span>
              <span class="text-gray-800 dark:text-white font-medium">
                {{ coupon.usedCount }} / {{ coupon.usageLimit || '∞' }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-500">หมดอายุ</span>
              <span class="text-gray-800 dark:text-white font-medium">{{ coupon.expiresAt }}</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 mt-2">
              <div
                class="bg-indigo-600 h-2 rounded-full"
                :style="{ width: coupon.usageLimit ? `${(coupon.usedCount / coupon.usageLimit) * 100}%` : '50%' }"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
            <NuxtLink
              :to="`/nuxnan-admin/coupons/${coupon.id}/edit`"
              class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors"
            >
              <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
            </NuxtLink>
            <button class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
              <Icon icon="fluent:delete-24-regular" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div v-if="coupons.length === 0" class="col-span-full p-8 text-center">
          <Icon icon="fluent:ticket-diagonal-24-regular" class="w-12 h-12 text-gray-300 mx-auto" />
          <p class="text-gray-500 mt-2">ไม่พบคูปอง</p>
        </div>
      </template>
    </div>
  </div>
</template>
