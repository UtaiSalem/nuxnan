<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import IconWrapper from '~/components/IconWrapper.vue'
import DonorCard from '~/components/DonorCard.vue'

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
  donates: Donate[]  // Laravel Resource Collection returns array directly
  donateRecipients: DonateRecipient[]  // Laravel Resource Collection returns array directly
}

const authStore = useAuthStore()
const { data: welcomeData, pending, error } = await useFetch<WelcomeData>('/api/welcome', {
  baseURL: useRuntimeConfig().public.apiBase
})

// Helper function to get full avatar URL
const getAvatarUrl = (avatar: string) => {
  if (avatar.startsWith('http')) {
    return avatar // Already a full URL (e.g., UI Avatars)
  }
  return `${useRuntimeConfig().public.apiBase}${avatar}` // Prepend backend URL
}

const currentTimes = ref('')
const seconds = ref(0)
const minutes = ref(0)
const hours = ref(0)
const daysNameTh = ref('')
const todayDate = ref(0)
const months = ref('')
const years = ref(0)

const isCreateDonateLoading = ref(false)
const isGetDonateLoading = ref(false)
const isCreateDonatePageLoading = ref(false)

onMounted(() => {
  getDayOfWeek()
  setInterval(function () {
    let datetime = new Date()
    currentTimes.value = datetime.toLocaleTimeString()
    seconds.value = datetime.getSeconds()
    minutes.value = datetime.getMinutes()
    hours.value = datetime.getHours()
  }, 1000)
})

const getDayOfWeek = () => {
  const today = new Date()
  const weekDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์']
  const todayMonth = [
    'มกราคม',
    'กุมภาพันธ์',
    'มีนาคม',
    'เมษายน',
    'พฤษภาคม',
    'มิถุนายน',
    'กรกฎาคม',
    'สิงหาคม',
    'กันยายน',
    'ตุลาคม',
    'พฤศจิกายน',
    'ธันวาคม',
  ]
  daysNameTh.value = weekDays[today.getDay()]
  months.value = todayMonth[today.getMonth()]
  years.value = today.getFullYear()
  todayDate.value = today.getDate()
}

import Swal from 'sweetalert2'



const handleLinkToCreateDonate = async () => {
  if (!authStore.isAuthenticated) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเข้าสู่ระบบ',
      text: 'คุณต้องเข้าสู่ระบบก่อนจึงจะสามารถให้การสนับสนุนได้',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#3085d6',
    }).then((result) => {
      if (result.isConfirmed) {
        navigateTo('/auth')
      }
    })
    return
  }

  isCreateDonateLoading.value = true
  await navigateTo('/supports/donates/create')
  isCreateDonateLoading.value = false
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
      if (result.isConfirmed) {
        navigateTo('/auth')
      }
    })
    return
  }

  try {
    isCreateDonateLoading.value = true
    
    const response = (await $fetch(`/api/donates/${donateId}/get-donate`, {
      baseURL: useRuntimeConfig().public.apiBase
    })) as any

    if (response.success) {
      Swal.fire({
        title: 'รับการสนับสนุนสำเร็จ',
        text: `คุณได้รับการสนับสนุนเรียบร้อยแล้ว ${response.donate_point || 270} แต้ม`,
        icon: 'success',
        showConfirmButton: false,
        timer: 1500,
      })

      if (authStore.user) {
        // Assuming user.pp or user.wallet is the field. The legacy code used 'pp'.
        // We'll update both if unsure, or check the interface. 
        // Based on legacy: usePage().props.auth.user.pp += 270
        // We will assert it exists or check type.
         if (typeof (authStore.user as any).pp !== 'undefined') {
            (authStore.user as any).pp += (response.donate_point || 270);
         }
      }

      if (welcomeData.value && welcomeData.value.donates) {
        welcomeData.value.donates[idx].remaining_points = response.donate.remaining_points
        if (welcomeData.value.donates[idx].remaining_points <= 0) {
          welcomeData.value.donates.splice(idx, 1)
        }
      }

      isCreateDonateLoading.value = false
    } else {
      Swal.fire({
        title: 'เกิดข้อผิดพลาด',
        text: response.message || 'ไม่สามารถรับการสนับสนุนได้',
        icon: 'error',
        showConfirmButton: false,
        timer: 1500,
      })
      isCreateDonateLoading.value = false
    }
  } catch (error: any) {
    console.error('Donate Error:', error)
    Swal.fire({
        title: 'เกิดข้อผิดพลาด',
        text: error.data?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ',
        icon: 'error',
        showConfirmButton: true,
      })
    isCreateDonateLoading.value = false
  }
}
</script>

