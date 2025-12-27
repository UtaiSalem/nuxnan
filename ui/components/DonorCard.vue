<script setup lang="ts">
interface DonorProfile {
  first_name: string
  last_name: string
  bio: string | null
  location: string | null
  website: string | null
  social_media_links: any | null
}

interface Donor {
  id: number
  username: string
  email: string
  phone: string | null
  avatar: string
  points: number
  wallet: number
  personal_code: string
  reference_code: string
  is_email_verified: boolean
  created_at: string
  profile?: DonorProfile
  roles?: string[]
}

interface Donate {
  id: number
  donor: Donor | null
  donor_name: string
  total_points: number
  remaining_points: number
  slip?: string
  transfer_date?: string
  transfer_time?: string
  amounts?: number
  status?: number
}

defineProps<{
  donate: Donate
}>()
</script>

<template>
  <div
    class="bg-gradient-to-br from-white to-indigo-50 border border-indigo-200 rounded-2xl hover:shadow-2xl hover:shadow-indigo-300 transform hover:scale-105 transition-all duration-300 overflow-hidden group"
  >
    <div class="h-2 bg-gradient-to-r from-sky-400 via-blue-500 to-indigo-600"></div>
    <div class="flex flex-col justify-between h-full p-5 rounded-b-2xl">
      <figure
        class="flex items-center p-3 mb-3 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-xl shadow-sm"
      >
        <div class="flex-shrink-0 relative">
          <img
            class="w-20 h-20 rounded-full border-4 border-white shadow-lg transform hover:scale-110 transition-transform duration-300"
            :src="donate.donor ? donate.donor.avatar : '/storages/plearnd-logo.png'"
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
              class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600"
            >
              {{ donate.donor ? donate.donor_name : 'ไม่ระบุนาม' }}
            </span>
            <span class="font-semibold text-gray-600 flex items-center gap-1">
              <Icon icon="mdi:identifier" class="w-3 h-3" />
              {{ donate.donor ? donate.donor.personal_code : '' }}
            </span>
          </div>
          <NuxtLink
            v-if="donate.donor && donate.donor.reference_code"
            :to="`/auth?tab=register&ref=${donate.donor.reference_code}`"
            class="inline-flex items-center gap-1 px-4 py-1.5 text-sm font-semibold text-white bg-gradient-to-r from-teal-400 to-emerald-500 rounded-lg hover:from-teal-500 hover:to-emerald-600 transform hover:scale-105 transition-all duration-300 shadow-md hover:shadow-lg"
          >
            <Icon icon="mdi:account-plus" class="w-4 h-4" />
            <span>สมัครต่อ</span>
          </NuxtLink>
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
