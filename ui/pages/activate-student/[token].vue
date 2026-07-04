<script setup lang="ts">
import { Icon } from '@iconify/vue'

definePageMeta({
  layout: false,
  ssr: false
})

const route = useRoute()
const router = useRouter()
const api = useApi()
const authStore = useAuthStore()

const token = computed(() => route.params.token as string)

const state = ref<'loading' | 'ready' | 'submitting' | 'success' | 'error'>('loading')
const errorMessage = ref('')

const studentInfo = ref({
  student_name: '',
  student_id: '',
  academy_name: '',
  academy_logo: null as string | null,
})

const form = ref({
  email: '',
  password: '',
  password_confirmation: '',
})

const validationErrors = ref<Record<string, string[]>>({})

const fetchInfo = async () => {
  try {
    const res: any = await api.get(`/api/student-activate/${token.value}`)
    if (res?.success) {
      studentInfo.value = res.data
      state.value = 'ready'
    }
  } catch (error: any) {
    state.value = 'error'
    errorMessage.value = error?.data?.message || error?.message || 'ลิงก์ไม่ถูกต้องหรือหมดอายุ'
  }
}

const submit = async () => {
  validationErrors.value = {}
  state.value = 'submitting'

  try {
    const res: any = await api.post(`/api/student-activate/${token.value}`, form.value)
    if (res?.success && res?.token) {
      state.value = 'success'
      authStore.setToken(res.token)
      authStore.setUser(res.user)
      setTimeout(() => router.push('/Dashboard'), 2000)
    }
  } catch (error: any) {
    state.value = 'ready'
    if (error?.data?.errors) {
      validationErrors.value = error.data.errors
    } else {
      errorMessage.value = error?.data?.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่'
    }
  }
}

onMounted(() => fetchInfo())
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Loading -->
      <div v-if="state === 'loading'" class="text-center py-12">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-10 h-10 mx-auto text-primary-600 animate-spin" />
        <p class="mt-4 text-gray-500">กำลังตรวจสอบลิงก์...</p>
      </div>

      <!-- Error -->
      <div v-else-if="state === 'error'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-4">
          <Icon icon="fluent:error-circle-24-filled" class="w-8 h-8 text-red-600 dark:text-red-400" />
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่สามารถเปิดบัญชีได้</h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6">{{ errorMessage }}</p>
        <NuxtLink to="/auth" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
          <Icon icon="fluent:arrow-left-24-regular" class="w-5 h-5" />
          ไปหน้าเข้าสู่ระบบ
        </NuxtLink>
      </div>

      <!-- Success -->
      <div v-else-if="state === 'success'" class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-4">
          <Icon icon="fluent:checkmark-circle-24-filled" class="w-8 h-8 text-green-600 dark:text-green-400" />
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">เปิดบัญชีสำเร็จ!</h2>
        <p class="text-gray-500 dark:text-gray-400">กำลังพาคุณไปยังหน้าหลัก...</p>
      </div>

      <!-- Form -->
      <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-8 py-6 text-center text-white">
          <Icon icon="fluent:person-add-24-filled" class="w-10 h-10 mx-auto mb-2 opacity-90" />
          <h1 class="text-xl font-bold">เปิดบัญชีนักเรียน</h1>
          <p class="text-sm opacity-80 mt-1">{{ studentInfo.academy_name }}</p>
        </div>

        <!-- Student Info -->
        <div class="px-8 pt-6">
          <div class="flex items-center gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:person-24-regular" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p class="font-bold text-gray-900 dark:text-white text-sm">{{ studentInfo.student_name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">รหัส: {{ studentInfo.student_id }}</p>
            </div>
          </div>
        </div>

        <!-- Form Fields -->
        <form @submit.prevent="submit" class="px-8 py-6 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อีเมล <span class="text-red-500">*</span></label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="student@example.com"
              class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
            <p v-if="validationErrors.email" class="mt-1 text-xs text-red-500">{{ validationErrors.email[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">รหัสผ่าน <span class="text-red-500">*</span></label>
            <input
              v-model="form.password"
              type="password"
              required
              minlength="8"
              placeholder="อย่างน้อย 8 ตัวอักษร"
              class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
            <p v-if="validationErrors.password" class="mt-1 text-xs text-red-500">{{ validationErrors.password[0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ยืนยันรหัสผ่าน <span class="text-red-500">*</span></label>
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              minlength="8"
              placeholder="กรอกรหัสผ่านอีกครั้ง"
              class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
          </div>

          <div v-if="errorMessage && state === 'ready'" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-400">{{ errorMessage }}</p>
          </div>

          <button
            type="submit"
            :disabled="state === 'submitting'"
            class="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white font-medium rounded-lg transition-colors flex items-center justify-center gap-2"
          >
            <Icon v-if="state === 'submitting'" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <Icon v-else icon="fluent:checkmark-24-regular" class="w-5 h-5" />
            เปิดบัญชี
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