<template>
  <div v-if="pending" class="flex justify-center items-center min-h-screen">
    <div
      class="animate-spin rounded-full h-32 w-32 border-t-2 border-b-2 border-vikinger-blue"
    ></div>
  </div>
  <div v-else-if="error" class="flex justify-center items-center min-h-screen">
    <p class="text-red-500">Error loading data: {{ error.message }}</p>
  </div>
  <div v-else>
    <Head>
      <Title>Welcome</Title>
    </Head>

    <!-- Loading Overlay -->
    <div
      v-if="isGetDonateLoading"
      class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
    >
      <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>

    <div
      class="relative flex justify-center items-center min-h-screen bg-[url('/storage/landing/joanna-kosinska-education-unsplash.png')] bg-cover bg-no-repeat dark:bg-gray-900 selection:bg-red-500 selection:text-white"
    >
      <!-- Gradient Overlay -->
      <div
        class="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/60"
      ></div>

      <div class="flex flex-col items-center justify-center w-full h-full mt-8 relative z-10">
        <div
          class="z-20 p-6 text-center sm:fixed sm:top-0 sm:right-0 md:text-right animate-fade-in"
        >
          <NuxtLink
            v-if="authStore.isAuthenticated"
            to="/play/newsfeed"
            class="text-md font-semibold text-white md:text-lg bg-gradient-to-r from-blue-500 to-indigo-600 p-4 rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transform transition-all duration-300 inline-flex items-center gap-2 backdrop-blur-sm"
          >
            <IconWrapper icon="mdi:newspaper-variant-outline" class="w-5 h-5" />
            <span>กระดานข่าว</span>
          </NuxtLink>

          <template v-else>
            <NuxtLink
              to="/auth"
              class="p-3 px-5 text-sm font-semibold text-white bg-white/20 backdrop-blur-md rounded-xl md:text-lg font-prompt hover:bg-white/30 hover:scale-105 transform transition-all duration-300 inline-flex items-center gap-2 shadow-lg hover:shadow-xl"
            >
              <IconWrapper icon="mdi:login" class="w-5 h-5" />
              <span>เข้าใช้งาน</span>
            </NuxtLink>

            <NuxtLink
              to="/auth?tab=register"
              class="p-3 px-5 ml-4 text-sm font-semibold text-white bg-gradient-to-r from-teal-500 to-emerald-600 rounded-xl md:text-lg font-prompt hover:from-teal-600 hover:to-emerald-700 hover:scale-105 transform transition-all duration-300 inline-flex items-center gap-2 shadow-lg hover:shadow-xl"
            >
              <IconWrapper icon="mdi:account-plus" class="w-5 h-5" />
              <span>สมัครสมาชิก</span>
            </NuxtLink>
          </template>
        </div>

        <div class="animate-fade-in-up text-center space-y-6">
          <div class="relative inline-block">
            <div
              class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 blur-3xl opacity-30 animate-pulse-slow"
            ></div>
            <p
              class="relative text-lg text-white sm:text-3xl font-prompt drop-shadow-2xl flex items-center justify-center gap-3 animate-bounce-slow"
            >
              <IconWrapper
                icon="fluent:book-open-globe-20-filled  "
                class="w-10 h-10 sm:w-12 sm:h-12 text-yellow-300 animate-spin-slow"
              />
              <span
                class="bg-white/10 backdrop-blur-sm px-6 py-3 rounded-2xl border border-white/20 shadow-xl"
                >เรียนบ้าง เล่นบ้าง สร้างรายได้ด้วย</span
              >
              <IconWrapper
                icon="fluent:emoji:money-bag"
                class="w-10 h-10 sm:w-12 sm:h-12 animate-bounce-slow"
              />
            </p>
          </div>
          <h3 class="text-2xl text-white md:text-6xl drop-shadow-2xl animate-pulse-slow">
            <span class="text-3xl md:text-5xl font-light">www.</span
            ><b
              class="audiowide-font text-4xl md:text-8xl bg-clip-text text-transparent bg-gradient-to-r from-cyan-300 via-purple-300 to-pink-300 drop-shadow-[0_0_30px_rgba(168,85,247,0.5)]"
              >plearnd</b
            ><span class="text-3xl md:text-5xl font-light">.com</span>
          </h3>
          <div
            class="flex items-center justify-center gap-4 text-white/90 text-sm sm:text-base animate-fade-in-up delay-200"
          >
            <div
              class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/20 transform hover:scale-110 transition-all duration-300 shadow-lg"
            >
              <IconWrapper icon="solar:graduation-hat-bold" class="w-5 h-5 text-green-300" />
              <span>เรียนรู้</span>
            </div>
            <div
              class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/20 transform hover:scale-110 transition-all duration-300 shadow-lg"
            >
              <IconWrapper icon="fluent-emoji-flat:party-popper" class="w-5 h-5" />
              <span>สนุกสนาน</span>
            </div>
            <div
              class="flex items-center gap-2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/20 transform hover:scale-110 transition-all duration-300 shadow-lg"
            >
              <IconWrapper icon="noto:money-with-wings" class="w-5 h-5" />
              <span>สร้างรายได้</span>
            </div>
          </div>
        </div>

        <div class="p-3 mb-4 font-medium min-w-40 min-h-48 animate-fade-in-up delay-200">
            <div
              class="flex-none w-40 text-center rounded-t shadow-2xl lg:rounded-t-none lg:rounded-l transform hover:scale-110 transition-all duration-300"
            >
            <div
              class="block overflow-hidden text-center rounded-lg font-prompt shadow-xl hover:shadow-2xl"
            >
              <div
                class="py-1 bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center gap-1"
              >
                <IconWrapper icon="mdi:calendar-month" class="w-4 h-4 text-white" />
                <p class="text-white font-prompt">
                  {{ months }}
                </p>
              </div>
              <div class="py-1 bg-gradient-to-r from-blue-500 to-indigo-600">
                <p class="text-white font-prompt flex items-center justify-center gap-1">
                  <IconWrapper icon="mdi:calendar" class="w-4 h-4" />
                  {{ years }}
                </p>
              </div>
              <div class="pt-3 bg-white border-l border-r border-white">
                <span
                  class="text-6xl font-bold leading-tight bg-clip-text text-transparent bg-gradient-to-br from-blue-500 to-purple-600"
                >
                  {{ todayDate }}
                </span>
              </div>
              <div
                class="pb-3 -mb-1 text-center bg-white border-b border-l border-r border-white rounded-b-lg"
              >
                <span
                  class="font-extrabold text-blue-600 text-lg flex items-center justify-center gap-1"
                >
                  <IconWrapper icon="mdi:weather-sunny" class="w-5 h-5" />
                  {{ daysNameTh }}
                </span>
              </div>
              <div
                class="py-2 mt-2 text-center bg-gradient-to-br from-gray-50 to-gray-100 border-b border-l border-r border-white rounded-lg shadow-inner"
              >
                <span
                  class="text-xl leading-normal text-gray-800 font-semibold flex items-center justify-center gap-1"
                >
                  <IconWrapper icon="mdi:clock-outline" class="w-5 h-5 text-blue-500" />
                  {{ currentTimes }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <section class="text-gray-700 body-font animate-fade-in-up delay-300">
          <div class="container px-5 py-12 mx-auto">
            <div class="flex flex-wrap -m-4 text-center text-blue-500">
              <div
                class="w-full px-4 py-2 md:w-1/4 sm:w-1/2 transform hover:scale-110 transition-all duration-500"
              >
                <div
                  class="relative w-full p-8 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-blue-500 overflow-hidden group"
                >
                  <div
                    class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"
                  ></div>
                  <div
                    class="absolute -top-2 -left-2 w-4 h-4 bg-blue-400 rounded-full animate-pulse-slow"
                  ></div>
                  <div
                    class="absolute -bottom-2 -right-2 w-4 h-4 bg-blue-400 rounded-full animate-pulse-slow delay-100"
                  ></div>
                  <div class="relative z-10">
                    <div class="flex justify-center mb-4">
                      <div
                        class="p-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow"
                      >
                        <IconWrapper
                          icon="fluent:people-community-20-filled"
                          class="w-12 h-12 text-white"
                        />
                      </div>
                    </div>
                    <div class="my-4">
                      <h2
                        class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-700 drop-shadow-sm"
                      >
                        <span>{{ welcomeData?.usersCount }}</span>
                      </h2>
                    </div>
                    <div
                      class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2"
                    >
                      <IconWrapper
                        icon="solar:users-group-two-rounded-bold-duotone"
                        class="w-6 h-6 text-blue-600"
                      />
                      <span>ผู้ใช้งาน</span>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="w-full px-4 py-2 md:w-1/4 sm:w-1/2 transform hover:scale-110 transition-all duration-500"
              >
                <div
                  class="relative w-full p-8 bg-gradient-to-br from-purple-50 to-pink-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-purple-500 overflow-hidden group"
                >
                  <div
                    class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"
                  ></div>
                  <div
                    class="absolute -top-2 -left-2 w-4 h-4 bg-purple-400 rounded-full animate-pulse-slow"
                  ></div>
                  <div
                    class="absolute -bottom-2 -right-2 w-4 h-4 bg-purple-400 rounded-full animate-pulse-slow delay-100"
                  ></div>
                  <div class="relative z-10">
                    <div class="flex justify-center mb-4">
                      <div
                        class="p-4 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow"
                      >
                        <IconWrapper
                          icon="fluent:chat-bubbles-question-20-filled"
                          class="w-12 h-12 text-white"
                        />
                      </div>
                    </div>
                    <div class="my-4">
                      <h2
                        class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-pink-700 drop-shadow-sm"
                      >
                        <span>{{ welcomeData?.postsCount }}</span>
                      </h2>
                    </div>
                    <div
                      class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2"
                    >
                      <IconWrapper
                        icon="solar:chat-round-bold-duotone"
                        class="w-6 h-6 text-purple-600"
                      />
                      <span>โพสต์</span>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="w-full px-4 py-2 md:w-1/4 sm:w-1/2 transform hover:scale-110 transition-all duration-500"
              >
                <div
                  class="relative w-full p-8 bg-gradient-to-br from-green-50 to-emerald-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-green-500 overflow-hidden group"
                >
                  <div
                    class="absolute top-0 right-0 w-32 h-32 bg-green-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"
                  ></div>
                  <div
                    class="absolute -top-2 -left-2 w-4 h-4 bg-green-400 rounded-full animate-pulse-slow"
                  ></div>
                  <div
                    class="absolute -bottom-2 -right-2 w-4 h-4 bg-green-400 rounded-full animate-pulse-slow delay-100"
                  ></div>
                  <div class="relative z-10">
                    <div class="flex justify-center mb-4">
                      <div
                        class="p-4 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow"
                      >
                        <IconWrapper
                          icon="fluent:book-open-globe-20-filled"
                          class="w-12 h-12 text-white"
                        />
                      </div>
                    </div>
                    <div class="my-4">
                      <h2
                        class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-green-600 to-emerald-700 drop-shadow-sm"
                      >
                        <span>{{ welcomeData?.coursesCount }}</span>
                      </h2>
                    </div>
                    <div
                      class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2"
                    >
                      <IconWrapper
                        icon="solar:notebook-bold-duotone"
                        class="w-6 h-6 text-green-600"
                      />
                      <span>รายวิชา</span>
                    </div>
                  </div>
                </div>
              </div>
              <div
                class="w-full px-4 py-2 md:w-1/4 sm:w-1/2 transform hover:scale-110 transition-all duration-500"
              >
                <div
                  class="relative w-full p-8 bg-gradient-to-br from-orange-50 to-red-100 rounded-3xl shadow-xl hover:shadow-2xl border-t-4 border-orange-500 overflow-hidden group"
                >
                  <div
                    class="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"
                  ></div>
                  <div
                    class="absolute -top-2 -left-2 w-4 h-4 bg-orange-400 rounded-full animate-pulse-slow"
                  ></div>
                  <div
                    class="absolute -bottom-2 -right-2 w-4 h-4 bg-orange-400 rounded-full animate-pulse-slow delay-100"
                  ></div>
                  <div class="relative z-10">
                    <div class="flex justify-center mb-4">
                      <div
                        class="p-4 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-lg transform group-hover:rotate-12 transition-transform duration-500 animate-pulse-slow"
                      >
                        <IconWrapper icon="fluent:book-template-20-filled" class="w-12 h-12 text-white" />
                      </div>
                    </div>
                    <div class="my-4">
                      <h2
                        class="text-4xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-orange-600 to-red-700 drop-shadow-sm"
                      >
                        <span>{{ welcomeData?.lessonsCount }}</span>
                      </h2>
                    </div>
                    <div
                      class="text-gray-700 font-bold text-lg flex items-center justify-center gap-2"
                    >
                      <IconWrapper
                        icon="solar:document-text-bold-duotone"
                        class="w-6 h-6 text-orange-600"
                      />
                      <span>บทเรียน</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="container flex justify-center w-full animate-fade-in-up delay-400">
          <div
            class="relative flex items-center justify-center w-full m-5 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 rounded-3xl sm:w-64 shadow-2xl hover:shadow-3xl transform hover:scale-110 hover:rotate-2 transition-all duration-500 overflow-hidden group"
          >
            <div
              class="absolute inset-0 bg-gradient-to-tr from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"
            ></div>
            <div
              class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500"
            ></div>
            <!-- Floating particles -->
            <div
              class="absolute top-4 left-4 w-2 h-2 bg-white/60 rounded-full animate-float-slow"
            ></div>
            <div
              class="absolute top-8 right-8 w-3 h-3 bg-white/40 rounded-full animate-float-slow delay-200"
            ></div>
            <div
              class="absolute bottom-6 left-6 w-2 h-2 bg-white/50 rounded-full animate-float-slow delay-400"
            ></div>
            <div
              class="absolute bottom-4 right-4 w-4 h-4 bg-white/30 rounded-full animate-float-slow delay-600"
            ></div>

            <div class="relative z-10 p-6 space-y-4">
              <div class="text-center">
                <div
                  class="inline-block p-4 bg-white/20 backdrop-blur-sm rounded-2xl animate-pulse-slow"
                >
                  <IconWrapper
                    icon="fluent:people-community-20-filled"
                    class="w-16 h-16 mx-auto text-white animate-bounce-slow"
                  />
                </div>
              </div>
              <div class="my-3 text-center">
                <h2 class="text-5xl font-bold text-white drop-shadow-2xl animate-pulse-slow">
                  <span>{{ welcomeData?.visitorCounter }}</span>
                </h2>
              </div>
              <div
                class="text-center text-white font-bold text-xl flex items-center justify-center gap-2 bg-white/10 backdrop-blur-sm rounded-full py-2 px-4 hover:bg-white/20 transition-all duration-300"
              >
                <IconWrapper icon="solar:eye-bold-duotone" class="w-6 h-6 animate-pulse-slow" />
                <span>ผู้เข้าชม</span>
              </div>
              <div
                class="text-sm text-center text-yellow-200 font-medium flex items-center justify-center gap-2 bg-yellow-500/20 rounded-full py-2 px-3 hover:bg-yellow-500/30 transition-all duration-300"
              >
                <IconWrapper icon="solar:calendar-bold-duotone" class="w-5 h-5" />
                <span>ตั้งแต่ {{ new Date('1/1/2024').toLocaleDateString('th-TH') }}</span>
              </div>
            </div>
          </div>
        </section>

        <div class="max-w-6xl mt-20 mb-4 text-center animate-fade-in-up delay-500">
          <div class="relative inline-block">
            <div
              class="absolute inset-0 bg-yellow-500/30 blur-2xl rounded-full animate-pulse-slow"
            ></div>
            <p
              class="relative text-white text-md sm:text-lg font-prompt bg-gradient-to-r from-yellow-500/30 to-orange-500/30 backdrop-blur-md px-8 py-4 rounded-full inline-flex items-center gap-3 shadow-2xl border border-yellow-500/50"
            >
              <IconWrapper icon="svg-spinners:blocks-shuffle-3" class="w-6 h-6 text-yellow-300" />
              <span class="font-bold">***อยู่ระหว่างการพัฒนาและทดลองใช้งาน***</span>
              <IconWrapper icon="fluent-emoji:construction" class="w-6 h-6" />
            </p>
          </div>
        </div>
      </div>
    </div>
    <section
      class="text-gray-700 border-t border-gray-200 body-font bg-gradient-to-b from-white to-blue-50"
    >
      <div class="container px-5 py-16 mx-auto">
        <div class="flex flex-col w-full mb-12 text-center animate-fade-in-up">
          <div class="flex items-center justify-center gap-3 mb-4">
            <IconWrapper icon="noto:sparkling-heart" class="w-12 h-12 animate-bounce-slow" />
            <h1
              class="text-3xl m-6 font-bold bg-clip-text text-transparent bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 sm:text-5xl title-font drop-shadow-lg"
            >
              ผู้ให้การสนับสนุนทุนการเรียนรู้
            </h1>
            <IconWrapper
              icon="noto:sparkling-heart"
              class="w-12 h-12 animate-bounce-slow delay-100"
            />
          </div>
          <p
            class="text-gray-600 text-base font-medium flex items-center justify-center gap-2 bg-pink-50 py-2 px-6 rounded-full mx-auto"
          >
            <IconWrapper icon="twemoji:red-heart" class="w-5 h-5 animate-pulse-slow" />
            <span>ขอบคุณทุกท่านที่ให้การสนับสนุน</span>
            <IconWrapper icon="twemoji:folded-hands" class="w-5 h-5" />
          </p>
        </div>
        <div class="flex flex-wrap">
          <div
            class="grid grid-cols-1 gap-6 p-4 mx-auto sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
          >
            <DonorCard
              v-for="(donate, index) in welcomeData?.donates" :key="donate.id || index"
              :donate="donate"
            />
          </div>
        </div>
      </div>
      <div class="container mx-auto mb-20 text-center rounded-lg animate-fade-in-up delay-300">
        <button
          @click.prevent="handleLinkToCreateDonate"
          class="relative px-20 py-8 text-2xl font-bold text-white bg-gradient-to-r from-teal-500 via-emerald-500 to-green-500 rounded-3xl hover:from-teal-600 hover:via-emerald-600 hover:to-green-600 transform hover:scale-110 transition-all duration-500 shadow-2xl hover:shadow-3xl inline-flex items-center gap-4 overflow-hidden group"
        >
          <div
            class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"
          ></div>
          <!-- Floating particles around button -->
          <div
            class="absolute -top-2 -left-2 w-3 h-3 bg-white/60 rounded-full animate-float-slow"
          ></div>
          <div
            class="absolute -bottom-2 -right-2 w-4 h-4 bg-white/40 rounded-full animate-float-slow delay-200"
          ></div>
          <div
            class="absolute top-4 right-8 w-2 h-2 bg-white/50 rounded-full animate-float-slow delay-400"
          ></div>

          <IconWrapper
            icon="svg-spinners:pulse-3"
            class="w-10 h-10 relative z-10"
            v-if="isCreateDonatePageLoading"
          />
          <IconWrapper icon="noto:money-bag" class="w-10 h-10 relative z-10 animate-bounce-slow" v-else />
          <span class="relative z-10">สนับสนุนทุนการเรียนรู้</span>
          <IconWrapper
            icon="fluent-emoji:sparkles"
            class="w-8 h-8 relative z-10 animate-pulse-slow"
          />
        </button>
      </div>
    </section>

    <section class="text-gray-700 border-t bg-slate-200 body-font sm:px-4">
      <div class="container px-5 py-10 mx-auto">
        <div class="flex flex-col w-full mb-4 text-center">
          <h1 class="text-2xl font-semibold text-gray-700 sm:text-3xl title-font">ผู้ได้รับการสนับสนุนทุนการเรียนรู้</h1>
        </div>
      </div>
      <div class="pb-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 px-4">
        <div v-for="(recipient, index) in welcomeData?.donateRecipients" :key="index">
          <div class="p-2 max-w-md mx-auto bg-white rounded-lg shadow-lg space-y-2 sm:py-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <img class="h-16 w-16 rounded-full sm:mx-0 sm:shrink-0" :src="getAvatarUrl(recipient.avatar)" alt="recipient-image">
              <div class="">
                <div class="mb-2">
                  <p class="text-sm text-gray-700 font-semibold">{{ recipient.username }}</p>
                  <p class="text-xs text-gray-500">{{ recipient.reference_code }}</p>
                </div>
              </div>
            </div>
            <span class="text-green-500 font-semibold">{{ recipient.points }} แต้ม</span>
          </div>
        </div>
      </div>
    </section>
    <!-- <section class="w-full px-4 mx-auto bg-blue-100 max-w-container sm:px-6 lg:px-8">
        <div class="container px-5 py-10 mx-auto">
            <div class="flex flex-col w-full mb-10 text-center">
                <h1 class="text-2xl font-semibold text-gray-700 sm:text-3xl title-font">สินค้าร่วมรายการ</h1>
            </div>
        </div>
    </section> -->
    <footer
      class="w-full px-4 mx-auto bg-gradient-to-b from-gray-100 via-gray-200 to-gray-300 max-w-container sm:px-6 lg:px-8"
    >
      <div
        class="py-16 text-center border-t-4 border-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"
      >
        <h3 class="text-gray-700 mb-4">
          <span class="text-2xl font-light">www.</span>
          <b
            class="audiowide-font text-5xl md:text-6xl bg-clip-text text-transparent bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 drop-shadow-lg"
            >plearnd</b
          >
          <span class="text-2xl font-light">.com</span>
        </h3>
        <p
          class="mt-6 text-lg leading-6 text-center text-gray-800 font-prompt font-bold flex items-center justify-center gap-3 bg-blue-50 py-3 px-8 rounded-full mx-auto"
        >
          <IconWrapper icon="fluent-emoji:books" class="w-6 h-6 animate-bounce-slow" />
          <span>เล่นบ้าง เรียนบ้าง สร้างรายได้ด้วย เพลิน!!</span>
          <IconWrapper icon="noto:smiling-face-with-hearts" class="w-6 h-6 animate-pulse-slow" />
        </p>
        <div class="mt-10 flex justify-center">
          <div class="relative group">
            <div
              class="absolute inset-0 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full blur-xl opacity-50 group-hover:opacity-75 transition-opacity duration-500"
            ></div>
            <!-- Floating particles around CEO image -->
            <div
              class="absolute -top-2 -left-2 w-3 h-3 bg-purple-400 rounded-full animate-float-slow"
            ></div>
            <div
              class="absolute -bottom-2 -right-2 w-4 h-4 bg-pink-400 rounded-full animate-float-slow delay-200"
            ></div>
            <div
              class="absolute top-4 right-4 w-2 h-2 bg-indigo-400 rounded-full animate-float-slow delay-400"
            ></div>

            <img
              :src="'/storage/landing/ceo.jpg'"
              alt="CEO"
              class="relative w-32 h-32 rounded-full border-4 border-white shadow-2xl transform group-hover:scale-110 transition-transform duration-500"
            />
          </div>
        </div>
        <div
          class="mt-10 max-w-md mx-auto bg-gradient-to-br from-white to-blue-50 rounded-3xl p-8 shadow-2xl border-2 border-blue-200"
        >
          <h4 class="text-xl font-bold text-gray-800 mb-6 flex items-center justify-center gap-2">
            <IconWrapper
              icon="solar:letter-bold-duotone"
              class="w-7 h-7 text-blue-600 animate-pulse-slow"
            />
            <span>ติดต่อเรา</span>
          </h4>
          <div class="space-y-4">
            <div
              class="flex items-center gap-4 p-3 bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group"
            >
              <IconWrapper
                icon="logos:facebook"
                class="w-8 h-8 group-hover:scale-110 transition-transform duration-300"
              />
              <span class="text-base font-bold text-slate-700">Utai Salem</span>
            </div>
            <div
              class="flex items-center gap-4 p-3 bg-gradient-to-r from-blue-50 to-purple-50 hover:from-blue-100 hover:to-purple-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group"
            >
              <IconWrapper
                icon="logos:messenger"
                class="w-8 h-8 group-hover:scale-110 transition-transform duration-300"
              />
              <span class="text-base font-bold text-slate-700">Bhupha MustaFa</span>
            </div>
            <div
              class="flex items-center gap-4 p-3 bg-gradient-to-r from-green-50 to-emerald-50 hover:from-green-100 hover:to-emerald-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group"
            >
              <IconWrapper
                icon="simple-icons:line"
                class="w-8 h-8 text-green-600 group-hover:scale-110 transition-transform duration-300"
              />
              <span class="text-base font-bold text-slate-700">babobhupha</span>
            </div>
            <div
              class="flex items-center gap-4 p-3 bg-gradient-to-r from-purple-50 to-pink-50 hover:from-purple-100 hover:to-pink-100 rounded-2xl transition-all duration-300 transform hover:scale-105 shadow-sm hover:shadow-md group"
            >
              <IconWrapper
                icon="solar:phone-bold-duotone"
                class="w-8 h-8 text-purple-600 group-hover:scale-110 transition-transform duration-300"
              />
              <span class="text-base font-bold text-slate-700">093-840-3000</span>
            </div>
          </div>
        </div>
        <div
          class="mt-10 text-sm text-gray-600 items-center justify-center gap-2 bg-gray-100 py-3 px-6 rounded-full inline-flex mx-auto"
        >
          <IconWrapper
            icon="solar:copyright-bold-duotone"
            class="w-5 h-5 text-gray-500 animate-pulse-slow"
          />
          <span class="font-semibold">2024 Plearnd. All rights reserved.</span>
        </div>
      </div>
    </footer>
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

@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

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

@keyframes spin-slow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

@keyframes float-slow {
  0%,
  100% {
    transform: translateY(0px) translateX(0px);
  }
  25% {
    transform: translateY(-10px) translateX(5px);
  }
  50% {
    transform: translateY(-5px) translateX(-5px);
  }
  75% {
    transform: translateY(-15px) translateX(3px);
  }
}

.animate-fade-in {
  animation: fade-in 1s ease-out;
}

.animate-fade-in-up {
  animation: fade-in-up 1s ease-out;
}

.animate-bounce-slow {
  animation: bounce-slow 3s ease-in-out infinite;
}

.animate-pulse-slow {
  animation: pulse-slow 3s ease-in-out infinite;
}

.animate-spin-slow {
  animation: spin-slow 3s linear infinite;
}

.animate-float-slow {
  animation: float-slow 4s ease-in-out infinite;
}

.delay-100 {
  animation-delay: 0.1s;
}

.delay-200 {
  animation-delay: 0.2s;
}

.delay-300 {
  animation-delay: 0.3s;
}

.delay-400 {
  animation-delay: 0.4s;
}

.delay-500 {
  animation-delay: 0.5s;
}

.delay-600 {
  animation-delay: 0.6s;
}
</style>
