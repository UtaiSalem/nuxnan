<script setup lang="ts">
import { ref } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'

definePageMeta({
  layout: 'auth',
  middleware: 'admin-guest'
})

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

// Form state
const form = ref({
  email: '',
  password: '',
  remember: false
})

const isLoading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false)

// Check for error in query
if (route.query.error === 'unauthorized') {
  errorMessage.value = 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณาเข้าสู่ระบบด้วยบัญชีผู้ดูแลระบบ'
}

// Handle login
const handleLogin = async () => {
  if (!form.value.email || !form.value.password) {
    errorMessage.value = 'กรุณากรอกอีเมลและรหัสผ่าน'
    return
  }

  isLoading.value = true
  errorMessage.value = ''

  try {
    await authStore.login({
      email: form.value.email,
      password: form.value.password
    })

    // Check if user is admin
    if (!authStore.user?.is_plearnd_admin && !authStore.user?.is_super_admin) {
      await authStore.logout()
      errorMessage.value = 'บัญชีนี้ไม่มีสิทธิ์เข้าถึงระบบผู้ดูแล'
      return
    }

    // Redirect to admin dashboard
    router.push('/nuxnan-admin')
  } catch (error: any) {
    errorMessage.value = error.message || 'เข้าสู่ระบบไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-900 via-purple-900 to-indigo-800 p-4">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-500/30 rounded-full blur-3xl"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-500/30 rounded-full blur-3xl"></div>
    </div>

    <!-- Login Card -->
    <div class="relative w-full max-w-md">
      <div class="bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/20 p-8">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-xl mb-4">
            <img src="/storage/images/plearnd-logo.png" alt="Logo" class="w-10 h-10" />
          </div>
          <h1 class="text-2xl font-bold text-white mb-2">Nuxnan Admin</h1>
          <p class="text-gray-300 text-sm">เข้าสู่ระบบเพื่อจัดการข้อมูล</p>
        </div>

        <!-- Error Message -->
        <Transition name="fade">
          <div
            v-if="errorMessage"
            class="mb-6 p-4 bg-red-500/20 border border-red-500/30 rounded-xl flex items-start gap-3"
          >
            <Icon icon="fluent:warning-24-regular" class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
            <p class="text-red-200 text-sm">{{ errorMessage }}</p>
          </div>
        </Transition>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-200 mb-2">
              อีเมล
            </label>
            <div class="relative">
              <Icon icon="fluent:mail-24-regular" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="admin@nuxnan.com"
                class="w-full pl-12 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
              />
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <label for="password" class="block text-sm font-medium text-gray-200 mb-2">
              รหัสผ่าน
            </label>
            <div class="relative">
              <Icon icon="fluent:lock-closed-24-regular" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                class="w-full pl-12 pr-12 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white transition-colors"
              >
                <Icon :icon="showPassword ? 'fluent:eye-off-24-regular' : 'fluent:eye-24-regular'" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input
                v-model="form.remember"
                type="checkbox"
                class="w-4 h-4 rounded border-white/20 bg-white/10 text-purple-500 focus:ring-purple-500 focus:ring-offset-0"
              />
              <span class="text-sm text-gray-300">จดจำฉัน</span>
            </label>
            <NuxtLink to="/auth/forgot-password" class="text-sm text-purple-400 hover:text-purple-300 transition-colors">
              ลืมรหัสผ่าน?
            </NuxtLink>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <Icon v-if="isLoading" icon="fluent:spinner-ios-20-regular" class="w-5 h-5 animate-spin" />
            <span>{{ isLoading ? 'กำลังเข้าสู่ระบบ...' : 'เข้าสู่ระบบ' }}</span>
          </button>
        </form>

        <!-- Back to main site -->
        <div class="mt-6 text-center">
          <NuxtLink to="/" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
            <Icon icon="fluent:arrow-left-24-regular" class="w-4 h-4" />
            กลับไปหน้าหลัก
          </NuxtLink>
        </div>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-400 text-xs mt-6">
        © {{ new Date().getFullYear() }} Nuxnan. All rights reserved.
      </p>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
