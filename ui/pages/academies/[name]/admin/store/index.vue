<script setup lang="ts">
/**
 * Academy Admin - Store Management Dashboard
 * หน้าจัดการร้านค้าของโรงเรียน (Dashboard)
 */
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'main'
})

const route = useRoute()
const academyName = computed(() => route.params.name as string)

// Composables
const api = useApi()
const { getStore, createStore, getStoreStats } = useSchoolStore()

// State
const academy = ref<any>(null)
const academyId = ref<number | null>(null)
const store = ref<any>(null)
const stats = ref<any>(null)
const isLoading = ref(true)
const showCreateModal = ref(false)
const isSaving = ref(false)

// Academy Role
const { can, isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Create store form
const storeForm = ref({
  name: '',
  description: '',
  allow_points_payment: true,
  allow_wallet_payment: true,
  min_order_amount: 0,
})

// Quick Actions
const quickActions = computed(() => [
  {
    title: 'จัดการสินค้า',
    description: 'เพิ่ม แก้ไข และจัดการสินค้าทั้งหมด',
    icon: 'fluent:box-24-filled',
    to: `/academies/${academyName.value}/admin/store/products`,
    color: 'from-blue-500 to-blue-600',
    count: stats.value?.total_products || 0,
    countLabel: 'สินค้า',
  },
  {
    title: 'คำสั่งซื้อ',
    description: 'ดูและจัดการคำสั่งซื้อ',
    icon: 'fluent:receipt-24-filled',
    to: `/academies/${academyName.value}/admin/store/orders`,
    color: 'from-green-500 to-green-600',
    count: stats.value?.pending_orders || 0,
    countLabel: 'รอดำเนินการ',
  },
  {
    title: 'หมวดหมู่',
    description: 'จัดการหมวดหมู่สินค้า',
    icon: 'fluent:tag-multiple-24-filled',
    to: `/academies/${academyName.value}/admin/store/categories`,
    color: 'from-purple-500 to-purple-600',
    count: stats.value?.total_categories || 0,
    countLabel: 'หมวดหมู่',
  },
  {
    title: 'ตั้งค่าร้านค้า',
    description: 'ปรับแต่งการตั้งค่าร้าน',
    icon: 'fluent:settings-24-filled',
    to: `/academies/${academyName.value}/admin/store/settings`,
    color: 'from-amber-500 to-amber-600',
    count: null,
    countLabel: null,
  },
])

// Stats cards
const statsCards = computed(() => [
  {
    label: 'สินค้าทั้งหมด',
    value: stats.value?.total_products || 0,
    icon: 'fluent:box-24-filled',
    color: 'blue',
    bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    textColor: 'text-blue-600 dark:text-blue-400',
  },
  {
    label: 'สินค้าพร้อมขาย',
    value: stats.value?.active_products || 0,
    icon: 'fluent:checkmark-circle-24-filled',
    color: 'green',
    bgColor: 'bg-green-100 dark:bg-green-900/30',
    textColor: 'text-green-600 dark:text-green-400',
  },
  {
    label: 'สินค้าใกล้หมด',
    value: stats.value?.low_stock_products || 0,
    icon: 'fluent:warning-24-filled',
    color: 'yellow',
    bgColor: 'bg-yellow-100 dark:bg-yellow-900/30',
    textColor: 'text-yellow-600 dark:text-yellow-400',
  },
  {
    label: 'รายได้รวม',
    value: `฿${Number(stats.value?.total_revenue || 0).toLocaleString()}`,
    icon: 'fluent:money-24-filled',
    color: 'purple',
    bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    textColor: 'text-purple-600 dark:text-purple-400',
  },
])

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academy.value = response.academy
      academyId.value = response.academy.id
      await fetchMyRole()

      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }

      await fetchStoreData()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchStoreData = async () => {
  if (!academyId.value) return
  try {
    const response: any = await getStore(academyId.value)
    if (response.success && response.data) {
      store.value = response.data
      stats.value = response.data.stats
    }
  } catch (err) {
    console.error('Failed to fetch store:', err)
  }
}

