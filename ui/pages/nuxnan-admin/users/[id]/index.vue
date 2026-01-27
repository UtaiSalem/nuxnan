<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: 'nuxnan-admin-layout',
  middleware: 'nuxnan-admin'
})

const config = useRuntimeConfig()
const apiBase = config.public.apiBase as string
const route = useRoute()

const userId = route.params.id as string

// State
const user = ref<any>(null)
const isLoading = ref(true)
const error = ref('')
const isVerifying = ref(false)
const verifyMessage = ref('')
const verifyError = ref('')

// Fetch user data
const fetchUser = async () => {
  isLoading.value = true
  error.value = ''
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      user.value = response.data
    }
  } catch (err: any) {
    console.error('Failed to fetch user:', err)
    error.value = err.data?.message || 'ไม่สามารถโหลดข้อมูลผู้ใช้ได้'
  } finally {
    isLoading.value = false
  }
}

// Get status badge class
const getStatusBadge = (status: string) => {
  const badges: Record<string, string> = {
    active: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    inactive: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
  }
  return badges[status] || badges.inactive
}

// Get role badge class
const getRoleBadge = (role: string) => {
  const badges: Record<string, string> = {
    admin: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    instructor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    user: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
  }
  return badges[role] || badges.user
}

// Format date
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Verify email
const verifyEmail = async () => {
  if (!confirm('ต้องการยืนยันอีเมลของผู้ใช้นี้หรือไม่?')) return
  
  isVerifying.value = true
  verifyMessage.value = ''
  verifyError.value = ''
  
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}/verify-email`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      verifyMessage.value = response.message
      user.value.email_verified_at = response.data.email_verified_at
    }
  } catch (err: any) {
    console.error('Failed to verify email:', err)
    verifyError.value = err.data?.message || 'ไม่สามารถยืนยันอีเมลได้'
  } finally {
    isVerifying.value = false
  }
}

// Unverify email
const unverifyEmail = async () => {
  if (!confirm('ต้องการยกเลิกการยืนยันอีเมลของผู้ใช้นี้หรือไม่?')) return
  
  isVerifying.value = true
  verifyMessage.value = ''
  verifyError.value = ''
  
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}/unverify-email`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      verifyMessage.value = response.message
      user.value.email_verified_at = null
    }
  } catch (err: any) {
    console.error('Failed to unverify email:', err)
    verifyError.value = err.data?.message || 'ไม่สามารถยกเลิกการยืนยันอีเมลได้'
  } finally {
    isVerifying.value = false
  }
}

onMounted(() => {
  fetchUser()
})
</script>

