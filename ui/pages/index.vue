<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import IconWrapper from '~/components/IconWrapper.vue'
import DonorCard from '~/components/DonorCard.vue'
import Swal from 'sweetalert2'

definePageMeta({
  layout: false,
})

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
  user?: {
    avatar: string
    name: string
  }
}

interface DonateRecipient {
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
}

interface WelcomeData {
  usersCount: number
  postsCount: number
  coursesCount: number
  lessonsCount: number
  visitorCounter: number
  donates: Donate[]
  donateRecipients: DonateRecipient[]
}

const authStore = useAuthStore()
const apiBase = useRuntimeConfig().public.apiBase
const { data: welcomeData, pending, error } = await useFetch<WelcomeData>('/api/welcome', {
  baseURL: apiBase,
})

const getAvatarUrl = (avatar: string) =>
  avatar.startsWith('http') ? avatar : `${apiBase}${avatar}`

const isGetDonateLoading = ref(false)
const isCreateDonatePageLoading = ref(false)

const sinceDate = new Date('1/1/2024').toLocaleDateString('th-TH')
const formatNumber = (n?: number) => (n ?? 0).toLocaleString('th-TH')

const handleLinkToCreateDonate = async () => {
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถให้การสนับสนุนได้',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#3085d6',
    }).then((result) => {
      if (result.isConfirmed) navigateTo('/auth')
    })
    return
  }
  isCreateDonatePageLoading.value = true
  await navigateTo('/earn/donates/create')
  isCreateDonatePageLoading.value = false
}

const handleGetDonate = async (donateId: number, idx: number) => {
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถรับการสนับสนุนได้',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#3085d6',
    }).then((result) => {
      if (result.isConfirmed) navigateTo('/auth')
    })
    return
  }

  try {
    isGetDonateLoading.value = true
    const response = (await $fetch(`/api/donates/${donateId}/get-donate`, {
      baseURL: apiBase,
    })) as any

    if (response.success) {
      Swal.fire({
        title: 'รับการสนับสนุนสำเร็จ',
        text: `คุณได้รับการสนับสนุนเรียบร้อยแล้ว ${response.donate_point || 270} แต้ม`,
        icon: 'success',
        showConfirmButton: false,
        timer: 1500,
      })
      if (authStore.user && typeof (authStore.user as any).pp !== 'undefined') {
        (authStore.user as any).pp += response.donate_point || 270
      }
      if (welcomeData.value?.donates) {
        welcomeData.value.donates[idx].remaining_points = response.donate.remaining_points
        if (welcomeData.value.donates[idx].remaining_points <= 0) {
          welcomeData.value.donates.splice(idx, 1)
        }
      }
    } else {
      Swal.fire({
        title: 'เกิดข้อผิดพลาด',
        text: response.message || 'ไม่สามารถรับการสนับสนุนได้',
        icon: 'error',
        showConfirmButton: false,
        timer: 1500,
      })
    }
  } catch (error: any) {
    console.error('Donate Error:', error)
    Swal.fire({
      title: 'เกิดข้อผิดพลาด',
      text: error.data?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
      icon: 'error',
      showConfirmButton: true,
    })
  } finally {
    isGetDonateLoading.value = false
  }
}
</script>

