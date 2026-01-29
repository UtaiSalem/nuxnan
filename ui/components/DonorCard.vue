<script setup lang="ts">
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useAuthStore } from '~/stores/auth'
import Swal from 'sweetalert2'

const api = useApi()
const authStore = useAuthStore()
const config = useRuntimeConfig()

interface DonorProfile {
  first_name?: string
  last_name?: string
  bio?: string | null
  location?: string | null
  website?: string | null
  social_media_links?: any | null
}

interface Donor {
  id: number
  username: string
  name?: string
  email: string
  phone: string | null
  avatar: string
  points: number
  wallet: number
  personal_code: string
  reference_code: string
  is_email_verified: boolean
  is_plearnd_admin?: boolean
  is_super_admin?: boolean
  created_at: string
  profile?: DonorProfile | null
  roles?: string[]
}

interface Donate {
  id: number
  donor: Donor | null
  donor_name: string
  amounts?: string | number
  total_points: number | string
  remaining_points: number | string
  slip?: string | null
  transfer_date?: string | null
  transfer_time?: string | null
  donor_email?: string | null
  donation_purpose?: string | null
  payment_method?: string | null
  transaction_id?: string | null
  donor_address?: string | null
  status?: number
  privacy_setting?: string | null
  approved_by?: number | null
  notes?: string | null
  created_at?: string | null
  updated_at?: string | null
  diff_humans_created_at?: string | null
}

const props = defineProps<{
  donate: Donate
}>()

const emit = defineEmits<{
  friendRequestSent: [donorId: number]
}>()

const isSendingRequest = ref(false)
const requestSent = ref(false)

// Computed property for donor avatar with simple fallback
const donorAvatar = computed(() => {
  return props.donate.donor?.avatar || '/images/default-avatar.png'
})

const sendFriendRequest = async () => {
  if (!props.donate.donor) return
  
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถเพิ่มเพื่อนได้',
      confirmButtonText: 'เข้าสู่ระบบ',
      confirmButtonColor: '#3085d6',
      showCancelButton: true,
      cancelButtonText: 'ยกเลิก'
    }).then((result) => {
      if (result.isConfirmed) {
        navigateTo('/auth')
      }
    })
    return
  }

  isSendingRequest.value = true
  try {
    await api.post(`/api/friends/${props.donate.donor.id}`, {})
    requestSent.value = true
    emit('friendRequestSent', props.donate.donor.id)
    
    Swal.fire({
      icon: 'success',
      title: 'ส่งคำขอเพิ่มเพื่อนแล้ว',
      text: `ส่งคำขอเพิ่มเพื่อนไปยัง ${props.donate.donor_name} แล้ว`,
      showConfirmButton: false,
      timer: 1500
    })
  } catch (err: any) {
    console.error('Error sending friend request:', err)
    Swal.fire({
      icon: 'error',
      title: 'ไม่สำเร็จ',
      text: err?.data?.message || 'ไม่สามารถส่งคำขอเพิ่มเพื่อนได้',
      confirmButtonText: 'ตกลง'
    })
  } finally {
    isSendingRequest.value = false
  }
}

// Check if should show add friend button
const shouldShowAddFriend = () => {
  // Don't show if no donor info
  if (!props.donate.donor) return false
  // Don't show if not authenticated
  if (!authStore.isAuthenticated) return true // Show but will prompt login
  // Don't show if it's the current user
  if (authStore.user?.id === props.donate.donor.id) return false
  // Show the button
  return true
}
</script>

<template>
  <div
    class="bg-gradient-to-br from-white to-indigo-50 dark:from-gray-800 dark:to-gray-700 border border-indigo-200 dark:border-gray-600 rounded-2xl hover:shadow-2xl hover:shadow-indigo-300 dark:hover:shadow-gray-900 transform hover:scale-105 transition-all duration-300 overflow-hidden group h-full flex flex-col"
  >
    <div class="h-2 bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600 shrink-0"></div>
    <div class="flex flex-col justify-between flex-1 p-5 pb-20 rounded-b-2xl">
      <figure
        class="flex items-center p-3 mb-3 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-xl shadow-sm"
      >
        <div class="flex-shrink-0 relative">
          <img
            class="w-20 h-20 rounded-full border-4 border-white shadow-lg transform hover:scale-110 transition-transform duration-300"
            :src="donorAvatar"
            :alt="donate.donor ? donate.donor.username + ' photo' : 'donor-image'"
          />
          <div
            class="absolute -bottom-1 -right-1 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full p-1 animate-pulse-slow"
          >
            <Icon icon="mdi:check-decagram" class="w-5 h-5 text-white" />
          </div>
        </div>
        <div class="w-full ps-4">
          <div class="flex flex-col mb-2 text-sm">
            <span
              class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400"
            >
              {{ donate.donor ? donate.donor_name : 'ไม่ระบุนาม' }}
            </span>
            <span class="font-semibold text-gray-600 dark:text-gray-400 flex items-center gap-1">
              <Icon icon="mdi:identifier" class="w-3 h-3" />
              {{ donate.donor ? donate.donor.personal_code : '' }}
            </span>
          </div>
          <!-- Action Buttons -->
          <div class="flex flex-wrap gap-2">
            <!-- Register Link -->
            <NuxtLink
              v-if="donate.donor && donate.donor.reference_code"
              :to="`/auth?tab=register&ref=${donate.donor.reference_code}`"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-teal-400 to-emerald-500 rounded-lg hover:from-teal-500 hover:to-emerald-600 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-lg"
            >
              <Icon icon="mdi:account-star" class="w-4 h-4" />
              <span>สมัครต่อ</span>
            </NuxtLink>
            <!-- Add Friend Button -->
            <button
              v-if="shouldShowAddFriend() && !requestSent"
              @click.stop="sendFriendRequest"
              :disabled="isSendingRequest"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg hover:from-blue-600 hover:to-indigo-700 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Icon v-if="isSendingRequest" icon="mdi:loading" class="w-4 h-4 animate-spin" />
              <Icon v-else icon="mdi:account-plus" class="w-4 h-4" />
              <span>เพิ่มเพื่อน</span>
            </button>
            <!-- Request Sent Badge -->
            <span
              v-if="requestSent"
              class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg"
            >
              <Icon icon="mdi:check" class="w-4 h-4" />
              <span>ส่งคำขอแล้ว</span>
            </span>
          </div>
        </div>
      </figure>
      <div
        class="mt-4 p-4 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl border border-yellow-200"
      >
        <p
          class="text-center text-sm font-semibold text-gray-600 mb-2 flex items-center justify-center gap-1"
        >
          <Icon icon="mdi:gift" class="w-4 h-4 text-pink-500 animate-bounce-slow" />
          <span>ให้การสนับสนุน</span>
        </p>
        <div class="flex items-center justify-center gap-2 flex-wrap">
          <div class="flex items-baseline gap-1">
            <Icon icon="mdi:star-circle" class="w-6 h-6 text-yellow-500 animate-pulse-slow" />
            <span
              class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-yellow-500 to-orange-500"
            >
              {{ donate.total_points.toLocaleString() }}
            </span>
          </div>
          <span class="text-gray-400 font-bold">/</span>
          <div class="flex flex-col items-start">
            <span class="text-xs text-blue-600 font-medium">คงเหลือ</span>
            <span class="text-lg font-bold text-green-600">
              {{ donate.remaining_points }}
            </span>
          </div>
          <span class="text-sm text-gray-600 font-medium">แต้ม</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes bounce-slow {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

@keyframes pulse-slow {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

.animate-bounce-slow {
  animation: bounce-slow 3s ease-in-out infinite;
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}
</style>
