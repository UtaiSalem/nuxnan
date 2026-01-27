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

const userId = route.params.id as string

// Form state
const isLoading = ref(true)
const isSubmitting = ref(false)
const errors = ref<Record<string, string>>({})
const successMessage = ref('')

const form = reactive({
  name: '',
  email: '',
  username: '',
  password: '',
  password_confirmation: '',
  role: 'user',
  is_super_admin: false,
  is_plearnd_admin: false,
  status: 'active'
})

// Roles
const roles = [
  { value: 'user', label: 'ผู้ใช้ทั่วไป' },
  { value: 'instructor', label: 'ผู้สอน' },
  { value: 'admin', label: 'ผู้ดูแล' }
]

// Statuses
const statuses = [
  { value: 'active', label: 'ใช้งาน' },
  { value: 'inactive', label: 'ไม่ใช้งาน' },
  { value: 'suspended', label: 'ระงับการใช้งาน' }
]

// Fetch user data
const fetchUser = async () => {
  isLoading.value = true
  try {
    const token = useCookie('token')
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}`, {
      headers: {
        Authorization: `Bearer ${token.value}`
      }
    })
    
    if (response.success) {
      const user = response.data
      form.name = user.name || ''
      form.email = user.email || ''
      form.username = user.username || ''
      form.role = user.role || 'user'
      form.is_super_admin = user.is_super_admin || false
      form.is_plearnd_admin = user.is_plearnd_admin || false
      form.status = user.status || 'active'
    }
  } catch (error) {
    console.error('Failed to fetch user:', error)
    errors.value.general = 'ไม่สามารถโหลดข้อมูลผู้ใช้ได้'
  } finally {
    isLoading.value = false
  }
}

// Validate form
const validateForm = () => {
  errors.value = {}
  
  if (!form.name) {
    errors.value.name = 'กรุณากรอกชื่อ'
  }
  
  if (!form.email) {
    errors.value.email = 'กรุณากรอกอีเมล'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.value.email = 'รูปแบบอีเมลไม่ถูกต้อง'
  }
  
  if (!form.username) {
    errors.value.username = 'กรุณากรอก Username'
  }
  
  // Password is optional for edit
  if (form.password && form.password.length < 8) {
    errors.value.password = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'
  }
  
  if (form.password && form.password !== form.password_confirmation) {
    errors.value.password_confirmation = 'รหัสผ่านไม่ตรงกัน'
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
    
    // Build data without empty password
    const data: any = {
      name: form.name,
      email: form.email,
      username: form.username,
      role: form.role,
      is_super_admin: form.is_super_admin,
      is_plearnd_admin: form.is_plearnd_admin,
      status: form.status
    }
    
    if (form.password) {
      data.password = form.password
      data.password_confirmation = form.password_confirmation
    }
    
    const response = await $fetch(`${apiBase}/api/admin/users/${userId}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token.value}`,
        'Content-Type': 'application/json'
      },
      body: data
    })
    
    if (response.success) {
      successMessage.value = 'บันทึกข้อมูลสำเร็จ'
      setTimeout(() => {
        router.push('/nuxnan-admin/users')
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
  fetchUser()
})
</script>

<template>
  <div class="space-y-6 max-w-2xl mx-auto">
    <!-- Page Header -->
    <div class="flex items-center gap-4">
      <NuxtLink
        to="/nuxnan-admin/users"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"
      >
        <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
      </NuxtLink>
      <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">แก้ไขผู้ใช้</h1>
        <p class="text-gray-500 dark:text-gray-400 mt-1">แก้ไขข้อมูลผู้ใช้ #{{ userId }}</p>
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
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            ชื่อ <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.name }"
            placeholder="กรอกชื่อผู้ใช้"
          />
          <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            อีเมล <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.email"
            type="email"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.email }"
            placeholder="example@email.com"
          />
          <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
        </div>

        <!-- Username -->
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Username <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.username"
            type="text"
            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            :class="{ 'border-red-500': errors.username }"
            placeholder="username"
          />
          <p v-if="errors.username" class="mt-1 text-sm text-red-500">{{ errors.username }}</p>
        </div>

        <!-- Password (Optional) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              รหัสผ่านใหม่ <span class="text-gray-400 text-xs">(เว้นว่างถ้าไม่เปลี่ยน)</span>
            </label>
            <input
              v-model="form.password"
              type="password"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.password }"
              placeholder="••••••••"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              ยืนยันรหัสผ่านใหม่
            </label>
            <input
              v-model="form.password_confirmation"
              type="password"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              :class="{ 'border-red-500': errors.password_confirmation }"
              placeholder="••••••••"
            />
            <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-500">{{ errors.password_confirmation }}</p>
          </div>
        </div>

        <!-- Role & Status -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              บทบาท
            </label>
            <select
              v-model="form.role"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option v-for="role in roles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              สถานะ
            </label>
            <select
              v-model="form.status"
              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option v-for="status in statuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
          </div>
        </div>

        <!-- Admin Options -->
        <div class="space-y-3 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
          <h3 class="font-medium text-gray-700 dark:text-gray-300">สิทธิ์ผู้ดูแล</h3>
          
          <label class="flex items-center gap-3 cursor-pointer">
            <input
              v-model="form.is_super_admin"
              type="checkbox"
              class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-gray-700 dark:text-gray-300">Super Admin</span>
          </label>

          <label class="flex items-center gap-3 cursor-pointer">
            <input
              v-model="form.is_plearnd_admin"
              type="checkbox"
              class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-gray-700 dark:text-gray-300">Plearnd Admin</span>
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
            to="/nuxnan-admin/users"
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
