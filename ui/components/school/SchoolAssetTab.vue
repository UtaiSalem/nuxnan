<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">การจัดการทรัพย์สินและพัสดุ</h2>
      <button
        @click="showAssetModal = true"
        class="min-h-[44px] sm:min-h-0 inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors"
      >
        <Icon icon="heroicons:plus" class="h-5 w-5" />
        <span>เพิ่มทรัพย์สิน</span>
      </button>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">ทรัพย์สินทั้งหมด</p>
        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ assets.length }} รายการ</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">กำลังซ่อมแซม</p>
        <p class="text-2xl font-black text-amber-500">{{ maintenanceCount }} รายการ</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">มูลค่ารวม</p>
        <p class="text-2xl font-black text-primary-600">฿{{ formatMoney(totalValue) }}</p>
      </div>
      <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">สูญหาย/จำหน่าย</p>
        <p class="text-2xl font-black text-red-500">{{ retiredCount }} รายการ</p>
      </div>
    </div>

    <!-- Assets Grid -->
    <div v-if="loadingAssets" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
    </div>

    <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-4">รหัสทรัพย์สิน</th>
              <th class="px-6 py-4">ชื่อรายการ</th>
              <th class="px-6 py-4">หมวดหมู่</th>
              <th class="px-6 py-4">สถานที่</th>
              <th class="px-6 py-4">สถานะ</th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50 dark:hover:bg-gray-900/20">
              <td class="px-6 py-4 font-mono text-xs font-bold text-primary-600">{{ asset.asset_code }}</td>
              <td class="px-6 py-4">
                <div class="font-bold text-gray-900 dark:text-white text-sm">{{ asset.name }}</div>
                <div class="text-[10px] text-gray-400">{{ asset.description || 'ไม่มีคำอธิบาย' }}</div>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">{{ asset.category }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">
                <div class="flex items-center gap-1">
                  <Icon icon="heroicons:map-pin" class="h-4 w-4" />
                  {{ asset.location || '-' }}
                </div>
              </td>
              <td class="px-6 py-4">
                <span :class="getStatusClass(asset.status)" class="px-2 py-1 rounded-full text-[10px] font-black uppercase">
                  {{ getStatusLabel(asset.status) }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button 
                    @click="openMaintenanceModal(asset)"
                    class="p-2 text-gray-400 hover:text-amber-500 hover:bg-amber-50 rounded-lg transition-colors"
                    title="แจ้งซ่อม"
                  >
                    <Icon icon="heroicons:wrench-screwdriver" class="h-5 w-5" />
                  </button>
                  <button 
                    @click="viewAssetDetails(asset)"
                    class="p-2 text-gray-400 hover:text-primary-500 hover:bg-primary-50 rounded-lg transition-colors"
                  >
                    <Icon icon="heroicons:eye" class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="assets.length === 0" class="p-12 text-center text-gray-400">
        <Icon icon="heroicons:archive-box" class="h-12 w-12 mx-auto mb-3 opacity-20" />
        <p>ยังไม่มีข้อมูลทรัพย์สินในระบบ</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Icon } from '@iconify/vue'

const props = defineProps<{
  academyId: number
}>()

const schoolApi = useSchoolManagement()

// State
const assets = ref<any[]>([])
const loadingAssets = ref(false)
const showAssetModal = ref(false)

// Computed
const maintenanceCount = computed(() => assets.value.filter(a => a.status === 'maintenance').length)
const retiredCount = computed(() => assets.value.filter(a => ['retired', 'lost'].includes(a.status)).length)
const totalValue = computed(() => assets.value.reduce((sum, a) => sum + parseFloat(a.purchase_price || 0), 0))

// Actions
const loadAssets = async () => {
  loadingAssets.value = true
  try {
    const response: any = await schoolApi.getAssets(props.academyId)
    assets.value = response.data?.data || []
  } catch (err) {
    console.error('Failed to load assets:', err)
  } finally {
    loadingAssets.value = false
  }
}

const openMaintenanceModal = (_asset: any) => {
  // Logic
}

const viewAssetDetails = (_asset: any) => {
  // Logic
}

// Helpers
const formatMoney = (amount: number) => new Intl.NumberFormat('th-TH').format(amount || 0)

const getStatusClass = (status: string) => {
  switch (status) {
    case 'active': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    case 'maintenance': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
    case 'retired': return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    case 'lost': return 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
    default: return 'bg-gray-100'
  }
}

const getStatusLabel = (status: string) => {
  switch (status) {
    case 'active': return 'ใช้งานปกติ'
    case 'maintenance': return 'กำลังซ่อม'
    case 'retired': return 'จำหน่ายออก'
    case 'lost': return 'สูญหาย'
    default: return status
  }
}

onMounted(() => {
  loadAssets()
})
</script>