<template>
  <div class="space-y-6 max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        to="/nuxnan-admin/users"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div class="flex-1">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">รายละเอียดผู้ใช้</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">ผู้ใช้ #{{ userId }}</p>
      </div>
      <NuxtLink
        v-if="user"
        :to="`/nuxnan-admin/users/${userId}/edit`"
        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
      >
        <Icon icon="fluent:edit-24-regular" class="w-5 h-5" />
        แก้ไข
      </NuxtLink>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 text-indigo-600 animate-spin mx-auto" />
        <p class="text-gray-500 mt-2">กำลังโหลดข้อมูล...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-sm border border-gray-100 dark:border-gray-700">
      <div class="text-center">
        <Icon icon="fluent:error-circle-24-regular" class="w-12 h-12 text-red-400 mx-auto" />
        <p class="text-gray-500 mt-2">{{ error }}</p>
        <button
          @click="fetchUser"
          class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-xl text-white transition-colors"
        >
          ลองใหม่อีกครั้ง
        </button>
      </div>
    </div>

    <!-- User Details -->
    <template v-else-if="user">
      <!-- Profile Card -->
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
          <!-- Avatar -->
          <div class="w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold">
            {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
          </div>
          
          <!-- Info -->
          <div class="flex-1 text-center sm:text-left">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">{{ user.name }}</h2>
            <p class="text-gray-500 dark:text-gray-400">@{{ user.username || 'no-username' }}</p>
            
            <div class="flex flex-wrap justify-center sm:justify-start gap-2 mt-3">
              <span class="px-3 py-1 text-sm rounded-full" :class="getStatusBadge(user.status || 'active')">
                {{ user.status === 'active' ? 'ใช้งาน' : user.status === 'suspended' ? 'ระงับ' : 'ไม่ใช้งาน' }}
              </span>
              <span class="px-3 py-1 text-sm rounded-full" :class="getRoleBadge(user.role || 'user')">
                {{ user.role === 'admin' ? 'ผู้ดูแล' : user.role === 'instructor' ? 'ผู้สอน' : 'ผู้ใช้' }}
              </span>
              <span v-if="user.is_super_admin" class="px-3 py-1 text-sm rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                Super Admin
              </span>
              <span v-if="user.is_plearnd_admin" class="px-3 py-1 text-sm rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400">
                Plearnd Admin
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Details Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Contact Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-indigo-600" />
            ข้อมูลติดต่อ
          </h3>
          
          <div class="space-y-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">อีเมล</p>
              <p class="text-gray-800 dark:text-white">{{ user.email || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">เบอร์โทรศัพท์</p>
              <p class="text-gray-800 dark:text-white">{{ user.phone || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">รหัสอ้างอิง</p>
              <p class="text-gray-800 dark:text-white font-mono">{{ user.reference_code || '-' }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">รหัสส่วนตัว</p>
              <p class="text-gray-800 dark:text-white font-mono">{{ user.personal_code || '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Account Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:calendar-24-regular" class="w-5 h-5 text-indigo-600" />
            ข้อมูลบัญชี
          </h3>
          
          <div class="space-y-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ</p>
              <p class="text-gray-800 dark:text-white">{{ formatDate(user.created_at) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">อัปเดตล่าสุด</p>
              <p class="text-gray-800 dark:text-white">{{ formatDate(user.updated_at) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">ยืนยันอีเมลเมื่อ</p>
              <div class="flex items-center gap-3">
                <p class="text-gray-800 dark:text-white">
                  {{ user.email_verified_at ? formatDate(user.email_verified_at) : 'ยังไม่ยืนยัน' }}
                </p>
                <!-- Verify/Unverify Button -->
                <button
                  v-if="!user.email_verified_at"
                  @click="verifyEmail"
                  :disabled="isVerifying"
                  class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-lg transition-colors"
                >
                  <Icon v-if="isVerifying" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                  <Icon v-else icon="fluent:checkmark-circle-24-regular" class="w-4 h-4" />
                  ยืนยัน
                </button>
                <button
                  v-else
                  @click="unverifyEmail"
                  :disabled="isVerifying"
                  class="inline-flex items-center gap-1 px-3 py-1 text-sm bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white rounded-lg transition-colors"
                >
                  <Icon v-if="isVerifying" icon="fluent:spinner-ios-20-regular" class="w-4 h-4 animate-spin" />
                  <Icon v-else icon="fluent:dismiss-circle-24-regular" class="w-4 h-4" />
                  ยกเลิก
                </button>
              </div>
              <!-- Success/Error Message -->
              <p v-if="verifyMessage" class="text-sm text-green-600 dark:text-green-400 mt-1">{{ verifyMessage }}</p>
              <p v-if="verifyError" class="text-sm text-red-600 dark:text-red-400 mt-1">{{ verifyError }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">เข้าสู่ระบบล่าสุด</p>
              <p class="text-gray-800 dark:text-white">{{ user.last_login_at ? formatDate(user.last_login_at) : '-' }}</p>
            </div>
          </div>
        </div>

        <!-- Wallet & Points -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:wallet-24-regular" class="w-5 h-5 text-indigo-600" />
            กระเป๋าเงิน & คะแนน
          </h3>
          
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
              <span class="text-gray-600 dark:text-gray-300">ยอดเงิน</span>
              <span class="text-lg font-bold text-green-600 dark:text-green-400">
                ฿{{ (user.wallet_balance || 0).toLocaleString() }}
              </span>
            </div>
            <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
              <span class="text-gray-600 dark:text-gray-300">คะแนน</span>
              <span class="text-lg font-bold text-purple-600 dark:text-purple-400">
                {{ (user.points || 0).toLocaleString() }} pts
              </span>
            </div>
          </div>
        </div>

        <!-- Statistics -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <Icon icon="fluent:data-trending-24-regular" class="w-5 h-5 text-indigo-600" />
            สถิติ
          </h3>
          
          <div class="grid grid-cols-2 gap-4">
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ user.courses_count || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">คอร์สที่ลงทะเบียน</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ user.completed_courses || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">คอร์สที่เรียนจบ</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ user.referrals_count || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">ผู้ที่แนะนำ</p>
            </div>
            <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
              <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ user.login_count || 0 }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนเข้าสู่ระบบ</p>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
