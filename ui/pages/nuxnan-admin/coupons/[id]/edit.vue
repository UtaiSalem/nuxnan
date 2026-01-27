<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const router = useRouter()
const route = useRoute()

const couponId = route.params.id as string

// Form state
const isLoading = ref(true)
const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})
const successMessage = ref('')

const form = reactive({
  code: '',
  name: '',
  description: '',
  discount_type: 'percentage',
  discount_value: 0,
  min_purchase: 0,
  max_discount: 0,
  usage_limit: null as number | null,
  per_user_limit: 1,
  start_date: '',
  end_date: '',
  is_active: true,
  applicable_to: 'all'
})

// Discount types
const discountTypes = [
  { value: 'percentage', label: 'เปอร์เซ็นต์ (%)' },
  { value: 'fixed', label: 'จำนวนเงิน (บาท)' }
]

// Applicable options
const applicableOptions = [
  { value: 'all', label: 'ทุกสินค้า/บริการ' },
  { value: 'courses', label: 'เฉพาะคอร์สเรียน' },
  { value: 'products', label: 'เฉพาะสินค้า' }
]

// Fetch coupon data
const fetchCoupon = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/coupons/${couponId}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      const coupon = response.data
      form.code = coupon.code || ''
      form.name = coupon.name || ''
      form.description = coupon.description || ''
      form.discount_type = coupon.discount_type || 'percentage'
      form.discount_value = coupon.discount_value || 0
      form.min_purchase = coupon.min_purchase || 0
      form.max_discount = coupon.max_discount || 0
      form.usage_limit = coupon.usage_limit
      form.per_user_limit = coupon.per_user_limit || 1
      form.start_date = coupon.start_date ? coupon.start_date.slice(0, 16) : ''
      form.end_date = coupon.end_date ? coupon.end_date.slice(0, 16) : ''
      form.is_active = coupon.is_active ?? true
      form.applicable_to = coupon.applicable_to || 'all'
    }
  } catch (error) {
    console.error('Failed to fetch coupon:', error)
    errors.value.general = 'ไม่สามารถโหลดข้อมูลคูปองได้'
  } finally {
    isLoading.value = false
  }
}

// Validate form
const validateForm = () => {
  errors.value = {}
  
  if (!form.code) {
    errors.value.code = 'กรุณากรอกรหัสคูปอง'
  }
  
  if (!form.name) {
    errors.value.name = 'กรุณากรอกชื่อคูปอง'
  }
  
  if (form.discount_value <= 0) {
    errors.value.discount_value = 'กรุณากรอกมูลค่าส่วนลด'
  }
  
  if (form.discount_type === 'percentage' && form.discount_value > 100) {
    errors.value.discount_value = 'เปอร์เซ็นต์ต้องไม่เกิน 100'
  }
  
  if (!form.start_date) {
    errors.value.start_date = 'กรุณาเลือกวันที่เริ่มต้น'
  }
  
  if (!form.end_date) {
    errors.value.end_date = 'กรุณาเลือกวันที่สิ้นสุด'
  }
  
  if (form.start_date && form.end_date && new Date(form.start_date) > new Date(form.end_date)) {
    errors.value.end_date = 'วันที่สิ้นสุดต้องหลังจากวันที่เริ่มต้น'
  }
  
  return Object.keys(errors.value).length === 0
}

// Submit form
const handleSubmit = async () => {
  if (!validateForm()) return
  
  isSubmitting.value = true
  successMessage.value = ''
  
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/coupons/${couponId}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body: form
    })
    
    if (response.success) {
      successMessage.value = 'บันทึกคูปองสำเร็จ'
      setTimeout(() => {
        router.push('/nuxnan-admin/coupons')
      }, 1500)
    }
  } catch (error: any) {
    if (error.data?.errors) {
      errors.value = error.data.errors
    } else {
      errors.value.general = error.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง'
    }
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchCoupon()
})
</script>

