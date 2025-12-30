<script setup lang="ts">
import { Icon } from '@iconify/vue'
import CourseFeedsList from '~/components/course/CourseFeedsList.vue'

definePageMeta({
  layout: false,
})

// Props from parent route
const props = defineProps<{
  course?: any
  academy?: any
  isCourseAdmin?: boolean
}>()

// Use avatar composable
const { getAvatarUrl } = useAvatar()

// Inject from parent if props not passed
const injectedCourse = inject<Ref<any>>('course')
const injectedAcademy = inject<Ref<any>>('academy')
const injectedIsCourseAdmin = inject<Ref<boolean>>('isCourseAdmin')
const courseMemberOfAuth = inject<Ref<any>>('courseMemberOfAuth')

const course = computed(() => props.course || injectedCourse?.value)
const academy = computed(() => props.academy || injectedAcademy?.value)
const isCourseAdmin = computed(() => props.isCourseAdmin || injectedIsCourseAdmin?.value)

// Course teacher avatar
const teacherAvatar = computed(() => course.value?.user ? getAvatarUrl(course.value.user) : null)

// Course stats
const courseStats = computed(() => {
  if (!course.value) return null
  return {
    members: course.value.members_count || course.value.course_members_count || 0,
    posts: course.value.posts_count || 0,
    materials: course.value.materials_count || 0,
    assignments: course.value.assignments_count || 0
  }
})

// Page title
useHead({
  title: computed(() => course.value?.name ? `ฟีด - ${course.value.name}` : 'ฟีดรายวิชา')
})
</script>

