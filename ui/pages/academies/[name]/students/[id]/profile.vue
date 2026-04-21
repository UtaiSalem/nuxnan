<script setup lang="ts">
/**
 * Student Profile Page
 * 
 * Route: /academies/[name]/students/[id]/profile
 * 
 * Displays complete student profile information.
 * Accessible by: student (self), parents, teachers, admins
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

definePageMeta({
  middleware: ['auth'],
  layout: 'default',
})

const route = useRoute()
const router = useRouter()

const academyName = computed(() => route.params.name as string)
const studentId = computed(() => route.params.id as string)

const {
  isLoading,
  error,
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
} = useStudentProfile(academyName, studentId)

// Page head
useHead({
  title: computed(() => fullNameTh.value ? `โปรไฟล์ ${fullNameTh.value}` : 'โปรไฟล์นักเรียน'),
})

onMounted(() => {
  fetchProfile()
})

const goBack = () => {
  router.back()
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
          <h2 class="text-sm font-semibold text-gray-700">โปรไฟล์นักเรียน</h2>
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

        <!-- Print / Export Section (visible to admin/homeroom) -->
        <div v-if="accessLevel === 'admin' || accessLevel === 'homeroom'"
             class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-gray-900">เครื่องมือ</h3>
              <p class="text-xs text-gray-500 mt-0.5">จัดการข้อมูลเพิ่มเติม</p>
            </div>
            <div class="flex gap-2">
              <button @click="window.print()"
                      class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                พิมพ์โปรไฟล์
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