const handleCreateStore = async () => {
  if (!academyId.value || isSaving.value) return
  isSaving.value = true
  try {
    const response: any = await createStore(academyId.value, storeForm.value)
    if (response.success) {
      store.value = response.data
      showCreateModal.value = false
      await fetchStoreData()
    }
  } catch (err) {
    console.error('Failed to create store:', err)
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Back Navigation -->
      <NuxtLink
        :to="`/academies/${academyName}/admin/school-management`"
        class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับไปหน้าจัดการ</span>
      </NuxtLink>

      <!-- No Store State -->
      <div v-if="!store" class="text-center py-16">
        <div class="mx-auto w-24 h-24 bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center mb-6">
          <Icon icon="fluent:store-microsoft-24-filled" class="w-12 h-12 text-primary-600 dark:text-primary-400" />
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">เปิดร้านค้าของโรงเรียน</h2>
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8">
          สร้างร้านค้าออนไลน์ให้โรงเรียนของคุณ เพื่อจำหน่ายสินค้า อุปกรณ์การเรียน เครื่องแบบ และอื่นๆ
        </p>
        <button
          @click="showCreateModal = true"
          class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-medium transition-colors inline-flex items-center gap-2"
        >
          <Icon icon="fluent:add-24-regular" class="w-5 h-5" />
          สร้างร้านค้า
        </button>
      </div>

      <!-- Store Dashboard -->
      <template v-else>
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
              <Icon icon="fluent:store-microsoft-24-filled" class="w-8 h-8 text-primary-600 dark:text-primary-400" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ store.name }}</h1>
              <p class="text-gray-600 dark:text-gray-400">ร้านค้าของ {{ academy?.name }}</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span
              :class="store.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
              class="px-3 py-1 rounded-full text-sm font-medium"
            >
              {{ store.is_active ? 'เปิดให้บริการ' : 'ปิดร้าน' }}
            </span>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="stat in statsCards"
            :key="stat.label"
            class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-center gap-3">
              <div :class="[stat.bgColor, 'p-2 rounded-lg']">
                <Icon :icon="stat.icon" :class="[stat.textColor, 'w-6 h-6']" />
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">จัดการร้านค้า</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <NuxtLink
              v-for="action in quickActions"
              :key="action.to"
              :to="action.to"
              class="group bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all hover:border-primary-300 dark:hover:border-primary-600"
            >
              <div class="flex items-start gap-4">
                <div :class="['p-3 rounded-xl bg-gradient-to-br shrink-0', action.color]">
                  <Icon :icon="action.icon" class="w-6 h-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <h4 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                    {{ action.title }}
                  </h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ action.description }}</p>
                  <p v-if="action.count !== null" class="text-xs text-gray-400 mt-2">
                    {{ action.count }} {{ action.countLabel }}
                  </p>
                </div>
              </div>
            </NuxtLink>
          </div>
        </div>

        <!-- Recent Orders Preview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">คำสั่งซื้อล่าสุด</h3>
            <NuxtLink
              :to="`/academies/${academyName}/admin/store/orders`"
              class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400"
            >
              ดูทั้งหมด →
            </NuxtLink>
          </div>
          <div class="p-4 text-center text-gray-500 dark:text-gray-400 py-8">
            <Icon icon="fluent:receipt-24-regular" class="w-12 h-12 mx-auto mb-3 opacity-50" />
            <p>ไปที่หน้าคำสั่งซื้อเพื่อดูรายละเอียด</p>
          </div>
        </div>
      </template>
    </div>

    <!-- Create Store Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 space-y-5">
          <div class="flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">สร้างร้านค้า</h3>
            <button @click="showCreateModal = false" class="min-h-[44px] sm:min-h-0 min-w-[44px] sm:min-w-0 inline-flex items-center justify-center p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
              <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5 text-gray-500" />
            </button>
          </div>

          <form @submit.prevent="handleCreateStore" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อร้านค้า *</label>
              <input
                v-model="storeForm.name"
                type="text"
                required
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="เช่น ร้านค้าโรงเรียน..."
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
              <textarea
                v-model="storeForm.description"
                rows="3"
                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                placeholder="อธิบายร้านค้าของคุณ..."
              ></textarea>
            </div>

            <div class="space-y-3">
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  v-model="storeForm.allow_points_payment"
                  type="checkbox"
                  class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">รับชำระด้วยพ้อยท์</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input
                  v-model="storeForm.allow_wallet_payment"
                  type="checkbox"
                  class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300">รับชำระด้วยกระเป๋าเงิน</span>
              </label>
            </div>

            <button
              type="submit"
              :disabled="isSaving || !storeForm.name"
              class="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-medium transition-colors flex items-center justify-center gap-2"
            >
              <div v-if="isSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
              <Icon v-else icon="fluent:store-microsoft-24-regular" class="w-5 h-5" />
              {{ isSaving ? 'กำลังสร้าง...' : 'สร้างร้านค้า' }}
            </button>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