<template>
  <!-- 3 Column Layout for Course Feeds -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Left Sidebar - Course Info Widgets -->
    <div class="hidden lg:block lg:col-span-3 space-y-4">
      <!-- Course Quick Stats Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <Icon icon="fluent:info-24-regular" class="w-4 h-4" />
          ข้อมูลรายวิชา
        </h3>
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
              <Icon icon="fluent:people-24-regular" class="w-4 h-4" />
              สมาชิก
            </span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              {{ courseStats?.members || 0 }} คน
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
              <Icon icon="fluent:chat-24-regular" class="w-4 h-4" />
              โพสต์
            </span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              {{ courseStats?.posts || 0 }} โพสต์
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
              <Icon icon="fluent:document-24-regular" class="w-4 h-4" />
              เอกสาร
            </span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              {{ courseStats?.materials || 0 }} ไฟล์
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
              <Icon icon="fluent:task-list-24-regular" class="w-4 h-4" />
              ภารกิจ
            </span>
            <span class="text-sm font-medium text-gray-900 dark:text-white">
              {{ courseStats?.assignments || 0 }} งาน
            </span>
          </div>
        </div>
      </div>
      
      <!-- Quick Links Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <Icon icon="fluent:link-24-regular" class="w-4 h-4" />
          ลิงก์ด่วน
        </h3>
        <div class="space-y-1">
          <NuxtLink 
            :to="`/courses/${course?.id}/materials`" 
            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
          >
            <Icon icon="fluent:document-24-regular" class="w-4 h-4 text-blue-500" />
            เอกสารประกอบการเรียน
          </NuxtLink>
          <NuxtLink 
            :to="`/courses/${course?.id}/assignments`" 
            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
          >
            <Icon icon="fluent:task-list-24-regular" class="w-4 h-4 text-green-500" />
            ภารกิจ / งานที่มอบหมาย
          </NuxtLink>
          <NuxtLink 
            :to="`/courses/${course?.id}/members`" 
            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
          >
            <Icon icon="fluent:people-24-regular" class="w-4 h-4 text-purple-500" />
            สมาชิกในรายวิชา
          </NuxtLink>
          <NuxtLink 
            :to="`/courses/${course?.id}/groups`" 
            class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-vikinger-dark-100 rounded-lg transition-colors"
          >
            <Icon icon="fluent:people-team-24-regular" class="w-4 h-4 text-orange-500" />
            กลุ่มย่อย
          </NuxtLink>
        </div>
      </div>
      
      <!-- Admin Section Widget -->
      <div v-if="isCourseAdmin" class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 shadow-sm text-white">
        <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
          <Icon icon="fluent:shield-24-regular" class="w-4 h-4" />
          จัดการรายวิชา
        </h3>
        <div class="space-y-1">
          <NuxtLink 
            :to="`/courses/${course?.id}/settings`" 
            class="flex items-center gap-2 px-3 py-2 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
          >
            <Icon icon="fluent:settings-24-regular" class="w-4 h-4" />
            ตั้งค่ารายวิชา
          </NuxtLink>
          <NuxtLink 
            :to="`/courses/${course?.id}/analytics`" 
            class="flex items-center gap-2 px-3 py-2 text-sm bg-white/10 hover:bg-white/20 rounded-lg transition-colors"
          >
            <Icon icon="fluent:data-trending-24-regular" class="w-4 h-4" />
            สถิติและรายงาน
          </NuxtLink>
        </div>
      </div>
    </div>
    
    <!-- Main Content - Feed -->
    <div class="lg:col-span-6 space-y-4">
      <!-- Feeds List -->
      <CourseFeedsList 
        v-if="course?.id"
        :course-id="course.id"
        :is-course-admin="isCourseAdmin"
      />
      
      <!-- Loading State if no course -->
      <div v-else class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-8 text-center">
        <Icon icon="fluent:spinner-ios-20-regular" class="w-8 h-8 animate-spin mx-auto text-blue-500 mb-4" />
        <p class="text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูลรายวิชา...</p>
      </div>
    </div>
    
    <!-- Right Sidebar - Activity & Announcements -->
    <div class="hidden lg:block lg:col-span-3 space-y-4">
      <!-- Course Teacher Widget -->
      <div v-if="course?.user" class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <Icon icon="fluent:person-24-regular" class="w-4 h-4 text-blue-500" />
          ผู้สอน
        </h3>
        <div class="flex items-center gap-3">
          <img 
            :src="teacherAvatar" 
            :alt="course.user.name"
            class="w-12 h-12 rounded-full object-cover border-2 border-blue-500"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ course.user.name }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">ผู้สร้างรายวิชา</p>
          </div>
        </div>
      </div>
      
      <!-- Recent Activity Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <Icon icon="fluent:flash-24-regular" class="w-4 h-4 text-yellow-500" />
          กิจกรรมล่าสุด
        </h3>
        <div class="space-y-3">
          <div class="flex items-start gap-3 text-sm">
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:document-add-24-regular" class="w-4 h-4 text-blue-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-gray-600 dark:text-gray-300 text-xs">มีเอกสารใหม่ถูกเพิ่ม</p>
              <p class="text-xs text-gray-400">2 ชั่วโมงที่แล้ว</p>
            </div>
          </div>
          <div class="flex items-start gap-3 text-sm">
            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:person-add-24-regular" class="w-4 h-4 text-green-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-gray-600 dark:text-gray-300 text-xs">สมาชิกใหม่เข้าร่วม</p>
              <p class="text-xs text-gray-400">5 ชั่วโมงที่แล้ว</p>
            </div>
          </div>
          <div class="flex items-start gap-3 text-sm">
            <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
              <Icon icon="fluent:chat-24-regular" class="w-4 h-4 text-purple-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-gray-600 dark:text-gray-300 text-xs">มีโพสต์ใหม่ในฟีด</p>
              <p class="text-xs text-gray-400">เมื่อวาน</p>
            </div>
          </div>
        </div>
        <NuxtLink 
          :to="`/courses/${course?.id}/activities`"
          class="mt-3 block text-center text-xs text-blue-500 hover:text-blue-600"
        >
          ดูทั้งหมด
        </NuxtLink>
      </div>
      
      <!-- Announcements Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <Icon icon="fluent:megaphone-24-regular" class="w-4 h-4 text-red-500" />
          ประกาศสำคัญ
        </h3>
        <div class="space-y-2">
          <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
            <p class="text-xs text-yellow-800 dark:text-yellow-200 font-medium flex items-center gap-1">
              <Icon icon="fluent:alert-24-regular" class="w-3 h-3" />
              แจ้งเตือน
            </p>
            <p class="text-xs text-yellow-600 dark:text-yellow-300 mt-1">
              ส่งงานภายในวันที่กำหนด
            </p>
          </div>
        </div>
      </div>
      
      <!-- Upcoming Events Widget -->
      <div class="bg-white dark:bg-vikinger-dark-200 rounded-xl p-4 shadow-sm">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
          <Icon icon="fluent:calendar-24-regular" class="w-4 h-4 text-indigo-500" />
          กำหนดการ
        </h3>
        <div class="space-y-2">
          <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-vikinger-dark-100 rounded-lg">
            <div class="text-center bg-indigo-500 text-white rounded-lg px-2 py-1 min-w-[40px]">
              <p class="text-xs font-bold">31</p>
              <p class="text-[10px]">ธ.ค.</p>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-gray-900 dark:text-white truncate">
                ส่งงานชิ้นที่ 1
              </p>
              <p class="text-[10px] text-gray-500 dark:text-gray-400">
                23:59 น.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

