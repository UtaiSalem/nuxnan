<script setup lang="ts">
/**
 * My Profile Page
 *
 * Route: /academies/[name]/my-profile
 *
 * Shows the current authenticated user's own student profile
 * by calling /api/academies/{name}/students/me/profile.
 *
 * Reuses all ProfileViewCards components from the [id]/profile.vue page
 * so the visual output is identical.
 *
 * Handles these states:
 * - Loading: skeleton spinner
 * - STUDENT_NOT_LINKED (user has no linked student record): specific empty state
 * - Other errors: standard error card with retry
 * - Success: full profile view
 */
import {
  ProfileHeader,
  PersonalInfoCard,
  AcademicInfoViewCard,
  AddressViewCard,
  ContactViewCard,
  GuardianViewCard,
  HealthInfoViewCard,
} from '~/components/learn/student/ProfileViewCards.vue'
import { Icon } from '@iconify/vue'

definePageMeta({
  middleware: ['auth'],
  layout: 'default',
})

const route = useRoute()
const router = useRouter()

const academyName = computed(() => route.params.name as string)

const {
  isLoading,
  error,
  isUnlinked,
  student,
  classroom,
  academicInfo,
  addresses,
  contacts,
  guardians,
  healthInfo,
  accessLevel,
  accessLevelLabel,
  academy,
  fullNameTh,
  classDisplay,
  fetchProfile,
} = useMyStudentProfile(academyName)

// Page head
useHead({
  title: computed(() => fullNameTh.value ? `โปรไฟล์ ${fullNameTh.value}` : 'โปรไฟล์ของฉัน'),
})

onMounted(() => {
  fetchProfile()
})

const goBack = () => {
  router.back()
}

const goToDashboard = () => {
  navigateTo(`/academies/${academyName.value}/`)
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50">
    <!-- Top Navigation Bar -->
    <div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-200/60">
      <div class="max-w-5xl mx-auto px-3 sm:px-4 lg:px-6">
        <div class="flex items-center justify-between h-14">
          <button @click="goBack"
                  class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-gray-900 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            <span class="hidden sm:inline">ย้อนกลับ</span>
          </button>
          <h2 class="text-sm font-semibold text-gray-700">โปรไฟล์ของฉัน</h2>
          <div class="w-16"></div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto px-3 sm:px-4 lg:px-6 py-4 sm:py-6 lg:py-8">

      <!-- Loading State -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center py-24">
        <div class="relative">
          <div class="w-16 h-16 rounded-full border-4 border-blue-200 animate-spin border-t-blue-600"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
        </div>
        <p class="mt-4 text-sm text-gray-500">กำลังโหลดข้อมูลโปรไฟล์...</p>
      </div>

      <!-- Student Not Linked (Empty State) -->
      <div v-else-if="isUnlinked" class="flex flex-col items-center justify-center py-24">
        <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mb-5">
          <Icon icon="fluent:person-feedback-24-filled" class="w-10 h-10 text-amber-600" />
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">ยังไม่ได้เชื่อมต่อข้อมูลนักเรียน</h3>
        <p class="text-sm text-gray-500 mb-6 text-center max-w-md leading-relaxed">
          บัญชีของคุณยังไม่ได้เชื่อมกับข้อมูลนักเรียนในโรงเรียนนี้
          หากคุณเป็นนักเรียน กรุณาติดต่อครูประจำชั้นหรือเจ้าหน้าที่โรงเรียน
          เพื่อลงทะเบียนเชื่อมต่อบัญชีผู้ใช้ของคุณ
        </p>
        <div class="flex gap-3">
          <button @click="goToDashboard"
                  class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            <Icon icon="fluent:home-24-regular" class="w-4 h-4 mr-1.5" />
            กลับไปหน้าแดชบอร์ด
          </button>
          <button @click="fetchProfile"
                  class="inline-flex items-center px-5 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
            <Icon icon="fluent:arrow-clockwise-24-regular" class="w-4 h-4 mr-1.5" />
            ลองอีกครั้ง
          </button>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="flex flex-col items-center justify-center py-24">
        <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-1">ไม่สามารถโหลดข้อมูลได้</h3>
        <p class="text-sm text-gray-500 mb-6 text-center max-w-sm">{{ error }}</p>
        <div class="flex gap-3">
          <button @click="fetchProfile"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            ลองอีกครั้ง
          </button>
          <button @click="goBack"
                  class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
            ย้อนกลับ
          </button>
        </div>
      </div>

      <!-- Profile Content -->
      <div v-else-if="student" class="space-y-5 sm:space-y-6">

        <!-- Profile Header -->
        <ProfileHeader
          :student="student"
          :classroom="classroom"
          :academy="academy"
          :access-level="accessLevel || ''"
          :access-level-label="accessLevelLabel"
        />

        <!-- Two Column Layout for Desktop -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 sm:gap-6">
          <!-- Left Column -->
          <div class="space-y-5 sm:space-y-6">
            <!-- Personal Info -->
            <PersonalInfoCard :student="student" />

            <!-- Academic Info -->
            <AcademicInfoViewCard :academic-info="academicInfo" />

            <!-- Health Info -->
            <HealthInfoViewCard :health-info="healthInfo" />
          </div>

          <!-- Right Column -->
          <div class="space-y-5 sm:space-y-6">
            <!-- Guardian Info -->
            <GuardianViewCard :guardians="guardians" />

            <!-- Address Info -->
            <AddressViewCard :addresses="addresses" />

            <!-- Contact Info -->
            <ContactViewCard :contacts="contacts" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
