<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import Swal from 'sweetalert2'
import { useAuthStore } from '~/stores/auth'
import DonorCard from '~/components/DonorCard.vue'
import BaseCard from '~/components/atoms/BaseCard.vue'

definePageMeta({
  layout: 'main'
})

useHead({
  title: 'สะสมแต้ม - Nuxni'
})

// Types
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
  status: number // 0=รออนุมัติ, 1=อนุมัติแล้ว, 2=ปฏิเสธ
  privacy_setting?: string | null
  approved_by?: number | null
  notes?: string | null
  created_at?: string | null
  updated_at?: string | null
  diff_humans_created_at?: string | null
}

interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

interface DonatesResponse {
  donates: {
    data: Donate[]
    links: PaginationLinks
    meta: PaginationMeta
  }
}

// State
const authStore = useAuthStore()
const api = useApi()

const donates = ref<Donate[]>([])
const isLoading = ref(true)
const isLoadingMore = ref(false)
const processingDonateId = ref<number | null>(null)
const error = ref<string | null>(null)
const currentPage = ref(1)
const lastPage = ref(1)
const totalDonates = ref(0)
const dailyLimitReachedDonates = ref<Set<number>>(new Set()) // Track donates that hit daily limit

// Countdown Modal State
const selectedDonate = ref<Donate | null>(null)
const selectedDonateIndex = ref<number>(-1)
const isShowingDonorModal = ref(false)
const countdownSeconds = ref(10)
const countdownInterval = ref<NodeJS.Timeout | null>(null)
const isProcessingDonate = ref(false)
const lastClickTime = ref(0) // For double-click prevention
const animatedPoints = ref(0) // Points animation counter (0 -> 240)
const pointsAnimationInterval = ref<NodeJS.Timeout | null>(null)

// Helper function to parse points (handles both string and number)
const parsePoints = (points: string | number | undefined | null): number => {
  if (points === undefined || points === null) return 0
  if (typeof points === 'string') {
    return parseInt(points.replace(/,/g, '')) || 0
  }
  return points
}

// Stats
const totalAvailablePoints = computed(() => {
  return donates.value.reduce((sum, d) => {
    const points = typeof d.remaining_points === 'string' 
      ? parseInt(d.remaining_points.replace(/,/g, '')) 
      : (d.remaining_points || 0)
    return sum + points
  }, 0)
})

const activeDonorsCount = computed(() => {
  return donates.value.filter(d => {
    const points = typeof d.remaining_points === 'string' 
      ? parseInt(d.remaining_points.replace(/,/g, '')) 
      : (d.remaining_points || 0)
    return points > 0
  }).length
})