<template>
  <div class="page-root">
    <!-- Loading -->
    <div v-if="pending" class="flex flex-col items-center justify-center min-h-screen bg-white gap-4">
      <div class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      <p class="text-gray-400 font-prompt text-sm">กำลังโหลด...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex flex-col items-center justify-center min-h-screen bg-white gap-4">
      <IconWrapper icon="solar:danger-bold" class="w-14 h-14 text-red-400" />
      <p class="text-red-500 font-prompt">{{ error.message }}</p>
    </div>

    <div v-else>
      <Head>
        <Title>NUXNAN — เรียนบ้าง เล่นบ้าง สร้างรายได้</Title>
      </Head>

      <!-- Global loading overlay -->
      <Transition name="fade">
        <div
          v-if="isGetDonateLoading"
          class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center"
        >
          <div class="bg-white rounded-2xl px-10 py-8 flex flex-col items-center gap-4 shadow-2xl">
            <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="text-gray-600 font-prompt font-medium text-sm">กำลังดำเนินการ...</p>
          </div>
        </div>
      </Transition>

      <!-- ① Sticky Navbar -->
      <nav class="fixed top-0 left-0 right-0 z-40 bg-white/80 backdrop-blur-xl border-b border-gray-100/80 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between h-14 sm:h-16">
            <!-- Logo -->
            <div class="flex items-center shrink-0 whitespace-nowrap">
              <span class="hidden sm:inline text-sm text-gray-400 font-light">www.</span>
              <span class="audiowide-font text-base sm:text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-purple-600 to-pink-500">NUXNAN</span>
              <span class="hidden sm:inline text-sm text-gray-400 font-light">.com</span>
            </div>
            <!-- Auth -->
            <div class="flex items-center gap-2 sm:gap-3">
              <NuxtLink
                v-if="authStore.isAuthenticated"
                to="/play/newsfeed"
                class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl hover:shadow-md hover:shadow-blue-500/25 hover:-translate-y-px transition-all duration-200"
              >
                <IconWrapper icon="mdi:newspaper-variant-outline" class="w-4 h-4" />
                <span class="hidden sm:inline">กระดานข่าว</span>
              </NuxtLink>
              <template v-else>
                <NuxtLink
                  to="/auth"
                  class="inline-flex items-center gap-1.5 px-2.5 sm:px-4 py-2 text-sm font-semibold text-gray-700 border border-gray-200 rounded-xl hover:border-blue-300 hover:text-blue-600 hover:-translate-y-px transition-all duration-200"
                >
                  <IconWrapper icon="mdi:login" class="w-4 h-4 shrink-0" />
                  <span class="hidden sm:inline">เข้าใช้งาน</span>
                </NuxtLink>
                <NuxtLink
                  to="/auth?tab=register"
                  class="inline-flex items-center gap-1.5 px-2.5 sm:px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-teal-500 to-emerald-500 rounded-xl hover:shadow-md hover:shadow-teal-500/25 hover:-translate-y-px transition-all duration-200"
                >
                  <IconWrapper icon="mdi:account-plus" class="w-4 h-4 shrink-0" />
                  <span class="hidden sm:inline">สมัครสมาชิก</span>
                </NuxtLink>
              </template>
            </div>
          </div>
        </div>
      </nav>

      <!-- ② Hero Section -->
      <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-14 sm:pt-16">
        <!-- CSS animated mesh background — no image required -->
        <div class="absolute inset-0 mesh-bg"></div>
        <!-- Decorative blobs -->
        <div class="absolute top-1/4 left-1/6 w-64 sm:w-96 h-64 sm:h-96 bg-blue-400/10 rounded-full blur-3xl blob-1 pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/6 w-64 sm:w-96 h-64 sm:h-96 bg-purple-400/10 rounded-full blur-3xl blob-2 pointer-events-none"></div>
        <div class="absolute top-1/2 right-1/4 w-48 sm:w-72 h-48 sm:h-72 bg-pink-400/8 rounded-full blur-3xl blob-3 pointer-events-none"></div>

        <div class="relative z-10 text-center px-4 sm:px-6 max-w-5xl mx-auto py-16 sm:py-24">
          <!-- Dev badge -->
          <div
            class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700 text-xs sm:text-sm font-medium px-3 sm:px-4 py-1.5 rounded-full mb-6 sm:mb-8 hero-enter"
            style="animation-delay: 0ms"
          >
            <IconWrapper icon="svg-spinners:blocks-shuffle-3" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
            <span>อยู่ระหว่างการพัฒนาและทดลองใช้งาน</span>
          </div>

          <!-- Brand name — all inline, no wrapping -->
          <h1
            class="font-bold leading-none mb-4 sm:mb-6 hero-enter whitespace-nowrap"
            style="animation-delay: 80ms"
          >
            <span class="text-lg sm:text-3xl text-gray-400 font-light">www.</span><span class="audiowide-font text-5xl sm:text-7xl md:text-8xl lg:text-9xl bg-clip-text text-transparent bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500">NUXNAN</span><span class="text-lg sm:text-3xl text-gray-400 font-light">.com</span>
          </h1>

          <!-- Tagline -->
          <p
            class="text-base sm:text-xl md:text-2xl text-gray-600 font-prompt mb-6 sm:mb-10 leading-relaxed hero-enter"
            style="animation-delay: 160ms"
          >
            หนุกหนานๆ
            <span class="text-blue-600 font-semibold">เรียนบ้าง</span>
            <span class="text-gray-300 mx-1.5">·</span>
            <span class="text-purple-600 font-semibold">เล่นบ้าง</span>
            <span class="text-gray-300 mx-1.5">·</span>
            <span class="text-emerald-600 font-semibold">สร้างรายได้ด้วย</span>
          </p>

          <!-- Feature pills -->
          <div
            class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 mb-8 sm:mb-12 hero-enter"
            style="animation-delay: 240ms"
          >
            <div class="flex items-center gap-1.5 sm:gap-2 bg-blue-50 text-blue-700 px-3 sm:px-5 py-2 rounded-full border border-blue-200 font-medium text-sm sm:text-base">
              <IconWrapper icon="solar:graduation-hat-bold" class="w-4 h-4 sm:w-5 sm:h-5" />
              <span>เรียนรู้</span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 bg-purple-50 text-purple-700 px-3 sm:px-5 py-2 rounded-full border border-purple-200 font-medium text-sm sm:text-base">
              <IconWrapper icon="fluent-emoji-flat:party-popper" class="w-4 h-4 sm:w-5 sm:h-5" />
              <span>สนุกสนาน</span>
            </div>
            <div class="flex items-center gap-1.5 sm:gap-2 bg-emerald-50 text-emerald-700 px-3 sm:px-5 py-2 rounded-full border border-emerald-200 font-medium text-sm sm:text-base">
              <IconWrapper icon="noto:money-with-wings" class="w-4 h-4 sm:w-5 sm:h-5" />
              <span>สร้างรายได้</span>
            </div>
          </div>

          <!-- CTA buttons -->
          <div
            class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 hero-enter"
            style="animation-delay: 320ms"
          >
            <NuxtLink
              v-if="authStore.isAuthenticated"
              to="/play/newsfeed"
              class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 sm:px-9 py-3.5 sm:py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold text-base sm:text-lg rounded-2xl hover:shadow-xl hover:shadow-blue-500/25 hover:-translate-y-0.5 transition-all duration-200"
            >
              <IconWrapper icon="mdi:newspaper-variant-outline" class="w-5 h-5" />
              เข้าสู่แพลตฟอร์ม
            </NuxtLink>
            <template v-else>
              <NuxtLink
                to="/auth"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 sm:px-9 py-3.5 sm:py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold text-base sm:text-lg rounded-2xl hover:shadow-xl hover:shadow-blue-500/25 hover:-translate-y-0.5 transition-all duration-200"
              >
                <IconWrapper icon="mdi:login" class="w-5 h-5" />
                เข้าใช้งาน
              </NuxtLink>
              <NuxtLink
                to="/auth?tab=register"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 sm:px-9 py-3.5 sm:py-4 bg-white text-gray-700 font-bold text-base sm:text-lg rounded-2xl border-2 border-gray-200 hover:border-purple-400 hover:text-purple-600 hover:-translate-y-0.5 transition-all duration-200 shadow-sm"
              >
                <IconWrapper icon="mdi:account-plus" class="w-5 h-5" />
                สมัครสมาชิก
              </NuxtLink>
            </template>
          </div>

          <!-- Scroll cue -->
          <div
            class="mt-12 sm:mt-16 flex flex-col items-center gap-1.5 text-gray-400 hero-enter"
            style="animation-delay: 400ms"
          >
            <span class="text-xs sm:text-sm font-prompt">เลื่อนลงเพื่อดูเพิ่มเติม</span>
            <IconWrapper icon="solar:arrow-down-bold" class="w-4 h-4 sm:w-5 sm:h-5 animate-bounce" />
          </div>
        </div>
      </section>

      <!-- ③ Stats Bar -->
      <section class="bg-white border-y border-gray-100 py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-px bg-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-white px-4 sm:px-6 py-4 sm:py-5 text-center group hover:bg-blue-50 transition-colors duration-200 cursor-default">
              <p class="text-2xl sm:text-3xl font-bold text-blue-600 font-prompt group-hover:scale-110 transition-transform duration-200 origin-bottom">{{ formatNumber(welcomeData?.usersCount) }}</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1 flex items-center justify-center gap-1 font-prompt">
                <IconWrapper icon="solar:users-group-two-rounded-bold-duotone" class="w-3.5 h-3.5 text-blue-400" />
                ผู้ใช้งาน
              </p>
            </div>
            <div class="bg-white px-4 sm:px-6 py-4 sm:py-5 text-center group hover:bg-purple-50 transition-colors duration-200 cursor-default">
              <p class="text-2xl sm:text-3xl font-bold text-purple-600 font-prompt group-hover:scale-110 transition-transform duration-200 origin-bottom">{{ formatNumber(welcomeData?.postsCount) }}</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1 flex items-center justify-center gap-1 font-prompt">
                <IconWrapper icon="solar:chat-round-bold-duotone" class="w-3.5 h-3.5 text-purple-400" />
                โพสต์
              </p>
            </div>
            <div class="bg-white px-4 sm:px-6 py-4 sm:py-5 text-center group hover:bg-emerald-50 transition-colors duration-200 cursor-default">
              <p class="text-2xl sm:text-3xl font-bold text-emerald-600 font-prompt group-hover:scale-110 transition-transform duration-200 origin-bottom">{{ formatNumber(welcomeData?.coursesCount) }}</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1 flex items-center justify-center gap-1 font-prompt">
                <IconWrapper icon="solar:notebook-bold-duotone" class="w-3.5 h-3.5 text-emerald-400" />
                รายวิชา
              </p>
            </div>
            <div class="bg-white px-4 sm:px-6 py-4 sm:py-5 text-center group hover:bg-orange-50 transition-colors duration-200 cursor-default">
              <p class="text-2xl sm:text-3xl font-bold text-orange-500 font-prompt group-hover:scale-110 transition-transform duration-200 origin-bottom">{{ formatNumber(welcomeData?.lessonsCount) }}</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1 flex items-center justify-center gap-1 font-prompt">
                <IconWrapper icon="solar:document-text-bold-duotone" class="w-3.5 h-3.5 text-orange-400" />
                บทเรียน
              </p>
            </div>
            <!-- Visitor: spans 2 cols on mobile/tablet, 1 col on lg -->
            <div class="bg-white px-4 sm:px-6 py-4 sm:py-5 text-center col-span-2 sm:col-span-3 lg:col-span-1 group hover:bg-pink-50 transition-colors duration-200 cursor-default">
              <p class="text-2xl sm:text-3xl font-bold text-pink-600 font-prompt group-hover:scale-110 transition-transform duration-200 origin-bottom">{{ formatNumber(welcomeData?.visitorCounter) }}</p>
              <p class="text-xs sm:text-sm text-gray-500 mt-1 flex items-center justify-center gap-1 font-prompt">
                <IconWrapper icon="solar:eye-bold-duotone" class="w-3.5 h-3.5 text-pink-400" />
                ผู้เข้าชมตั้งแต่ {{ sinceDate }}
              </p>
            </div>
          </div>
        </div>
      </section>

      <!-- ④ Three Pillars -->
      <section class="py-16 sm:py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center mb-10 sm:mb-16">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 sm:mb-4 font-prompt">
              ทำอะไรได้บ้างบน
              <span class="audiowide-font bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-purple-600">NUXNAN</span>?
            </h2>
            <p class="text-gray-500 text-sm sm:text-base md:text-lg max-w-2xl mx-auto font-prompt">
              แพลตฟอร์มเดียวที่รวม
              <strong class="text-blue-600">การเรียนรู้</strong>
              <strong class="text-purple-600"> ความสนุก</strong> และ
              <strong class="text-emerald-600">การสร้างรายได้</strong> ไว้ด้วยกัน
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <!-- Learn -->
            <div class="group bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-transparent hover:border-blue-100 hover:-translate-y-1 cursor-default">
              <div class="w-12 h-12 sm:w-14 sm:h-14 bg-blue-50 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:bg-blue-500 transition-colors duration-300">
                <IconWrapper icon="solar:graduation-hat-bold" class="w-6 h-6 sm:w-7 sm:h-7 text-blue-500 group-hover:text-white transition-colors duration-300" />
              </div>
              <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3 font-prompt">เรียนรู้</h3>
              <p class="text-gray-500 text-sm sm:text-base leading-relaxed font-prompt">เรียนรู้ทักษะใหม่ๆ ผ่านคอร์สที่หลากหลาย เข้าถึงได้ทุกที่ทุกเวลา พัฒนาตัวเองได้อย่างต่อเนื่อง</p>
              <div class="mt-5 sm:mt-6 flex items-center gap-2 text-blue-500 font-semibold text-sm group-hover:gap-3 transition-all duration-200">
                <span class="font-prompt">{{ formatNumber(welcomeData?.coursesCount) }} รายวิชา</span>
                <IconWrapper icon="solar:arrow-right-bold" class="w-4 h-4" />
              </div>
            </div>

            <!-- Play -->
            <div class="group bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-transparent hover:border-purple-100 hover:-translate-y-1 cursor-default">
              <div class="w-12 h-12 sm:w-14 sm:h-14 bg-purple-50 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:bg-purple-500 transition-colors duration-300">
                <IconWrapper icon="fluent-emoji-flat:party-popper" class="w-6 h-6 sm:w-7 sm:h-7 group-hover:scale-125 transition-transform duration-300" />
              </div>
              <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3 font-prompt">สนุกสนาน</h3>
              <p class="text-gray-500 text-sm sm:text-base leading-relaxed font-prompt">ร่วมกิจกรรม เล่นเกม แชร์ประสบการณ์ พร้อมชุมชนที่มีชีวิตชีวา</p>
              <div class="mt-5 sm:mt-6 flex items-center gap-2 text-purple-500 font-semibold text-sm group-hover:gap-3 transition-all duration-200">
                <span class="font-prompt">{{ formatNumber(welcomeData?.postsCount) }} โพสต์</span>
                <IconWrapper icon="solar:arrow-right-bold" class="w-4 h-4" />
              </div>
            </div>

            <!-- Earn -->
            <div class="group bg-white rounded-3xl p-6 sm:p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-transparent hover:border-emerald-100 hover:-translate-y-1 cursor-default">
              <div class="w-12 h-12 sm:w-14 sm:h-14 bg-emerald-50 rounded-2xl flex items-center justify-center mb-5 sm:mb-6 group-hover:bg-emerald-500 transition-colors duration-300">
                <IconWrapper icon="noto:money-with-wings" class="w-6 h-6 sm:w-7 sm:h-7 group-hover:scale-125 transition-transform duration-300" />
              </div>
              <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3 font-prompt">สร้างรายได้</h3>
              <p class="text-gray-500 text-sm sm:text-base leading-relaxed font-prompt">รับทุนการเรียนรู้ สร้างรายได้จากทักษะ แปลงความรู้เป็นรายได้จริง</p>
              <div class="mt-5 sm:mt-6 flex items-center gap-2 text-emerald-500 font-semibold text-sm group-hover:gap-3 transition-all duration-200">
                <span class="font-prompt">เริ่มต้นได้เลย</span>
                <IconWrapper icon="solar:arrow-right-bold" class="w-4 h-4" />
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ⑤ Donors Section -->
      <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center mb-10 sm:mb-16">
            <div class="inline-flex items-center gap-2 bg-pink-50 text-pink-600 text-xs sm:text-sm font-medium px-4 py-1.5 rounded-full border border-pink-200 mb-4">
              <IconWrapper icon="noto:sparkling-heart" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              <span>ขอบคุณทุกท่านที่ให้การสนับสนุน</span>
            </div>
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3 font-prompt">ผู้ให้การสนับสนุนทุนการเรียนรู้</h2>
            <p class="text-gray-500 font-prompt text-sm sm:text-base">ร่วมเป็นส่วนหนึ่งของการสร้างโอกาสทางการศึกษา</p>
          </div>

          <div
            v-if="welcomeData?.donates?.length"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6 mb-10 sm:mb-14"
          >
            <DonorCard
              v-for="(donate, index) in welcomeData.donates"
              :key="donate.id || index"
              :donate="donate"
            />
          </div>
          <div v-else class="text-center py-12 text-gray-400 font-prompt mb-10 sm:mb-14">
            <IconWrapper icon="solar:heart-bold-duotone" class="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p class="text-sm sm:text-base">ยังไม่มีผู้สนับสนุนในขณะนี้</p>
          </div>

          <div class="text-center">
            <button
              @click.prevent="handleLinkToCreateDonate"
              class="group relative inline-flex items-center gap-2 sm:gap-3 px-7 sm:px-12 py-3.5 sm:py-5 text-base sm:text-xl font-bold text-white bg-gradient-to-r from-teal-500 to-emerald-500 rounded-2xl hover:from-teal-600 hover:to-emerald-600 hover:shadow-2xl hover:shadow-emerald-500/25 hover:-translate-y-0.5 transition-all duration-200 overflow-hidden"
            >
              <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/10 to-white/0 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
              <IconWrapper
                v-if="isCreateDonatePageLoading"
                icon="svg-spinners:pulse-3"
                class="w-5 h-5 sm:w-6 sm:h-6 relative z-10"
              />
              <IconWrapper v-else icon="noto:money-bag" class="w-5 h-5 sm:w-6 sm:h-6 relative z-10" />
              <span class="relative z-10 font-prompt">สนับสนุนทุนการเรียนรู้</span>
              <IconWrapper icon="fluent-emoji:sparkles" class="w-4 h-4 sm:w-5 sm:h-5 relative z-10" />
            </button>
          </div>
        </div>
      </section>

      <!-- ⑥ Recipients Section -->
      <section class="py-12 sm:py-16 bg-gray-50 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="text-center mb-8 sm:mb-12">
            <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 font-prompt">ผู้ได้รับการสนับสนุนทุนการเรียนรู้</h2>
          </div>
          <div
            v-if="welcomeData?.donateRecipients?.length"
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4"
          >
            <div
              v-for="(recipient, index) in welcomeData.donateRecipients"
              :key="index"
              class="flex items-center justify-between gap-3 bg-white rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100"
            >
              <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <img
                  class="h-10 w-10 sm:h-12 sm:w-12 rounded-full shrink-0 object-cover border-2 border-gray-100"
                  :src="getAvatarUrl(recipient.avatar)"
                  alt="recipient"
                />
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-800 truncate font-prompt">{{ recipient.username }}</p>
                  <p class="text-xs text-gray-400 truncate">{{ recipient.personal_code }}</p>
                </div>
              </div>
              <span class="shrink-0 text-sm font-bold text-emerald-600 font-prompt whitespace-nowrap">
                {{ formatNumber(recipient.points) }} แต้ม
              </span>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-400 font-prompt text-sm">
            ยังไม่มีข้อมูลผู้รับทุน
          </div>
        </div>
      </section>

      <!-- ⑦ Footer -->
      <footer class="bg-white border-t border-gray-100 py-12 sm:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex flex-col items-center gap-8 sm:gap-10">
            <!-- Brand -->
            <div class="text-center">
              <div class="flex items-center justify-center whitespace-nowrap">
                <span class="text-base sm:text-lg text-gray-400 font-light">www.</span>
                <span class="audiowide-font text-2xl sm:text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-purple-600 to-pink-500">nuxnan</span>
                <span class="text-base sm:text-lg text-gray-400 font-light">.com</span>
              </div>
              <p class="mt-2 text-gray-500 font-prompt text-sm sm:text-base">เล่นบ้าง เรียนบ้าง สร้างรายได้ด้วย หนุกหนาน!</p>
            </div>

            <!-- CEO photo -->
            <div class="relative">
              <div class="absolute inset-0 bg-gradient-to-r from-purple-300 to-pink-300 rounded-full blur-xl opacity-40"></div>
              <img
                :src="`${apiBase}/storage/landing/ceo.jpg`"
                alt="CEO"
                class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-full border-4 border-white shadow-xl object-cover"
              />
            </div>

            <!-- Contact grid -->
            <div class="w-full max-w-xs sm:max-w-sm">
              <h4 class="text-center text-xs sm:text-sm font-bold text-gray-600 mb-3 sm:mb-4 font-prompt flex items-center justify-center gap-1.5">
                <IconWrapper icon="solar:letter-bold-duotone" class="w-4 h-4 text-blue-500" />
                ติดต่อเรา
              </h4>
              <div class="grid grid-cols-2 gap-2 sm:gap-3">
                <div class="flex items-center gap-2 p-2.5 sm:p-3 bg-gray-50 rounded-xl hover:bg-blue-50 transition-colors duration-200 cursor-default">
                  <IconWrapper icon="logos:facebook" class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" />
                  <span class="text-xs sm:text-sm font-medium text-gray-700 truncate font-prompt">Utai Salem</span>
                </div>
                <div class="flex items-center gap-2 p-2.5 sm:p-3 bg-gray-50 rounded-xl hover:bg-blue-50 transition-colors duration-200 cursor-default">
                  <IconWrapper icon="logos:messenger" class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" />
                  <span class="text-xs sm:text-sm font-medium text-gray-700 truncate font-prompt">Bhupha MustaFa</span>
                </div>
                <div class="flex items-center gap-2 p-2.5 sm:p-3 bg-gray-50 rounded-xl hover:bg-green-50 transition-colors duration-200 cursor-default">
                  <IconWrapper icon="simple-icons:line" class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 text-green-600" />
                  <span class="text-xs sm:text-sm font-medium text-gray-700 truncate font-prompt">babobhupha</span>
                </div>
                <div class="flex items-center gap-2 p-2.5 sm:p-3 bg-gray-50 rounded-xl hover:bg-purple-50 transition-colors duration-200 cursor-default">
                  <IconWrapper icon="solar:phone-bold-duotone" class="w-4 h-4 sm:w-5 sm:h-5 shrink-0 text-purple-600" />
                  <span class="text-xs sm:text-sm font-medium text-gray-700 truncate font-prompt">093-840-3000</span>
                </div>
              </div>
            </div>

            <!-- Copyright -->
            <p class="text-xs sm:text-sm text-gray-400 flex items-center gap-1.5 font-prompt">
              <IconWrapper icon="solar:copyright-bold-duotone" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
              2024 NUXNAN. All rights reserved.
            </p>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Audiowide&display=swap');

