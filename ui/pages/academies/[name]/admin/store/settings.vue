<script setup lang="ts">
/**
 * Academy Admin - Store Settings
 * หน้าตั้งค่าร้านค้า
 */
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({ layout: 'main' })

const route = useRoute()
const academyName = computed(() => route.params.name as string)

const api = useApi()
const { getStore, updateStore } = useSchoolStore()

// State
const academyId = ref<number | null>(null)
const store = ref<any>(null)
const isLoading = ref(true)
const isSaving = ref(false)
const saveMessage = ref('')

const { isAdmin, fetchMyRole } = useAcademyRole(academyId)

// Form
const form = ref({
  name: '',
  description: '',
  is_active: true,
  allow_points_payment: true,
  allow_wallet_payment: true,
  min_order_amount: 0,
})

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
      await fetchStoreData()
    }
  } catch (err) { console.error(err) }
  finally { isLoading.value = false }
})

const fetchStoreData = async () => {
  if (!academyId.value) return
  try {
    const response: any = await getStore(academyId.value)
    if (response.success && response.data) {
      store.value = response.data
      form.value = {
        name: response.data.name || '',
        description: response.data.description || '',
        is_active: response.data.is_active ?? true,
        allow_points_payment: response.data.allow_points_payment ?? true,
        allow_wallet_payment: response.data.allow_wallet_payment ?? true,
        min_order_amount: response.data.min_order_amount || 0,
      }
    }
  } catch (err) { console.error(err) }
}

const handleSave = async () => {
  if (!academyId.value || isSaving.value) return
  isSaving.value = true
  saveMessage.value = ''
  try {
    const response: any = await updateStore(academyId.value, form.value)
    if (response.success) {
      saveMessage.value = 'บันทึกเรียบร้อย'
      setTimeout(() => saveMessage.value = '', 3000)
    }
  } catch (err) {
    console.error(err)
    saveMessage.value = 'เกิดข้อผิดพลาด'
  } finally { isSaving.value = false }
}
</script>

<template>
  <div class="px-4 sm:px-0">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Back -->
      <NuxtLink :to="`/academies/${academyName}/admin/store`"
        class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary-600 transition-colors">
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
        <span>กลับร้านค้า</span>
      </NuxtLink>

      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">ตั้งค่าร้านค้า</h1>

      <div v-if="!store" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 text-yellow-700 dark:text-yellow-400">
        <p>ยังไม่ได้สร้างร้านค้า กรุณาสร้างร้านค้าก่อน</p>
      </div>

      <form v-else @submit.prevent="handleSave" class="space-y-6">
        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
          <h3 class="font-semibold text-gray-900 dark:text-white">ข้อมูลร้านค้า</h3>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อร้านค้า</label>
            <input v-model="form.name" type="text" required
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
            <textarea v-model="form.description" rows="3"
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"></textarea>
          </div>
        </div>

        <!-- Store Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
          <h3 class="font-semibold text-gray-900 dark:text-white">สถานะร้านค้า</h3>

          <label class="flex items-center justify-between cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div>
              <p class="font-medium text-gray-900 dark:text-white">เปิดให้บริการ</p>
              <p class="text-sm text-gray-500">เมื่อเปิด สมาชิกจะสามารถสั่งซื้อสินค้าได้</p>
            </div>
            <input v-model="form.is_active" type="checkbox"
              class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
          </label>
        </div>

        <!-- Payment Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 space-y-4">
          <h3 class="font-semibold text-gray-900 dark:text-white">ช่องทางชำระเงิน</h3>

          <label class="flex items-center justify-between cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                <Icon icon="fluent:star-24-filled" class="w-5 h-5 text-amber-600" />
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">รับชำระด้วยพ้อยท์</p>
                <p class="text-sm text-gray-500">สมาชิกสามารถใช้พ้อยท์ซื้อสินค้าได้</p>
              </div>
            </div>
            <input v-model="form.allow_points_payment" type="checkbox"
              class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
          </label>

          <label class="flex items-center justify-between cursor-pointer p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <Icon icon="fluent:wallet-24-filled" class="w-5 h-5 text-green-600" />
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-white">รับชำระด้วยกระเป๋าเงิน</p>
                <p class="text-sm text-gray-500">สมาชิกสามารถใช้เงินจากกระเป๋าซื้อสินค้าได้</p>
              </div>
            </div>
            <input v-model="form.allow_wallet_payment" type="checkbox"
              class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500" />
          </label>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ยอดสั่งซื้อขั้นต่ำ (บาท)</label>
            <input v-model.number="form.min_order_amount" type="number" min="0" step="0.01"
              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
            <p class="text-xs text-gray-500 mt-1">ตั้งเป็น 0 เพื่อไม่จำกัดขั้นต่ำ</p>
          </div>
        </div>

        <!-- Save Button -->
        <div class="flex items-center gap-4">
          <button type="submit" :disabled="isSaving"
            class="px-6 py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-medium transition-colors flex items-center gap-2">
            <div v-if="isSaving" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
            {{ isSaving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า' }}
          </button>
          <span v-if="saveMessage" :class="saveMessage.includes('ผิดพลาด') ? 'text-red-600' : 'text-green-600'" class="text-sm font-medium">
            {{ saveMessage }}
          </span>
        </div>
      </form>
    </div>
  </div>
</template>