// Fetch donates (public endpoint - no auth required for viewing)
const fetchDonates = async (page: number = 1) => {
  if (page === 1) {
    isLoading.value = true
  } else {
    isLoadingMore.value = true
  }
  error.value = null
  
  try {
    // Use public endpoint that doesn't require authentication
    const response = await $fetch<DonatesResponse>(`/api/donates/available?page=${page}`, {
      baseURL: useRuntimeConfig().public.apiBase
    })
    
    if (response?.donates) {
      if (page === 1) {
        donates.value = response.donates.data || []
      } else {
        donates.value = [...donates.value, ...(response.donates.data || [])]
      }
      currentPage.value = response.donates.meta?.current_page || page
      lastPage.value = response.donates.meta?.last_page || 1
      totalDonates.value = response.donates.meta?.total || 0
    }
  } catch (err: any) {
    console.error('Error fetching donates:', err)
    error.value = 'ไม่สามารถโหลดข้อมูลการสนับสนุนได้'
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Load more donates
const loadMore = () => {
  if (currentPage.value < lastPage.value && !isLoadingMore.value) {
    fetchDonates(currentPage.value + 1)
  }
}

// Handle get donate (receive points) - Shows countdown modal first
const handleGetDonate = (donate: Donate, index: number) => {
  // Double-click prevention (500ms cooldown)
  const now = Date.now()
  if (now - lastClickTime.value < 500) {
    return
  }
  lastClickTime.value = now

  // Check if already processing
  if (isProcessingDonate.value || isShowingDonorModal.value) {
    return
  }

  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถรับการสนับสนุนได้',
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

  // Check if donate has enough points
  if (parsePoints(donate.remaining_points) < 270) {
    Swal.fire({
      icon: 'error',
      title: 'แต้มไม่เพียงพอ',
      text: 'การสนับสนุนนี้แต้มหมดแล้ว',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#3085d6'
    })
    return
  }

  // Show countdown modal
  selectedDonate.value = donate
  selectedDonateIndex.value = index
  isShowingDonorModal.value = true
  countdownSeconds.value = 10
  animatedPoints.value = 0

  // Start countdown
  countdownInterval.value = setInterval(() => {
    countdownSeconds.value--
    if (countdownSeconds.value <= 0) {
      // Auto-receive points when countdown ends
      processReceiveDonate()
    }
  }, 1000)

  // Start points animation (0 -> 240 over 10 seconds = 24 points per second = 2.4 points per 100ms)
  pointsAnimationInterval.value = setInterval(() => {
    if (animatedPoints.value < 240) {
      animatedPoints.value = Math.min(240, animatedPoints.value + 3) // +3 every 125ms = 240 in 10s
    }
  }, 125)
}

// Cancel countdown and close modal
const cancelCountdown = () => {
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
    countdownInterval.value = null
  }
  if (pointsAnimationInterval.value) {
    clearInterval(pointsAnimationInterval.value)
    pointsAnimationInterval.value = null
  }
  isShowingDonorModal.value = false
  selectedDonate.value = null
  selectedDonateIndex.value = -1
  countdownSeconds.value = 10
  animatedPoints.value = 0
}

// Process the actual donation receive after countdown
const processReceiveDonate = async () => {
  if (!selectedDonate.value || selectedDonateIndex.value < 0) return

  // Clear countdown
  if (countdownInterval.value) {
    clearInterval(countdownInterval.value)
    countdownInterval.value = null
  }
  if (pointsAnimationInterval.value) {
    clearInterval(pointsAnimationInterval.value)
    pointsAnimationInterval.value = null
  }

  isProcessingDonate.value = true
  const donate = selectedDonate.value
  const index = selectedDonateIndex.value

  try {
    const response = await api.get(`/api/donates/${donate.id}/get-donate`) as any

    // Close modal
    isShowingDonorModal.value = false

    if (response.success) {
      // Update points in auth store
      authStore.addPoints(240)
      
      // Update local donate data
      donates.value[index].remaining_points = response.donate.remaining_points
      
      // Remove if points depleted
      if (response.donate.remaining_points < 270) {
        donates.value.splice(index, 1)
      }

      Swal.fire({
        title: 'รับการสนับสนุนสำเร็จ!',
        html: `
          <div class="text-center">
            <div class="text-5xl mb-4">🎉</div>
            <p class="text-lg">คุณได้รับ <span class="font-bold text-yellow-500">240 แต้ม</span></p>
            <p class="text-sm text-gray-500 mt-2">แต้มจะถูกเพิ่มเข้าบัญชีของคุณ</p>
          </div>
        `,
        icon: 'success',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
      })
    } else {
      // Handle various error states
      if (response.pending_approval) {
        Swal.fire({
          icon: 'info',
          title: 'รอการอนุมัติ',
          html: `
            <div class="text-center">
              <div class="text-5xl mb-4">⏳</div>
              <p class="text-lg">${response.message || 'การสนับสนุนนี้ยังรอการตรวจสอบและอนุมัติจากแอดมิน'}</p>
              <p class="text-sm text-gray-500 mt-2">กรุณารอสักครู่</p>
            </div>
          `,
          confirmButtonText: 'ตกลง',
          confirmButtonColor: '#3b82f6'
        })
      } else if (response.daily_limit_reached) {
        // Add to daily limit reached set to disable button
        dailyLimitReachedDonates.value.add(donate.id)
        
        Swal.fire({
          icon: 'warning',
          title: 'ถึงลิมิตประจำวัน',
          html: `
            <div class="text-center">
              <div class="text-5xl mb-4">⏰</div>
              <p class="text-lg">${response.message || 'คุณได้รับแต้มจากการสนับสนุนนี้ครบ 10 ครั้งแล้วในวันนี้'}</p>
              <p class="text-sm text-gray-500 mt-2">กรุณารอวันใหม่หรือรับแต้มจากการสนับสนุนอื่น</p>
            </div>
          `,
          confirmButtonText: 'ตกลง',
          confirmButtonColor: '#f59e0b'
        })
      } else {
        Swal.fire({
          icon: 'error',
          title: 'ไม่สำเร็จ',
          text: response.message || 'ไม่สามารถรับการสนับสนุนได้ กรุณาลองใหม่อีกครั้ง',
          confirmButtonText: 'ตกลง',
          confirmButtonColor: '#3085d6'
        })
      }
    }
  } catch (err: any) {
    isShowingDonorModal.value = false
    console.error('Error getting donate:', err)
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาด',
      text: 'ไม่สามารถรับการสนับสนุนได้ กรุณาลองใหม่อีกครั้ง',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#3085d6'
    })
  } finally {
    isProcessingDonate.value = false
    selectedDonate.value = null
    selectedDonateIndex.value = -1
    countdownSeconds.value = 10
  }
}