<template>
  <div class="space-y-6 max-w-2xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        to="/nuxnan-admin/coupons"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">แก้ไขคูปอง</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">แก้ไขข้อมูลคูปอง #{{ couponId }}</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>
    </div>

    <template v-else>
      <!-- Success Message -->
      <div v-if="successMessage" class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-xl">
        <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-5 h-5" />
          <span>{{ successMessage }}</span>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="errors.general" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl">
        <div class="flex items-center gap-2 text-red-700 dark:text-red-400">
          <Icon icon="fluent:error-circle-24-filled" class="w-5 h-5" />
          <span>{{ errors.general }}</span>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
        <!-- Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            รหัสคูปอง <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.code"
            type="text"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent uppercase"
            :class="{ 'border-red-500': errors.code }"
            placeholder="SAVE20"
          />
          <p v-if="errors.code" class="mt-1 text-sm text-red-500">{{ errors.code }}</p>
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            ชื่อคูปอง <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.name }"
            placeholder="ส่วนลด 20% สำหรับสมาชิกใหม่"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            คำอธิบาย
          </label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none"
            placeholder="รายละเอียดเพิ่มเติมของคูปอง"
          ></textarea>
        </div>

        <!-- Discount Type & Value -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              ประเภทส่วนลด
            </label>
            <select
              v-model="form.discount_type"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option v-for="type in discountTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              มูลค่าส่วนลด <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model.number="form.discount_value"
                type="number"
                min="0"
                class="w-full px-4 py-2.5 pr-10 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                :class="{ 'border-red-500': errors.discount_value }"
              />
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
                {{ form.discount_type === 'percentage' ? '%' : '฿' }}
              </span>
            </div>
            <p v-if="errors.discount_value" class="mt-1 text-sm text-red-500">{{ errors.discount_value }}</p>
          </div>
        </div>

        <!-- Min Purchase & Max Discount -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              ยอดสั่งซื้อขั้นต่ำ (บาท)
            </label>
            <input
              v-model.number="form.min_purchase"
              type="number"
              min="0"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              placeholder="0"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              ส่วนลดสูงสุด (บาท)
            </label>
            <input
              v-model.number="form.max_discount"
              type="number"
              min="0"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              placeholder="0 = ไม่จำกัด"
            />
          </div>
        </div>

        <!-- Usage Limits -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              จำนวนใช้งานทั้งหมด
            </label>
            <input
              v-model.number="form.usage_limit"
              type="number"
              min="0"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              placeholder="ไม่จำกัด"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              จำกัดต่อผู้ใช้
            </label>
            <input
              v-model.number="form.per_user_limit"
              type="number"
              min="1"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              วันที่เริ่มต้น <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.start_date"
              type="datetime-local"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.start_date }"
            />
            <p v-if="errors.start_date" class="mt-1 text-sm text-red-500">{{ errors.start_date }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              วันที่สิ้นสุด <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.end_date"
              type="datetime-local"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.end_date }"
            />
            <p v-if="errors.end_date" class="mt-1 text-sm text-red-500">{{ errors.end_date }}</p>
          </div>
        </div>

        <!-- Applicable To -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            ใช้ได้กับ
          </label>
          <select
            v-model="form.applicable_to"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option v-for="option in applicableOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Active Status -->
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
          <label class="flex items-center gap-3 cursor-pointer">
            <input
              v-model="form.is_active"
              type="checkbox"
              class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 rounded-xl text-white font-medium transition-colors"
          >
            <Icon v-if="isSubmitting" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:save-24-regular" class="w-5 h-5" />
            {{ isSubmitting ? 'กำลังบันทึก...' : 'บันทึกการเปลี่ยนแปลง' }}
          </button>
          
          <NuxtLink
            to="/nuxnan-admin/coupons"
            class="flex-1 inline-flex justify-center items-center gap-2 px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl text-gray-700 dark:text-gray-300 font-medium transition-colors"
          >
            <Icon icon="fluent:dismiss-24-regular" class="w-5 h-5" />
            ยกเลิก
          </NuxtLink>
        </div>
      </form>
    </template>
  </div>
</template>