* {
  font-family: 'Prompt', 'Inter', sans-serif;
}

.audiowide-font {
  font-family: 'Audiowide', cursive;
}

/* CSS gradient mesh — no image, pure CSS */
.mesh-bg {
  background-color: #f5f7ff;
  background-image:
    radial-gradient(ellipse 70% 70% at 10% 10%, rgba(59, 130, 246, 0.20) 0%, transparent 60%),
    radial-gradient(ellipse 60% 60% at 90% 10%, rgba(168, 85, 247, 0.16) 0%, transparent 60%),
    radial-gradient(ellipse 60% 60% at 80% 90%, rgba(236, 72, 153, 0.12) 0%, transparent 60%),
    radial-gradient(ellipse 50% 50% at 10% 90%, rgba(6, 182, 212, 0.12) 0%, transparent 60%);
}

/* Subtle blob drift */
.blob-1 { animation: blob-drift 12s ease-in-out infinite; }
.blob-2 { animation: blob-drift 15s ease-in-out infinite reverse; }
.blob-3 { animation: blob-drift 10s ease-in-out infinite 3s; }

@keyframes blob-drift {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(24px, -16px) scale(1.04); }
  66%       { transform: translate(-16px, 12px) scale(0.96); }
}

/* Hero entrance — staggered via inline animation-delay */
.hero-enter {
  animation: hero-in 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes hero-in {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Loading overlay transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
