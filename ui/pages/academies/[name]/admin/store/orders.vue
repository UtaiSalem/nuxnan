<script setup lang="ts">
/**
 * Academy Admin - Store Orders Management
 * หน้าจัดการคำสั่งซื้อ
 */
import { ref, computed, onMounted, watch } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const academyName = computed(() => route.params.name as string)

const api = useApi()
const { getOrders, confirmOrder, processOrder, markOrderReady, completeOrder, cancelOrder } = useSchoolStore()

// State
const academyId = ref<number | null>(null)
const orders = ref<any[]>([])
const isLoading = ref(true)
const meta = ref<any>({})
const selectedOrder = ref<any>(null)
const showCancelModal = ref(false)
const cancelReason = ref('')

// Filters
const statusFilter = ref('')
const search = ref('')
const currentPage = ref(1)

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

const statusOptions = [
  { value: '', label: 'ทุกสถานะ' },
  { value: 'pending', label: 'รอดำเนินการ' },
  { value: 'confirmed', label: 'ยืนยันแล้ว' },
  { value: 'processing', label: 'กำลังจัดเตรียม' },
  { value: 'ready', label: 'พร้อมรับ' },
  { value: 'completed', label: 'เสร็จสิ้น' },
  { value: 'cancelled', label: 'ยกเลิก' },
]

const statusColors: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
  confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
  processing: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
  ready: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
  completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
  cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  refunded: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
}

onMounted(async () => {
  try {
    const response: any = await api.get(`/api/academies/${academyName.value}`)
    if (response.success) {
      academyId.value = response.academy.id
      await fetchMyRole()
      if (!isAdmin.value) {
        navigateTo(`/academies/${academyName.value}/admin`)
        return
      }
      await fetchOrders()
    }
  } catch (err) {
    console.error('Failed to load:', err)
  } finally {
    isLoading.value = false
  }
})

const fetchOrders = async () => {
  if (!academyId.value) return
  try {
    const params: any = { page: currentPage.value, per_page: 20 }
    if (statusFilter.value) params.status = statusFilter.value
    if (search.value) params.search = search.value

    const response: any = await getOrders(academyId.value, params)
    if (response.success) {
      orders.value = response.data || []
      meta.value = response.meta || {}
    }
  } catch (err) {
    console.error('Failed to fetch orders:', err)
  }
}

watch([statusFilter, search], () => {
  currentPage.value = 1
  fetchOrders()
})

const handleConfirm = async (order: any) => {
  if (!academyId.value) return
  try {
    const response: any = await confirmOrder(academyId.value, order.id)
    if (response.success) await fetchOrders()
  } catch (err) { console.error(err) }
}

const handleProcess = async (order: any) => {
  if (!academyId.value) return
  try {
    const response: any = await processOrder(academyId.value, order.id)
    if (response.success) await fetchOrders()
  } catch (err) { console.error(err) }
}

const handleMarkReady = async (order: any) => {
  if (!academyId.value) return
  try {
    const response: any = await markOrderReady(academyId.value, order.id)
    if (response.success) await fetchOrders()
  } catch (err) { console.error(err) }
}

const handleComplete = async (order: any) => {
  if (!academyId.value) return
  try {
    const response: any = await completeOrder(academyId.value, order.id)
    if (response.success) await fetchOrders()
  } catch (err) { console.error(err) }
}

const openCancelModal = (order: any) => {
  selectedOrder.value = order
  cancelReason.value = ''
  showCancelModal.value = true
}

const handleCancel = async () => {
  if (!academyId.value || !selectedOrder.value || !cancelReason.value) return
  try {
    const response: any = await cancelOrder(academyId.value, selectedOrder.value.id, cancelReason.value)
    if (response.success) {
      showCancelModal.value = false
      await fetchOrders()
    }
  } catch (err) { console.error(err) }
}