// Navigate to create donate page
const goToCreateDonate = () => {
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถให้การสนับสนุนได้',
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
  navigateTo('/earn/donates/create')
}

// Lifecycle
onMounted(() => {
  fetchDonates()
})
</script>

<template>
  <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-amber-400 to-amber-600 rounded-full flex items-center justify-center shadow-lg">
          <Icon icon="mdi:hand-coin-outline" class="w-10 h-10 text-white" />
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
          สะสมแต้ม
        </h1>
        <p class="text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
          รวบรวมแต้มจากกิจกรรมต่างๆ เพื่อแลกรับของรางวัลพิเศษ
        </p>
      </div>


      <!-- User Points Display (when authenticated) -->
      <div v-if="authStore.isAuthenticated" class="mb-8">
        <BaseCard class="bg-gradient-to-r from-yellow-400 via-amber-500 to-orange-500 border-0">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-white">
            <div class="flex items-center gap-4">
              <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
                <Icon icon="mdi:star-circle" class="w-10 h-10" />
              </div>
              <div>
                <p class="text-white/80 text-sm">แต้มของคุณ</p>
                <p class="text-3xl font-bold">{{ authStore.points.toLocaleString() }}</p>
              </div>
            </div>
            <button
              @click="goToCreateDonate"
              class="px-6 py-3 bg-white text-amber-600 font-semibold rounded-xl hover:bg-amber-50 transition-colors flex items-center gap-2 shadow-lg"
            >
              <Icon icon="mdi:gift-outline" class="w-5 h-5" />
              ให้การสนับสนุน
            </button>
          </div>
        </BaseCard>
      </div>

      <!-- How It Works Section -->
      <BaseCard class="mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Icon icon="mdi:information-outline" class="w-6 h-6 text-blue-500" />
          วิธีสะสมแต้ม
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-amber-600 font-bold">1</span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">เลือกผู้สนับสนุน</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">เลือกจากรายการผู้สนับสนุนที่มีแต้มเหลืออยู่</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-amber-600 font-bold">2</span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">กดรับแต้ม</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">กดปุ่ม "รับแต้ม" เพื่อรับ 240 แต้ม</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center flex-shrink-0">
              <span class="text-amber-600 font-bold">3</span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white">ใช้แต้ม</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400">ใช้แต้มในกิจกรรมต่างๆ บนแพลตฟอร์ม</p>
            </div>
          </div>
        </div>
      </BaseCard>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="animate-pulse">
          <BaseCard>
            <div class="space-y-4">
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                <div class="flex-1 space-y-2">
                  <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
                  <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                </div>
              </div>
              <div class="h-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
          </BaseCard>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <BaseCard>
          <Icon icon="mdi:alert-circle-outline" class="w-16 h-16 mx-auto text-red-500 mb-4" />
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">เกิดข้อผิดพลาด</h2>
          <p class="text-gray-500 dark:text-gray-400 mb-4">{{ error }}</p>
          <button
            @click="fetchDonates(1)"
            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            ลองใหม่อีกครั้ง
          </button>
        </BaseCard>
      </div>

      <!-- Empty State -->
      <div v-else-if="donates.length === 0" class="text-center py-12">
        <BaseCard>
          <Icon icon="mdi:hand-coin-outline" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
          <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">ยังไม่มีการสนับสนุน</h2>
          <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีผู้สนับสนุนในขณะนี้ เป็นคนแรกที่ให้การสนับสนุน!</p>
          <button
            @click="goToCreateDonate"
            class="px-6 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 mx-auto"
          >
            <Icon icon="mdi:gift-outline" class="w-5 h-5" />
            ให้การสนับสนุน
          </button>
        </BaseCard>
      </div>

      <!-- Donates Grid -->
      <div v-else>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            รายการสนับสนุน ({{ totalDonates }})
          </h2>
          <button
            @click="goToCreateDonate"
            class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:opacity-90 transition-opacity flex items-center gap-2 text-sm"
          >
            <Icon icon="mdi:plus" class="w-4 h-4" />
            ให้การสนับสนุน
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="(donate, index) in donates" 
            :key="donate.id"
            class="relative"
          >
            <!-- Pending Approval Badge -->
            <div 
              v-if="donate.status === 0"
              class="absolute top-4 right-4 z-10 px-3 py-1.5 bg-amber-500/90 backdrop-blur-sm text-white text-xs font-semibold rounded-full flex items-center gap-1.5 shadow-lg"
            >
              <Icon icon="mdi:clock-outline" class="w-4 h-4" />
              <span>รออนุมัติ</span>
            </div>

            <DonorCard :donate="donate" />
            
            <!-- Get Points Button Overlay -->
            <div class="absolute bottom-4 left-4 right-4">
              <button
                @click.stop="handleGetDonate(donate, index)"
                :disabled="processingDonateId === donate.id || parsePoints(donate.remaining_points) < 270 || donate.status === 0 || dailyLimitReachedDonates.has(donate.id)"
                :class="[
                  'w-full py-3 font-semibold rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg',
                  donate.status === 0 || dailyLimitReachedDonates.has(donate.id)
                    ? 'bg-gray-400 dark:bg-gray-600 text-white cursor-not-allowed' 
                    : 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed'
                ]"
              >
                <template v-if="processingDonateId === donate.id">
                  <Icon icon="mdi:loading" class="w-5 h-5 animate-spin" />
                  <span>กำลังดำเนินการ...</span>
                </template>
                <template v-else-if="donate.status === 0">
                  <Icon icon="mdi:clock-outline" class="w-5 h-5" />
                  <span>รอการอนุมัติ</span>
                </template>
                <template v-else-if="dailyLimitReachedDonates.has(donate.id)">
                  <Icon icon="mdi:clock-check-outline" class="w-5 h-5" />
                  <span>ครบ 10 ครั้งวันนี้</span>
                </template>
                <template v-else-if="parsePoints(donate.remaining_points) < 270">
                  <Icon icon="mdi:close-circle" class="w-5 h-5" />
                  <span>แต้มหมดแล้ว</span>
                </template>
                <template v-else>
                  <Icon icon="mdi:hand-coin" class="w-5 h-5" />
                  <span>รับ 240 แต้ม</span>
                </template>
              </button>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="currentPage < lastPage" class="text-center mt-8">
          <button
            @click="loadMore"
            :disabled="isLoadingMore"
            class="px-8 py-3 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 mx-auto"
          >
            <template v-if="isLoadingMore">
              <Icon icon="mdi:loading" class="w-5 h-5 animate-spin" />
              <span>กำลังโหลด...</span>
            </template>
            <template v-else>
              <Icon icon="mdi:chevron-down" class="w-5 h-5" />
              <span>โหลดเพิ่มเติม</span>
            </template>
          </button>
        </div>
      </div>

      <!-- Additional Earning Methods Section -->
      <div class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
          วิธีอื่นในการสะสมแต้ม
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <NuxtLink to="/newsfeed" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group">
              <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:thumb-up" class="w-7 h-7 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ถูกใจโพสต์</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">รับแต้มเมื่อถูกกดถูกใจ</p>
              </div>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/newsfeed" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group">
              <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-3 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:comment-text" class="w-7 h-7 text-green-600 dark:text-green-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">แสดงความคิดเห็น</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">รับแต้มจากการแสดงความเห็น</p>
              </div>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/courses" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group">
              <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:school" class="w-7 h-7 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">เรียนรู้</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">รับแต้มจากการเรียนบทเรียน</p>
              </div>
            </BaseCard>
          </NuxtLink>
          
          <NuxtLink to="/quests" class="block">
            <BaseCard class="h-full hover:shadow-lg transition-shadow cursor-pointer group">
              <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-3 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                  <Icon icon="mdi:trophy" class="w-7 h-7 text-amber-600 dark:text-amber-400" />
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">ภารกิจ</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">รับแต้มจากการทำภารกิจ</p>
              </div>
            </BaseCard>
          </NuxtLink>
        </div>
      </div>

      <!-- FAQ Section -->
      <div class="mt-12">
        <BaseCard>
          <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <Icon icon="mdi:help-circle-outline" class="w-6 h-6 text-blue-500" />
            คำถามที่พบบ่อย
          </h2>
          <div class="space-y-4">
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
              <h3 class="font-semibold text-gray-900 dark:text-white mb-2">แต้มสามารถใช้ทำอะไรได้บ้าง?</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">แต้มสามารถใช้ในการกดถูกใจ, แสดงความคิดเห็น, แชร์โพสต์ และกิจกรรมอื่นๆ บนแพลตฟอร์ม</p>
            </div>
            <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
              <h3 class="font-semibold text-gray-900 dark:text-white mb-2">รับแต้มได้กี่ครั้งต่อวัน?</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">ไม่จำกัดจำนวนครั้งในการรับแต้ม ขึ้นอยู่กับจำนวนการสนับสนุนที่มีอยู่</p>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 dark:text-white mb-2">ฉันสามารถให้การสนับสนุนได้อย่างไร?</h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">คุณสามารถให้การสนับสนุนได้โดยกดปุ่ม "ให้การสนับสนุน" และกรอกข้อมูลการโอนเงิน แต้มจะถูกคำนวณจากจำนวนเงินที่โอน (1 บาท = 1,080 แต้ม)</p>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>
  </div>

  <!-- Countdown Modal for Receiving Donation -->
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="isShowingDonorModal && selectedDonate" 
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
        @click.self="cancelCountdown"
      >
        <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden animate-scale-in">
          <!-- Header with Gradient -->
          <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 p-6 text-white text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-white/10 backdrop-blur-sm"></div>
            <div class="relative z-10">
              <div class="w-24 h-24 mx-auto mb-4 relative">
                <!-- Circular Progress -->
                <svg class="w-24 h-24 transform -rotate-90">
                  <circle
                    cx="48"
                    cy="48"
                    r="44"
                    stroke="rgba(255,255,255,0.3)"
                    stroke-width="8"
                    fill="transparent"
                  />
                  <circle
                    cx="48"
                    cy="48"
                    r="44"
                    stroke="white"
                    stroke-width="8"
                    fill="transparent"
                    stroke-linecap="round"
                    :stroke-dasharray="276.46"
                    :stroke-dashoffset="276.46 * (1 - countdownSeconds / 10)"
                    class="transition-all duration-1000 ease-linear"
                  />
                </svg>
                <!-- Avatar in center -->
                <div class="absolute inset-0 flex items-center justify-center">
                  <img 
                    v-if="selectedDonate.donor?.avatar" 
                    :src="selectedDonate.donor.avatar" 
                    class="w-16 h-16 rounded-full border-3 border-white shadow-lg object-cover"
                    :alt="selectedDonate.donor_name"
                  />
                  <div v-else class="w-16 h-16 rounded-full bg-white/30 flex items-center justify-center">
                    <Icon icon="mdi:account" class="w-8 h-8 text-white" />
                  </div>
                </div>
              </div>
              <h2 class="text-2xl font-bold mb-1">{{ selectedDonate.donor_name }}</h2>
              <p class="text-sm text-white/80">ผู้ให้การสนับสนุน</p>
            </div>
          </div>

          <!-- Body -->
          <div class="p-6 text-center">
            <!-- Countdown Display -->
            <div class="mb-6">
              <div class="text-6xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600 mb-2">
                {{ countdownSeconds }}
              </div>
              <p class="text-gray-600 dark:text-gray-400">วินาที</p>
            </div>

            <!-- Linear Progress Bar -->
            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-6">
              <div 
                class="h-full bg-gradient-to-r from-purple-600 via-pink-500 to-orange-400 transition-all duration-1000 ease-linear rounded-full"
                :style="{ width: `${((10 - countdownSeconds) / 10) * 100}%` }"
              ></div>
            </div>

            <!-- Animated Points Counter -->
            <div class="flex justify-center items-center gap-4 mb-6">
              <div class="flex items-center gap-3 bg-gradient-to-r from-yellow-100 to-amber-100 dark:from-yellow-900/30 dark:to-amber-900/30 px-6 py-4 rounded-2xl shadow-inner">
                <Icon icon="mdi:star" class="w-8 h-8 text-yellow-500 animate-pulse" />
                <div class="text-center">
                  <div class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-500 to-orange-500 tabular-nums">
                    +{{ animatedPoints }}
                  </div>
                  <p class="text-xs text-yellow-700 dark:text-yellow-400 font-medium">แต้ม</p>
                </div>
              </div>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
              {{ isProcessingDonate ? 'กำลังดำเนินการ...' : 'กรุณารอสักครู่ ระบบกำลังเตรียมแต้มให้คุณ' }}
            </p>

            <!-- Button -->
            <div class="flex justify-center">
              <button
                @click="cancelCountdown"
                :disabled="isProcessingDonate"
                class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isProcessingDonate ? 'กำลังดำเนินการ...' : 'ยกเลิก' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
/* Fade transition for modal backdrop */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Scale-in animation for modal content */
@keyframes scale-in {
  0% {
    opacity: 0;
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-scale-in {
  animation: scale-in 0.3s ease-out;
}

/* Border width utility */
.border-3 {
  border-width: 3px;
}
</style>
