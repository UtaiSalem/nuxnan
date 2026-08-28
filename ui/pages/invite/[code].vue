<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center px-0 py-4 sm:px-4">
    <div class="w-full max-w-md">
      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto mb-4"></div>
        <p class="text-gray-500">กำลังตรวจสอบลิงก์เชิญ...</p>
      </div>

      <!-- Invalid Link -->
      <div v-else-if="!isValid" class="bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <Icon icon="mdi:link-off" class="w-8 h-8 text-red-600" />
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2">ลิงก์ไม่ถูกต้อง</h1>
        <p class="text-gray-500 mb-6">{{ errorMessage || 'ลิงก์เชิญนี้ไม่สามารถใช้งานได้' }}</p>
        <NuxtLink
          to="/"
          class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          <Icon icon="mdi:home" class="w-5 h-5 mr-2" />
          กลับหน้าแรก
        </NuxtLink>
      </div>

      <!-- Valid Link - Show Academy Info -->
      <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Academy Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-center text-white">
          <img
            v-if="academyInfo.logo"
            :src="academyInfo.logo"
            :alt="academyInfo.display_name"
            class="w-20 h-20 rounded-full mx-auto mb-4 border-4 border-white/30 object-cover"
          />
          <div v-else class="w-20 h-20 bg-white/20 rounded-full mx-auto mb-4 flex items-center justify-center text-3xl">
            🏫
          </div>
          <h1 class="text-xl font-bold">{{ academyInfo.display_name || academyInfo.name }}</h1>
          <p v-if="academyInfo.description" class="text-indigo-100 mt-2 text-sm line-clamp-2">
            {{ academyInfo.description }}
          </p>
        </div>

        <!-- Content -->
        <div class="p-6">
          <div class="text-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">คุณได้รับเชิญให้เข้าร่วม</h2>
            <p class="text-gray-500 text-sm">
              {{ roleInfo ? `บทบาท: ${roleInfo.display_name || roleInfo.name}` : 'เข้าร่วมเป็นสมาชิก' }}
            </p>
          </div>

          <!-- Info Cards -->
          <div class="space-y-3 mb-6">
            <div v-if="requireApproval" class="flex items-center p-3 bg-orange-50 rounded-lg">
              <Icon icon="mdi:clock-outline" class="w-5 h-5 text-orange-500 mr-3" />
              <span class="text-sm text-orange-700">ต้องรอการอนุมัติจากผู้ดูแล</span>
            </div>
            <div v-if="remainingUses !== null" class="flex items-center p-3 bg-blue-50 rounded-lg">
              <Icon icon="mdi:account-group" class="w-5 h-5 text-blue-500 mr-3" />
              <span class="text-sm text-blue-700">เหลือที่ว่าง {{ remainingUses }} ที่</span>
            </div>
            <div v-if="expiresAt" class="flex items-center p-3 bg-gray-50 rounded-lg">
              <Icon icon="mdi:calendar-clock" class="w-5 h-5 text-gray-500 mr-3" />
              <span class="text-sm text-gray-700">หมดอายุ: {{ formatDate(expiresAt) }}</span>
            </div>
          </div>

          <!-- Not Logged In -->
          <div v-if="!isLoggedIn" class="space-y-3">
            <p class="text-center text-sm text-gray-500 mb-4">
              กรุณาเข้าสู่ระบบเพื่อเข้าร่วม
            </p>
            <NuxtLink
              :to="`/login?redirect=/invite/${code}`"
              class="w-full flex items-center justify-center px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
            >
              <Icon icon="mdi:login" class="w-5 h-5 mr-2" />
              เข้าสู่ระบบ
            </NuxtLink>
            <NuxtLink
              :to="`/register?redirect=/invite/${code}`"
              class="w-full flex items-center justify-center px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <Icon icon="mdi:account-plus" class="w-5 h-5 mr-2" />
              สมัครสมาชิกใหม่
            </NuxtLink>
          </div>

          <!-- Logged In - Join Button -->
          <div v-else class="space-y-3">
            <div class="flex items-center p-3 bg-green-50 rounded-lg mb-4">
              <Icon icon="mdi:account-check" class="w-5 h-5 text-green-500 mr-3" />
              <span class="text-sm text-green-700">เข้าสู่ระบบแล้วในชื่อ {{ currentUser?.name }}</span>
            </div>
            <button
              @click="joinAcademy"
              :disabled="joining"
              class="w-full flex items-center justify-center px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="joining" icon="mdi:loading" class="w-5 h-5 mr-2 animate-spin" />
              <Icon v-else icon="mdi:check" class="w-5 h-5 mr-2" />
              {{ joining ? 'กำลังดำเนินการ...' : 'เข้าร่วมเลย' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted } from 'vue'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const { $api } = useNuxtApp()

const code = route.params.code as string

// State
const loading = ref(true)
const isValid = ref(false)
const errorMessage = ref('')
const academyInfo = ref<any>({})
const roleInfo = ref<any>(null)
const requireApproval = ref(true)
const remainingUses = ref<number | null>(null)
const expiresAt = ref<string | null>(null)
const joining = ref(false)

// Auth
const authStore = useAuthStore()
const isLoggedIn = computed(() => authStore.isAuthenticated)
const currentUser = computed(() => authStore.user)

// Validate invite code
const validateCode = async () => {
  loading.value = true
  try {
    const response = await $api.get(`/invite/${code}`)
    if (response.data.success && response.data.valid) {
      isValid.value = true
      academyInfo.value = response.data.academy
      roleInfo.value = response.data.role
      requireApproval.value = response.data.require_approval
      remainingUses.value = response.data.remaining_uses
      expiresAt.value = response.data.expires_at
    } else {
      isValid.value = false
      errorMessage.value = response.data.message
    }
  } catch (error: any) {
    isValid.value = false
    errorMessage.value = error.response?.data?.message || 'ไม่สามารถตรวจสอบลิงก์ได้'
  } finally {
    loading.value = false
  }
}

// Join academy
const joinAcademy = async () => {
  joining.value = true
  try {
    const response = await $api.post(`/invite/${code}/join`)
    if (response.data.success) {
      const status = response.data.status
      const academyName = response.data.academy.name

      if (status === 'approved') {
        await Swal.fire({
          icon: 'success',
          title: 'เข้าร่วมสำเร็จ!',
          text: 'คุณเป็นสมาชิกแล้ว',
          confirmButtonText: 'ไปที่หน้าสถาบัน',
        })
        router.push(`/academies/${academyName}`)
      } else {
        await Swal.fire({
          icon: 'success',
          title: 'ส่งคำขอแล้ว',
          text: 'กรุณารอการอนุมัติจากผู้ดูแล',
          confirmButtonText: 'ตกลง',
        })
        router.push('/')
      }
    }
  } catch (error: any) {
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: error.response?.data?.message || 'ไม่สามารถเข้าร่วมได้',
    })
  } finally {
    joining.value = false
  }
}

// Helper
const formatDate = (dateStr: string) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

onMounted(() => {
  validateCode()
})
</script>