const getNextAction = (order: any) => {
  switch (order.status) {
    case 'pending': return { label: 'ยืนยัน', icon: 'fluent:checkmark-24-regular', action: () => handleConfirm(order), color: 'text-blue-600' }
    case 'confirmed': return { label: 'จัดเตรียม', icon: 'fluent:box-24-regular', action: () => handleProcess(order), color: 'text-indigo-600' }
    case 'processing': return { label: 'พร้อมรับ', icon: 'fluent:hand-wave-24-regular', action: () => handleMarkReady(order), color: 'text-cyan-600' }
    case 'ready': return { label: 'เสร็จสิ้น', icon: 'fluent:checkmark-circle-24-regular', action: () => handleComplete(order), color: 'text-green-600' }
    default: return null
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Back -->
      <NuxtLink
        :to="`/academies/${academyName}/admin/store`"
        class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับร้านค้า</span>
      </NuxtLink>

      <!-- Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คำสั่งซื้อ</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ meta.total || 0 }} คำสั่งซื้อ</p>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="relative">
            <Icon icon="fluent:search-24-regular" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            <input v-model="search" type="text" placeholder="ค้นหาเลขคำสั่งซื้อ..."
              class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
          </div>
          <select v-model="statusFilter"
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>
      </div>

      <!-- Orders List -->
      <div class="space-y-4">
        <div v-if="!orders.length" class="bg-white dark:bg-gray-800 rounded-xl p-16 text-center shadow-sm border border-gray-200 dark:border-gray-700">
          <Icon icon="fluent:receipt-24-regular" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
          <p class="text-gray-500 dark:text-gray-400">ยังไม่มีคำสั่งซื้อ</p>
        </div>

        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden"
        >
          <!-- Order Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-gray-500" />
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ order.user?.name || 'ไม่ทราบ' }}</p>
                <p class="text-xs text-gray-500">{{ order.order_number }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span :class="[statusColors[order.status] || 'bg-gray-100 text-gray-700', 'px-3 py-1 rounded-full text-xs font-medium']">
                {{ order.status_label }}
              </span>
              <span class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</span>
            </div>
          </div>

          <!-- Order Items -->
          <div class="p-4">
            <div class="flex flex-wrap gap-3 mb-3">
              <div v-for="item in order.items" :key="item.id" class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">x{{ item.quantity }}</span>
                {{ item.product_name }}
              </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
              <div class="flex items-center gap-4">
                <div>
                  <span class="text-sm text-gray-500">ยอดรวม: </span>
                  <span class="font-bold text-gray-900 dark:text-white">
                    {{ order.payment_method === 'points' ? `${order.points_spent} พ้อยท์` : `฿${Number(order.total).toLocaleString()}` }}
                  </span>
                </div>
                <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                  {{ order.payment_method === 'points' ? 'พ้อยท์' : 'กระเป๋าเงิน' }}
                </span>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <template v-if="getNextAction(order)">
                  <button
                    @click="getNextAction(order)?.action()"
                    :class="[getNextAction(order)?.color]"
                    class="min-h-[44px] sm:min-h-0 px-3 py-1.5 text-sm font-medium border border-current rounded-lg hover:opacity-80 transition-opacity inline-flex items-center gap-1"
                  >
                    <Icon :icon="getNextAction(order)?.icon || ''" class="w-4 h-4" />
                    {{ getNextAction(order)?.label }}
                  </button>
                </template>
                <button
                  v-if="order.status === 'pending' || order.status === 'confirmed'"
                  @click="openCancelModal(order)"
                  class="min-h-[44px] sm:min-h-0 px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                >
                  ยกเลิก
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-center gap-2">
        <button @click="currentPage > 1 && (currentPage--, fetchOrders())" :disabled="currentPage <= 1"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700">
          ก่อนหน้า
        </button>
        <span class="text-sm text-gray-600 dark:text-gray-400">{{ currentPage }} / {{ meta.last_page }}</span>
        <button @click="currentPage < meta.last_page && (currentPage++, fetchOrders())" :disabled="currentPage >= meta.last_page"
          class="min-h-[44px] sm:min-h-0 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-gray-700">
          ถัดไป
        </button>
      </div>
    </div>

    <!-- Cancel Modal -->
    <Teleport to="body">
      <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="showCancelModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-sm w-full p-6 space-y-4">
          <h3 class="text-lg font-bold text-gray-900 dark:text-white">ยกเลิกคำสั่งซื้อ</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400">คำสั่งซื้อ: {{ selectedOrder?.order_number }}</p>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผลการยกเลิก *</label>
            <textarea v-model="cancelReason" rows="3" required
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
              placeholder="ระบุเหตุผล..."></textarea>
          </div>
          <div class="flex gap-3">
            <button @click="showCancelModal = false"
              class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
              ปิด
            </button>
            <button @click="handleCancel" :disabled="!cancelReason"
              class="min-h-[44px] sm:min-h-0 flex-1 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl font-medium transition-colors">
              ยืนยันยกเลิก
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
